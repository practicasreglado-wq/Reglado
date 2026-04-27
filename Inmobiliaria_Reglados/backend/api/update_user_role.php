<?php
declare(strict_types=1);

/**
 * Endpoint para que un admin cambie el rol de un usuario manualmente
 * (sin pasar por la solicitud Premium del usuario).
 *
 * Roles válidos: admin, real, basic (los 3 niveles de la plataforma).
 *
 * Defensas:
 *  - Confirmación de contraseña del admin (lib/admin_password_check.php).
 *  - Rate limit por admin para evitar cambios masivos accidentales (audit
 *    queda con 'user.role_change_rate_limited' si se supera).
 *  - Auth fallida queda registrada con 'user.role_change_auth_failed'.
 *  - Cambio exitoso → 'user.role_change' + notificación al usuario afectado
 *    + opción de forzar re-login si baja de nivel.
 */

require_once dirname(__DIR__) . '/lib/env_loader.php';
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once __DIR__ . '/../config/cors.php';
require_once dirname(__DIR__) . '/lib/audit.php';
require_once dirname(__DIR__) . '/lib/email_layout.php';
require_once dirname(__DIR__) . '/lib/error_reporting.php';
require_once dirname(__DIR__) . '/lib/notifications.php';
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
$adminPassword = (string) ($input['admin_password'] ?? '');

$allowedRoles = ['user', 'real'];
if ($targetUserId <= 0 || !in_array($newRole, $allowedRoles, true)) {
    respondJson(422, ['success' => false, 'message' => 'Datos inválidos. Solo se permite cambiar entre user y real.']);
}

if ($adminPassword === '') {
    respondJson(422, ['success' => false, 'message' => 'Debes confirmar tu contraseña para cambiar el rol.']);
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

    $adminId = (int) ($auth['sub'] ?? 0);

    $rateScope = 'admin_role_change_password';
    $rateKeyHash = hash('sha256', $rateScope . '|' . $adminId);
    $rateWindowSeconds = 900;
    $rateMaxFailures = 5;

    $rlStmt = $pdoAuth->prepare('SELECT id, attempts, updated_at FROM rate_limits WHERE key_hash = ? AND scope_name = ? LIMIT 1');
    $rlStmt->execute([$rateKeyHash, $rateScope]);
    $rlRow = $rlStmt->fetch();

    if ($rlRow) {
        $updatedAt = strtotime((string) $rlRow['updated_at']) ?: 0;
        $withinWindow = $updatedAt >= time() - $rateWindowSeconds;
        if ($withinWindow && (int) $rlRow['attempts'] >= $rateMaxFailures) {
            auditLog($pdo, 'user.role_change_rate_limited', array_merge(
                auditContextFromAuth($auth),
                ['resource_type' => 'user', 'resource_id' => (string) $targetUserId]
            ));
            respondJson(429, ['success' => false, 'message' => 'Demasiados intentos fallidos. Espera unos minutos antes de volver a probar.']);
        }
    }

    $adminStmt = $pdoAuth->prepare("SELECT password FROM users WHERE id = :id LIMIT 1");
    $adminStmt->execute(['id' => $adminId]);
    $adminRow = $adminStmt->fetch();

    if (!$adminRow || !password_verify($adminPassword, (string) $adminRow['password'])) {
        if (!$rlRow) {
            $pdoAuth->prepare('INSERT INTO rate_limits(key_hash, scope_name, attempts, updated_at, created_at) VALUES(?, ?, 1, NOW(), NOW())')
                    ->execute([$rateKeyHash, $rateScope]);
        } else {
            $updatedAt = strtotime((string) $rlRow['updated_at']) ?: 0;
            if ($updatedAt < time() - $rateWindowSeconds) {
                $pdoAuth->prepare('UPDATE rate_limits SET attempts = 1, updated_at = NOW() WHERE id = ?')
                        ->execute([(int) $rlRow['id']]);
            } else {
                $pdoAuth->prepare('UPDATE rate_limits SET attempts = attempts + 1, updated_at = NOW() WHERE id = ?')
                        ->execute([(int) $rlRow['id']]);
            }
        }

        auditLog($pdo, 'user.role_change_auth_failed', array_merge(
            auditContextFromAuth($auth),
            ['resource_type' => 'user', 'resource_id' => (string) $targetUserId]
        ));
        respondJson(401, ['success' => false, 'message' => 'Contraseña incorrecta.']);
    }

    $pdoAuth->prepare('DELETE FROM rate_limits WHERE key_hash = ? AND scope_name = ?')
            ->execute([$rateKeyHash, $rateScope]);

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

    try {
        createNotification($pdo, $targetUserId, [
            'title'   => $newRole === 'real' ? 'Has sido promocionado a Usuario Premium' : 'Tu rol ha cambiado',
            'message' => $newRole === 'real'
                ? 'Tu rol en Reglado Real Estate ha sido actualizado a Usuario Premium. Deberás volver a iniciar sesión para que los cambios surtan efecto.'
                : 'Tu rol en Reglado Real Estate ha sido actualizado a Usuario estándar. Deberás volver a iniciar sesión para que los cambios surtan efecto.',
            'type'    => $newRole === 'real' ? 'role_promotion' : 'role_demotion',
        ]);
    } catch (Throwable $e) {
        error_log('[update_user_role] notification falló: ' . $e->getMessage());
    }

    if ($newRole === 'user') {
        try {
            $pdo->prepare('DELETE FROM user_match_preferences WHERE user_id = :uid')
                ->execute(['uid' => $targetUserId]);
        } catch (Throwable $e) {
            error_log('[update_user_role] delete user_match_preferences falló: ' . $e->getMessage());
        }

        try {
            $pdo->prepare('DELETE FROM search_history WHERE user_id = :uid')
                ->execute(['uid' => $targetUserId]);
        } catch (Throwable $e) {
            error_log('[update_user_role] delete search_history falló: ' . $e->getMessage());
        }
    }

    if (filter_var($targetUser['email'], FILTER_VALIDATE_EMAIL)) {
        $newRoleLabel = $newRole === 'real' ? 'Usuario Premium' : 'Usuario estándar';
        $emailBody = renderEmailLayout(
            'Tu rol ha cambiado',
            'Tu perfil en Reglado Real Estate ha sido actualizado',
            '<p style="margin:0 0 16px;">Tu rol en Reglado Real Estate ha sido actualizado a: <strong>' . htmlspecialchars($newRoleLabel) . '</strong>.</p>
<p style="margin:0;">Por seguridad, deberás volver a iniciar sesión para que los cambios surtan efecto.</p>'
        );

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
    $errorId = logAndReferenceError('update_user_role', $e);
    respondJson(500, [
        'success' => false,
        'message' => 'Error al actualizar el rol. Referencia: ' . $errorId,
    ]);
}
