<?php
require_once "config/session.php";

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once "config/db.php";

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(["success" => false, "message" => "Usuario no autenticado"]);
    exit;
}

$user_id = $_SESSION['user']['id'];

if (!isset($_POST['nombre'], $_POST['apellidos'], $_POST['email'], $_POST['telefono'], $_POST['nombre_usuario'])) {
    echo json_encode(["success" => false, "message" => "Faltan datos"]);
    exit;
}

$nombre = $_POST['nombre'];
$apellidos = $_POST['apellidos'];
$email = $_POST['email'];
$telefono = $_POST['telefono'];
$nombre_usuario = $_POST['nombre_usuario'];

$current_password = $_POST['current_password'] ?? null;
$new_password = $_POST['new_password'] ?? null;

$passwordUpdate = false;
$hashedPassword = null;

/* ===========================
   VALIDAR CAMBIO CONTRASEÑA
=========================== */

if ($current_password && $new_password) {

    $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($current_password, $user['password'])) {
        echo json_encode([
            "success" => false,
            "message" => "La contraseña actual es incorrecta"
        ]);
        exit;
    }

    $hashedPassword = password_hash($new_password, PASSWORD_BCRYPT);
    $passwordUpdate = true;
}

/* ===========================
   ACTUALIZAR DATOS USUARIO
=========================== */

if ($passwordUpdate) {

    $stmt = $pdo->prepare("UPDATE usuarios SET
        nombre = :nombre,
        apellidos = :apellidos,
        email = :email,
        telefono = :telefono,
        nombre_usuario = :nombre_usuario,
        password = :password
        WHERE id = :id");

    $stmt->execute([
        ':nombre' => $nombre,
        ':apellidos' => $apellidos,
        ':email' => $email,
        ':telefono' => $telefono,
        ':nombre_usuario' => $nombre_usuario,
        ':password' => $hashedPassword,
        ':id' => $user_id
    ]);

} else {

    $stmt = $pdo->prepare("UPDATE usuarios SET
        nombre = :nombre,
        apellidos = :apellidos,
        email = :email,
        telefono = :telefono,
        nombre_usuario = :nombre_usuario
        WHERE id = :id");

    $stmt->execute([
        ':nombre' => $nombre,
        ':apellidos' => $apellidos,
        ':email' => $email,
        ':telefono' => $telefono,
        ':nombre_usuario' => $nombre_usuario,
        ':id' => $user_id
    ]);
}

/* ===========================
   SUBIR FOTO PERFIL
=========================== */

if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['tmp_name']) {

    $image = $_FILES['profile_picture'];

    $allowedTypes = ['image/jpeg','image/png','image/jpg'];

    if (!in_array($image['type'], $allowedTypes)) {
        echo json_encode([
            "success" => false,
            "message" => "Formato no permitido"
        ]);
        exit;
    }

    $uploadDir = "uploads/profile_pictures/";

    if (!file_exists($uploadDir)) {
        mkdir($uploadDir,0777,true);
    }

    $extension = pathinfo($image['name'], PATHINFO_EXTENSION);

    $fileName = $user_id . "." . $extension;

    $imagePath = $uploadDir . $fileName;

    if (move_uploaded_file($image['tmp_name'], $imagePath)) {

        $stmt = $pdo->prepare("UPDATE usuarios SET profile_picture = :profile_picture WHERE id = :id");

        $stmt->execute([
            ":profile_picture" => $imagePath,
            ":id" => $user_id
        ]);

    } else {

        echo json_encode([
            "success" => false,
            "message" => "Error subiendo imagen"
        ]);
        exit;

    }
}

echo json_encode([
    "success" => true
]);