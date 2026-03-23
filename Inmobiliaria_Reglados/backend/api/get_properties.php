<?php

declare(strict_types=1);
require_once __DIR__ . '/../config/cors.php';
applyCors();
handlePreflight();
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';


$context = requireAuthenticatedUser($pdo);
$userId = (int) ($context['local']['iduser'] ?? 0);

$favoriteStmt = $pdo->prepare('SELECT propiedad_id FROM propiedades_favoritas WHERE user_id = :user_id');
$favoriteStmt->execute(['user_id' => $userId]);
$favoriteIds = array_column($favoriteStmt->fetchAll(PDO::FETCH_ASSOC), 'propiedad_id');
$favoriteLookup = array_fill_keys(array_map('intval', $favoriteIds), true);

$propertyId = (int) ($_GET['id'] ?? 0);
if ($propertyId <= 0) {
    $filters = [];
    $params = [];

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
        respondJson(404, ['success' => false, 'message' => 'Propiedad no encontrada.']);
    }

    respondJson(200, ['success' => true, 'property' => hydrateProperty($row, $favoriteLookup)]);
}

$baseSql = 'SELECT * FROM propiedades';
if (!empty($filters)) {
    $baseSql .= ' WHERE ' . implode(' AND ', $filters);
}
$baseSql .= ' ORDER BY created_at DESC';

$stmt = $pdo->prepare($baseSql);
$stmt->execute($params);

$properties = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $properties[] = hydrateProperty($row, $favoriteLookup);
}

respondJson(200, [
    'success' => true,
    'properties' => $properties,
]);

function hydrateProperty(array $row, array $favorites): array
{
    return [
        'id' => (int) $row['id'],
        'categoria' => $row['categoria'],
        'titulo' => $row['titulo'],
        'ubicacion_general' => $row['ubicacion_general'],
        'tipo_propiedad' => $row['tipo_propiedad'],
        'subtipo' => $row['subtipo'],
        'ciudad' => $row['ciudad'],
        'zona' => $row['zona'],
        'direccion' => $row['direccion'],
        'metros_cuadrados' => (int) $row['metros_cuadrados'],
        'habitaciones' => (int) $row['habitaciones'],
        'precio' => (float) $row['precio'],
        'tipo_input' => $row['tipo_input'],
        'precio_m2' => $row['precio_m2'],
        'ingresos_actuales' => $row['ingresos_actuales'],
        'ingresos_estimados' => $row['ingresos_estimados'],
        'gastos_estimados' => $row['gastos_estimados'],
        'EBITDA' => $row['EBITDA'],
        'cash_flow' => $row['cash_flow'],
        'rentabilidad_bruta' => $row['rentabilidad_bruta'],
        'rentabilidad_neta' => $row['rentabilidad_neta'],
        'cap_rate' => $row['cap_rate'],
        'roi' => $row['roi'],
        'payback' => $row['payback'],
        'ocupacion' => $row['ocupacion'],
        'ADR' => $row['ADR'],
        'RevPAR' => $row['RevPAR'],
        'analisis' => $row['analisis'],
        'analisis_json' => $row['analisis_json'],
        'caracteristicas' => !empty($row['caracteristicas_json']) ? json_decode($row['caracteristicas_json'], true) : [],
        'dossier_file' => $row['dossier_file'],
        'confidentiality_file' => $row['confidentiality_file'],
        'intention_file' => $row['intention_file'],
        'captador_id' => $row['captador_id'],
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at'] ?? $row['created_at'],
        'imagen_principal' => $row['imagen_principal'],
        'is_favorite' => isset($favorites[(int) $row['id']]),
    ];
}
