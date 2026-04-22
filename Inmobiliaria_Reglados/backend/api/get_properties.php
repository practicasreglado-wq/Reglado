<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/cors.php';
applyCors();
handlePreflight();

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';

$favoriteLookup = [];
$userId = 0;

try {
    $context = requireAuthenticatedUser($pdo);
    $userId = (int) (
        $context['local']['iduser']
        ?? $context['local']['id']
        ?? $context['auth']['id']
        ?? 0
    );

    if ($userId > 0) {
        $favoriteStmt = $pdo->prepare('
            SELECT propiedad_id
            FROM propiedades_favoritas
            WHERE user_id = :user_id
        ');
        $favoriteStmt->execute(['user_id' => $userId]);
        $favoriteIds = array_column($favoriteStmt->fetchAll(PDO::FETCH_ASSOC), 'propiedad_id');
        $favoriteLookup = array_fill_keys(array_map('intval', $favoriteIds), true);
    }
} catch (Throwable $e) {
    $favoriteLookup = [];
}

$propertyId = (int) ($_GET['id'] ?? 0);
$filters = [];
$params = [];

if ($propertyId <= 0) {
    $categoriaFilter = trim((string) ($_GET['categoria'] ?? ''));
    if ($categoriaFilter !== '') {
        $filters[] = 'p.categoria = :categoria';
        $params['categoria'] = $categoriaFilter;
    }

    $tipoFilter = trim((string) ($_GET['tipo_propiedad'] ?? ''));
    if ($tipoFilter !== '') {
        $filters[] = 'p.tipo_propiedad = :tipo_propiedad';
        $params['tipo_propiedad'] = $tipoFilter;
    }
}

if ($propertyId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM propiedades WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $propertyId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        respondJson(404, [
            'success' => false,
            'message' => 'Propiedad no encontrada.',
        ]);
    }

    respondJson(200, [
        'success' => true,
        'property' => hydratePropertyDetail($row, $favoriteLookup),
    ]);
}

$baseSql = 'SELECT p.* FROM propiedades p';
if (!empty($filters)) {
    $baseSql .= ' WHERE ' . implode(' AND ', $filters);
}
$baseSql .= ' ORDER BY p.created_at DESC';

$stmt = $pdo->prepare($baseSql);
$stmt->execute($params);

$properties = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $properties[] = hydratePropertyCard($row, $favoriteLookup);
}

respondJson(200, [
    'success' => true,
    'properties' => $properties,
]);

function buildPrivacyMapCoordinates(array $row): array
{
    $lat = isset($row['latitud']) ? (float) $row['latitud'] : null;
    $lon = isset($row['longitud']) ? (float) $row['longitud'] : null;

    if (!is_float($lat) || !is_float($lon)) {
        return [
            'map_latitud' => null,
            'map_longitud' => null,
        ];
    }

    if (!is_finite($lat) || !is_finite($lon)) {
        return [
            'map_latitud' => null,
            'map_longitud' => null,
        ];
    }

    if (abs($lat) < 0.000001 || abs($lon) < 0.000001) {
        return [
            'map_latitud' => null,
            'map_longitud' => null,
        ];
    }

    $seedBase = (string) ($row['id'] ?? '');
    $hash = crc32($seedBase);
    $offsetMeters = 120 + ($hash % 60);
    $angleDeg = $hash % 360;
    $angleRad = deg2rad((float) $angleDeg);

    $earthRadius = 6378137.0;

    $dLat = ($offsetMeters * cos($angleRad)) / $earthRadius * (180 / M_PI);
    $dLon = ($offsetMeters * sin($angleRad)) / ($earthRadius * cos(deg2rad($lat))) * (180 / M_PI);

    return [
        'map_latitud' => round($lat + $dLat, 7),
        'map_longitud' => round($lon + $dLon, 7),
    ];
}

