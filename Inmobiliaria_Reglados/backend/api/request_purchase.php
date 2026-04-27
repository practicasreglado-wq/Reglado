<?php
declare(strict_types=1);

/**
 * Endpoint para que un comprador agende cita de firma con notario.
 *
 * Pre-requisito: el comprador debe tener docs firmados validados por admin
 * (buyer_property_access.dossier_unlocked = 1).
 *
 * Validaciones clave:
 *  - Datos de la notaría (nombre, dirección, contacto) obligatorios.
 *  - Fecha y hora futuras.
 *  - **Ventana de bloqueo de 3h**: no puede haber otra cita programada en
 *    [reqdate - 180min, reqdate + 180min] (ver bloque más abajo). Se valida
 *    server-side aunque el frontend ya filtre huecos en el dropdown.
 *
 * Tras crear la cita en `purchase_appointments` (status=scheduled), envía
 * email de confirmación al comprador y al admin con el detalle.
 */

require_once __DIR__ . '/../config/cors.php';
applyCors();
handlePreflight();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../lib/env_loader.php';
require_once __DIR__ . '/../lib/email_layout.php';
require_once __DIR__ . '/../lib/error_reporting.php';
require_once __DIR__ . '/../lib/audit.php';
require_once __DIR__ . '/../send_mail.php';

loadEnv(__DIR__ . '/../.env');

header('Content-Type: application/json; charset=utf-8');

