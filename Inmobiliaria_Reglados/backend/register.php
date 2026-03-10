<?php

require_once "config/session.php";
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
require_once "config/db.php";

/* PHPMailer */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

$data = json_decode(file_get_contents("php://input"), true);

$nombre = trim($data["nombre"] ?? "");
$apellidos = trim($data["apellido"] ?? "");
$email = trim($data["email"] ?? "");
$telefono = trim($data["telefono"] ?? "");
$username = trim($data["username"] ?? "");
$password = $data["password"] ?? "";
$confirmPassword = $data["confirmPassword"] ?? "";
$fechaNacimiento = $data["fechaNacimiento"] ?? "";

try {

    if(!$nombre || !$apellidos || !$email || !$telefono || !$username || !$password || !$confirmPassword || !$fechaNacimiento){
        echo json_encode([
            "success"=>false,
            "message"=>"Todos los campos son obligatorios"
        ]);
        exit;
    }

    if($password !== $confirmPassword){
        echo json_encode([
            "success"=>false,
            "message"=>"Las contraseñas no coinciden"
        ]);
        exit;
    }

    if(!preg_match("/^(?=.*[A-Z])(?=.*[0-9]).{8,}$/",$password)){
        echo json_encode([
            "success"=>false,
            "message"=>"La contraseña debe tener mínimo 8 caracteres, una mayúscula y un número"
        ]);
        exit;
    }

    /* validar edad */
    $fecha = new DateTime($fechaNacimiento);
    $hoy = new DateTime();
    $edad = $hoy->diff($fecha)->y;

    if($edad < 18){
        echo json_encode([
            "success"=>false,
            "message"=>"Debes ser mayor de 18 años"
        ]);
        exit;
    }

    /* comprobar usuario existente */
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email OR nombre_usuario = :username");

    $stmt->execute([
        ":email"=>$email,
        ":username"=>$username
    ]);

    if($stmt->fetch()){
        echo json_encode([
            "success"=>false,
            "message"=>"El usuario o correo ya existe"
        ]);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    /* token activación */
    $token = bin2hex(random_bytes(32));

    $stmt = $pdo->prepare("
        INSERT INTO usuarios
        (nombre, apellidos, email, telefono, nombre_usuario, password, fecha_nacimiento, token_activacion)
        VALUES
        (:nombre, :apellidos, :email, :telefono, :username, :password, :fecha_nacimiento, :token)
    ");

    $stmt->execute([
        ":nombre"=>$nombre,
        ":apellidos"=>$apellidos,
        ":email"=>$email,
        ":telefono"=>$telefono,
        ":username"=>$username,
        ":password"=>$hash,
        ":fecha_nacimiento"=>$fechaNacimiento,
        ":token"=>$token
    ]);

    /* enlace activación */
    $activationLink = "http://localhost/inmobiliaria/backend/activar.php?token=".$token;

    /* PHPMailer */

    $mail = new PHPMailer(true);

try{

    $mail->isSMTP();
    $mail->Host       = 'smtp.hostinger.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'info@regladoconsultores.com';
    $mail->Password   = 'Reglado130891.*';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('info@regladoconsultores.com', 'Reglado Real Estate');
    $mail->addAddress($email, $nombre);

    $mail->isHTML(true);
    $mail->Subject = 'Activar cuenta';

    $mail->Body = "
    <h2>Hola $nombre</h2>
    <p>Gracias por registrarte.</p>
    <p>Para activar tu cuenta haz click en el siguiente enlace:</p>

    <a href='$activationLink'>Activar cuenta</a>

    <br><br>

    <p>Si no te registraste ignora este mensaje.</p>
    ";

    $mail->send();

}catch(Exception $e){

    echo json_encode([
        "success"=>false,
        "message"=>"Error enviando email",
        "error"=>$mail->ErrorInfo
    ]);
    exit;

}

    echo json_encode([
        "success"=>true,
        "message"=>"Registro correcto. Revisa tu correo para activar la cuenta"
    ]);

}catch(PDOException $e){

    echo json_encode([
        "success"=>false,
        "message"=>"Error en el servidor"
    ]);

}