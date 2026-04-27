<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once __DIR__ . '/../config/cors.php';
require_once dirname(__DIR__) . '/lib/audit.php';

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
$requestId = (int) ($input['request_id'] ?? 0);
$newStatus = strtolower(trim((string) ($input['status'] ?? '')));
$notes = trim((string) ($input['notes'] ?? ''));

$allowed = ['pending', 'contacted', 'closed'];

if ($requestId <= 0) {
    respondJson(422, ['success' => false, 'message' => 'ID de solicitud no válido.']);
}

if (!in_array($newStatus, $allowed, true)) {
    respondJson(422, ['success' => false, 'message' => 'Estado no válido.']);
}

try {
    $stmt = $pdo->prepare("
        UPDATE purchase_requests
        SET status = :status,
            notes = :notes,
            resolved_at = CASE WHEN :status_check = 'pending' THEN NULL ELSE NOW() END
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([
        'status'       => $newStatus,
        'status_check' => $newStatus,
        'notes'        => $notes !== '' ? $notes : null,
        'id'           => $requestId,
    ]);

    if ($stmt->rowCount() === 0) {
        respondJson(404, ['success' => false, 'message' => 'Solicitud no encontrada.']);
    }

    auditLog($pdo, 'purchase_request.status_change', array_merge(
        auditContextFromAuth($auth),
        [
            'resource_type' => 'purchase_request',
            'resource_id'   => (string) $requestId,
            'metadata'      => ['nuevo_estado' => $newStatus]
        ]
    ));

    respondJson(200, [
        'success' => true,
        'message' => 'Estado actualizado correctamente.',
    ]);
} catch (Throwable $e) {
    respondJson(500, [
        'success' => false,
        'message' => 'Error al actualizar la solicitud: ' . $e->getMessage()
    ]);
}
