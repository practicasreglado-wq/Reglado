<?php
require_once "config/session.php";

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once "config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$identifier = trim($data["identifier"] ?? "");
$password = trim($data["password"] ?? "");

if (!$identifier || !$password) {
    echo json_encode([
        "success" => false,
        "message" => "Campos obligatorios"
    ]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        id,
        nombre,
        apellidos,
        telefono,
        email,
        nombre_usuario,
        password,
        profile_picture,
        categoria_seleccionada,
        preferencias,
        activado
    FROM usuarios
    WHERE email = :identifier OR nombre_usuario = :identifier
    LIMIT 1
");

$stmt->execute(["identifier" => $identifier]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    echo json_encode(["success" => false, "message" => "Credenciales incorrectas"]);
    exit;
}

if(!$usuario["activado"]){
    echo json_encode([
        "success"=>false,
        "message"=>"Debes activar tu cuenta desde el correo"
    ]);
    exit;
}

if (!password_verify($password, $usuario["password"])) {
    echo json_encode(["success" => false, "message" => "Credenciales incorrectas"]);
    exit;
}

$_SESSION["user"] = [
    "id" => $usuario["id"],
    "nombre" => $usuario["nombre"],
    "apellidos" => $usuario["apellidos"],
    "telefono" => $usuario["telefono"],
    "email" => $usuario["email"],
    "nombre_usuario" => $usuario["nombre_usuario"],
    "profile_picture" => $usuario["profile_picture"],
    "categoria" => $usuario["categoria_seleccionada"]
];

echo json_encode([
    "success" => true,
    "user" => [
        "id" => $usuario["id"],
        "nombre" => $usuario["nombre"],
        "apellidos" => $usuario["apellidos"],
        "telefono" => $usuario["telefono"],
        "email" => $usuario["email"],
        "nombre_usuario" => $usuario["nombre_usuario"],
        "profile_picture" => $usuario["profile_picture"],
        "categoria" => $usuario["categoria_seleccionada"],
        "preferencias" => $usuario["preferencias"]
            ? json_decode($usuario["preferencias"], true)
            : null
    ]
]);