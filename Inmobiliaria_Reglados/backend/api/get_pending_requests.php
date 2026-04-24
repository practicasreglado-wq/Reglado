<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once __DIR__ . '/../config/cors.php';
require_once dirname(__DIR__) . '/lib/audit.php';
require_once dirname(__DIR__) . '/lib/error_reporting.php';

applyCors();
handlePreflight();

$context = requireAuthenticatedUser($pdo);
$auth = $context['auth'] ?? [];
$role = strtolower((string) ($auth['role'] ?? ''));

if ($role !== 'admin') {
    respondJson(403, [
        'success' => false,
        'message' => 'Acceso restringido. Solo administradores.'
    ]);
}

try {
    $stmt = $pdo->prepare("
        SELECT id, user_id, user_email, first_name, last_name, username,
               message, status, created_at, resolved_at
        FROM role_promotion_requests
        WHERE status = 'pending'
        ORDER BY created_at DESC, id DESC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    auditLog($pdo, 'admin.list_pending_requests', array_merge(
        auditContextFromAuth($auth),
        ['metadata' => ['total' => count($rows)]]
    ));

    respondJson(200, [
        'success'  => true,
        'total'    => count($rows),
        'requests' => $rows,
    ]);
} catch (Throwable $e) {
    $errorId = logAndReferenceError('get_pending_requests', $e);
    respondJson(500, [
        'success' => false,
        'message' => 'Error al consultar las solicitudes. Referencia: ' . $errorId,
    ]);
}
