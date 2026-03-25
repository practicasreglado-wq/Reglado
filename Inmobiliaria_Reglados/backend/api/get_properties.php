<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/cors.php';
applyCors();
handlePreflight();

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';

$favoriteLookup = [];
$userId = 0;

/*
|--------------------------------------------------------------------------
| Autenticación opcional temporal
|--------------------------------------------------------------------------
| Para seguir desarrollando, permitimos listar propiedades aunque no haya
| sesión/token válidos. Si el usuario está autenticado, marcamos favoritos.
| Si no lo está, simplemente devolvemos las propiedades sin favoritos.
|--------------------------------------------------------------------------
*/
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
    // Usuario no autenticado: permitimos seguir sin favoritos
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
        'property' => hydrateProperty($row, $favoriteLookup),
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
        'categoria' => $row['categoria'] ?? '',
        'titulo' => $row['titulo'] ?? '',
        'ubicacion_general' => $row['ubicacion_general'] ?? '',
        'tipo_propiedad' => $row['tipo_propiedad'] ?? '',
        'subtipo' => $row['subtipo'] ?? '',
        'ciudad' => $row['ciudad'] ?? '',
        'zona' => $row['zona'] ?? '',
        'direccion' => $row['direccion'] ?? '',
        'metros_cuadrados' => isset($row['metros_cuadrados']) ? (int) $row['metros_cuadrados'] : 0,
        'habitaciones' => isset($row['habitaciones']) ? (int) $row['habitaciones'] : 0,
        'precio' => isset($row['precio']) ? (float) $row['precio'] : 0,
        'tipo_input' => $row['tipo_input'] ?? '',
        'precio_m2' => $row['precio_m2'] ?? null,
        'ingresos_actuales' => $row['ingresos_actuales'] ?? null,
        'ingresos_estimados' => $row['ingresos_estimados'] ?? null,
        'gastos_estimados' => $row['gastos_estimados'] ?? null,
        'EBITDA' => $row['EBITDA'] ?? null,
        'cash_flow' => $row['cash_flow'] ?? null,
        'rentabilidad_bruta' => $row['rentabilidad_bruta'] ?? null,
        'rentabilidad_neta' => $row['rentabilidad_neta'] ?? null,
        'cap_rate' => $row['cap_rate'] ?? null,
        'roi' => $row['roi'] ?? null,
        'payback' => $row['payback'] ?? null,
        'ocupacion' => $row['ocupacion'] ?? null,
        'ADR' => $row['ADR'] ?? null,
        'RevPAR' => $row['RevPAR'] ?? null,
        'analisis' => $row['analisis'] ?? '',
        'analisis_json' => $row['analisis_json'] ?? null,
        'caracteristicas' => !empty($row['caracteristicas_json'])
            ? (json_decode((string) $row['caracteristicas_json'], true) ?: [])
            : [],
        'dossier_file' => $row['dossier_file'] ?? '',
        'confidentiality_file' => $row['confidentiality_file'] ?? '',
        'intention_file' => $row['intention_file'] ?? '',
        'captador_id' => $row['captador_id'] ?? null,
        'created_at' => $row['created_at'] ?? null,
        'updated_at' => $row['updated_at'] ?? ($row['created_at'] ?? null),
        'imagen_principal' => $row['imagen_principal'] ?? '',
        'is_favorite' => isset($favorites[(int) $row['id']]),
    ];
}