if ($pdo instanceof PDO) {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

function purchaseLog(string $message, $data = null): void
{
    if ($data === null) {
        error_log('[request_purchase] ' . $message);
        return;
    }

    error_log('[request_purchase] ' . $message . ' => ' . print_r($data, true));
}

$context = requireAuthenticatedUser($pdo);
$auth = $context['auth'] ?? [];

$buyerUserId = (int) (
    $context['local']['iduser']
    ?? $context['local']['id']
    ?? $context['auth']['id']
    ?? 0
);

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
$propertyId = (int) ($data['property_id'] ?? 0);
$appointmentDateRaw = trim((string) ($data['appointment_date'] ?? ''));
$appointmentNotes = trim((string) ($data['notes'] ?? ''));
$notaryName = trim((string) ($data['notary_name'] ?? ''));
$notaryAddress = trim((string) ($data['notary_address'] ?? ''));
$notaryCity = trim((string) ($data['notary_city'] ?? ''));
$notaryPhone = trim((string) ($data['notary_phone'] ?? ''));

// Validar que la fecha es un DATETIME ISO válido y futura
$appointmentDate = null;
if ($appointmentDateRaw !== '') {
    $ts = strtotime($appointmentDateRaw);
    if ($ts !== false && $ts > time()) {
        $appointmentDate = date('Y-m-d H:i:s', $ts);
    }
}

// Caps de longitud por seguridad (coinciden con columnas BD)
if (mb_strlen($appointmentNotes) > 1000) $appointmentNotes = mb_substr($appointmentNotes, 0, 1000);
if (mb_strlen($notaryName) > 255)        $notaryName = mb_substr($notaryName, 0, 255);
if (mb_strlen($notaryAddress) > 500)     $notaryAddress = mb_substr($notaryAddress, 0, 500);
if (mb_strlen($notaryCity) > 150)        $notaryCity = mb_substr($notaryCity, 0, 150);
if (mb_strlen($notaryPhone) > 50)        $notaryPhone = mb_substr($notaryPhone, 0, 50);

purchaseLog('START');
purchaseLog('buyerUserId', $buyerUserId);
purchaseLog('propertyId', $propertyId);
purchaseLog('appointmentDate', $appointmentDate);

if ($buyerUserId > 0) {
    // Rate limit: máx. 5 solicitudes de compra por usuario cada hora.
    // Reutiliza la tabla regladousers.rate_limits del sistema de auth.
    try {
        $rlPdo = new PDO(
            sprintf(
                'mysql:host=%s;port=%s;dbname=regladousers;charset=utf8mb4',
                (string) getenv('DB_HOST'),
                (string) getenv('DB_PORT')
            ),
            (string) getenv('DB_USER'),
            (string) getenv('DB_PASS'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );

        $rateScope = 'purchase_request';
        $rateKeyHash = hash('sha256', $rateScope . '|' . $buyerUserId);
        $rateWindowSeconds = 3600;
        $rateMaxAttempts = 5;

        $rlRead = $rlPdo->prepare('SELECT id, attempts, updated_at FROM rate_limits WHERE key_hash = ? AND scope_name = ? LIMIT 1');
        $rlRead->execute([$rateKeyHash, $rateScope]);
        $rlRow = $rlRead->fetch();

        $nowTs = time();
        $withinWindow = $rlRow && (strtotime((string) $rlRow['updated_at']) ?: 0) >= $nowTs - $rateWindowSeconds;

        if ($withinWindow && (int) $rlRow['attempts'] >= $rateMaxAttempts) {
            respondJson(429, [
                'success' => false,
                'message' => 'Has alcanzado el límite de solicitudes de compra. Intenta de nuevo en una hora.',
            ]);
        }

        if (!$rlRow) {
            $rlPdo->prepare('INSERT INTO rate_limits(key_hash, scope_name, attempts, updated_at, created_at) VALUES(?, ?, 1, NOW(), NOW())')
                  ->execute([$rateKeyHash, $rateScope]);
        } elseif (!$withinWindow) {
            $rlPdo->prepare('UPDATE rate_limits SET attempts = 1, updated_at = NOW() WHERE id = ?')
                  ->execute([(int) $rlRow['id']]);
        } else {
            $rlPdo->prepare('UPDATE rate_limits SET attempts = attempts + 1, updated_at = NOW() WHERE id = ?')
                  ->execute([(int) $rlRow['id']]);
        }
    } catch (Throwable $e) {
        // Fail-open: el endpoint ya exige JWT válido.
        purchaseLog('Rate limit check falló', ['error' => $e->getMessage()]);
    }
}

if ($buyerUserId <= 0) {
    respondJson(401, [
        'success' => false,
        'message' => 'Debes iniciar sesión.',
    ]);
}

$buyerStmt = $pdo->prepare('
    SELECT
        id,
        email,
        first_name,
        last_name,
        username,
        phone
    FROM regladousers.users
    WHERE id = :id
    LIMIT 1
');
$buyerStmt->execute([
    'id' => $buyerUserId,
]);
$buyer = $buyerStmt->fetch(PDO::FETCH_ASSOC);

purchaseLog('BUYER DB', ['found' => (bool) $buyer]);

if (!$buyer) {
    respondJson(404, [
        'success' => false,
        'message' => 'No se encontró el comprador autenticado en la base de datos.',
    ]);
}

if ($propertyId <= 0) {
    respondJson(422, [
        'success' => false,
        'message' => 'Propiedad no especificada.',
    ]);
}

try {
    $stmt = $pdo->prepare('
    SELECT
        p.id,
        p.owner_user_id,
        p.tipo_propiedad,
        p.ciudad,
        p.zona,
        p.precio,
        p.metros_cuadrados,
        p.categoria,
        p.caracteristicas_json,

        u.id AS owner_id,
        u.first_name AS owner_first_name,
        u.last_name AS owner_last_name,
        u.email AS owner_email,
        u.phone AS owner_phone
    FROM propiedades p
    LEFT JOIN regladousers.users u ON u.id = p.owner_user_id
    WHERE p.id = :id
    LIMIT 1
');
    $stmt->execute(['id' => $propertyId]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);
    $ownerFirstName = trim((string) ($property['owner_first_name'] ?? ''));
$ownerLastName = trim((string) ($property['owner_last_name'] ?? ''));
$ownerFullName = trim($ownerFirstName . ' ' . $ownerLastName);
$ownerEmail = trim((string) ($property['owner_email'] ?? ''));
$ownerPhone = trim((string) ($property['owner_phone'] ?? ''));
$ownerId = (int) ($property['owner_id'] ?? 0);
    purchaseLog('PROPERTY', $property);

    if (!$property) {
        respondJson(404, [
            'success' => false,
            'message' => 'Propiedad no encontrada.',
        ]);
    }

    $accessStmt = $pdo->prepare('
        SELECT *
        FROM buyer_property_access
        WHERE property_id = :property_id
          AND buyer_user_id = :buyer_user_id
        LIMIT 1
    ');
    $accessStmt->execute([
        'property_id' => $propertyId,
        'buyer_user_id' => $buyerUserId,
    ]);
    $access = $accessStmt->fetch(PDO::FETCH_ASSOC);

    purchaseLog('ACCESS', $access);

    if (!$access || (int) ($access['dossier_unlocked'] ?? 0) !== 1) {
        respondJson(403, [
            'success' => false,
            'message' => 'Debes completar la firma y validación administrativa para solicitar la compra.',
            'status' => $access['dossier_unlocked'] ?? 0,
        ]);
    }

    // Exigir cita agendada: si el cliente no mandó fecha válida, no continuamos
    if ($appointmentDate === null) {
        respondJson(422, [
            'success' => false,
            'message' => 'Debes seleccionar una fecha y hora válida para la cita.',
        ]);
    }

    if ($notaryName === '' || $notaryAddress === '' || $notaryCity === '') {
        respondJson(422, [
            'success' => false,
            'message' => 'Debes indicar nombre, dirección y ciudad de la notaría.',
        ]);
    }

    // Bloquear si ya hay una cita pendiente (scheduled) para este usuario y propiedad
    $existingStmt = $pdo->prepare('
        SELECT id, appointment_date
        FROM purchase_appointments
        WHERE user_id = :uid
          AND property_id = :pid
          AND status = "scheduled"
        LIMIT 1
    ');
    $existingStmt->execute([
        'uid' => $buyerUserId,
        'pid' => $propertyId,
    ]);
    $existingAppointment = $existingStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingAppointment) {
        respondJson(409, [
            'success' => false,
            'message' => 'Ya tienes una cita pendiente para esta propiedad. Espera a que el administrador la gestione antes de agendar otra.',
            'appointment_date' => $existingAppointment['appointment_date'],
        ]);
    }

    $buyerFirstName = trim((string) ($buyer['first_name'] ?? ''));
    $buyerLastName = trim((string) ($buyer['last_name'] ?? ''));
    $buyerFullName = trim($buyerFirstName . ' ' . $buyerLastName);
    $buyerEmail = filter_var((string) ($buyer['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $buyerPhone = trim((string) ($buyer['phone'] ?? ''));
    $buyerUsername = trim((string) ($buyer['username'] ?? ''));

    $characteristics = json_decode($property['caracteristicas_json'] ?? '[]', true);
    if (!is_array($characteristics)) {
        $characteristics = [];
    }

    $subject = sprintf(
    'Solicitud de compra propiedad #%d - %s',
    $propertyId,
    $property['tipo_propiedad'] ?? ($property['titulo'] ?? 'Activo')
);

$appointmentFormatted = '';
if ($appointmentDate) {
    $dt = new DateTime($appointmentDate);
    $appointmentFormatted = $dt->format('d/m/Y') . ' a las ' . $dt->format('H:i');
}

$appointmentBlock = $appointmentFormatted !== '' ? '
<div style="margin-bottom:24px;padding:16px;border:1px solid #bfdbfe;border-radius:8px;background:#eff6ff;">
    <p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;color:#1d4ed8;font-weight:700;">Cita de firma agendada</p>
    <p style="margin:0 0 14px;font-size:18px;color:#0b3d91;font-weight:700;line-height:1.4;">' . htmlspecialchars($appointmentFormatted, ENT_QUOTES, 'UTF-8') . '</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;font-size:14px;color:#374151;">
        <tr>
            <td style="padding:6px 0;width:180px;font-weight:700;color:#6b7280;">Notaría</td>
            <td style="padding:6px 0;">' . htmlspecialchars($notaryName, ENT_QUOTES, 'UTF-8') . '</td>
        </tr>
        <tr>
            <td style="padding:6px 0;font-weight:700;color:#6b7280;">Dirección</td>
            <td style="padding:6px 0;">' . htmlspecialchars($notaryAddress, ENT_QUOTES, 'UTF-8') . '</td>
        </tr>
        <tr>
            <td style="padding:6px 0;font-weight:700;color:#6b7280;">Ciudad</td>
            <td style="padding:6px 0;">' . htmlspecialchars($notaryCity, ENT_QUOTES, 'UTF-8') . '</td>
        </tr>' .
        ($notaryPhone !== '' ? '<tr><td style="padding:6px 0;font-weight:700;color:#6b7280;">Teléfono</td><td style="padding:6px 0;">' . htmlspecialchars($notaryPhone, ENT_QUOTES, 'UTF-8') . '</td></tr>' : '') . '
    </table>' .
    ($appointmentNotes !== '' ? '<p style="margin:14px 0 0;font-size:14px;color:#4b5563;line-height:1.6;"><strong>Notas del comprador:</strong> ' . htmlspecialchars($appointmentNotes, ENT_QUOTES, 'UTF-8') . '</p>' : '') .
'</div>' : '';

$body = '
<p style="margin:0 0 20px;color:#1f2937;">Se ha registrado una nueva solicitud de compra sobre un activo inmobiliario con acceso documental previamente validado.</p>

' . $appointmentBlock . '

<div style="margin-bottom:24px;padding:16px;border:1px solid #d1d5db;border-radius:8px;background:#fafafa;">
                <p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;color:#6b7280;font-weight:700;">
                    Resumen de la operación
                </p>
                <p style="margin:0;font-size:18px;color:#111827;font-weight:700;line-height:1.4;">
                    ' . htmlspecialchars($property['tipo_propiedad'] ?? ($property['titulo'] ?? 'Activo'), ENT_QUOTES, 'UTF-8') . '
                    <span style="color:#6b7280;font-weight:600;">· Propiedad #' . htmlspecialchars((string) $propertyId, ENT_QUOTES, 'UTF-8') . '</span>
                </p>
                <p style="margin:8px 0 0;font-size:14px;color:#4b5563;line-height:1.6;">
                    ' . htmlspecialchars($property['ciudad'] ?? 'No especificado', ENT_QUOTES, 'UTF-8') . ' · ' . htmlspecialchars($property['zona'] ?? 'No especificada', ENT_QUOTES, 'UTF-8') . '
                </p>
            </div>

            <div style="margin-bottom:22px;">
                <h3 style="margin:0 0 12px;font-size:18px;font-weight:700;color:#111827;">
                    Comprador
                </h3>
                <div style="background:#ffffff;border:1px solid #d1d5db;border-radius:8px;padding:16px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;font-size:14px;color:#374151;">
                        <tr>
                            <td style="padding:8px 0;width:190px;font-weight:700;color:#6b7280;">ID comprador</td>
                            <td style="padding:8px 0;">' . htmlspecialchars((string) $buyerUserId, ENT_QUOTES, 'UTF-8') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;font-weight:700;color:#6b7280;">Nombre completo</td>
                            <td style="padding:8px 0;">' . htmlspecialchars($buyerFullName ?: 'Sin nombre', ENT_QUOTES, 'UTF-8') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;font-weight:700;color:#6b7280;">Nombre</td>
                            <td style="padding:8px 0;">' . htmlspecialchars($buyerFirstName ?: '-', ENT_QUOTES, 'UTF-8') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;font-weight:700;color:#6b7280;">Apellidos</td>
                            <td style="padding:8px 0;">' . htmlspecialchars($buyerLastName ?: '-', ENT_QUOTES, 'UTF-8') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;font-weight:700;color:#6b7280;">Email</td>
                            <td style="padding:8px 0;">' . htmlspecialchars($buyerEmail ?: 'Sin email', ENT_QUOTES, 'UTF-8') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;font-weight:700;color:#6b7280;">Teléfono</td>
                            <td style="padding:8px 0;">' . htmlspecialchars($buyerPhone ?: 'Sin teléfono', ENT_QUOTES, 'UTF-8') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;font-weight:700;color:#6b7280;">Username</td>
                            <td style="padding:8px 0;">' . htmlspecialchars($buyerUsername ?: '-', ENT_QUOTES, 'UTF-8') . '</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div style="margin-bottom:22px;">
                <h3 style="margin:0 0 12px;font-size:18px;font-weight:700;color:#111827;">
                    Propiedad
                </h3>
                <div style="background:#ffffff;border:1px solid #d1d5db;border-radius:8px;padding:16px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;font-size:14px;color:#374151;">
                        <tr>
                            <td style="padding:8px 0;width:190px;font-weight:700;color:#6b7280;">ID</td>
                            <td style="padding:8px 0;">' . htmlspecialchars((string) $propertyId, ENT_QUOTES, 'UTF-8') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;font-weight:700;color:#6b7280;">Tipo</td>
                            <td style="padding:8px 0;">' . htmlspecialchars($property['tipo_propiedad'] ?? 'No especificado', ENT_QUOTES, 'UTF-8') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;font-weight:700;color:#6b7280;">Ciudad</td>
                            <td style="padding:8px 0;">' . htmlspecialchars($property['ciudad'] ?? 'No especificado', ENT_QUOTES, 'UTF-8') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;font-weight:700;color:#6b7280;">Zona</td>
                            <td style="padding:8px 0;">' . htmlspecialchars($property['zona'] ?? 'No especificada', ENT_QUOTES, 'UTF-8') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;font-weight:700;color:#6b7280;">Precio</td>
                            <td style="padding:8px 0;">' . number_format((float) ($property['precio'] ?? 0), 2, ',', '.') . ' €</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;font-weight:700;color:#6b7280;">Metros cuadrados</td>
                            <td style="padding:8px 0;">' . htmlspecialchars((string) ($property['metros_cuadrados'] ?? 0), ENT_QUOTES, 'UTF-8') . ' m²</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div style="margin-bottom:22px;">
                <h3 style="margin:0 0 12px;font-size:18px;font-weight:700;color:#111827;">
                    Propietario
                </h3>
                <div style="background:#ffffff;border:1px solid #d1d5db;border-radius:8px;padding:16px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;font-size:14px;color:#374151;">
                        <tr>
                            <td style="padding:8px 0;width:190px;font-weight:700;color:#6b7280;">ID propietario</td>
                            <td style="padding:8px 0;">' . htmlspecialchars((string) $ownerId, ENT_QUOTES, 'UTF-8') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;font-weight:700;color:#6b7280;">Nombre completo</td>
                            <td style="padding:8px 0;">' . htmlspecialchars($ownerFullName ?: 'No disponible', ENT_QUOTES, 'UTF-8') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;font-weight:700;color:#6b7280;">Email</td>
                            <td style="padding:8px 0;">' . htmlspecialchars($ownerEmail ?: 'No disponible', ENT_QUOTES, 'UTF-8') . '</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;font-weight:700;color:#6b7280;">Teléfono</td>
                            <td style="padding:8px 0;">' . htmlspecialchars($ownerPhone ?: 'No disponible', ENT_QUOTES, 'UTF-8') . '</td>
                        </tr>
                    </table>
                </div>
            </div>';
if ($characteristics) {
$body .= '
            <div style="margin-bottom:22px;">
                <h3 style="margin:0 0 12px;font-size:18px;font-weight:700;color:#111827;">
                    Características destacadas
                </h3>
                <div style="background:#fafafa;border:1px solid #d1d5db;border-radius:8px;padding:16px;">';

    foreach ($characteristics as $key => $value) {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        $body .= '
                    <p style="margin:0 0 10px;font-size:14px;line-height:1.6;color:#111827;">
                        <strong>' .
                            htmlspecialchars(ucfirst(str_replace('_', ' ', (string) $key)), ENT_QUOTES, 'UTF-8') .
                        ':</strong> ' .
                            htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') .
                    '</p>';
    }

    $body .= '
                </div>
            </div>';
}

$body .= '
<div style="margin-top:24px;padding:14px 16px;background:#f8fbff;border:1px solid #dbeafe;border-radius:8px;">
    <p style="margin:0;font-size:13px;line-height:1.6;color:#1d4ed8;">
        Esta solicitud ha sido generada desde la plataforma inmobiliaria automatizada y corresponde a un comprador con acceso documental habilitado.
    </p>
</div>';

$body = renderEmailLayout(
    'Solicitud de compra registrada',
    'Nueva oportunidad comercial · Activo con acceso documental validado',
    $body
);

    $officeEmail = 'practicasreglado@gmail.com';

    purchaseLog('ANTES DE SEND MAIL', [
        'officeEmail' => $officeEmail,
        'buyerEmail' => $buyerEmail,
        'subject' => $subject,
    ]);

    sendNotificationEmail(
        $officeEmail,
        $subject,
        $body,
        $buyerEmail ?: null
    );

    purchaseLog('EMAIL ENVIADO OK');

    try {
        $stmtPurchase = $pdo->prepare('
            INSERT INTO purchase_requests
                (buyer_user_id, buyer_email, buyer_name, buyer_phone,
                 property_id, property_title, status)
            VALUES
                (:buyer_user_id, :buyer_email, :buyer_name, :buyer_phone,
                 :property_id, :property_title, "pending")
        ');
        $stmtPurchase->execute([
            'buyer_user_id'  => $buyerUserId,
            'buyer_email'    => $buyerEmail ?: ($buyer['email'] ?? ''),
            'buyer_name'     => $buyerFullName !== '' ? $buyerFullName : ($buyer['username'] ?? null),
            'buyer_phone'    => $buyerPhone ?: null,
            'property_id'    => $propertyId,
            'property_title' => (string) ($property['tipo_propiedad'] ?? ''),
        ]);
    } catch (Throwable $insertException) {
        purchaseLog('REGISTRO PURCHASE_REQUESTS FALLÓ', [
            'message' => $insertException->getMessage(),
        ]);
    }

    // Ventana de bloqueo: dos citas no pueden estar a menos de 3 horas entre
    // sí. Se comprueba ahora (server-side) aunque el frontend ya filtra
    // huecos — así evitamos que alguien que envíe una petición manual por
    // API se salte la regla.
    try {
        $conflictStmt = $pdo->prepare('
            SELECT COUNT(*)
            FROM purchase_appointments
            WHERE status = "scheduled"
              AND appointment_date > DATE_SUB(:reqdate, INTERVAL 180 MINUTE)
              AND appointment_date < DATE_ADD(:reqdate, INTERVAL 180 MINUTE)
        ');
        $conflictStmt->execute(['reqdate' => $appointmentDate]);
        $conflicts = (int) $conflictStmt->fetchColumn();

        if ($conflicts > 0) {
            respondJson(409, [
                'success' => false,
                'message' => 'El horario elegido no está disponible. Ya hay otra cita en las 3 horas cercanas. Selecciona otro hueco.',
            ]);
        }
    } catch (Throwable $conflictException) {
        purchaseLog('Conflict check falló', ['error' => $conflictException->getMessage()]);
        // Fail-closed: si no podemos comprobar conflictos, rechazamos.
        respondJson(500, [
            'success' => false,
            'message' => 'No se pudo validar el horario. Inténtalo de nuevo.',
        ]);
    }

    // Insertar la cita agendada. Si esto falla, devolvemos 500 para que el
    // usuario se entere — a diferencia del INSERT de purchase_requests, la
    // cita es la parte visible del flujo y sin ella el admin no ve nada.
    try {
        $stmtAppointment = $pdo->prepare('
            INSERT INTO purchase_appointments
                (user_id, property_id, appointment_date,
                 notary_name, notary_address, notary_city, notary_phone,
                 notes, status)
            VALUES
                (:user_id, :property_id, :appointment_date,
                 :notary_name, :notary_address, :notary_city, :notary_phone,
                 :notes, "scheduled")
        ');
        $stmtAppointment->execute([
            'user_id'          => $buyerUserId,
            'property_id'      => $propertyId,
            'appointment_date' => $appointmentDate,
            'notary_name'      => $notaryName,
            'notary_address'   => $notaryAddress,
            'notary_city'      => $notaryCity,
            'notary_phone'     => $notaryPhone !== '' ? $notaryPhone : null,
            'notes'            => $appointmentNotes !== '' ? $appointmentNotes : null,
        ]);
    } catch (Throwable $appointmentException) {
        $errorId = logAndReferenceError('request_purchase.appointment', $appointmentException);
        purchaseLog('REGISTRO APPOINTMENT FALLÓ', [
            'error_id' => $errorId,
            'message'  => $appointmentException->getMessage(),
        ]);
        respondJson(500, [
            'success' => false,
            'message' => 'No se pudo registrar la cita. Referencia: ' . $errorId .
                         ' (¿está la tabla purchase_appointments creada con las columnas notary_*?)',
        ]);
    }

    auditLog($pdo, 'purchase.request', array_merge(
        auditContextFromAuth($auth, $buyerUserId),
        [
            'resource_type' => 'property',
            'resource_id'   => (string) $propertyId,
            'metadata'      => [
                'property_id'      => $propertyId,
                'property_title'   => (string) ($property['tipo_propiedad'] ?? ''),
                'buyer_email'      => $buyerEmail ?: null,
                'appointment_date' => $appointmentDate,
            ],
        ]
    ));

    respondJson(200, [
        'success' => true,
        'message' => 'Solicitud enviada. Nuestro equipo te contactará pronto.',
        'access_status' => 'validado',
    ]);

} catch (Throwable $exception) {
    $errorId = logAndReferenceError('request_purchase', $exception);
    purchaseLog('ERROR', [
        'error_id' => $errorId,
        'message'  => $exception->getMessage(),
    ]);

    respondJson(500, [
        'success' => false,
        'message' => 'No se pudo enviar la solicitud. Referencia: ' . $errorId,
    ]);
}