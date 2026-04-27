<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/env_loader.php';
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once __DIR__ . '/../config/cors.php';
require_once dirname(__DIR__) . '/lib/audit.php';
require_once dirname(__DIR__) . '/lib/error_reporting.php';

loadEnv(dirname(__DIR__) . '/.env');
applyCors();
handlePreflight();

$context = requireAuthenticatedUser($pdo);
$auth = $context['auth'] ?? [];
$role = strtolower((string) ($auth['role'] ?? ''));

if ($role !== 'admin') {
    respondJson(403, ['success' => false, 'message' => 'Acceso restringido. Solo administradores.']);
}

$mode = $_GET['mode'] ?? 'active';
$allowed = ['active', 'all'];
if (!in_array($mode, $allowed, true)) {
    $mode = 'active';
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

    $usersStmt = $pdoAuth->query("
        SELECT id, username, email, first_name, last_name, phone, role, created_at
        FROM users
        ORDER BY created_at DESC, id DESC
    ");
    $allUsers = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

    $propStmt = $pdo->query("
        SELECT user_id, COUNT(*) AS total FROM (
            SELECT owner_user_id AS user_id FROM propiedades WHERE owner_user_id IS NOT NULL
            UNION ALL
            SELECT created_by_user_id AS user_id FROM propiedades WHERE created_by_user_id IS NOT NULL
        ) t GROUP BY user_id
    ");
    $propsByUser = [];
    foreach ($propStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $propsByUser[(int) $row['user_id']] = (int) $row['total'];
    }

    $auditStmt = $pdo->query("
        SELECT user_id, COUNT(*) AS total, MAX(timestamp) AS last_action
        FROM audit_log
        WHERE user_id IS NOT NULL
        GROUP BY user_id
    ");
    $auditByUser = [];
    foreach ($auditStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $auditByUser[(int) $row['user_id']] = [
            'total' => (int) $row['total'],
            'last_action' => $row['last_action'],
        ];
    }

    $purchaseStmt = $pdo->query("
        SELECT buyer_user_id AS user_id, COUNT(*) AS total
        FROM purchase_requests
        GROUP BY buyer_user_id
    ");
    $purchasesByUser = [];
    foreach ($purchaseStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $purchasesByUser[(int) $row['user_id']] = (int) $row['total'];
    }

    $accessByUser = [];
    try {
        $accessStmt = $pdo->query("
            SELECT buyer_user_id AS user_id, COUNT(*) AS total
            FROM buyer_property_access
            GROUP BY buyer_user_id
        ");
        foreach ($accessStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $accessByUser[(int) $row['user_id']] = (int) $row['total'];
        }
    } catch (Throwable $e) {
        // Tabla puede no existir en todos los entornos
    }

    $result = [];
    foreach ($allUsers as $u) {
        $uid = (int) $u['id'];
        $properties = $propsByUser[$uid] ?? 0;
        $audits = $auditByUser[$uid]['total'] ?? 0;
        $purchases = $purchasesByUser[$uid] ?? 0;
        $accesses = $accessByUser[$uid] ?? 0;
        $isActive = ($properties > 0 || $audits > 0 || $purchases > 0 || $accesses > 0);

        if ($mode === 'active' && !$isActive) continue;

        $result[] = [
            'id' => $uid,
            'username' => $u['username'],
            'email' => $u['email'],
            'first_name' => $u['first_name'],
            'last_name' => $u['last_name'],
            'phone' => $u['phone'],
            'role' => $u['role'],
            'created_at' => $u['created_at'],
            'is_active_in_inmo' => $isActive,
            'activity' => [
                'properties' => $properties,
                'audit_events' => $audits,
                'purchase_requests' => $purchases,
                'document_accesses' => $accesses,
                'last_action' => $auditByUser[$uid]['last_action'] ?? null,
            ],
        ];
    }

    auditLog($pdo, 'admin.list_users', array_merge(
        auditContextFromAuth($auth),
        ['metadata' => ['mode' => $mode, 'total' => count($result)]]
    ));

    respondJson(200, [
        'success' => true,
        'mode' => $mode,
        'total' => count($result),
        'users' => $result,
    ]);

} catch (Throwable $e) {
    $errorId = logAndReferenceError('get_inmo_users', $e);
    respondJson(500, [
        'success' => false,
        'message' => 'Error al consultar usuarios. Referencia: ' . $errorId,
    ]);
}
