<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once __DIR__ . '/../config/cors.php';
require_once dirname(__DIR__) . '/lib/audit.php';

applyCors();
handlePreflight();

$context = requireAuthenticatedUser($pdo);
$auth = $context['auth'] ?? [];
$role = strtolower((string) ($auth['role'] ?? ''));

if ($role !== 'admin') {
    respondJson(403, ['success' => false, 'message' => 'Acceso restringido. Solo administradores.']);
}

$onlyPending = isset($_GET['only_pending']) && $_GET['only_pending'] === '1';

try {
    $sql = "
        SELECT
            pr.id, pr.buyer_user_id, pr.buyer_email, pr.buyer_name, pr.buyer_phone,
            pr.property_id, pr.property_title, pr.status, pr.notes,
            pr.created_at, pr.resolved_at,
            p.ciudad   AS property_city,
            p.zona     AS property_zone,
            p.precio   AS property_price
        FROM purchase_requests pr
        LEFT JOIN inmobiliaria.propiedades p ON p.id = pr.property_id
    ";
    if ($onlyPending) {
        $sql .= " WHERE pr.status = 'pending' ";
    }
    $sql .= " ORDER BY pr.created_at DESC, pr.id DESC ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    auditLog($pdo, 'admin.list_purchase_requests', array_merge(
        auditContextFromAuth($auth),
        ['metadata' => ['total' => count($rows), 'only_pending' => $onlyPending]]
    ));

    respondJson(200, [
        'success'  => true,
        'total'    => count($rows),
        'requests' => $rows,
    ]);
} catch (Throwable $e) {
    respondJson(500, [
        'success' => false,
        'message' => 'Error al consultar las solicitudes de compra: ' . $e->getMessage()
    ]);
}
