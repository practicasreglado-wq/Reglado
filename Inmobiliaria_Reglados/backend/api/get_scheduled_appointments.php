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
    respondJson(403, ['success' => false, 'message' => 'Acceso restringido. Solo administradores.']);
}

$statusFilter = (string) ($_GET['status'] ?? 'scheduled');
$allowed = ['scheduled', 'completed', 'cancelled', 'all'];
if (!in_array($statusFilter, $allowed, true)) {
    $statusFilter = 'scheduled';
}

try {
    $sql = "
        SELECT
            a.id,
            a.user_id,
            a.property_id,
            a.appointment_date,
            a.notary_name,
            a.notary_address,
            a.notary_city,
            a.notary_phone,
            a.notes,
            a.status,
            a.admin_notes,
            a.completed_at,
            a.cancelled_at,
            a.created_at,
            p.tipo_propiedad  AS property_title,
            p.ciudad          AS property_city,
            p.zona            AS property_zone,
            p.precio          AS property_price,
            p.owner_user_id   AS owner_id,
            u.email           AS buyer_email,
            u.username        AS buyer_username,
            u.first_name      AS buyer_first_name,
            u.last_name       AS buyer_last_name,
            u.phone           AS buyer_phone,
            owner.email       AS owner_email,
            owner.username    AS owner_username,
            owner.first_name  AS owner_first_name,
            owner.last_name   AS owner_last_name,
            owner.phone       AS owner_phone
        FROM purchase_appointments a
        LEFT JOIN inmobiliaria.propiedades p ON p.id = a.property_id
        LEFT JOIN regladousers.users u       ON u.id = a.user_id
        LEFT JOIN regladousers.users owner   ON owner.id = p.owner_user_id
    ";

    if ($statusFilter !== 'all') {
        $sql .= ' WHERE a.status = :status ';
        $sql .= ' ORDER BY a.appointment_date ASC ';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['status' => $statusFilter]);
    } else {
        $sql .= ' ORDER BY a.appointment_date ASC ';
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    auditLog($pdo, 'admin.list_appointments', array_merge(
        auditContextFromAuth($auth),
        ['metadata' => ['status' => $statusFilter, 'total' => count($rows)]]
    ));

    respondJson(200, [
        'success'      => true,
        'total'        => count($rows),
        'status'       => $statusFilter,
        'appointments' => $rows,
    ]);
} catch (Throwable $e) {
    $errorId = logAndReferenceError('get_scheduled_appointments', $e);
    respondJson(500, [
        'success' => false,
        'message' => 'Error al consultar las citas. Referencia: ' . $errorId,
    ]);
}
