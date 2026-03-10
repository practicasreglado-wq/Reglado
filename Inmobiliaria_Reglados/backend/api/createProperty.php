<?php

require_once "../config/session.php";

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once "../config/db.php";

if (!isset($_SESSION["user"])) {
    echo json_encode(["error" => "No logueado"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$nombre = $data["nombre"];
$ubicacion = $data["ubicacion"];
$precio = $data["precio"];
$tipo = $data["tipo"];

$userId = $_SESSION["user"]["id"];

$stmt = $pdo->prepare("
INSERT INTO propiedades (nombre, ubicacion, precio, tipo, userId)
VALUES (?, ?, ?, ?, ?)
");

$stmt->execute([$nombre, $ubicacion, $precio, $tipo, $userId]);

echo json_encode(["success" => true]);