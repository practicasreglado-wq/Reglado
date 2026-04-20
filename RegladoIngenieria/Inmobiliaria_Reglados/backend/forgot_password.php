<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

require_once "config/db.php";
require_once "../vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$data = json_decode(file_get_contents("php://input"), true);

$email = $data["email"] ?? "";
$username = $data["username"] ?? "";

if(!$email || !$username){
echo json_encode(["message"=>"Datos incompletos"]);
exit;
}

try{

$stmt = $pdo->prepare(
"SELECT id FROM usuarios WHERE email = :email AND nombre_usuario = :username"
);

$stmt->execute([
"email"=>$email,
"username"=>$username
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if($user){

date_default_timezone_set("Europe/Madrid");

$token = bin2hex(random_bytes(50));

$expire = date("Y-m-d H:i:s", strtotime("+1 hour"));

$stmt = $pdo->prepare(
"UPDATE usuarios
SET reset_token = :token, reset_expire = :expire
WHERE id = :id"
);

$stmt->execute([
"token"=>$token,
"expire"=>$expire,
"id"=>$user["id"]
]);

$link = "http://localhost:5173/reset-password?token=".$token;

$mail = new PHPMailer(true);

$mail->isSMTP();
$mail->Host = "smtp.hostinger.com";
$mail->SMTPAuth = true;
$mail->Username = "info@regladoconsultores.com";
$mail->Password = "Reglado130891.*";
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;

$mail->setFrom("info@regladoconsultores.com","Reglado Real Estate");
$mail->addAddress($email);

$mail->isHTML(true);
$mail->Subject = "Recuperar contrasena";

$mail->Body = "
<h2>Recuperar contraseña</h2>

<p>Hola <b>$username</b></p>

<p>Haz clic en el siguiente enlace:</p>

<a href='$link'>$link</a>

<p>Este enlace expira en 1 hora.</p>
";

$mail->send();

echo json_encode([
"message"=>"Se ha enviado un correo con instrucciones"
]);

}else{

echo json_encode([
"message"=>"Usuario o email incorrecto"
]);

}

}catch(Exception $e){

echo json_encode([
"message"=>"Error al enviar el correo"
]);

}catch(PDOException $e){

echo json_encode([
"message"=>"Error de servidor"
]);

}