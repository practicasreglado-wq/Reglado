<?php
declare(strict_types=1);

/**
 * Endpoint para que un admin AUTENTICADO apruebe una solicitud de
 * promoción a Premium desde el panel admin (NO desde el correo).
 *
 * Diferencias con approve_real_role.php:
 *  - Aquel: enlace de email con token, sin login.
 *  - Este: SPA del admin con JWT + confirmación de pwd.
 *
 * Mismo efecto de fondo: sube el rol a 'real' en regladousers.users +
 * notificación al usuario + audit 'role.promotion.approve'.
 */

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
    'admin_role_approve'
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
    $pdoAuth->beginTransaction();

    $stmtCheck = $pdo->prepare("
        SELECT id, user_email FROM role_promotion_requests
        WHERE id = ? AND status = 'pending' LIMIT 1
    ");
    $stmtCheck->execute([$requestId]);
    $request = $stmtCheck->fetch();

    if (!$request) {
        $pdo->rollBack();
        $pdoAuth->rollBack();
        respondJson(404, ['success' => false, 'message' => 'La solicitud no existe o ya fue procesada.']);
    }

    $email = (string) $request['user_email'];

    $stmtUser = $pdoAuth->prepare("SELECT id, role, email FROM users WHERE email = ? LIMIT 1");
    $stmtUser->execute([$email]);
    $userRow = $stmtUser->fetch();

    if (!$userRow) {
        $pdo->rollBack();
        $pdoAuth->rollBack();
        respondJson(404, ['success' => false, 'message' => 'Usuario no encontrado.']);
    }

    $stmtUpdateUser = $pdoAuth->prepare("UPDATE users SET role = 'real' WHERE email = ?");
    $stmtUpdateUser->execute([$email]);

    $stmtMarkResolved = $pdo->prepare("
        UPDATE role_promotion_requests
        SET status = 'approved', resolved_at = NOW()
        WHERE id = ?
    ");
    $stmtMarkResolved->execute([$requestId]);

    createUserNotification($pdo, [
        'user_id'    => (int) $userRow['id'],
        'user_email' => $email,
        'title'      => 'Solicitud aprobada',
        'message'    => 'Tu solicitud para acceder como usuario Premium ha sido aprobada. Ya puedes acceder a las funciones habilitadas para este perfil.',
        'type'       => 'success',
        'link'       => '/profile',
    ]);

    $pdoAuth->commit();
    $pdo->commit();

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailBody = renderEmailLayout(
            'Solicitud aprobada',
            'Acceso como usuario Premium activado',
            <<<'HTML'
<div style="display:inline-block;background:#e9f9ef;color:#1f7a3d;font-size:13px;font-weight:700;padding:8px 14px;border-radius:999px;margin-bottom:20px;">Acceso aprobado</div>
<p style="margin:0 0 16px;">Nos complace informarle que su solicitud para acceder como <strong>usuario Premium</strong> ha sido <strong>aprobada correctamente</strong>.</p>
<p style="margin:0 0 16px;">A partir de este momento ya puede acceder a las funcionalidades habilitadas para este perfil dentro de la plataforma.</p>
<div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:18px;margin:20px 0;">
<p style="margin:0 0 8px;font-weight:700;color:#111827;">¿Qué puede hacer ahora?</p>
<p style="margin:0;font-size:14px;color:#4b5563;line-height:1.6;">Ya puede iniciar sesión y utilizar las opciones y accesos reservados para usuarios Premium dentro de su cuenta.</p>
</div>
HTML
        );

        try {
            sendNotificationEmail($email, 'Solicitud de acceso Premium - Aprobada', $emailBody);
        } catch (Throwable $emailEx) {
            error_log('[approve_pending_request] email falló: ' . $emailEx->getMessage());
        }
    }

    auditLog($pdo, 'role.promotion.approve', array_merge(
        auditContextFromAuth($auth),
        [
            'resource_type' => 'role_promotion_request',
            'resource_id'   => (string) $requestId,
            'metadata'      => ['target_user_id' => (int) $userRow['id'], 'target_email' => $email, 'via' => 'admin_panel']
        ]
    ));

    respondJson(200, ['success' => true, 'message' => 'Solicitud aprobada correctamente.']);

} catch (Throwable $e) {
    if ($pdoAuth instanceof PDO && $pdoAuth->inTransaction()) $pdoAuth->rollBack();
    if ($pdo->inTransaction()) $pdo->rollBack();
    $errorId = logAndReferenceError('approve_pending_request', $e);
    respondJson(500, [
        'success' => false,
        'message' => 'Error al aprobar la solicitud. Referencia: ' . $errorId,
    ]);
}
