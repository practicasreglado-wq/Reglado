<?php
declare(strict_types=1);

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
    respondJson(403, ['success' => false, 'message' => 'Acceso restringido. Solo administradores.']);
}

try {
    $hasTitulo = (bool) $pdo
        ->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA='inmobiliaria' AND TABLE_NAME='propiedades' AND COLUMN_NAME='titulo' LIMIT 1")
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
            p.precio           AS property_price,
            u.email            AS requester_email,
            u.username         AS requester_username,
            u.first_name       AS requester_first_name,
            u.last_name        AS requester_last_name,
            u.phone            AS requester_phone
        FROM property_deletion_requests pdr
        LEFT JOIN inmobiliaria.propiedades p ON p.id = pdr.property_id
        LEFT JOIN regladousers.users u       ON u.id = pdr.requester_user_id
        WHERE pdr.status = 'pending'
        ORDER BY pdr.created_at DESC, pdr.id DESC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
