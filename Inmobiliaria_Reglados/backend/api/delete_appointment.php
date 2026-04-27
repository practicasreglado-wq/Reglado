<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once __DIR__ . '/../config/cors.php';
require_once dirname(__DIR__) . '/lib/audit.php';
require_once dirname(__DIR__) . '/lib/error_reporting.php';
require_once dirname(__DIR__) . '/lib/admin_password_check.php';

applyCors();
handlePreflight();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondJson(405, ['success' => false, 'message' => 'Método no permitido.']);
}

$context = requireAuthenticatedUser($pdo);
$auth = $context['auth'] ?? [];
$role = strtolower((string) ($auth['role'] ?? ''));

if ($role !== 'admin') {
    respondJson(403, ['success' => false, 'message' => 'Acceso restringido. Solo administradores.']);
}

$input = json_decode(file_get_contents('php://input') ?: '{}', true);
$appointmentId = (int) ($input['appointment_id'] ?? 0);
$adminPassword = (string) ($input['admin_password'] ?? '');

if ($appointmentId <= 0) {
    respondJson(422, ['success' => false, 'message' => 'Identificador de cita inválido.']);
}

requireAdminPasswordConfirmation(
    (int) ($auth['sub'] ?? 0),
    $adminPassword,
    'admin_appointment_delete'
);

try {
    $stmt = $pdo->prepare('
        SELECT id, user_id, property_id, appointment_date, status
        FROM purchase_appointments
        WHERE id = :id
        LIMIT 1
    ');
    $stmt->execute(['id' => $appointmentId]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        respondJson(404, ['success' => false, 'message' => 'Cita no encontrada.']);
    }

    $pdo->beginTransaction();

    // Borra la solicitud de compra asociada (mismo comprador + propiedad).
    // No usamos JOIN con purchase_appointments porque no hay FK explícita.
    $deletedPurchases = 0;
    try {
        $purgePurchases = $pdo->prepare('
            DELETE FROM purchase_requests
            WHERE buyer_user_id = :uid
              AND property_id = :pid
        ');
        $purgePurchases->execute([
            'uid' => (int) $appointment['user_id'],
            'pid' => (int) $appointment['property_id'],
        ]);
        $deletedPurchases = (int) $purgePurchases->rowCount();
    } catch (Throwable $purgeException) {
        // Si la tabla no existe o hay un fallo, no bloqueamos el borrado
        // principal. Lo registramos y continuamos.
        error_log('[delete_appointment] No se pudo limpiar purchase_requests: ' . $purgeException->getMessage());
    }

    // Borra la cita en sí.
    $deleteStmt = $pdo->prepare('DELETE FROM purchase_appointments WHERE id = :id');
    $deleteStmt->execute(['id' => $appointmentId]);

    $pdo->commit();

    auditLog($pdo, 'appointment.delete', array_merge(
        auditContextFromAuth($auth),
        [
            'resource_type' => 'appointment',
            'resource_id'   => (string) $appointmentId,
            'metadata'      => [
                'user_id'            => (int) $appointment['user_id'],
                'property_id'        => (int) $appointment['property_id'],
                'appointment_date'   => $appointment['appointment_date'],
                'prev_status'        => $appointment['status'],
                'purchases_deleted'  => $deletedPurchases,
            ],
        ]
    ));

    respondJson(200, [
        'success' => true,
        'message' => 'Cita eliminada correctamente junto con sus registros asociados.',
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $errorId = logAndReferenceError('delete_appointment', $e);
    respondJson(500, [
        'success' => false,
        'message' => 'Error al eliminar la cita. Referencia: ' . $errorId,
    ]);
}
