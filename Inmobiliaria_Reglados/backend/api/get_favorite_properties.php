<?php

require_once "../config/session.php";

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once "../config/db.php";

if(!isset($_SESSION["user"]["id"])){
    echo json_encode([]);
    exit;
}

$userId = $_SESSION["user"]["id"];

$stmt = $pdo->prepare("
SELECT propiedades.*
FROM propiedades_favoritas
JOIN propiedades
ON propiedades.id = propiedades_favoritas.propiedadId
WHERE propiedades_favoritas.userId = ?
");

$stmt->execute([$userId]);

$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($favorites);