<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/session.php';

applyCors();
handlePreflight();

$userId = 0;

try {
    $context = requireAuthenticatedUser($pdo);
    $userId = (int) (
        $context['local']['id']
        ?? $context['local']['iduser']
        ?? $context['user']['id']
        ?? $context['user']['iduser']
        ?? 0
    );
} catch (\Throwable $error) {
    $userId = (int) (
        $_SESSION['user']['id']
        ?? $_SESSION['user']['iduser']
        ?? $_SESSION['id']
        ?? $_SESSION['iduser']
        ?? 0
    );
}

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