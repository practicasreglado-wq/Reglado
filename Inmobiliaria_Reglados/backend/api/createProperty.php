<?php
declare(strict_types=1);

require_once "../config/session.php";
require_once __DIR__ . '/../config/cors.php';
require_once "../config/db.php";
require_once dirname(__DIR__) . '/lib/geocoding.php';

applyCors();
handlePreflight();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["user"])) {
    echo json_encode([
        "success" => false,
        "error" => "No logueado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode(file_get_contents("php://input") ?: '{}', true);

if (!is_array($data)) {
    echo json_encode([
        "success" => false,
        "error" => "Solicitud no válida"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$nombre = trim((string) ($data["nombre"] ?? ""));
$ubicacion = trim((string) ($data["ubicacion"] ?? ""));
$precio = (float) ($data["precio"] ?? 0);
$tipo = trim((string) ($data["tipo"] ?? ""));

if ($nombre === "" || $ubicacion === "" || $tipo === "") {
    echo json_encode([
        "success" => false,
        "error" => "Datos incompletos"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int) ($_SESSION["user"]["id"] ?? 0);

if ($userId <= 0) {
    echo json_encode([
        "success" => false,
        "error" => "Usuario inválido"
    ], JSON_UNESCAPED_UNICODE);
    exit;
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

$stmt = $pdo->prepare("
    INSERT INTO inmobiliaria.propiedades (
        categoria,
        titulo,
        ubicacion_general,
        zona,
        ciudad,
        provincia,
        pais,
        latitud,
        longitud,
        precio,
        metros_cuadrados,
        imagen_principal,
        caracteristicas_json,
        owner_user_id,
        activo_recibido_id
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $tipo,
    $nombre,
    $ubicacion,
    $zona !== '' ? $zona : null,
    $ciudad !== '' ? $ciudad : null,
    $provincia !== '' ? $provincia : null,
    $pais,
    $latitud,
    $longitud,
    $precio,
    0,
    null,
    json_encode(new stdClass(), JSON_UNESCAPED_UNICODE),
    $userId,
    null,
]);

echo json_encode([
    "success" => true,
    "propertyId" => (int) $pdo->lastInsertId(),
    "latitud" => $latitud,
    "longitud" => $longitud,
], JSON_UNESCAPED_UNICODE);