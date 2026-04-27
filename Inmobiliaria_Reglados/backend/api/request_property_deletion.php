<?php
declare(strict_types=1);

/**
 * Endpoint para que un usuario (no admin) pida la eliminación de su propiedad.
 *
 * Crea una fila en `property_deletion_requests` con status='pending' y
 * notifica a TODOS los admins (in-app + email) para que la revisen y la
 * aprueben o rechacen vía:
 *   - api/approve_property_deletion.php
 *   - api/reject_property_deletion.php
 *
 * El usuario debe ser dueño de la propiedad (owner_user_id == auth.sub) para
 * poder pedir su eliminación. Admins usan delete_property.php directamente.
 */

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once __DIR__ . '/../config/cors.php';
require_once dirname(__DIR__) . '/lib/audit.php';
require_once dirname(__DIR__) . '/lib/error_reporting.php';
require_once dirname(__DIR__) . '/lib/property_deletion_notify.php';

applyCors();
handlePreflight();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondJson(405, ['success' => false, 'message' => 'Método no permitido.']);
}

$context = requireAuthenticatedUser($pdo);
$auth = $context['auth'] ?? [];
$userId = (int) ($context['local']['iduser'] ?? $context['local']['id'] ?? $context['auth']['sub'] ?? 0);

if ($userId <= 0) {
    respondJson(401, ['success' => false, 'message' => 'Debes iniciar sesión.']);
}

$input = json_decode(file_get_contents('php://input') ?: '{}', true);
$propertyId = (int) ($input['property_id'] ?? 0);
$reason = trim((string) ($input['reason'] ?? ''));

if ($propertyId <= 0) {
    respondJson(422, ['success' => false, 'message' => 'Identificador de propiedad inválido.']);
}

if (mb_strlen($reason) > 1000) {
    $reason = mb_substr($reason, 0, 1000);
}

try {
    // Confirmar que la propiedad existe y es del usuario que solicita.
    // Usamos SELECT * por tolerancia a esquemas ligeramente distintos
    // entre instalaciones (p. ej. presencia o no de columna `titulo`).
    $propStmt = $pdo->prepare('SELECT * FROM propiedades WHERE id = :id LIMIT 1');
    $propStmt->execute(['id' => $propertyId]);
    $property = $propStmt->fetch(PDO::FETCH_ASSOC);

    if (!$property) {
        respondJson(404, ['success' => false, 'message' => 'Propiedad no encontrada.']);
    }

    $ownerId = (int) ($property['owner_user_id'] ?? 0);
    if ($ownerId !== $userId) {
        respondJson(403, ['success' => false, 'message' => 'Solo el propietario puede solicitar la eliminación.']);
    }

    // Impedir duplicados: ya hay una solicitud pendiente para esta propiedad.
    $dupStmt = $pdo->prepare('
        SELECT id FROM property_deletion_requests
        WHERE property_id = :pid AND status = "pending"
        LIMIT 1
    ');
    $dupStmt->execute(['pid' => $propertyId]);
    if ($dupStmt->fetchColumn()) {
        respondJson(409, [
            'success' => false,
            'message' => 'Ya hay una solicitud de eliminación pendiente para esta propiedad.',
        ]);
    }

    $insertStmt = $pdo->prepare('
        INSERT INTO property_deletion_requests (property_id, requester_user_id, reason, status)
        VALUES (:pid, :uid, :reason, "pending")
    ');
    $insertStmt->execute([
        'pid' => $propertyId,
        'uid' => $userId,
        'reason' => $reason !== '' ? $reason : null,
    ]);
    $requestId = (int) $pdo->lastInsertId();

    $propertyTitle = trim((string) (
        ($property['titulo'] ?? '') !== ''
            ? $property['titulo']
            : ($property['tipo_propiedad'] ?? '')
    ));
    $requester = fetchUserDisplayById($userId);
    $notified = notifyAdminsOfDeletionRequest(
        $pdo,
        $requestId,
        $propertyId,
        $propertyTitle,
        (string) ($requester['name'] ?? ''),
        $reason !== '' ? $reason : null
    );

    auditLog($pdo, 'property.deletion_requested', array_merge(
        auditContextFromAuth($auth, $userId),
        [
            'resource_type' => 'property_deletion_request',
            'resource_id'   => (string) $requestId,
            'metadata'      => [
                'property_id' => $propertyId,
                'reason'      => $reason !== '' ? $reason : null,
                'admins_notified' => $notified,
            ],
        ]
    ));

    respondJson(200, [
        'success'    => true,
        'request_id' => $requestId,
        'message'    => 'Solicitud enviada. Un administrador la revisará en breve.',
    ]);
} catch (Throwable $e) {
    $errorId = logAndReferenceError('request_property_deletion', $e);
    respondJson(500, [
        'success' => false,
        'message' => 'No se pudo enviar la solicitud. Referencia: ' . $errorId,
    ]);
}
