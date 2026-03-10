<?php

require_once "../config/session.php";

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once "../config/db.php";

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(["error" => "Usuario no autenticado"]);
    exit;
}

$userId = $_SESSION['user']['id'];

$stmt = $pdo->prepare("SELECT id, nombre, ubicacion, precio, tipo FROM propiedades WHERE userId = ?");
$stmt->execute([$userId]);

$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($properties);