<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/lib/property_matching.php';

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
    $match = calculatePropertyMatch($preferences, $characteristics);
    error_log("MATCH: " . json_encode($match));
    error_log("PREF: " . json_encode($preferences));
    error_log("CHAR: " . json_encode($characteristics));

    if ($match['percentage'] < 50) {
        continue;
    }

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
        'match_percentage' => $match['percentage'],
        'match_count' => $match['matches'],
        'match_total' => $match['total'],
        'is_favorite' => isset($favoriteLookup[(int) $row['id']]),
        'created_at' => $row['created_at'],
    ];
}

respondJson(200, [
    'success' => true,
    'category' => $selectedCategory,
    'properties' => $properties,
]);
