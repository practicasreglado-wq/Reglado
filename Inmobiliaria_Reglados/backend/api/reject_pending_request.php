<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/env_loader.php';
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once __DIR__ . '/../config/cors.php';
require_once dirname(__DIR__) . '/lib/notifications_helper.php';
require_once dirname(__DIR__) . '/lib/audit.php';
require_once dirname(__DIR__) . '/send_mail.php';

loadEnv(dirname(__DIR__) . '/.env');

applyCors();
handlePreflight();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondJson(405, ['success' => false, 'message' => 'Método no permitido.']);
}

$context = requireAuthenticatedUser($pdo);
$auth = $context['auth'] ?? [];
$role = strtolower((string) ($auth['role'] ?? ''));

if ($role !== 'admin') {
    respondJson(403, ['success' => false, 'message' => 'Acceso restringido. Solo administradores.']);
}

$input = json_decode(file_get_contents('php://input') ?: '{}', true);
$requestId = (int) ($input['request_id'] ?? 0);

if ($requestId <= 0) {
    respondJson(422, ['success' => false, 'message' => 'ID de solicitud no válido.']);
}

$host = (string) getenv('DB_HOST');
$port = (string) getenv('DB_PORT');
$user = (string) getenv('DB_USER');
$pass = (string) getenv('DB_PASS');

$pdoAuth = null;

try {
    $pdoAuth = new PDO(
        "mysql:host={$host};port={$port};dbname=regladousers;charset=utf8mb4",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );

    $pdo->beginTransaction();

    $stmtCheck = $pdo->prepare("
        SELECT id, user_email FROM role_promotion_requests
        WHERE id = ? AND status = 'pending' LIMIT 1
    ");
    $stmtCheck->execute([$requestId]);
    $request = $stmtCheck->fetch();

    if (!$request) {
        $pdo->rollBack();
        respondJson(404, ['success' => false, 'message' => 'La solicitud no existe o ya fue procesada.']);
    }

    $email = (string) $request['user_email'];

    $stmtUser = $pdoAuth->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmtUser->execute([$email]);
    $userRow = $stmtUser->fetch();

    $stmtMarkResolved = $pdo->prepare("
        UPDATE role_promotion_requests
        SET status = 'rejected', resolved_at = NOW()
        WHERE id = ?
    ");
    $stmtMarkResolved->execute([$requestId]);

    createUserNotification($pdo, [
        'user_id'    => $userRow ? (int) $userRow['id'] : null,
        'user_email' => $email,
        'title'      => 'Solicitud rechazada',
        'message'    => 'Tu solicitud para acceder como usuario real ha sido revisada y no ha sido aprobada en este momento. Puedes volver a solicitarla más adelante.',
        'type'       => 'warning',
        'link'       => '/profile',
    ]);

    $pdo->commit();

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailBody = <<<'HTML'
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Solicitud rechazada</title></head>
<body style="margin:0;padding:0;background-color:#f4f6f8;font-family:Arial,sans-serif;color:#1f2937;">
<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f8;padding:30px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(0,0,0,0.08);">
<tr><td style="background:linear-gradient(135deg,#0b3d91,#123f7a);padding:30px;text-align:center;color:#ffffff;">
<h2 style="margin:0;font-size:24px;font-weight:700;">Solicitud revisada</h2>
<p style="margin:10px 0 0;font-size:14px;color:rgba(255,255,255,0.85);">Resultado de su solicitud de acceso</p>
</td></tr>
<tr><td style="padding:32px;color:#374151;">
<div style="display:inline-block;background:#fef2f2;color:#b91c1c;font-size:13px;font-weight:700;padding:8px 14px;border-radius:999px;margin-bottom:20px;">
Solicitud no aprobada
</div>
<p style="font-size:15px;line-height:1.7;margin:0 0 16px;">
Le informamos que su solicitud para acceder como <strong>usuario real</strong> ha sido revisada y <strong>no ha sido aprobada</strong> en este momento.
</p>
<p style="font-size:15px;line-height:1.7;margin:0 0 16px;">
Puede volver a enviar la solicitud más adelante si lo considera necesario.
</p>
<div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:18px;margin:20px 0;">
<p style="margin:0;font-size:14px;color:#4b5563;line-height:1.6;">
Si necesita más información o desea realizar cualquier consulta, nuestro equipo estará encantado de atenderle.
</p>
</div>
</td></tr>
<tr><td style="padding:18px;text-align:center;font-size:12px;color:#9ca3af;border-top:1px solid #e5e7eb;">
Reglado Real Estate
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;

        try {
            sendNotificationEmail($email, 'Solicitud de acceso como usuario real - Revisada', $emailBody);
        } catch (Throwable $emailEx) {
            error_log('[reject_pending_request] email falló: ' . $emailEx->getMessage());
        }
    }

    auditLog($pdo, 'role.promotion.reject', array_merge(
        auditContextFromAuth($auth),
        [
            'resource_type' => 'role_promotion_request',
            'resource_id'   => (string) $requestId,
            'metadata'      => ['target_user_id' => $userRow ? (int) $userRow['id'] : null, 'target_email' => $email, 'via' => 'admin_panel']
        ]
    ));

    respondJson(200, ['success' => true, 'message' => 'Solicitud rechazada correctamente.']);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    respondJson(500, ['success' => false, 'message' => 'Error al rechazar la solicitud: ' . $e->getMessage()]);
}
