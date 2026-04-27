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
$newStatus = strtolower(trim((string) ($input['status'] ?? '')));
$adminNotes = trim((string) ($input['admin_notes'] ?? ''));
$adminPassword = (string) ($input['admin_password'] ?? '');

$allowed = ['completed', 'cancelled'];
if ($appointmentId <= 0 || !in_array($newStatus, $allowed, true)) {
    respondJson(422, ['success' => false, 'message' => 'Datos inválidos. Solo se permite completar o cancelar.']);
}

if (mb_strlen($adminNotes) > 1000) {
    $adminNotes = mb_substr($adminNotes, 0, 1000);
}

requireAdminPasswordConfirmation(
    (int) ($auth['sub'] ?? 0),
    $adminPassword,
    'admin_appointment_action'
);

try {
    $stmt = $pdo->prepare('SELECT id, status FROM purchase_appointments WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $appointmentId]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        respondJson(404, ['success' => false, 'message' => 'Cita no encontrada.']);
    }

    if ($appointment['status'] !== 'scheduled') {
        respondJson(409, [
            'success' => false,
            'message' => 'Esta cita ya está ' . $appointment['status'] . ' y no se puede modificar.',
        ]);
    }

    $timestampCol = $newStatus === 'completed' ? 'completed_at' : 'cancelled_at';

    $updateStmt = $pdo->prepare("
        UPDATE purchase_appointments
        SET status = :status,
            admin_notes = :admin_notes,
            {$timestampCol} = NOW()
        WHERE id = :id
    ");
    $updateStmt->execute([
        'status'      => $newStatus,
        'admin_notes' => $adminNotes !== '' ? $adminNotes : null,
        'id'          => $appointmentId,
    ]);

    auditLog($pdo, 'appointment.' . $newStatus, array_merge(
        auditContextFromAuth($auth),
        [
            'resource_type' => 'appointment',
            'resource_id'   => (string) $appointmentId,
            'metadata'      => ['admin_notes' => $adminNotes !== '' ? $adminNotes : null],
        ]
    ));

    respondJson(200, [
        'success' => true,
        'message' => $newStatus === 'completed'
            ? 'Cita marcada como completada.'
            : 'Cita cancelada.',
    ]);
} catch (Throwable $e) {
    $errorId = logAndReferenceError('update_appointment_status', $e);
    respondJson(500, [
        'success' => false,
        'message' => 'Error al actualizar la cita. Referencia: ' . $errorId,
    ]);
}
