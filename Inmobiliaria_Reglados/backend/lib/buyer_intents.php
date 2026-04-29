<?php
declare(strict_types=1);

require_once __DIR__ . '/env_loader.php';
require_once __DIR__ . '/notifications.php';
require_once __DIR__ . '/email_layout.php';
require_once __DIR__ . '/apiloging_client.php';
require_once dirname(__DIR__) . '/send_mail.php';

/**
 * Feature "matchmaking" comprador ↔ vendedor.
 *
 * Cuando un comprador no encuentra lo que busca, puede registrar un
 * buyer_intent con los criterios. Se notifica a todos los usuarios (salvo el
 * propio comprador) con un botón que lleva al formulario de alta de
 * propiedad. Cuando alguien sube una propiedad que encaja con el intent, se
 * marca el intent como matched y se notifica al comprador con un botón que
 * abre la ficha de la propiedad.
 */

/**
 * Construye un resumen legible a partir de los criterios del intent.
 */
function buildIntentCriteriaSummary(array $criteria): string
{
    $category = trim((string) ($criteria['category'] ?? ''));
    $city = trim((string) ($criteria['city'] ?? ''));
    $maxPrice = isset($criteria['max_price']) && $criteria['max_price'] !== ''
        ? (float) $criteria['max_price'] : null;
    $minM2 = isset($criteria['min_m2']) && $criteria['min_m2'] !== ''
        ? (int) $criteria['min_m2'] : null;

    $parts = [];

    if ($category !== '') {
        $parts[] = $category;
    }

    if ($city !== '') {
        $parts[] = 'en ' . $city;
    }

    if ($maxPrice !== null && $maxPrice > 0) {
        $parts[] = 'hasta ' . number_format($maxPrice, 0, ',', '.') . ' €';
    }

    if ($minM2 !== null && $minM2 > 0) {
        $parts[] = 'desde ' . number_format($minM2, 0, ',', '.') . ' m²';
    }

    if (empty($parts)) {
        return 'Criterios sin especificar';
    }

    return implode(', ', $parts);
}

/**
 * Inserta un nuevo buyer_intent y devuelve su id.
 *
 * $criteria puede incluir:
 *   - category, city, max_price, min_m2 (campos estructurados para matching)
 *   - criteria_display: array de { label, value } con todas las respuestas
 *     del cuestionario para mostrar al vendedor.
 */
