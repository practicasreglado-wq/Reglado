<?php
declare(strict_types=1);

/**
 * Lista de solicitudes de eliminación de propiedad con status='pending'.
 * Lo consume el panel admin para mostrar las que necesitan resolución vía
 * approve_property_deletion.php / reject_property_deletion.php.
 *
 * Solo accesible para role=admin.
 */

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once __DIR__ . '/../config/cors.php';
require_once dirname(__DIR__) . '/lib/error_reporting.php';
require_once dirname(__DIR__) . '/lib/apiloging_client.php';

applyCors();
handlePreflight();

$context = requireAuthenticatedUser($pdo);
$auth = $context['auth'] ?? [];
$role = strtolower((string) ($auth['role'] ?? ''));

if ($role !== 'admin') {
    respondJson(403, ['success' => false, 'message' => 'Acceso restringido. Solo administradores.']);
}

try {
    $hasTitulo = (bool) $pdo
        ->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='propiedades' AND COLUMN_NAME='titulo' LIMIT 1")
        ->fetchColumn();

    $titleExpr = $hasTitulo ? 'COALESCE(NULLIF(p.titulo, ""), p.tipo_propiedad)' : 'p.tipo_propiedad';

    $stmt = $pdo->prepare("
        SELECT
            pdr.id,
            pdr.property_id,
            pdr.requester_user_id,
            pdr.reason,
            pdr.status,
            pdr.created_at,
            {$titleExpr}       AS property_title,
            p.tipo_propiedad   AS property_type,
            p.categoria        AS property_category,
            p.ciudad           AS property_city,
            p.zona             AS property_zone,
            p.precio           AS property_price
        FROM property_deletion_requests pdr
        LEFT JOIN propiedades p ON p.id = pdr.property_id
        WHERE pdr.status = 'pending'
        ORDER BY pdr.created_at DESC, pdr.id DESC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Resolución batch de los requesters (users) vía ApiLogin.
    $requesterIds = array_values(array_unique(array_filter(array_map(
        static fn(array $r): int => (int) ($r['requester_user_id'] ?? 0),
        $rows
    ))));
    $usersById = $requesterIds === [] ? [] : apilogingFindManyUsersIndexedById($requesterIds);

    foreach ($rows as &$row) {
        $u = $usersById[(int) ($row['requester_user_id'] ?? 0)] ?? null;
        $row['requester_email']      = $u['email'] ?? null;
        $row['requester_username']   = $u['username'] ?? null;
        $row['requester_first_name'] = $u['first_name'] ?? null;
        $row['requester_last_name']  = $u['last_name'] ?? null;
        $row['requester_phone']      = $u['phone'] ?? null;
    }
    unset($row);

    respondJson(200, [
        'success'  => true,
        'total'    => count($rows),
        'requests' => $rows,
    ]);
} catch (Throwable $e) {
    $errorId = logAndReferenceError('get_pending_property_deletions', $e);
    respondJson(500, [
        'success' => false,
        'message' => 'Error al cargar las solicitudes. Referencia: ' . $errorId,
    ]);
}
