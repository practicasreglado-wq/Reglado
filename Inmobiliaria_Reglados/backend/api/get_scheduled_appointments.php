<?php
declare(strict_types=1);

/**
 * Lista de citas notariales programadas (purchase_appointments con
 * status='scheduled') para el panel admin. Vista calendario/agenda.
 *
 * Audit log: 'admin.list_appointments'. Solo role=admin.
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
            p.owner_user_id   AS owner_id
        FROM purchase_appointments a
        LEFT JOIN propiedades p ON p.id = a.property_id
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

    // JOIN cross-service de users (buyer + owner por cita) en una sola batch.
    $userIds = [];
    foreach ($rows as $row) {
        if (!empty($row['user_id']))  $userIds[] = (int) $row['user_id'];
        if (!empty($row['owner_id'])) $userIds[] = (int) $row['owner_id'];
    }
    $usersById = $userIds === [] ? [] : apilogingFindManyUsersIndexedById(array_unique($userIds));

    foreach ($rows as &$row) {
        $buyer = $usersById[(int) ($row['user_id'] ?? 0)]  ?? null;
        $owner = $usersById[(int) ($row['owner_id'] ?? 0)] ?? null;

        $row['buyer_email']      = $buyer['email'] ?? null;
        $row['buyer_username']   = $buyer['username'] ?? null;
        $row['buyer_first_name'] = $buyer['first_name'] ?? null;
        $row['buyer_last_name']  = $buyer['last_name'] ?? null;
        $row['buyer_phone']      = $buyer['phone'] ?? null;

        $row['owner_email']      = $owner['email'] ?? null;
        $row['owner_username']   = $owner['username'] ?? null;
        $row['owner_first_name'] = $owner['first_name'] ?? null;
        $row['owner_last_name']  = $owner['last_name'] ?? null;
        $row['owner_phone']      = $owner['phone'] ?? null;
    }
    unset($row);

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
