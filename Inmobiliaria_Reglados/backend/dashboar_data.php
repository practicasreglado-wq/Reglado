<?php
require_once "config/session.php";

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if (!isset($_SESSION["user"])) {
    http_response_code(401);
    echo json_encode(["message" => "No autorizado"]);
    exit;
}

echo json_encode([
    "message" => "Datos privados del dashboard",
    "user" => $_SESSION["user"]
]);