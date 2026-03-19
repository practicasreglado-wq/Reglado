<?php

declare(strict_types=1);

$userEmail = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';

if ($userEmail === '' || !filter_var($userEmail, FILTER_VALIDATE_EMAIL) || $token === '') {
    http_response_code(400);
    echo "<h1 style='color: red; font-family: sans-serif;'>Error: Email o Token inválido o no proporcionado.</h1>";
    exit;
}

try {
    // Conectar a la base de datos de inmobiliaria para validar el token
    $host = '127.0.0.1';
    $port = '3306';
    $name = 'inmobiliaria';
    $user = 'root';
    $pass = '';

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
    $pdoInmo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Verificar si la solicitud existe y está pendiente en 'inmobiliaria'
    $stmtCheck = $pdoInmo->prepare("SELECT id FROM role_promotion_requests WHERE user_email = ? AND token = ? AND status = 'pending' LIMIT 1");
    $stmtCheck->execute([$userEmail, $token]);
    $request = $stmtCheck->fetch();

    if (!$request) {
        echo "<h1 style='color: orange; font-family: sans-serif;'>Esta solicitud ya ha sido procesada o el enlace no es válido.</h1>";
        exit;
    }

    // Marcar la solicitud como rechazada en 'inmobiliaria'
    $stmtMarkResolved = $pdoInmo->prepare("UPDATE role_promotion_requests SET status = 'rejected', resolved_at = NOW() WHERE id = ?");
    $stmtMarkResolved->execute([$request['id']]);

} catch (PDOException $e) {
    http_response_code(500);
    echo "<h1 style='color: red; font-family: sans-serif;'>Error de base de datos: " . htmlspecialchars($e->getMessage()) . "</h1>";
    exit;
}

$autoloadCandidates = [
    dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php',
    dirname(dirname(dirname(__DIR__))) . '/ApiLoging/vendor/autoload.php',
];

$autoloadPath = null;
foreach ($autoloadCandidates as $candidate) {
    if (is_file($candidate)) {
        $autoloadPath = $candidate;
        break;
    }
}

if ($autoloadPath === null) {
    http_response_code(500);
    echo "<h1 style='color: red; font-family: sans-serif;'>Error: PHPMailer no está disponible.</h1>";
    exit;
}

require_once $autoloadPath;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

$subject = 'Solicitud de acceso como usuario real - Rechazada';

$body = <<<HTML
<p><strong>Estimado/a,</strong></p>
<p>Le agradecemos su interés en formar parte de nuestra plataforma como usuario real.</p>
<p>Tras revisar su solicitud, lamentamos informarle de que en este momento no ha sido posible aprobar su acceso bajo dicha condición.</p>
<p>Esta decisión se basa en criterios internos de validación y calidad de perfiles.</p>
<p>Le invitamos a seguir utilizando la plataforma en su modalidad actual y a volver a solicitar el acceso en el futuro si lo desea.</p>
<p>Quedamos a su disposición para cualquier consulta.</p>
<p><strong>Atentamente,<br>Reglado Real Estate</strong></p>
HTML;

$altBody = trim(
    "Estimado/a,\n\n" .
    "Le agradecemos su interés en formar parte de nuestra plataforma como usuario real.\n\n" .
    "Tras revisar su solicitud, lamentamos informarle de que en este momento no ha sido posible aprobar su acceso bajo dicha condición.\n\n" .
    "Esta decisión se basa en criterios internos de validación y calidad de perfiles.\n\n" .
    "Le invitamos a seguir utilizando la plataforma en su modalidad actual y a volver a solicitar el acceso en el futuro si lo desea.\n\n" .
    "Quedamos a su disposición para cualquier consulta.\n\n" .
    "Atentamente,\n" .
    "Reglado Real Estate"
);

try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.hostinger.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'info@regladoconsultores.com';
    $mail->Password = 'Reglado130891.*';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('info@regladoconsultores.com', 'Reglado Real Estate');
    $mail->addAddress($userEmail);
    $mail->addReplyTo('info@regladoconsultores.com', 'Reglado Real Estate');

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->AltBody = $altBody;

    $mail->send();

    // Confirmación mostrada al administrador
    echo "<div style='font-family: sans-serif; text-align: center; margin-top: 50px;'>";
    echo "<h1 style='color: #4CAF50;'>Solicitud rechazada correctamente</h1>";
    echo "<p>Se ha enviado el correo notificando el rechazo al usuario: <strong>" . htmlspecialchars($userEmail) . "</strong></p>";
    echo "</div>";

} catch (Exception $exception) {
    http_response_code(500);
    echo "<h1 style='color: red; font-family: sans-serif;'>Error al enviar el correo: " . htmlspecialchars($mail->ErrorInfo) . "</h1>";
}
