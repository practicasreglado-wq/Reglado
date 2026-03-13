<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';

applyAuthCors();
handlePreflight();

$context = requireAuthenticatedUser($pdo);
$local = $context['local'];

$payload = json_decode(file_get_contents('php://input'), true);

$propertyId = (int) ($payload['property_id'] ?? 0);
$categoria = $payload['categoria'] ?? null;
$preferences = $payload['preferences'] ?? null;

if ($propertyId <= 0) {
    respondJson(422, ['success' => false, 'message' => 'Propiedad no válida.']);
}

$existsStmt = $pdo->prepare('SELECT id FROM propiedades WHERE id = :id LIMIT 1');
$existsStmt->execute(['id' => $propertyId]);

if (!$existsStmt->fetch()) {
    respondJson(404, ['success' => false, 'message' => 'La propiedad no existe.']);
}

$stmt = $pdo->prepare('
    INSERT INTO propiedades_favoritas (user_id, property_id, categoria, preferencias)
    VALUES (:user_id, :property_id, :categoria, :preferencias)
    ON DUPLICATE KEY UPDATE 
        categoria = VALUES(categoria),
        preferencias = VALUES(preferencias),
        created_at = CURRENT_TIMESTAMP
');

$stmt->execute([
    'user_id' => (int) $local['iduser'],
    'property_id' => $propertyId,
    'categoria' => $categoria,
    'preferencias' => $preferences ? json_encode($preferences) : null,
]);

respondJson(200, [
    'success' => true,
    'message' => 'Propiedad guardada en favoritos.',
]);