function createBuyerIntent(PDO $pdo, int $buyerUserId, array $criteria): int
{
    $category = trim((string) ($criteria['category'] ?? '')) ?: null;
    $city = trim((string) ($criteria['city'] ?? '')) ?: null;
    $maxPrice = isset($criteria['max_price']) && $criteria['max_price'] !== ''
        ? (float) $criteria['max_price'] : null;
    $minM2 = isset($criteria['min_m2']) && $criteria['min_m2'] !== ''
        ? (int) $criteria['min_m2'] : null;

    $summary = buildIntentCriteriaSummary([
        'category' => $category,
        'city' => $city,
        'max_price' => $maxPrice,
        'min_m2' => $minM2,
    ]);

    // Guardamos TODAS las respuestas del cuestionario en criteria_json para
    // que el vendedor pueda verlas al abrir el detalle de la notificación.
    $display = [];
    if (isset($criteria['criteria_display']) && is_array($criteria['criteria_display'])) {
        foreach ($criteria['criteria_display'] as $entry) {
            if (!is_array($entry)) continue;
            $label = trim((string) ($entry['label'] ?? ''));
            $value = trim((string) ($entry['value'] ?? ''));
            if ($label === '' || $value === '') continue;
            $display[] = ['label' => $label, 'value' => $value];
        }
    }

    $jsonPayload = [
        'category'  => $category,
        'city'      => $city,
        'max_price' => $maxPrice,
        'min_m2'    => $minM2,
        'display'   => $display,
    ];

    $stmt = $pdo->prepare('
        INSERT INTO buyer_intents (
            buyer_user_id, category, city, max_price, min_m2,
            criteria_json, criteria_summary, status
        ) VALUES (
            :buyer_user_id, :category, :city, :max_price, :min_m2,
            :criteria_json, :criteria_summary, "active"
        )
    ');

    $stmt->execute([
        'buyer_user_id' => $buyerUserId,
        'category' => $category,
        'city' => $city,
        'max_price' => $maxPrice,
        'min_m2' => $minM2,
        'criteria_json' => json_encode($jsonPayload, JSON_UNESCAPED_UNICODE),
        'criteria_summary' => $summary,
    ]);

    return (int) $pdo->lastInsertId();
}

/**
 * Devuelve los datos completos de un intent por id, incluyendo el array
 * "display" con las respuestas del cuestionario. Se usa desde la UI del
 * vendedor para renderizar el detalle al pulsar "Abrir" en la notificación.
 */
function fetchBuyerIntentDetails(PDO $pdo, int $intentId): ?array
{
    if ($intentId <= 0) {
        return null;
    }

    $stmt = $pdo->prepare('
        SELECT id, buyer_user_id, category, city, max_price, min_m2,
               criteria_json, criteria_summary, status, created_at
        FROM buyer_intents
        WHERE id = :id
        LIMIT 1
    ');
    $stmt->execute(['id' => $intentId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return null;
    }

    $criteriaJson = $row['criteria_json'] ?? null;
    $parsed = is_string($criteriaJson) ? json_decode($criteriaJson, true) : null;
    $display = is_array($parsed) && isset($parsed['display']) && is_array($parsed['display'])
        ? $parsed['display']
        : [];

    return [
        'id'                => (int) $row['id'],
        'category'          => $row['category'],
        'city'              => $row['city'],
        'max_price'         => $row['max_price'] !== null ? (float) $row['max_price'] : null,
        'min_m2'            => $row['min_m2'] !== null ? (int) $row['min_m2'] : null,
        'criteria_summary'  => $row['criteria_summary'] ?? '',
        'display'           => $display,
        'status'            => $row['status'],
        'created_at'        => $row['created_at'],
    ];
}

/**
 * Notifica a todos los usuarios (excepto al propio comprador y a admins) sobre
 * un nuevo intent. Crea notificación in-app y envía email. El botón "Subir"
 * lleva al formulario de alta de propiedad con la categoría preseleccionada.
 */
function notifyUsersAboutBuyerIntent(PDO $pdo, int $intentId, int $buyerUserId, string $summary, ?string $category): int
{
    try {
        $allUsers = apilogingListAllUsers();
    } catch (Throwable $e) {
        error_log('[buyer_intents] No se pudo obtener users de ApiLogin: ' . $e->getMessage());
        return 0;
    }

    // Se excluye al comprador y a los admins (estos no venden activos).
    $users = array_values(array_filter($allUsers, static function (array $u) use ($buyerUserId): bool {
        return (int) ($u['id'] ?? 0) !== $buyerUserId
            && strtolower((string) ($u['role'] ?? '')) !== 'admin';
    }));

    $categoryParam = $category !== null && $category !== '' ? $category : '';
    $actionPath = '/profile/create-property'
        . ($categoryParam !== '' ? ('?category=' . rawurlencode($categoryParam)) : '');

    $title = 'Un comprador busca una propiedad';
    $message = $summary . '. ¿Tienes alguna así? Súbela y recibirá el activo.';

    $frontendUrl = rtrim((string) (getenv('FRONTEND_URL') ?: 'http://localhost:5175'), '/');
    $absoluteActionUrl = $frontendUrl . $actionPath;

    $emailHtml = renderEmailLayout(
        $title,
        'Hay un nuevo comprador en Reglado Real Estate',
        <<<HTML
<p style="margin:0 0 16px;color:#0f172a;font-size:15px;line-height:1.5;">Un comprador ha registrado una búsqueda con estos criterios:</p>
<div style="margin:0 0 20px;padding:14px 16px;border-left:4px solid #2563eb;background:#f1f5f9;border-radius:6px;color:#1e293b;font-size:14px;">
    {$summary}
</div>
<p style="margin:0 0 20px;color:#374151;font-size:14px;line-height:1.5;">Si tienes una propiedad que encaje, súbela a la plataforma y el sistema la emparejará automáticamente con el comprador.</p>
<p style="margin:0 0 24px;">
    <a href="{$absoluteActionUrl}" style="display:inline-block;background:#2563eb;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px;">Subir propiedad</a>
</p>
<p style="margin:0;color:#6b7280;font-size:12px;">Recibes este correo porque estás registrado como vendedor en Reglado Real Estate.</p>
HTML
    );

    $sent = 0;
    foreach ($users as $user) {
        $uid = (int) ($user['id'] ?? 0);
        if ($uid <= 0) continue;

        try {
            $created = createNotification($pdo, $uid, [
                'title' => $title,
                'message' => $message,
                'type' => 'buyer_intent',
                'related_request_id' => $intentId,
                'action_url' => $actionPath,
            ]);
            if ($created > 0) {
                $sent++;
            }
        } catch (Throwable $e) {
            error_log('[buyer_intents] Falló notificación in-app a user_id=' . $uid . ': ' . $e->getMessage());
        }

        $email = trim((string) ($user['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            continue;
        }

        try {
            sendNotificationEmail($email, $title, $emailHtml);
        } catch (Throwable $e) {
            error_log('[buyer_intents] Falló email a ' . $email . ': ' . $e->getMessage());
        }
    }

    return $sent;
}

/**
 * Busca intents activos que encajen con una propiedad recién creada.
 *
 * Matching (intencionadamente permisivo — el comprador decide de la ficha):
 *  - La categoría es filtro DURO: debe coincidir (case-insensitive).
 *  - El precio es filtro DURO solo si el comprador fijó un máximo: la
 *    propiedad no puede costar más de lo que el comprador aceptó.
 *  - La superficie (min_m2) NO se usa como filtro excluyente: Claude no
 *    siempre coloca bien "metros_cuadrados" vs "superficie_construida" y
 *    un margen de tamaño razonable sigue mereciendo ser notificado. El
 *    comprador compara las dimensiones en la ficha.
 *  - Ciudad tampoco se filtra (el cuestionario no la recoge).
 *
 * Un intent sin categoría es demasiado abierto; se ignora.
 */
function findMatchingIntentsForProperty(PDO $pdo, array $property): array
{
    $category = trim((string) ($property['categoria'] ?? $property['category'] ?? ''));
    $price = isset($property['precio']) && $property['precio'] !== ''
        ? (float) $property['precio'] : null;

    if ($category === '') {
        return [];
    }

    $sql = 'SELECT id, buyer_user_id, category, city, max_price, min_m2, criteria_summary
            FROM buyer_intents
            WHERE status = "active"
              AND category IS NOT NULL
              AND LOWER(category) = LOWER(:category)';
    $params = ['category' => $category];

    if ($price !== null) {
        $sql .= ' AND (max_price IS NULL OR max_price >= :price)';
        $params['price'] = $price;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Marca el intent como matched y notifica al comprador con un botón que
 * abre la ficha de la propiedad (in-app + email).
 */
function notifyBuyerOfIntentMatch(PDO $pdo, array $intent, int $propertyId, array $property): bool
{
    $intentId = (int) ($intent['id'] ?? 0);
    $buyerUserId = (int) ($intent['buyer_user_id'] ?? 0);
    $summary = (string) ($intent['criteria_summary'] ?? '');

    if ($intentId <= 0 || $buyerUserId <= 0 || $propertyId <= 0) {
        return false;
    }

    try {
        $update = $pdo->prepare('
            UPDATE buyer_intents
            SET status = "matched",
                matched_property_id = :prop,
                matched_at = NOW()
            WHERE id = :id AND status = "active"
        ');
        $update->execute(['prop' => $propertyId, 'id' => $intentId]);

        if ($update->rowCount() === 0) {
            return false;
        }

        $title = '¡Tenemos una propiedad para ti!';
        $message = 'Alguien ha subido una propiedad que encaja con lo que buscabas: '
                 . $summary . '.';
        $actionPath = '/property/' . $propertyId;

        createNotification($pdo, $buyerUserId, [
            'title' => $title,
            'message' => $message,
            'type' => 'intent_match',
            'related_request_id' => $propertyId,
            'action_url' => $actionPath,
        ]);

        // Email al comprador con el mismo contenido + CTA "Comprar".
        try {
            $buyerEmail = fetchBuyerEmailById($buyerUserId);

            if ($buyerEmail === null) {
                error_log('[buyer_intents] Email de match NO enviado: buyer_user_id=' . $buyerUserId . ' no tiene email válido (ApiLogin no devolvió user o el campo email está vacío).');
            } else {
                error_log('[buyer_intents] Enviando email de match a buyer_user_id=' . $buyerUserId . ' email=' . $buyerEmail . ' property_id=' . $propertyId);

                $frontendUrl = rtrim((string) (getenv('FRONTEND_URL') ?: 'http://localhost:5175'), '/');
                $absoluteUrl = $frontendUrl . $actionPath;

                $safeSummary = htmlspecialchars($summary, ENT_QUOTES, 'UTF-8');
                $emailHtml = renderEmailLayout(
                    $title,
                    'Coincidencia encontrada en Reglado Real Estate',
                    <<<HTML
<p style="margin:0 0 16px;color:#0f172a;font-size:15px;line-height:1.5;">Alguien acaba de subir una propiedad que encaja con lo que buscabas:</p>
<div style="margin:0 0 20px;padding:14px 16px;border-left:4px solid #16a34a;background:#f0fdf4;border-radius:6px;color:#1e293b;font-size:14px;">
    {$safeSummary}
</div>
<p style="margin:0 0 20px;color:#374151;font-size:14px;line-height:1.5;">Pulsa el botón para abrir la ficha del activo y continuar con el proceso de compra (firma de NDA/LOI, descarga de dossier, solicitud de cita).</p>
<p style="margin:0 0 24px;">
    <a href="{$absoluteUrl}" style="display:inline-block;background:#16a34a;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px;">Comprar</a>
</p>
<p style="margin:0;color:#6b7280;font-size:12px;">Recibes este correo porque registraste una búsqueda activa en Reglado Real Estate.</p>
HTML
                );

                sendNotificationEmail($buyerEmail, $title, $emailHtml);
                error_log('[buyer_intents] Email de match enviado OK a ' . $buyerEmail);
            }
        } catch (Throwable $mailErr) {
            error_log('[buyer_intents] Falló email de match al comprador (buyer_user_id=' . $buyerUserId . '): ' . $mailErr->getMessage());
        }

        return true;
    } catch (Throwable $e) {
        error_log('[buyer_intents] Error notificando match al comprador: ' . $e->getMessage());
        return false;
    }
}

/**
 * Devuelve el email del comprador consultando ApiLogin.
 */
function fetchBuyerEmailById(int $userId): ?string
{
    if ($userId <= 0) {
        return null;
    }

    try {
        $user = apilogingFindUserById($userId);
        $email = trim((string) ($user['email'] ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    } catch (Throwable $e) {
        error_log('[buyer_intents] No se pudo obtener email del buyer_user_id=' . $userId . ': ' . $e->getMessage());
        return null;
    }
}

/**
 * Hook principal: se llama tras crear una nueva propiedad en cualquiera de
 * los flujos (admin manual, web texto, email). Busca intents que matcheen y
 * notifica al comprador correspondiente.
 *
 * Fail-open: si algo falla, se logea y se ignora — no debe bloquear la
 * creación de la propiedad.
 */
function processNewPropertyMatching(PDO $pdo, int $propertyId, array $propertyData): int
{
    if ($propertyId <= 0) {
        return 0;
    }

    try {
        $matches = findMatchingIntentsForProperty($pdo, $propertyData);
        $notified = 0;
        foreach ($matches as $intent) {
            if (notifyBuyerOfIntentMatch($pdo, $intent, $propertyId, $propertyData)) {
                $notified++;
            }
        }
        return $notified;
    } catch (Throwable $e) {
        error_log('[buyer_intents] processNewPropertyMatching falló: ' . $e->getMessage());
        return 0;
    }
}
