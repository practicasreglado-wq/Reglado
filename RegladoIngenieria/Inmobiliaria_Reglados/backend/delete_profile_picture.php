<?php
require_once "config/session.php";

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");

require_once "config/db.php";

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(["success" => false, "message" => "Usuario no autenticado"]);
    exit;
}

$userId = $_SESSION['user']['id'];

/* obtener foto actual */
$stmt = $pdo->prepare("SELECT profile_picture FROM usuarios WHERE id = :id");
$stmt->execute([':id' => $userId]);

$user = $stmt->fetch();

if ($user && $user['profile_picture']) {

    $filePath = __DIR__ . "/" . $user['profile_picture'];

    if (file_exists($filePath)) {
        unlink($filePath);
    }

    /* borrar de la DB */
    $stmt = $pdo->prepare("UPDATE usuarios SET profile_picture = NULL WHERE id = :id");
    $stmt->execute([':id' => $userId]);
}

echo json_encode([
    "success" => true
]);