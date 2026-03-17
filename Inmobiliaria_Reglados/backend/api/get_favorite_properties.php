<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/lib/property_matching.php';

applyAuthCors();
handlePreflight();

$context = requireAuthenticatedUser($pdo);
$local = $context['local'];

$stmt = $pdo->prepare('
    SELECT 
        p.id,
        p.categoria,
        p.titulo,
        p.ubicacion_general,
        p.precio,
        p.metros_cuadrados,
        p.imagen_principal,
        p.caracteristicas_json,
        p.created_at,
        pf.created_at as favorited_at,
        pf.preferencias
    FROM propiedades_favoritas pf
    INNER JOIN propiedades p ON p.id = pf.property_id
    WHERE pf.user_id = :user_id
    ORDER BY pf.created_at DESC
');

$stmt->execute([
    'user_id' => (int) $local['iduser'],
]);

$favorites = [];

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {

    $characteristics = decodeJsonArray($row['caracteristicas_json'] ?? null);

    // ⚡ preferencias con las que se guardó el favorito
    $preferences = decodeJsonArray($row['preferencias'] ?? null);

    $match = calculatePropertyMatch($preferences, $characteristics);

    $favorites[] = [
        'id' => (int) $row['id'],
        'categoria' => $row['categoria'],
        'titulo' => $row['titulo'],
        'ubicacion_general' => $row['ubicacion_general'],
        'precio' => (float) $row['precio'],
        'metros_cuadrados' => (int) $row['metros_cuadrados'],
        'imagen_principal' => $row['imagen_principal'],
        'image_url' => propertyImageUrl($row['imagen_principal'] ?? null),

        'match_percentage' => $match['percentage'],
        'match_count' => $match['matches'],
        'match_total' => $match['total'],

        // 🔥 ESTO LO NECESITA EL POPPER
        'match_details' => $match['details'] ?? [],
        'created_at' => $row['created_at'],
        'favorited_at' => $row['favorited_at'] ?? null,

        'is_favorite' => true
    ];
}

// 🔥 Ordenar por porcentaje de match descendente
usort($favorites, function($a, $b) {
    return $b['match_percentage'] <=> $a['match_percentage'];
});

respondJson(200, [
    'success' => true,
    'properties' => $favorites,
]);
