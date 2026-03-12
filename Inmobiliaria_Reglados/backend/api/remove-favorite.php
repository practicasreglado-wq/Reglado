<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';

applyAuthCors();
handlePreflight();

$context = requireAuthenticatedUser($pdo);
$local = $context['local'];
$payload = json_decode(file_get_contents('php://input'), true);

$propertyId = (int) ($payload['propiedad_id'] ?? 0);

if ($propertyId <= 0) {
    respondJson(422, ['success' => false, 'message' => 'Propiedad no válida.']);
}

$stmt = $pdo->prepare('
    DELETE FROM propiedades_favoritas
    WHERE user_id = :user_id AND propiedad_id = :propiedad_id
');
$stmt->execute([
    'user_id' => (int) $local['iduser'],
    'propiedad_id' => $propertyId,
]);

respondJson(200, [
    'success' => true,
    'message' => 'Propiedad eliminada de favoritos.',
]);
