<?php
require_once "config/session.php";

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once "config/db.php";

if (!isset($_SESSION["user"])) {
    echo json_encode(["loggedIn" => false]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, nombre, apellidos, email, telefono, nombre_usuario,
           profile_picture,
           categoria_seleccionada, preferencias
    FROM usuarios
    WHERE id = :id
");

$stmt->execute(["id" => $_SESSION["user"]["id"]]);
$usuario = $stmt->fetch();

echo json_encode([
    "loggedIn" => true,
    "user" => [
        "id" => $usuario["id"],
        "nombre" => $usuario["nombre"],
        "apellidos" => $usuario["apellidos"],
        "email" => $usuario["email"],
        "telefono" => $usuario["telefono"],
        "nombre_usuario" => $usuario["nombre_usuario"],
        "profile_picture" => $usuario["profile_picture"],
        "categoria" => $usuario["categoria_seleccionada"],
        "preferencias" => $usuario["preferencias"]
            ? json_decode($usuario["preferencias"], true)
            : null
    ]
]);