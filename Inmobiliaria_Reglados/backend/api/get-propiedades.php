<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/lib/utils.php';

applyAuthCors();
handlePreflight();

$context = requireAuthenticatedUser($pdo);
$local = $context['local'];

$selectedCategory = trim((string) ($_GET['categoria'] ?? ($local['categoria'] ?? '')));
$preferences = decodeJsonArray($local['preferencias'] ?? null);

if ($selectedCategory === '') {
    respondJson(200, [
        'success' => true,
        'properties' => [],
        'category' => null,
    ]);
}

$favoriteStmt = $pdo->prepare('
    SELECT property_id
    FROM propiedades_favoritas
    WHERE user_id = :user_id
');
$favoriteStmt->execute([
    'user_id' => (int) $local['iduser'],
]);
$favoriteIds = array_map('intval', array_column($favoriteStmt->fetchAll(PDO::FETCH_ASSOC), 'property_id'));
$favoriteLookup = array_fill_keys($favoriteIds, true);

$stmt = $pdo->prepare('
    SELECT id, categoria, titulo, ubicacion_general, precio, metros_cuadrados, imagen_principal, caracteristicas_json, created_at
    FROM propiedades
    WHERE categoria = :categoria
    ORDER BY created_at DESC, id DESC
');
$stmt->execute([
    'categoria' => $selectedCategory,
]);

$properties = [];

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $characteristics = decodeJsonArray($row['caracteristicas_json'] ?? null);
    
    $properties[] = [
        'id' => (int) $row['id'],
        'categoria' => $row['categoria'],
        'titulo' => $row['titulo'],
        'ubicacion_general' => $row['ubicacion_general'],
        'precio' => (float) $row['precio'],
        'metros_cuadrados' => (int) $row['metros_cuadrados'],
        'imagen_principal' => $row['imagen_principal'],
        'image_url' => propertyImageUrl($row['imagen_principal'] ?? null),
        'caracteristicas' => $characteristics,

        // Valores estáticos para la UI (Matching eliminado)
        'match_percentage' => 100,
        'match_count' => 0,
        'match_total' => 0,
        'match_details' => [],

        'is_favorite' => isset($row['fav_id']) && $row['fav_id'] !== null,
        'created_at' => $row['created_at'],
    ];
}

respondJson(200, [
    'success' => true,
    'category' => $selectedCategory,
    'properties' => $properties,
]);
