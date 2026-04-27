<?php
declare(strict_types=1);

/**
 * Endpoint para que un admin apruebe una solicitud pendiente de eliminación
 * de propiedad (creada por el usuario en request_property_deletion.php).
 *
 * Pasos:
 *  1) Validación de admin + confirmación de su contraseña
 *     (lib/admin_password_check.php) para evitar borrados accidentales.
 *  2) Marca la fila de property_deletion_requests como 'approved'.
 *  3) Ejecuta el borrado completo de la propiedad y todas sus dependencias
 *     (lib/property_delete_ops.php → executePropertyDeletion).
 *  4) Notifica al solicitante del resultado (lib/property_deletion_notify.php
 *     → notifyRequesterOfDeletionResolution).
 *
 * Devuelve JSON al frontend del admin.
 */

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once __DIR__ . '/../config/cors.php';
require_once dirname(__DIR__) . '/lib/audit.php';
require_once dirname(__DIR__) . '/lib/error_reporting.php';
require_once dirname(__DIR__) . '/lib/admin_password_check.php';
require_once dirname(__DIR__) . '/lib/property_delete_ops.php';
require_once dirname(__DIR__) . '/lib/property_deletion_notify.php';

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
$adminPassword = (string) ($input['admin_password'] ?? '');
$adminNotes = trim((string) ($input['admin_notes'] ?? ''));

if ($requestId <= 0) {
    respondJson(422, ['success' => false, 'message' => 'Identificador de solicitud inválido.']);
}

if (mb_strlen($adminNotes) > 1000) {
    $adminNotes = mb_substr($adminNotes, 0, 1000);
}

requireAdminPasswordConfirmation(
    (int) ($auth['sub'] ?? 0),
    $adminPassword,
    'admin_property_deletion_approve'
);

try {
    $hasTitulo = (bool) $pdo
        ->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA='inmobiliaria' AND TABLE_NAME='propiedades' AND COLUMN_NAME='titulo' LIMIT 1")
        ->fetchColumn();
    $titleExpr = $hasTitulo ? 'COALESCE(NULLIF(p.titulo, ""), p.tipo_propiedad)' : 'p.tipo_propiedad';

    $stmt = $pdo->prepare("
        SELECT pdr.id, pdr.property_id, pdr.requester_user_id, pdr.status,
               {$titleExpr} AS property_title, p.tipo_propiedad AS property_type
        FROM property_deletion_requests pdr
        LEFT JOIN inmobiliaria.propiedades p ON p.id = pdr.property_id
        WHERE pdr.id = :id LIMIT 1
    ");
    $stmt->execute(['id' => $requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        respondJson(404, ['success' => false, 'message' => 'Solicitud no encontrada.']);
    }

    if ($request['status'] !== 'pending') {
        respondJson(409, [
            'success' => false,
            'message' => 'Esta solicitud ya está ' . $request['status'] . ' y no puede volver a procesarse.',
        ]);
    }

    $propertyId = (int) $request['property_id'];
    $requesterUserId = (int) $request['requester_user_id'];
    $propertyTitle = trim((string) ($request['property_title'] ?? $request['property_type'] ?? ''));

    // Ejecuta el borrado completo (propiedad + relacionados + ficheros).
    executePropertyDeletion($pdo, $propertyId);

    // Marca la solicitud como aprobada (y las que hubiera para la misma
    // propiedad, por si el usuario envió duplicadas antes de las guardias).
    $updateStmt = $pdo->prepare('
        UPDATE property_deletion_requests
        SET status = "approved",
            admin_notes = :notes,
            resolved_by_user_id = :by,
            resolved_at = NOW()
        WHERE id = :id
    ');
    $updateStmt->execute([
        'id' => $requestId,
        'by' => (int) ($auth['sub'] ?? 0),
        'notes' => $adminNotes !== '' ? $adminNotes : null,
    ]);

    notifyRequesterOfDeletionResolution(
        $pdo,
        $requesterUserId,
        $propertyId,
        $propertyTitle,
        'approved',
        $adminNotes !== '' ? $adminNotes : null
    );

    auditLog($pdo, 'property.deletion_approved', array_merge(
        auditContextFromAuth($auth),
        [
            'resource_type' => 'property_deletion_request',
            'resource_id'   => (string) $requestId,
            'metadata'      => [
                'property_id' => $propertyId,
                'requester_user_id' => $requesterUserId,
                'admin_notes' => $adminNotes !== '' ? $adminNotes : null,
            ],
        ]
    ));

    respondJson(200, [
        'success' => true,
        'message' => 'Solicitud aprobada. Propiedad eliminada y usuario notificado.',
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $errorId = logAndReferenceError('approve_property_deletion', $e);
    respondJson(500, [
        'success' => false,
        'message' => 'Error al aprobar la solicitud. Referencia: ' . $errorId,
    ]);
}
