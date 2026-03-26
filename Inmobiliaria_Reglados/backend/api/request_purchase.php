<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/cors.php';
applyCors();
handlePreflight();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../lib/env_loader.php';
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

purchaseLog('START');
purchaseLog('RAW INPUT', $rawInput);
purchaseLog('AUTH', $auth);
purchaseLog('buyerUserId', $buyerUserId);
purchaseLog('propertyId', $propertyId);

if ($buyerUserId <= 0) {
    respondJson(401, [
        'success' => false,
        'message' => 'Debes iniciar sesión.',
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

    $buyerFirstName = trim((string) ($auth['first_name'] ?? ''));
    $buyerLastName = trim((string) ($auth['last_name'] ?? ''));
    $buyerFullName = trim((string) ($auth['name'] ?? trim($buyerFirstName . ' ' . $buyerLastName)));
    $buyerEmail = filter_var($auth['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $buyerPhone = trim((string) ($auth['phone'] ?? ''));
    $buyerUsername = trim((string) ($auth['username'] ?? ''));

    $characteristics = json_decode($property['caracteristicas_json'] ?? '[]', true);
    if (!is_array($characteristics)) {
        $characteristics = [];
    }

    $subject = sprintf(
        'Solicitud de compra propiedad #%d - %s',
        $propertyId,
        $property['tipo_propiedad'] ?? ($property['titulo'] ?? 'Activo')
    );

    $body = '<h2>Solicitud de compra registrada</h2>';
    $body .= '<p><strong>Comprador:</strong></p><ul>';
    $body .= '<li>ID comprador: ' . htmlspecialchars((string) $buyerUserId, ENT_QUOTES, 'UTF-8') . '</li>';
    $body .= '<li>Nombre completo: ' . htmlspecialchars($buyerFullName ?: 'Sin nombre', ENT_QUOTES, 'UTF-8') . '</li>';
    $body .= '<li>Nombre: ' . htmlspecialchars($buyerFirstName ?: '-', ENT_QUOTES, 'UTF-8') . '</li>';
    $body .= '<li>Apellidos: ' . htmlspecialchars($buyerLastName ?: '-', ENT_QUOTES, 'UTF-8') . '</li>';
    $body .= '<li>Email: ' . htmlspecialchars($buyerEmail ?: 'Sin email', ENT_QUOTES, 'UTF-8') . '</li>';
    $body .= '<li>Teléfono: ' . htmlspecialchars($buyerPhone ?: 'Sin teléfono', ENT_QUOTES, 'UTF-8') . '</li>';
    $body .= '<li>Username: ' . htmlspecialchars($buyerUsername ?: '-', ENT_QUOTES, 'UTF-8') . '</li>';
    $body .= '</ul>';

    $body .= '<p><strong>Propiedad:</strong></p><ul>';
    $body .= '<li>ID: ' . htmlspecialchars((string) $propertyId, ENT_QUOTES, 'UTF-8') . '</li>';
    $body .= '<li>Tipo: ' . htmlspecialchars($property['tipo_propiedad'] ?? 'No especificado', ENT_QUOTES, 'UTF-8') . '</li>';
    $body .= '<li>Ciudad: ' . htmlspecialchars($property['ciudad'] ?? 'No especificado', ENT_QUOTES, 'UTF-8') . '</li>';
    $body .= '<li>Zona: ' . htmlspecialchars($property['zona'] ?? 'No especificada', ENT_QUOTES, 'UTF-8') . '</li>';
    $body .= '<li>Precio: ' . number_format((float) ($property['precio'] ?? 0), 2, ',', '.') . ' €</li>';
    $body .= '<li>Metros cuadrados: ' . htmlspecialchars((string) ($property['metros_cuadrados'] ?? 0), ENT_QUOTES, 'UTF-8') . ' m²</li>';
    $body .= '</ul>';

    $body .= '<p><strong>Propietario / captador del activo:</strong></p><ul>';
    $body .= '<li>ID propietario: ' . htmlspecialchars((string) $ownerId, ENT_QUOTES, 'UTF-8') . '</li>';
    $body .= '<li>Nombre completo: ' . htmlspecialchars($ownerFullName ?: 'No disponible', ENT_QUOTES, 'UTF-8') . '</li>';
    $body .= '<li>Email: ' . htmlspecialchars($ownerEmail ?: 'No disponible', ENT_QUOTES, 'UTF-8') . '</li>';
    $body .= '<li>Teléfono: ' . htmlspecialchars($ownerPhone ?: 'No disponible', ENT_QUOTES, 'UTF-8') . '</li>';
    $body .= '</ul>';

    if ($characteristics) {
        $body .= '<p><strong>Características destacadas:</strong></p><ul>';
        foreach ($characteristics as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }

            $body .= '<li>' .
                htmlspecialchars(ucfirst(str_replace('_', ' ', (string) $key)), ENT_QUOTES, 'UTF-8') .
                ': ' .
                htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') .
                '</li>';
        }
        $body .= '</ul>';
    }

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

    respondJson(200, [
        'success' => true,
        'message' => 'Solicitud enviada. Nuestro equipo te contactará pronto.',
        'access_status' => 'validado',
    ]);

} catch (Throwable $exception) {
    purchaseLog('ERROR', [
        'message' => $exception->getMessage(),
        'trace' => $exception->getTraceAsString(),
    ]);

    respondJson(500, [
        'success' => false,
        'message' => 'No se pudo enviar la solicitud: ' . $exception->getMessage(),
    ]);
}