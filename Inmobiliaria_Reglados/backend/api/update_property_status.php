<?php

declare(strict_types=1);

/**
 * Endpoint para que un admin cambie el estado de una propiedad
 * ('activa', 'inactiva', 'vendida', 'pendiente'…).
 *
 * Cambiar el estado afecta a la visibilidad pública (solo 'activa' aparece
 * en get_properties.php) y al flujo de compra (no se puede solicitar compra
 * si no está activa).
 *
 * Requiere confirmación de contraseña del admin. Audit log:
 * 'property.status_change' con metadata del estado anterior y nuevo.
 */

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once __DIR__ . '/../config/cors.php';
require_once dirname(__DIR__) . '/lib/audit.php';
require_once dirname(__DIR__) . '/lib/admin_password_check.php';

applyCors();
handlePreflight();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondJson(405, [
        'success' => false,
        'message' => 'Método no permitido.'
    ]);
}

$context = requireAuthenticatedUser($pdo);
$auth = $context['auth'] ?? [];

$role = strtolower((string) ($auth['role'] ?? ''));
$isAdmin = ($role === 'admin');

if (!$isAdmin) {
    respondJson(403, [
        'success' => false,
        'message' => 'Acceso restringido. Solo administradores.'
    ]);
}

$input = json_decode(file_get_contents('php://input') ?: '{}', true);

if (!is_array($input)) {
    respondJson(400, [
        'success' => false,
        'message' => 'Solicitud no válida.'
    ]);
}

$propertyId = (int) ($input['property_id'] ?? 0);
$estado = strtolower(trim((string) ($input['estado'] ?? '')));
$adminPassword = (string) ($input['admin_password'] ?? '');

$allowedStatuses = ['disponible', 'vendido'];

if ($propertyId <= 0) {
    respondJson(400, [
        'success' => false,
        'message' => 'ID de propiedad no válido.'
    ]);
}

if (!in_array($estado, $allowedStatuses, true)) {
    respondJson(422, [
        'success' => false,
        'message' => 'Estado no válido.'
    ]);
}

requireAdminPasswordConfirmation(
    (int) ($auth['sub'] ?? 0),
    $adminPassword,
    'admin_property_status'
);

$stmt = $pdo->prepare("
    SELECT id
    FROM inmobiliaria.propiedades
    WHERE id = :id
    LIMIT 1
");
$stmt->execute([
    'id' => $propertyId
]);

$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    respondJson(404, [
        'success' => false,
        'message' => 'La propiedad no existe.'
    ]);
}

$update = $pdo->prepare("
    UPDATE inmobiliaria.propiedades
    SET estado = :estado,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = :id
    LIMIT 1
");
$update->execute([
    'estado' => $estado,
    'id' => $propertyId
]);

auditLog($pdo, 'property.status_change', array_merge(
    auditContextFromAuth($auth),
    [
        'resource_type' => 'property',
        'resource_id'   => (string) $propertyId,
        'metadata'      => ['nuevo_estado' => $estado]
    ]
));

respondJson(200, [
    'success' => true,
    'message' => 'Estado actualizado correctamente.',
    'data' => [
        'property_id' => $propertyId,
        'estado' => $estado
    ]
]);