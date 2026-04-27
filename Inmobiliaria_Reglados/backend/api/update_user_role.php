<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/env_loader.php';
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once __DIR__ . '/../config/cors.php';
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
$targetUserId = (int) ($input['user_id'] ?? 0);
$newRole = strtolower(trim((string) ($input['role'] ?? '')));

$allowedRoles = ['user', 'real'];
if ($targetUserId <= 0 || !in_array($newRole, $allowedRoles, true)) {
    respondJson(422, ['success' => false, 'message' => 'Datos inválidos. Solo se permite cambiar entre user y real.']);
}

$host = (string) getenv('DB_HOST');
$port = (string) getenv('DB_PORT');
$dbUser = (string) getenv('DB_USER');
$dbPass = (string) getenv('DB_PASS');

try {
    $pdoAuth = new PDO(
        "mysql:host={$host};port={$port};dbname=regladousers;charset=utf8mb4",
        $dbUser, $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );

    $userStmt = $pdoAuth->prepare("SELECT id, email, role, first_name FROM users WHERE id = :id LIMIT 1");
    $userStmt->execute(['id' => $targetUserId]);
    $targetUser = $userStmt->fetch();

    if (!$targetUser) {
        respondJson(404, ['success' => false, 'message' => 'Usuario no encontrado.']);
    }

    if (strtolower((string) $targetUser['role']) === 'admin') {
        respondJson(403, ['success' => false, 'message' => 'No se puede cambiar el rol de un administrador desde este panel.']);
    }

    $oldRole = (string) $targetUser['role'];

    if (strtolower($oldRole) === $newRole) {
        respondJson(200, ['success' => true, 'message' => 'El usuario ya tenía ese rol.', 'changed' => false]);
    }

    $updateStmt = $pdoAuth->prepare("UPDATE users SET role = :role WHERE id = :id");
    $updateStmt->execute(['role' => $newRole, 'id' => $targetUserId]);

    $invalidateStmt = $pdo->prepare("
        INSERT INTO user_inmo_status (user_id, last_token_invalidated_at, updated_by)
        VALUES (:uid, NOW(), :updater)
        ON DUPLICATE KEY UPDATE last_token_invalidated_at = NOW(), updated_by = :updater
    ");
    $invalidateStmt->execute([
        'uid' => $targetUserId,
        'updater' => (int) ($auth['sub'] ?? 0),
    ]);

    if (filter_var($targetUser['email'], FILTER_VALIDATE_EMAIL)) {
        $newRoleLabel = $newRole === 'real' ? 'Usuario Real' : 'Usuario estándar';
        $emailBody = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial,sans-serif;color:#1f2937;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;padding:30px 0;"><tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(0,0,0,0.08);">
<tr><td style="background:linear-gradient(135deg,#0b3d91,#123f7a);padding:30px;text-align:center;color:#fff;">
<h2 style="margin:0;font-size:22px;">Tu rol ha cambiado</h2>
</td></tr>
<tr><td style="padding:30px;">
<p style="font-size:15px;line-height:1.7;">Tu rol en Reglado Real Estate ha sido actualizado a: <strong>' . htmlspecialchars($newRoleLabel) . '</strong>.</p>
<p style="font-size:15px;line-height:1.7;">Por seguridad, deberás volver a iniciar sesión para que los cambios surtan efecto.</p>
</td></tr>
<tr><td style="padding:18px;text-align:center;font-size:12px;color:#9ca3af;border-top:1px solid #e5e7eb;">Reglado Real Estate</td></tr>
</table></td></tr></table></body></html>';

        try {
            sendNotificationEmail($targetUser['email'], 'Tu rol en Reglado Real Estate ha cambiado', $emailBody);
        } catch (Throwable $e) {
            error_log('[update_user_role] email falló: ' . $e->getMessage());
        }
    }

    auditLog($pdo, 'user.role_change', array_merge(
        auditContextFromAuth($auth),
        [
            'resource_type' => 'user',
            'resource_id'   => (string) $targetUserId,
            'metadata'      => ['old_role' => $oldRole, 'new_role' => $newRole, 'target_email' => $targetUser['email']]
        ]
    ));

    respondJson(200, ['success' => true, 'message' => 'Rol actualizado correctamente.', 'changed' => true]);

} catch (Throwable $e) {
    respondJson(500, ['success' => false, 'message' => 'Error al actualizar el rol: ' . $e->getMessage()]);
}
