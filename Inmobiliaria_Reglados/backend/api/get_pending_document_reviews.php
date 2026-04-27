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

try {
    $stmt = $pdo->prepare("
        SELECT
            t.id,
            t.property_id,
            t.buyer_user_id,
            t.reviewer_email,
            t.expires_at,
            t.created_at,
            p.tipo_propiedad  AS property_title,
            p.categoria       AS property_category,
            p.ciudad          AS property_city,
            p.zona            AS property_zone,
            u.email           AS buyer_email,
            u.username        AS buyer_username,
            u.first_name      AS buyer_first_name,
            u.last_name       AS buyer_last_name,
            u.phone           AS buyer_phone
        FROM signed_document_review_tokens t
        LEFT JOIN inmobiliaria.propiedades p ON p.id = t.property_id
        LEFT JOIN regladousers.users u       ON u.id = t.buyer_user_id
        WHERE t.approved_at IS NULL
          AND t.expires_at > NOW()
        ORDER BY t.created_at DESC, t.id DESC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    auditLog($pdo, 'admin.list_pending_document_reviews', array_merge(
        auditContextFromAuth($auth),
        ['metadata' => ['total' => count($rows)]]
    ));

    respondJson(200, [
        'success'  => true,
        'total'    => count($rows),
        'reviews'  => $rows,
    ]);
} catch (Throwable $e) {
    respondJson(500, [
        'success' => false,
        'message' => 'Error al consultar las revisiones pendientes: ' . $e->getMessage()
    ]);
}
