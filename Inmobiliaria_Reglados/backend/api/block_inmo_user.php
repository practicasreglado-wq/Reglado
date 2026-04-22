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
$notes = trim((string) ($input['notes'] ?? ''));

if ($targetUserId <= 0) {
    respondJson(422, ['success' => false, 'message' => 'ID de usuario no válido.']);
}

if ($targetUserId === (int) ($auth['sub'] ?? 0)) {
    respondJson(403, ['success' => false, 'message' => 'No puedes bloquearte a ti mismo.']);
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

    $userStmt = $pdoAuth->prepare("SELECT id, email, role FROM users WHERE id = :id LIMIT 1");
    $userStmt->execute(['id' => $targetUserId]);
    $targetUser = $userStmt->fetch();

    if (!$targetUser) {
        respondJson(404, ['success' => false, 'message' => 'Usuario no encontrado.']);
    }

    if (strtolower((string) $targetUser['role']) === 'admin') {
        respondJson(403, ['success' => false, 'message' => 'No se puede bloquear a un administrador.']);
    }

    $stmt = $pdo->prepare("
        INSERT INTO user_inmo_status (user_id, is_blocked, last_token_invalidated_at, notes, updated_by)
        VALUES (:uid, 1, NOW(), :notes, :updater)
        ON DUPLICATE KEY UPDATE
            is_blocked = 1,
            last_token_invalidated_at = NOW(),
            notes = :notes,
            updated_by = :updater
    ");
    $stmt->execute([
        'uid' => $targetUserId,
        'notes' => $notes !== '' ? $notes : null,
        'updater' => (int) ($auth['sub'] ?? 0),
    ]);

    if (filter_var($targetUser['email'], FILTER_VALIDATE_EMAIL)) {
        $emailBody = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial,sans-serif;color:#1f2937;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;padding:30px 0;"><tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(0,0,0,0.08);">
<tr><td style="background:linear-gradient(135deg,#7f1d1d,#991b1b);padding:30px;text-align:center;color:#fff;">
<h2 style="margin:0;font-size:22px;">Acceso a Reglado Real Estate revocado</h2>
</td></tr>
<tr><td style="padding:30px;">
<p style="font-size:15px;line-height:1.7;">Te informamos que tu acceso a la plataforma de Reglado Real Estate ha sido <strong>revocado</strong> por un administrador.</p>
<p style="font-size:15px;line-height:1.7;">Si crees que se trata de un error o necesitas más información, por favor ponte en contacto con nosotros.</p>
</td></tr>
<tr><td style="padding:18px;text-align:center;font-size:12px;color:#9ca3af;border-top:1px solid #e5e7eb;">Reglado Real Estate</td></tr>
</table></td></tr></table></body></html>';

        try {
            sendNotificationEmail($targetUser['email'], 'Tu acceso a Reglado Real Estate ha sido revocado', $emailBody);
        } catch (Throwable $e) {
            error_log('[block_inmo_user] email falló: ' . $e->getMessage());
        }
    }

    auditLog($pdo, 'user.blocked_inmo', array_merge(
        auditContextFromAuth($auth),
        [
            'resource_type' => 'user',
            'resource_id'   => (string) $targetUserId,
            'metadata'      => ['target_email' => $targetUser['email'], 'notes' => $notes]
        ]
    ));

    respondJson(200, ['success' => true, 'message' => 'Usuario bloqueado correctamente en Inmobiliaria.']);

} catch (Throwable $e) {
    respondJson(500, ['success' => false, 'message' => 'Error al bloquear usuario: ' . $e->getMessage()]);
}
