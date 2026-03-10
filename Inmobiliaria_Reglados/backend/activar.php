<?php

require_once "config/session.php";
require_once "config/db.php";

$token = $_GET["token"] ?? "";

if(!$token){
    die("Token inválido");
}

/* buscar usuario con el token */
$stmt = $pdo->prepare("
SELECT id, nombre, email, nombre_usuario, profile_picture
FROM usuarios 
WHERE token_activacion = :token
");

$stmt->execute([
    ":token"=>$token
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$user){
    die("Token inválido o cuenta ya activada");
}

/* activar cuenta */
$stmt = $pdo->prepare("
UPDATE usuarios 
SET activado = 1, token_activacion = NULL
WHERE id = :id
");

$stmt->execute([
":id"=>$user["id"]
]);

/* crear la misma sesión que login.php */
$_SESSION["user"] = [
    "id" => $user["id"],
    "nombre" => $user["nombre"],
    "email" => $user["email"],
    "nombre_usuario" => $user["nombre_usuario"],
    "profile_picture" => $user["profile_picture"]
];

/* redirigir al perfil */
header("Location: http://localhost:5173/profile/properties-for-sale");
exit;