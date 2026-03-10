<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
exit;
}

require_once "config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$token = trim($data["token"] ?? "");
$password = $data["password"] ?? "";

if(!$token || !$password){
echo json_encode(["message"=>"Datos incompletos"]);
exit;
}

/* Validación contraseña */

if(!preg_match('/^(?=.*[A-Z])(?=.*\d).{8,}$/', $password)){
echo json_encode([
"message"=>"La contraseña debe tener mínimo 8 caracteres, una mayúscula y un número"
]);
exit;
}

try{

$stmt = $pdo->prepare(
"SELECT id FROM usuarios 
WHERE reset_token = :token 
AND reset_expire > NOW()"
);

$stmt->execute([
"token"=>$token
]);

$user = $stmt->fetch();

if($user){

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
"UPDATE usuarios
SET password = :password,
reset_token = NULL,
reset_expire = NULL
WHERE id = :id"
);

$stmt->execute([
"password"=>$hash,
"id"=>$user["id"]
]);

echo json_encode([
"message"=>"Contraseña actualizada correctamente"
]);

}else{

echo json_encode([
"message"=>"Token inválido o expirado"
]);

}

}catch(PDOException $e){

echo json_encode([
"message"=>"Error de servidor"
]);

}