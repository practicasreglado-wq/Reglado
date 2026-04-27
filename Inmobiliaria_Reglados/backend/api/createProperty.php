<?php
declare(strict_types=1);

/**
 * Endpoint de alta de propiedad desde el formulario web manual.
 *
 * Es la vía "directa" (vs create_property_from_text.php que extrae datos con
 * IA, y vs el pipeline de receive_email.php que procesa correos entrantes).
 *
 * Recibe los campos ya estructurados (tipo, ciudad, dirección, precio…),
 * los valida, geocodifica con Nominatim (lib/geocoding.php), calcula
 * address_hash para deduplicación (lib/address_hash.php), inserta la
 * propiedad y notifica matches con buyer_intents pendientes
 * (lib/buyer_intents.php).
 */

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once dirname(__DIR__) . '/lib/geocoding.php';
require_once dirname(__DIR__) . '/lib/audit.php';
require_once dirname(__DIR__) . '/lib/address_hash.php';
require_once dirname(__DIR__) . '/lib/buyer_intents.php';

applyCors();
handlePreflight();

header('Content-Type: application/json; charset=utf-8');

$context = requireAuthenticatedUser($pdo);
$userId = (int) ($context['local']['iduser'] ?? $context['auth']['sub'] ?? 0);

if ($userId <= 0) {
    respondJson(401, ['success' => false, 'error' => 'Usuario inválido']);
}

$data = json_decode(file_get_contents("php://input") ?: '{}', true);

if (!is_array($data)) {
    respondJson(400, ['success' => false, 'error' => 'Solicitud no válida']);
}

$nombre = trim((string) ($data["nombre"] ?? ""));
$ubicacion = trim((string) ($data["ubicacion"] ?? ""));
$precio = (float) ($data["precio"] ?? 0);
$tipo = trim((string) ($data["tipo"] ?? ""));

if ($nombre === "" || $ubicacion === "" || $tipo === "") {
    respondJson(422, ['success' => false, 'error' => 'Datos incompletos']);
}

$zona = '';
$ciudad = '';
$provincia = '';
$pais = 'España';

$parts = array_values(array_filter(array_map('trim', explode(',', $ubicacion))));

if (count($parts) >= 2) {
    $zona = $parts[0];
    $ciudad = $parts[1];
    if (isset($parts[2])) {
        $provincia = $parts[2];
    }
} elseif (count($parts) === 1) {
    $ciudad = $parts[0];
}

$geo = geocodeApproximateLocation([
    'zona' => $zona,
    'ciudad' => $ciudad,
    'provincia' => $provincia,
    'pais' => $pais,
]);

$latitud = $geo['latitud'] ?? null;
$longitud = $geo['longitud'] ?? null;

$addressHash = buildPropertyAddressHash([
    'ubicacion' => $ubicacion,
    'ciudad'    => $ciudad,
    'provincia' => $provincia,
    'pais'      => $pais,
]);

$addressHashColumnExists = false;
try {
    $colCheck = $pdo->prepare("
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = 'inmobiliaria'
          AND TABLE_NAME = 'propiedades'
          AND COLUMN_NAME = 'address_hash'
        LIMIT 1
    ");
    $colCheck->execute();
    $addressHashColumnExists = (bool) $colCheck->fetchColumn();
} catch (Throwable $e) {
    $addressHashColumnExists = false;
}

// Dedup pre-INSERT: si ya existe una propiedad con el mismo address_hash,
// devolvemos su id sin duplicar.
if ($addressHash !== null && $addressHashColumnExists) {
    $dupStmt = $pdo->prepare('SELECT id FROM inmobiliaria.propiedades WHERE address_hash = :h LIMIT 1');
    $dupStmt->execute(['h' => $addressHash]);
    $existing = $dupStmt->fetchColumn();

    if ($existing !== false) {
        echo json_encode([
            "success"    => true,
            "propertyId" => (int) $existing,
            "duplicate"  => true,
            "message"    => "Esta propiedad ya estaba registrada. Se ha reutilizado la existente.",
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$columns = ['categoria', 'titulo', 'ubicacion_general', 'zona', 'ciudad',
            'provincia', 'pais', 'latitud', 'longitud', 'precio',
            'metros_cuadrados', 'imagen_principal', 'caracteristicas_json',
            'owner_user_id', 'activo_recibido_id'];

$values = [
    $tipo, $nombre, $ubicacion,
    $zona !== '' ? $zona : null,
    $ciudad !== '' ? $ciudad : null,
    $provincia !== '' ? $provincia : null,
    $pais, $latitud, $longitud, $precio,
    0, null, json_encode(new stdClass(), JSON_UNESCAPED_UNICODE),
    $userId, null,
];

if ($addressHash !== null && $addressHashColumnExists) {
    $columns[] = 'address_hash';
    $values[] = $addressHash;
}

$placeholders = implode(', ', array_fill(0, count($columns), '?'));
$stmt = $pdo->prepare(
    'INSERT INTO inmobiliaria.propiedades (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')'
);

try {
    $stmt->execute($values);
} catch (PDOException $e) {
    // Race condition: otra petición insertó la misma propiedad entre el
    // SELECT y este INSERT.
    if ((string) $e->getCode() === '23000' && $addressHash !== null && $addressHashColumnExists) {
        $recover = $pdo->prepare('SELECT id FROM inmobiliaria.propiedades WHERE address_hash = :h LIMIT 1');
        $recover->execute(['h' => $addressHash]);
        $existingId = $recover->fetchColumn();
        if ($existingId !== false) {
            echo json_encode([
                "success"    => true,
                "propertyId" => (int) $existingId,
                "duplicate"  => true,
                "message"    => "Esta propiedad ya estaba registrada. Se ha reutilizado la existente.",
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    throw $e;
}

$newPropertyId = (int) $pdo->lastInsertId();

auditLog($pdo, 'property.create', array_merge(
    auditContextFromAuth($context['auth'] ?? [], $userId),
    [
        'resource_type' => 'property',
        'resource_id'   => (string) $newPropertyId,
        'metadata'      => [
            'tipo'      => $tipo,
            'titulo'    => $nombre,
            'ubicacion' => $ubicacion,
            'precio'    => $precio,
        ],
    ]
));

processNewPropertyMatching($pdo, $newPropertyId, [
    'categoria'        => $tipo,
    'ciudad'           => $ciudad,
    'precio'           => $precio,
    'metros_cuadrados' => 0,
]);

echo json_encode([
    "success" => true,
    "propertyId" => $newPropertyId,
], JSON_UNESCAPED_UNICODE);