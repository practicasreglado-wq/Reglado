<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/env_loader.php';
require_once dirname(__DIR__) . '/lib/notifications_helper.php';
require_once dirname(__DIR__) . '/lib/audit.php';

loadEnv(dirname(__DIR__) . '/.env');

$token = trim((string) ($_GET['token'] ?? ''));

if ($token === '') {
    http_response_code(400);
    echo "<h1 style='color:red;font-family:sans-serif;'>Error: Token inválido o no proporcionado.</h1>";
    exit;
}

$host = (string) getenv('DB_HOST');
$port = (string) getenv('DB_PORT');
$dbUser = (string) getenv('DB_USER');
$dbPass = (string) getenv('DB_PASS');

$pdoInmo = null;
$pdoAuth = null;

try {
    $pdoInmo = new PDO(
        "mysql:host={$host};port={$port};dbname=inmobiliaria;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $pdoAuth = new PDO(
        "mysql:host={$host};port={$port};dbname=regladousers;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $pdoInmo->beginTransaction();
    $pdoAuth->beginTransaction();

    $tokenHash = hash('sha256', $token);

    $stmtCheck = $pdoInmo->prepare("
        SELECT id, user_email
        FROM role_promotion_requests
        WHERE token_hash = ?
          AND status = 'pending'
        LIMIT 1
    ");
    $stmtCheck->execute([$tokenHash]);
    $request = $stmtCheck->fetch();

    $userEmail = $request ? (string) $request['user_email'] : '';

    if (!$request) {
        $pdoAuth->rollBack();
        $pdoInmo->rollBack();

        echo "<h1 style='color:orange;font-family:sans-serif;'>Esta solicitud ya ha sido procesada o el enlace no es válido.</h1>";
        exit;
    }

    $stmtUser = $pdoAuth->prepare("
        SELECT id, email
        FROM users
        WHERE email = ?
        LIMIT 1
    ");
    $stmtUser->execute([$userEmail]);
    $userRow = $stmtUser->fetch();

    $stmtMarkResolved = $pdoInmo->prepare("
        UPDATE role_promotion_requests
        SET status = 'rejected',
            resolved_at = NOW()
        WHERE id = ?
    ");
    $stmtMarkResolved->execute([$request['id']]);

    createUserNotification($pdoInmo, [
        'user_id'    => $userRow ? (int) $userRow['id'] : null,
        'user_email' => $userEmail,
        'title'      => 'Solicitud rechazada',
        'message'    => 'Tu solicitud para acceder como usuario real ha sido revisada y no ha sido aprobada en este momento. Puedes volver a solicitarla más adelante.',
        'type'       => 'warning',
        'link'       => '/profile',
    ]);

    $pdoAuth->commit();
    $pdoInmo->commit();

    auditLog($pdoInmo, 'role.promotion.reject', [
        'user_email'    => $userEmail,
        'resource_type' => 'role_promotion_request',
        'resource_id'   => (string) ($request['id'] ?? ''),
        'metadata'      => ['target_user_id' => $userRow ? (int) $userRow['id'] : null]
    ]);

} catch (Throwable $e) {
    if ($pdoAuth instanceof PDO && $pdoAuth->inTransaction()) {
        $pdoAuth->rollBack();
    }

    if ($pdoInmo instanceof PDO && $pdoInmo->inTransaction()) {
        $pdoInmo->rollBack();
    }

    http_response_code(500);
    echo "<h1 style='color:red;font-family:sans-serif;'>Error de base de datos: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</h1>";
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
    echo "<h1 style='color:red;font-family:sans-serif;'>Error: PHPMailer no está disponible.</h1>";
    exit;
}

require_once $autoloadPath;

$subject = 'Solicitud de acceso como usuario real - Rechazada';

$body = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Solicitud rechazada</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f6f8;margin:0;padding:0;">
    <tr>
      <td align="center" style="padding:32px 16px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:640px;background-color:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 8px 30px rgba(0,0,0,0.08);">
          
          <tr>
            <td style="background:linear-gradient(135deg,#0b3d91 0%,#123f7a 100%);padding:32px 36px;text-align:center;">
              <div style="display:inline-block;background-color:rgba(255,255,255,0.12);color:#ffffff;font-size:12px;font-weight:bold;letter-spacing:0.08em;text-transform:uppercase;padding:8px 14px;border-radius:999px;">
                Reglado Real Estate
              </div>
              <h1 style="margin:18px 0 8px 0;font-size:30px;line-height:1.2;color:#ffffff;font-weight:700;">
                Solicitud revisada
              </h1>
              <p style="margin:0;font-size:15px;line-height:1.6;color:black;">
                Resultado de su solicitud de acceso como usuario real
              </p>
            </td>
          </tr>

          <tr>
            <td style="padding:36px 36px 20px 36px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td>
                    <div style="display:inline-block;background-color:#fff4e5;color:#b45309;font-size:13px;font-weight:700;padding:8px 14px;border-radius:999px;margin-bottom:20px;">
                      Solicitud no aprobada
                    </div>

                    <p style="margin:0 0 16px 0;font-size:16px;line-height:1.7;color:#374151;">
                      <strong>Estimado/a,</strong>
                    </p>

                    <p style="margin:0 0 16px 0;font-size:16px;line-height:1.7;color:#374151;">
                      Le agradecemos su interés en formar parte de nuestra plataforma como <strong>usuario real</strong>.
                    </p>

                    <p style="margin:0 0 16px 0;font-size:16px;line-height:1.7;color:#374151;">
                      Tras revisar su solicitud, lamentamos informarle de que en este momento <strong>no ha sido posible aprobar su acceso</strong> bajo dicha condición.
                    </p>

                    <p style="margin:0 0 16px 0;font-size:16px;line-height:1.7;color:#374151;">
                      Esta decisión responde a nuestros criterios internos de validación y control de perfiles dentro de la plataforma.
                    </p>

                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0;background-color:#f9fafb;border:1px solid #e5e7eb;border-radius:14px;">
                      <tr>
                        <td style="padding:20px 22px;">
                          <p style="margin:0 0 10px 0;font-size:15px;line-height:1.6;color:#111827;font-weight:700;">
                            Puede seguir utilizando su cuenta actual con normalidad
                          </p>
                          <p style="margin:0;font-size:15px;line-height:1.7;color:#4b5563;">
                            Además, si lo desea, podrá volver a solicitar este acceso en el futuro.
                          </p>
                        </td>
                      </tr>
                    </table>

                    <p style="margin:0 0 24px 0;font-size:16px;line-height:1.7;color:#374151;">
                      Quedamos a su disposición para cualquier consulta adicional.
                    </p>

                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td style="padding:8px 36px 32px 36px;">
              <div style="height:1px;background-color:#e5e7eb;margin-bottom:22px;"></div>
              <p style="margin:0 0 6px 0;font-size:15px;line-height:1.6;color:#111827;font-weight:700;">
                Atentamente,
              </p>
              <p style="margin:0;font-size:15px;line-height:1.6;color:#4b5563;">
                <strong>Reglado Real Estate</strong><br>
                realstate@regladoconsultores.com
              </p>
            </td>
          </tr>

          <tr>
            <td style="background-color:#f9fafb;padding:18px 24px;text-align:center;border-top:1px solid #e5e7eb;">
              <p style="margin:0;font-size:12px;line-height:1.6;color:#6b7280;">
                Este correo ha sido enviado automáticamente desde la plataforma de Reglado Real Estate.
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
    "Estimado/a,\n\n" .
    "Le agradecemos su interés en formar parte de nuestra plataforma como usuario real.\n\n" .
    "Tras revisar su solicitud, lamentamos informarle de que en este momento no ha sido posible aprobar su acceso bajo dicha condición.\n\n" .
    "Esta decisión responde a nuestros criterios internos de validación y control de perfiles dentro de la plataforma.\n\n" .
    "Puede seguir utilizando su cuenta actual con normalidad y volver a solicitar este acceso en el futuro si lo desea.\n\n" .
    "Quedamos a su disposición para cualquier consulta adicional.\n\n" .
    "Contacto: info@regladoconsultores.com\n\n" .
    "Atentamente,\n" .
    "Reglado Real Estate"
);

try {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    $mail->isSMTP();
    $mail->Host = (string) getenv('SMTP_HOST');
    $mail->SMTPAuth = true;
    $mail->Username = (string) getenv('SMTP_USER');
    $mail->Password = (string) getenv('SMTP_PASS');
    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = (int) getenv('SMTP_PORT');

    $mail->setFrom((string) getenv('SMTP_FROM'), (string) getenv('SMTP_FROM_NAME'));
    $mail->addAddress($userEmail);
    $mail->addReplyTo((string) getenv('SMTP_FROM'), (string) getenv('SMTP_FROM_NAME'));

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->AltBody = $altBody;

    $mail->send();

    echo "<div style='font-family:sans-serif;text-align:center;margin-top:50px;'>";
    echo "<h1 style='color:#4CAF50;'>Solicitud rechazada correctamente</h1>";
    echo "<p>Se ha enviado el correo notificando el rechazo al usuario: <strong>" . htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8') . "</strong></p>";
    echo "<p>Además, se ha creado una notificación interna en la plataforma.</p>";
    echo "</div>";

} catch (Throwable $e) {
    http_response_code(500);
    echo "<h1 style='color:red;font-family:sans-serif;'>La solicitud se rechazó correctamente, pero falló el envío del correo: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</h1>";
}