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

$existsStmt = $pdo->prepare('SELECT id FROM propiedades WHERE id = :id LIMIT 1');
$existsStmt->execute(['id' => $propertyId]);

if (!$existsStmt->fetch()) {
    respondJson(404, ['success' => false, 'message' => 'La propiedad no existe.']);
}

$stmt = $pdo->prepare('
    INSERT INTO propiedades_favoritas (user_id, propiedad_id)
    VALUES (:user_id, :propiedad_id)
    ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP
');
$stmt->execute([
    'user_id' => (int) $local['iduser'],
    'propiedad_id' => $propertyId,
]);

respondJson(200, [
    'success' => true,
    'message' => 'Propiedad guardada en favoritos.',
]);
