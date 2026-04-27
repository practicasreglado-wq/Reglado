<?php
declare(strict_types=1);

/**
 * Endpoint que devuelve entradas de la tabla `audit_log` para el panel de
 * Registro de Auditoría del admin (src/views/AdminAuditView.vue).
 *
 * Solo accesible para role=admin. Soporta filtros por:
 *  - action (búsqueda por código, ej. 'document.signed.approve')
 *  - user_email
 *  - date_from / date_to
 * Y paginación (page, per_page).
 *
 * Los códigos de `action` que devuelve los traduce a español el frontend
 * con el mapping ACTION_LABELS de AdminAuditView.vue. Si añades una acción
 * nueva en lib/audit.php, recuerda mapearla allí.
 */

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once __DIR__ . '/../config/cors.php';
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

$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = max(10, min(200, (int) ($_GET['per_page'] ?? 50)));
$offset  = ($page - 1) * $perPage;

$filters = [];
$where   = [];

$action = trim((string) ($_GET['action'] ?? ''));
if ($action !== '') {
    $where[] = 'action LIKE :action';
    $filters['action'] = '%' . $action . '%';
}

$userEmail = trim((string) ($_GET['user_email'] ?? ''));
if ($userEmail !== '') {
    $where[] = 'user_email LIKE :user_email';
    $filters['user_email'] = '%' . $userEmail . '%';
}

$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
if ($dateFrom !== '') {
    $where[] = 'timestamp >= :date_from';
    $filters['date_from'] = $dateFrom . ' 00:00:00';
}

$dateTo = trim((string) ($_GET['date_to'] ?? ''));
if ($dateTo !== '') {
    $where[] = 'timestamp <= :date_to';
    $filters['date_to'] = $dateTo . ' 23:59:59';
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM audit_log {$whereSql}");
    $countStmt->execute($filters);
    $total = (int) $countStmt->fetchColumn();

    $sql = "
        SELECT id, timestamp, user_id, user_email, user_role, action,
               resource_type, resource_id, ip_address, user_agent, success, metadata
        FROM audit_log
        {$whereSql}
        ORDER BY timestamp DESC, id DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);
    foreach ($filters as $k => $v) {
        $stmt->bindValue(':' . $k, $v);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        if (!empty($row['metadata'])) {
            $decoded = json_decode((string) $row['metadata'], true);
            $row['metadata'] = is_array($decoded) ? $decoded : null;
        } else {
            $row['metadata'] = null;
        }
    }
    unset($row);

    respondJson(200, [
        'success'  => true,
        'page'     => $page,
        'per_page' => $perPage,
        'total'    => $total,
        'pages'    => (int) ceil($total / $perPage),
        'entries'  => $rows,
    ]);
} catch (Throwable $e) {
    $errorId = logAndReferenceError('get_audit_log', $e);
    respondJson(500, [
        'success' => false,
        'message' => 'Error al consultar el log de auditoría. Referencia: ' . $errorId,
    ]);
}
