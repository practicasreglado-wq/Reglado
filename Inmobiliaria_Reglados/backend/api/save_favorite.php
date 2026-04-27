<?php
declare(strict_types=1);

/**
 * Marca una propiedad como favorita del usuario autenticado (insert en
 * `favorites` si no existía ya). Idempotente.
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
        'message' => 'Propiedad inválida.'
    ]);
}

$existsStmt = $pdo->prepare('SELECT id FROM propiedades WHERE id = :id LIMIT 1');
$existsStmt->execute(['id' => $propertyId]);

if (!$existsStmt->fetch()) {
    respondJson(404, [
        'success' => false,
        'message' => 'La propiedad no existe.'
    ]);
}

$stmt = $pdo->prepare('
    INSERT INTO propiedades_favoritas (user_id, propiedad_id)
    VALUES (:user_id, :property_id)
    ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP
');

$stmt->execute([
    'user_id' => $userId,
    'property_id' => $propertyId,
]);

respondJson(200, [
    'success' => true,
    'message' => 'Propiedad guardada en favoritos.',
    'is_favorite' => true,
    'property_id' => $propertyId,
]);