<?php
declare(strict_types=1);

/**
 * Lista de documentos firmados pendientes de revisión por el admin.
 * Lo consume el panel admin → "Documentos por aprobar".
 *
 * Solo accesible para role=admin. Audit:
 * 'admin.list_pending_document_reviews'.
 */

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once __DIR__ . '/../config/cors.php';
require_once dirname(__DIR__) . '/lib/audit.php';
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
    // Datos locales (tokens + propiedad + documentos firmados). El JOIN con
    // users sale por separado vía ApiLogin para no depender de cross-DB.
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
            df.nda_file_path  AS nda_file_path,
            df.loi_file_path  AS loi_file_path
        FROM signed_document_review_tokens t
        LEFT JOIN propiedades p  ON p.id = t.property_id
        LEFT JOIN documentos_firmados df
               ON df.propiedad_id = t.property_id
              AND df.user_id = t.buyer_user_id
        WHERE t.approved_at IS NULL
          AND t.expires_at > NOW()
        ORDER BY t.created_at DESC, t.id DESC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Resolución batch de buyers vía ApiLogin.
    $buyerIds = array_values(array_unique(array_filter(array_map(
        static fn(array $r): int => (int) ($r['buyer_user_id'] ?? 0),
        $rows
    ))));
    $buyersById = $buyerIds === [] ? [] : apilogingFindManyUsersIndexedById($buyerIds);

    foreach ($rows as &$row) {
        $buyer = $buyersById[(int) ($row['buyer_user_id'] ?? 0)] ?? null;
        $row['buyer_email']      = $buyer['email'] ?? null;
        $row['buyer_username']   = $buyer['username'] ?? null;
        $row['buyer_first_name'] = $buyer['first_name'] ?? null;
        $row['buyer_last_name']  = $buyer['last_name'] ?? null;
        $row['buyer_phone']      = $buyer['phone'] ?? null;
    }
    unset($row);

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
    $errorId = logAndReferenceError('get_pending_document_reviews', $e);
    respondJson(500, [
        'success' => false,
        'message' => 'Error al consultar las revisiones pendientes. Referencia: ' . $errorId,
    ]);
}
