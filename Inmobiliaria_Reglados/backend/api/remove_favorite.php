<?php
declare(strict_types=1);

/**
 * Quita una propiedad de los favoritos del usuario autenticado (delete en
 * `favorites`). Idempotente: no falla si no estaba marcada.
 */

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

applyCors();
handlePreflight();

$context = requireAuthenticatedUser($pdo);
$userId = (int) (
    $context['local']['id']
    ?? $context['local']['iduser']
    ?? $context['auth']['sub']
    ?? 0
);

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$propertyId = (int) ($payload['property_id'] ?? $payload['propiedad_id'] ?? 0);

if ($userId <= 0) {
    respondJson(401, [
        'success' => false,
        'message' => 'Usuario no autenticado.'
    ]);
}

if ($propertyId <= 0) {
    respondJson(422, [
        'success' => false,
        'message' => 'Propiedad no válida.'
    ]);
}

$stmt = $pdo->prepare('
    DELETE FROM propiedades_favoritas
    WHERE user_id = :user_id
      AND propiedad_id = :property_id
');

$stmt->execute([
    'user_id' => $userId,
    'property_id' => $propertyId,
]);

respondJson(200, [
    'success' => true,
    'message' => 'Propiedad eliminada de favoritos.',
    'is_favorite' => false,
    'property_id' => $propertyId,
]);