function hydratePropertyCard(array $row, array $favorites): array
{
    $privacyMap = buildPrivacyMapCoordinates($row);

    $caracteristicasRaw = !empty($row['caracteristicas_json'])
        ? (json_decode((string) $row['caracteristicas_json'], true) ?: [])
        : [];

    $caracteristicas = [
        'uso_principal' => $caracteristicasRaw['uso_principal'] ?? '',
        'uso_alternativo' => $caracteristicasRaw['uso_alternativo'] ?? '',
        'propiedad_tipo' => $caracteristicasRaw['propiedad_tipo'] ?? '',
        'superficie_construida' => $caracteristicasRaw['superficie_construida'] ?? null,
    ];

    return [
        'id' => (int) $row['id'],
        'categoria' => $row['categoria'] ?? '',
        'estado' => !empty($row['estado']) ? (string) $row['estado'] : 'disponible',
        'titulo' => $row['titulo'] ?? '',
        'ubicacion_general' => $row['ubicacion_general'] ?? '',
        'tipo_propiedad' => $row['tipo_propiedad'] ?? '',
        'subtipo' => $row['subtipo'] ?? '',
        'ciudad' => $row['ciudad'] ?? '',
        'zona' => $row['zona'] ?? '',
        'map_latitud' => $privacyMap['map_latitud'],
        'map_longitud' => $privacyMap['map_longitud'],
        'metros_cuadrados' => isset($row['metros_cuadrados']) ? (int) $row['metros_cuadrados'] : 0,
        'precio' => isset($row['precio']) ? (float) $row['precio'] : 0,
        'ingresos_actuales' => $row['ingresos_actuales'] ?? null,
        'analisis' => $row['analisis'] ?? '',
        'estado_ocupacion' => $row['estado_ocupacion'] ?? '',
        'disponibilidad' => $row['disponibilidad'] ?? '',
        'situacion' => $row['situacion'] ?? '',
        'caracteristicas' => $caracteristicas,
        'imagen_principal' => $row['imagen_principal'] ?? '',
        'is_favorite' => isset($favorites[(int) $row['id']]),
    ];
}

function hydratePropertyDetail(array $row, array $favorites): array
{
    $privacyMap = buildPrivacyMapCoordinates($row);

    $caracteristicasRaw = !empty($row['caracteristicas_json'])
        ? (json_decode((string) $row['caracteristicas_json'], true) ?: [])
        : [];

    $caracteristicas = [
        'uso_principal' => $caracteristicasRaw['uso_principal'] ?? '',
        'uso_alternativo' => $caracteristicasRaw['uso_alternativo'] ?? '',
        'propiedad_tipo' => $caracteristicasRaw['propiedad_tipo'] ?? '',
        'superficie_construida' => $caracteristicasRaw['superficie_construida'] ?? null,
    ];

    return [
        'id' => (int) $row['id'],
        'categoria' => $row['categoria'] ?? '',
        'estado' => !empty($row['estado']) ? (string) $row['estado'] : 'disponible',
        'titulo' => $row['titulo'] ?? '',
        'ubicacion_general' => '',
        'tipo_propiedad' => $row['tipo_propiedad'] ?? '',
        'subtipo' => $row['subtipo'] ?? '',
        'ciudad' => $row['ciudad'] ?? '',
        'zona' => $row['zona'] ?? '',
        'map_latitud' => $privacyMap['map_latitud'],
        'map_longitud' => $privacyMap['map_longitud'],
        'metros_cuadrados' => isset($row['metros_cuadrados']) ? (int) $row['metros_cuadrados'] : 0,
        'habitaciones' => isset($row['habitaciones']) ? (int) $row['habitaciones'] : 0,
        'precio' => isset($row['precio']) ? (float) $row['precio'] : 0,
        'tipo_input' => $row['tipo_input'] ?? '',
        'precio_m2' => $row['precio_m2'] ?? null,
        'ingresos_actuales' => $row['ingresos_actuales'] ?? null,
        'analisis' => $row['analisis'] ?? '',
        'estado_ocupacion' => $row['estado_ocupacion'] ?? '',
        'disponibilidad' => $row['disponibilidad'] ?? '',
        'situacion' => $row['situacion'] ?? '',
        'caracteristicas' => $caracteristicas,
        'imagen_principal' => $row['imagen_principal'] ?? '',
        'dossier_file' => $row['dossier_file'] ?? '',
        'confidentiality_file' => $row['confidentiality_file'] ?? '',
        'intention_file' => $row['intention_file'] ?? '',
        'is_favorite' => isset($favorites[(int) $row['id']]),
    ];
}