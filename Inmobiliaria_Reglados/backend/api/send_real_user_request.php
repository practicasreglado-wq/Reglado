<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once __DIR__ . '/../config/cors.php';

applyCors();
handlePreflight();

$context = requireAuthenticatedUser($pdo);
$auth = $context['auth'] ?? [];

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$input = json_decode(file_get_contents('php://input') ?: '[]', true);
if (!is_array($input)) {
    respondJson(400, ['success' => false, 'message' => 'Solicitud no valida.']);
}

$message = trim((string) ($input['message'] ?? ''));

$userId = (int) (
    $context['local']['iduser']
    ?? $context['local']['id']
    ?? $context['auth']['id']
    ?? 0
);

if ($userId <= 0) {
    respondJson(401, ['success' => false, 'message' => 'Debes iniciar sesion para enviar la solicitud.']);
}

$userStmt = $pdo->prepare("
    SELECT
        id,
        email,
        first_name,
        last_name,
        username
    FROM regladousers.users
    WHERE id = :id
    LIMIT 1
");
$userStmt->execute([
    ':id' => $userId
]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    respondJson(404, ['success' => false, 'message' => 'No se encontro el usuario autenticado.']);
}

$firstName = trim((string) ($user['first_name'] ?? ''));
$lastName = trim((string) ($user['last_name'] ?? ''));
$username = trim((string) ($user['username'] ?? ''));
$userEmail = trim((string) ($user['email'] ?? ''));

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
    $stmt = $pdo->prepare("
        INSERT INTO role_promotion_requests (
            user_id,
            user_email,
            first_name,
            last_name,
            username,
            message,
            token,
            status,
            created_at
        ) VALUES (
            :user_id,
            :user_email,
            :first_name,
            :last_name,
            :username,
            :message,
            :token,
            'pending',
            NOW()
        )
    ");

    $stmt->execute([
        ':user_id' => $userId,
        ':user_email' => $userEmail,
        ':first_name' => $firstName !== '' ? $firstName : null,
        ':last_name' => $lastName !== '' ? $lastName : null,
        ':username' => $username !== '' ? $username : null,
        ':message' => $message,
        ':token' => $token,
    ]);
} catch (PDOException $e) {
    respondJson(500, ['success' => false, 'message' => 'No se pudo registrar la solicitud: ' . $e->getMessage()]);
}

$approveUrl = "http://localhost/Reglado/Inmobiliaria_Reglados/backend/api/approve_real_role.php?email=" . urlencode($userEmail) . "&token=" . $token;
$rejectUrl = "http://localhost/Reglado/Inmobiliaria_Reglados/backend/api/reject_user.php?email=" . urlencode($userEmail) . "&token=" . $token;

$body = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Solicitud de promoción a usuario Real</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f6f8;margin:0;padding:0;">
    <tr>
      <td align="center" style="padding:32px 16px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:680px;background-color:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 8px 30px rgba(0,0,0,0.08);">
          
          <tr>
            <td style="background:linear-gradient(135deg,#0b3d91 0%,#123f7a 100%);padding:32px 36px;text-align:center;">
              <div style="display:inline-block;background-color:rgba(255,255,255,0.12);color:#ffffff;font-size:12px;font-weight:bold;letter-spacing:0.08em;text-transform:uppercase;padding:8px 14px;border-radius:999px;">
                Reglado Real Estate
              </div>
              <h1 style="margin:18px 0 8px 0;font-size:30px;line-height:1.2;color:#ffffff;font-weight:700;">
                Nueva solicitud de promoción
              </h1>
              <p style="margin:0;font-size:15px;line-height:1.6;color:#dbe7ff;">
                Solicitud para promocionar un usuario al rol Real
              </p>
            </td>
          </tr>

          <tr>
            <td style="padding:36px 36px 20px 36px;">
              <div style="display:inline-block;background-color:#eef4ff;color:#0b3d91;font-size:13px;font-weight:700;padding:8px 14px;border-radius:999px;margin-bottom:20px;">
                Revisión administrativa
              </div>

              <p style="margin:0 0 18px 0;font-size:16px;line-height:1.7;color:#374151;">
                Se ha recibido una nueva solicitud para promocionar a un usuario al perfil <strong>Real</strong>.
              </p>

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e5e7eb;border-radius:16px;background-color:#f9fafb;margin:0 0 24px 0;">
                <tr>
                  <td style="padding:24px;">
                    <p style="margin:0 0 14px 0;font-size:15px;line-height:1.6;color:#111827;font-weight:700;">
                      Datos del solicitante
                    </p>

                    <p style="margin:0 0 10px 0;font-size:15px;line-height:1.6;color:#4b5563;">
                      <strong>Nombre:</strong> {$safeFirstName} {$safeLastName}
                    </p>

                    <p style="margin:0 0 10px 0;font-size:15px;line-height:1.6;color:#4b5563;">
                      <strong>Username:</strong> {$safeUsername}
                    </p>

                    <p style="margin:0 0 10px 0;font-size:15px;line-height:1.6;color:#4b5563;">
                      <strong>Correo de cuenta:</strong> {$safeEmail}
                    </p>

                    <p style="margin:0;font-size:15px;line-height:1.6;color:#4b5563;">
                      <strong>Estado:</strong> Interesado en ser usuario Real
                    </p>
                  </td>
                </tr>
              </table>

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e5e7eb;border-radius:16px;background-color:#ffffff;margin:0 0 28px 0;">
                <tr>
                  <td style="padding:24px;">
                    <p style="margin:0 0 12px 0;font-size:15px;line-height:1.6;color:#111827;font-weight:700;">
                      Motivo de la solicitud
                    </p>
                    <p style="margin:0;font-size:15px;line-height:1.8;color:#4b5563;">
                      {$safeMessage}
                    </p>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 18px 0;font-size:15px;line-height:1.7;color:#374151;font-weight:600;text-align:center;">
                Seleccione una acción para gestionar esta solicitud:
              </p>

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td align="center" style="padding:0 0 10px 0;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td align="center" style="border-radius:10px;background-color:#0b3d91;">
                          <a href="{$approveUrl}" style="display:inline-block;padding:14px 24px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:10px;">
                            Aprobar y asignar rol Real
                          </a>
                        </td>
                        <td style="width:12px;"></td>
                        <td align="center" style="border-radius:10px;background-color:#d32f2f;">
                          <a href="{$rejectUrl}" style="display:inline-block;padding:14px 24px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:10px;">
                            Rechazar solicitud
                          </a>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <p style="margin:22px 0 0 0;font-size:13px;line-height:1.7;color:#6b7280;text-align:center;">
                Si los botones no funcionan en su cliente de correo, puede copiar y pegar manualmente los enlaces desde la versión de texto del mensaje.
              </p>
            </td>
          </tr>

          <tr>
            <td style="padding:8px 36px 32px 36px;">
              <div style="height:1px;background-color:#e5e7eb;margin-bottom:22px;"></div>
              <p style="margin:0 0 6px 0;font-size:15px;line-height:1.6;color:#111827;font-weight:700;">
                Reglado Real Estate
              </p>
              <p style="margin:0;font-size:14px;line-height:1.6;color:#6b7280;">
                Correo automático de revisión de solicitudes de promoción de rol.
              </p>
            </td>
          </tr>

          <tr>
            <td style="background-color:#f9fafb;padding:18px 24px;text-align:center;border-top:1px solid #e5e7eb;">
              <p style="margin:0;font-size:12px;line-height:1.6;color:#6b7280;">
                Este correo ha sido generado automáticamente por la plataforma de Reglado Real Estate.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
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