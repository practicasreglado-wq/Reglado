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

purchaseLog('BUYER DB', $buyer);

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

$body = '
<div style="margin:0;padding:24px;background:#f5f7fa;font-family:Arial,sans-serif;color:#1f2937;">
    <div style="max-width:720px;margin:0 auto;background:#ffffff;border:1px solid #d9e2ec;border-radius:8px;overflow:hidden;">

        <div style="background:#2563eb;padding:20px 24px;color:#ffffff;">
            <p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;font-weight:700;">
                Nueva oportunidad comercial
            </p>
            <h2 style="margin:0;font-size:22px;line-height:1.3;font-weight:700;">
                Solicitud de compra registrada
            </h2>
            <p style="margin:8px 0 0;font-size:14px;line-height:1.6;">
                Se ha registrado una nueva solicitud de compra sobre un activo inmobiliario con acceso documental previamente validado.
            </p>
        </div>

        <div style="padding:24px;color:#1f2937;">

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
            </div>

        </div>

        <div style="padding:14px 18px;background:#f3f4f6;border-top:1px solid #d1d5db;text-align:center;">
            <p style="margin:0;font-size:12px;color:#6b7280;">
                Reglado Real Estate · Sistema automatizado de comercialización y control documental
            </p>
        </div>

    </div>
</div>';

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