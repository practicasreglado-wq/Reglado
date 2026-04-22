<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/env_loader.php';
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once __DIR__ . '/../config/cors.php';
require_once dirname(__DIR__) . '/lib/audit.php';

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

if ($targetUserId <= 0) {
    respondJson(422, ['success' => false, 'message' => 'ID de usuario no válido.']);
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO user_inmo_status (user_id, last_token_invalidated_at, updated_by)
        VALUES (:uid, NOW(), :updater)
        ON DUPLICATE KEY UPDATE
            last_token_invalidated_at = NOW(),
            updated_by = :updater
    ");
    $stmt->execute([
        'uid' => $targetUserId,
        'updater' => (int) ($auth['sub'] ?? 0),
    ]);

    auditLog($pdo, 'user.force_relogin', array_merge(
        auditContextFromAuth($auth),
        [
            'resource_type' => 'user',
            'resource_id'   => (string) $targetUserId,
        ]
    ));

    respondJson(200, ['success' => true, 'message' => 'Sesión del usuario invalidada. Deberá volver a iniciar sesión.']);

} catch (Throwable $e) {
    respondJson(500, ['success' => false, 'message' => 'Error al invalidar sesión: ' . $e->getMessage()]);
}
