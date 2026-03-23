<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once __DIR__ . '/../config/cors.php';
applyCors();
handlePreflight();

$context = requireAuthenticatedUser($pdo);
$auth = $context['auth'];

$input = json_decode(file_get_contents('php://input') ?: '[]', true);
if (!is_array($input)) {
    respondJson(400, ['success' => false, 'message' => 'Solicitud no valida.']);
}

$message = trim((string) ($input['message'] ?? ''));
$firstName = trim((string) (($input['name'] ?? '') ?: ($auth['first_name'] ?? '') ?: ($auth['name'] ?? '')));
$lastName = trim((string) (($input['lastName'] ?? '') ?: ($auth['last_name'] ?? '')));
$username = trim((string) (($input['username'] ?? '') ?: ($auth['username'] ?? '')));
$userEmail = trim((string) (($auth['email'] ?? '') ?: ($input['email'] ?? '')));

if ($userEmail === '' || !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
    respondJson(422, ['success' => false, 'message' => 'El usuario no tiene un email valido.']);
}

if ($message === '') {
    respondJson(422, ['success' => false, 'message' => 'El mensaje es obligatorio.']);
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
    respondJson(500, ['success' => false, 'message' => 'PHPMailer no esta disponible en el servidor.']);
}

require_once $autoloadPath;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

$subject = 'Solicitud de Usuario promocionar a Real';
$recipient = 'practicasreglado@gmail.com';
$safeFirstName = htmlspecialchars($firstName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeLastName = htmlspecialchars($lastName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeUsername = htmlspecialchars($username, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeEmail = htmlspecialchars($userEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

$token = bin2hex(random_bytes(32));

try {
    $stmt = $pdo->prepare("INSERT INTO role_promotion_requests (user_email, token, status) VALUES (?, ?, 'pending')");
    $stmt->execute([$userEmail, $token]);
} catch (PDOException $e) {
    respondJson(500, ['success' => false, 'message' => 'No se pudo registrar la solicitud: ' . $e->getMessage()]);
}

$approveUrl = "http://localhost/Reglado/Inmobiliaria_Reglados/backend/api/approve_real_role.php?email=" . urlencode($userEmail) . "&token=" . $token;
$rejectUrl = "http://localhost/Reglado/Inmobiliaria_Reglados/backend/api/reject_user.php?email=" . urlencode($userEmail) . "&token=" . $token;

$body = <<<HTML
<h2>Solicitud de Usuario promocionar a Real</h2>
<p><strong>El solicitante:</strong> {$safeFirstName} {$safeLastName}</p>
<p><strong>Username:</strong> {$safeUsername}</p>
<p><strong>Correo de cuenta:</strong> {$safeEmail}</p>
<p>Esta interesado en ser usuario Real.</p>
<p><strong>Motivo:</strong> {$safeMessage}</p>
<br>
<div style="display: flex; gap: 15px; margin-top: 10px;">
    <a href="{$approveUrl}" style="display: inline-block; padding: 10px 20px; background-color: #0b3d91; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; font-family: sans-serif;">Aprobar y asignar rol Real</a>
    <a href="{$rejectUrl}" style="display: inline-block; padding: 10px 20px; background-color: #d32f2f; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; font-family: sans-serif; margin-left: 15px;">Rechazar solicitud</a>
</div>
HTML;

$altBody = trim(
    "El solicitante: {$firstName} {$lastName}\n" .
    "Username: {$username}\n" .
    "Correo de cuenta: {$userEmail}\n" .
    "Esta interesado en ser usuario Real.\n" .
    "Motivo: {$message}\n\n" .
    "Enlace para aprobar: {$approveUrl}\n" .
    "Enlace para rechazar: {$rejectUrl}"
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
    $mail->addAddress($recipient);
    $replyToName = trim("{$firstName} {$lastName}");
    $mail->addReplyTo($userEmail, $replyToName !== '' ? $replyToName : $userEmail);

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->AltBody = $altBody;

    $mail->send();

    respondJson(200, ['success' => true, 'message' => 'Solicitud enviada correctamente']);
} catch (Exception $exception) {
    respondJson(500, ['success' => false, 'message' => 'No se pudo enviar la solicitud.']);
}
