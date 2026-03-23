<?php

require_once "../config/session.php";

require_once __DIR__ . '/../config/cors.php';
applyCors();

require_once "../config/db.php";

if (!isset($_SESSION["user"])) {
    echo json_encode(["error" => "No logueado"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$nombre = trim((string) ($data["nombre"] ?? ""));
$ubicacion = trim((string) ($data["ubicacion"] ?? ""));
$precio = (float) ($data["precio"] ?? 0);
$tipo = trim((string) ($data["tipo"] ?? ""));

if ($nombre === "" || $ubicacion === "" || $tipo === "") {
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

$userId = (int) $_SESSION["user"]["id"];

$stmt = $pdo->prepare("
    INSERT INTO propiedades (
        categoria,
        titulo,
        ubicacion_general,
        precio,
        metros_cuadrados,
        imagen_principal,
        caracteristicas_json,
        owner_user_id
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $tipo,
    $nombre,
    $ubicacion,
    $precio,
    0,
    null,
    json_encode(new stdClass(), JSON_UNESCAPED_UNICODE),
    $userId,
]);

echo json_encode(["success" => true]);
