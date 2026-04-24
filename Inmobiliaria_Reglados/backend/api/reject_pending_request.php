<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/env_loader.php';
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once __DIR__ . '/../config/cors.php';
require_once dirname(__DIR__) . '/lib/notifications_helper.php';
require_once dirname(__DIR__) . '/lib/audit.php';
require_once dirname(__DIR__) . '/lib/email_layout.php';
require_once dirname(__DIR__) . '/lib/error_reporting.php';
require_once dirname(__DIR__) . '/lib/admin_password_check.php';
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
$adminPassword = (string) ($input['admin_password'] ?? '');

if ($requestId <= 0) {
    respondJson(422, ['success' => false, 'message' => 'ID de solicitud no válido.']);
}

requireAdminPasswordConfirmation(
    (int) ($auth['sub'] ?? 0),
    $adminPassword,
    'admin_role_reject'
);

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
        'message'    => 'Tu solicitud para acceder como usuario Premium ha sido revisada y no ha sido aprobada en este momento. Puedes volver a solicitarla más adelante.',
        'type'       => 'warning',
        'link'       => '/profile',
    ]);

    $pdo->commit();

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $panelUrl = htmlspecialchars(
            rtrim((string) (getenv('FRONTEND_URL') ?: 'http://localhost:5175'), '/') . '/profile',
            ENT_QUOTES,
            'UTF-8'
        );
        $emailBody = renderEmailLayout(
            'Solicitud revisada',
            'Resultado de su solicitud de acceso',
            <<<HTML
<div style="display:inline-block;background:#fef2f2;color:#b91c1c;font-size:13px;font-weight:700;padding:8px 14px;border-radius:999px;margin-bottom:20px;">Solicitud no aprobada</div>
<p style="margin:0 0 16px;">Le informamos que su solicitud para acceder como <strong>usuario Premium</strong> ha sido revisada y <strong>no ha sido aprobada</strong> en este momento.</p>
<p style="margin:0 0 16px;">Puede volver a enviar la solicitud más adelante si lo considera necesario.</p>
<div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:18px;margin:20px 0;">
<p style="margin:0;font-size:14px;color:#4b5563;line-height:1.6;">Si necesita más información o desea realizar cualquier consulta, nuestro equipo estará encantado de atenderle.</p>
</div>
<div style="text-align:center;margin:24px 0;">
<a href="{$panelUrl}" target="_blank" rel="noopener" style="background:#0b3d91;color:#ffffff;padding:12px 24px;text-decoration:none;border-radius:8px;font-size:14px;font-weight:bold;display:inline-block;">Acceder a mi cuenta</a>
</div>
HTML
        );

        try {
            sendNotificationEmail($email, 'Solicitud de acceso Premium - Revisada', $emailBody);
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
    $errorId = logAndReferenceError('reject_pending_request', $e);
    respondJson(500, [
        'success' => false,
        'message' => 'Error al rechazar la solicitud. Referencia: ' . $errorId,
    ]);
}
