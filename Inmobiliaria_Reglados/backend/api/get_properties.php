<?php

declare(strict_types=1);

/**
 * Listado público de propiedades para la vista del comprador.
 *
 * Solo devuelve propiedades con status='activa' y NO expone coordenadas
 * reales — el mapa va con coordenadas desplazadas vía lib/privacy_map.php.
 *
 * Soporta filtros por tipo, ciudad, precio, m². La paginación la hace el
 * frontend al recibir el array completo (en futuro evaluar paginación
 * server-side si crece el volumen).
 */

require_once __DIR__ . '/../config/cors.php';
applyCors();
handlePreflight();

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/lib/privacy_map.php';
require_once dirname(__DIR__) . '/lib/rate_limit.php';

// Anti-scraping: 120 req/min por IP. Un usuario normal navegando y filtrando
// está muy por debajo; un scraper bajando todo el catálogo se para en seco.
enforceIpRateLimit('property_listing', clientIp(), 120, 60, true);

$favoriteLookup = [];
$intentMatchLookup = [];
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

        // Propiedades que hicieron match con un intent activo del comprador.
        // Se usa en el frontend para forzar match_percentage=100 y que no
        // queden filtradas por el umbral mínimo del catálogo.
        try {
            $intentStmt = $pdo->prepare('
                SELECT matched_property_id
                FROM buyer_intents
                WHERE buyer_user_id = :user_id
                  AND status = "matched"
                  AND matched_property_id IS NOT NULL
            ');
            $intentStmt->execute(['user_id' => $userId]);
            $matchedIds = array_column($intentStmt->fetchAll(PDO::FETCH_ASSOC), 'matched_property_id');
            $intentMatchLookup = array_fill_keys(array_map('intval', $matchedIds), true);
        } catch (Throwable $e) {
            // Si la tabla no existe aún (migración no aplicada), dejamos el
            // lookup vacío en lugar de romper el listado.
            $intentMatchLookup = [];
        }
    }
} catch (Throwable $e) {
    $favoriteLookup = [];
    $intentMatchLookup = [];
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
        'property' => hydratePropertyDetail($row, $favoriteLookup, $intentMatchLookup),
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
    $properties[] = hydratePropertyCard($row, $favoriteLookup, $intentMatchLookup);
}

respondJson(200, [
    'success' => true,
    'properties' => $properties,
]);

function hydratePropertyCard(array $row, array $favorites, array $intentMatches = []): array
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
        'matches_my_intent' => isset($intentMatches[(int) $row['id']]),
    ];
}

function hydratePropertyDetail(array $row, array $favorites, array $intentMatches = []): array
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
        'matches_my_intent' => isset($intentMatches[(int) $row['id']]),
    ];
}