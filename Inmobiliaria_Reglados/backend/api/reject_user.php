<?php
declare(strict_types=1);

/**
 * Endpoint del enlace "Rechazar usuario Premium" del correo al admin.
 *
 * Espejo de approve_real_role.php pero negativo:
 *  1) NO cambia el rol del usuario.
 *  2) Marca role_promotion_requests como resolved (rejected).
 *  3) Notifica al usuario (in-app + email) que su solicitud no fue aprobada
 *    y que puede volver a solicitarla más adelante.
 *
 * Devuelve HTML directo (no JSON) — se accede desde el navegador del admin.
 */

require_once dirname(__DIR__) . '/lib/env_loader.php';
require_once dirname(__DIR__) . '/lib/notifications_helper.php';
require_once dirname(__DIR__) . '/lib/audit.php';
require_once dirname(__DIR__) . '/lib/email_layout.php';
require_once dirname(__DIR__) . '/lib/error_reporting.php';
require_once dirname(__DIR__) . '/lib/apiloging_client.php';

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
$userRow = null;
$userEmail = '';

try {
    $pdoInmo = new PDO(
        "mysql:host={$host};port={$port};dbname=" . dbNameInmobiliaria() . ";charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $pdoInmo->beginTransaction();

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
        $pdoInmo->rollBack();
        echo "<h1 style='color:orange;font-family:sans-serif;'>Esta solicitud ya ha sido procesada o el enlace no es válido.</h1>";
        exit;
    }

    // Lookup del usuario (no obligatorio: si no existe seguimos rechazando
    // la solicitud, simplemente la notificación in-app no apunta a user_id).
    $userRow = apilogingFindUserByEmail($userEmail);

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
        'message'    => 'Tu solicitud para acceder como usuario Premium ha sido revisada y no ha sido aprobada en este momento. Puedes volver a solicitarla más adelante.',
        'type'       => 'warning',
        'link'       => '/profile',
    ]);

    $pdoInmo->commit();

    auditLog($pdoInmo, 'role.promotion.reject', [
        'user_email'    => $userEmail,
        'resource_type' => 'role_promotion_request',
        'resource_id'   => (string) ($request['id'] ?? ''),
        'metadata'      => ['target_user_id' => $userRow ? (int) $userRow['id'] : null]
    ]);

} catch (Throwable $e) {
    if ($pdoInmo instanceof PDO && $pdoInmo->inTransaction()) {
        $pdoInmo->rollBack();
    }

    $errorId = logAndReferenceError('reject_user.db', $e);
    http_response_code(500);
    echo "<h1 style='color:red;font-family:sans-serif;'>Error interno. Referencia: " . htmlspecialchars($errorId, ENT_QUOTES, 'UTF-8') . "</h1>";
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

$subject = 'Solicitud de acceso Premium - Rechazada';

$panelUrl = htmlspecialchars(
    rtrim((string) (getenv('FRONTEND_URL') ?: 'http://localhost:5175'), '/') . '/profile',
    ENT_QUOTES,
    'UTF-8'
);

$body = renderEmailLayout(
    'Solicitud revisada',
    'Resultado de su solicitud de acceso Premium',
    <<<HTML
<div style="display:inline-block;background-color:#fff4e5;color:#b45309;font-size:13px;font-weight:700;padding:8px 14px;border-radius:999px;margin-bottom:20px;">Solicitud no aprobada</div>
<p style="margin:0 0 16px 0;"><strong>Estimado/a,</strong></p>
<p style="margin:0 0 16px 0;">Le agradecemos su interés en formar parte de nuestra plataforma como <strong>usuario Premium</strong>.</p>
<p style="margin:0 0 16px 0;">Tras revisar su solicitud, lamentamos informarle de que en este momento <strong>no ha sido posible aprobar su acceso</strong> bajo dicha condición.</p>
<p style="margin:0 0 16px 0;">Esta decisión responde a nuestros criterios internos de validación y control de perfiles dentro de la plataforma.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0;background-color:#f9fafb;border:1px solid #e5e7eb;border-radius:14px;">
  <tr>
    <td style="padding:20px 22px;">
      <p style="margin:0 0 10px 0;font-size:15px;line-height:1.6;color:#111827;font-weight:700;">Puede seguir utilizando su cuenta actual con normalidad</p>
      <p style="margin:0;font-size:15px;line-height:1.7;color:#4b5563;">Además, si lo desea, podrá volver a solicitar este acceso en el futuro.</p>
    </td>
  </tr>
</table>
<div style="text-align:center;margin:24px 0;">
<a href="{$panelUrl}" target="_blank" rel="noopener" style="background:#0b3d91;color:#ffffff;padding:12px 24px;text-decoration:none;border-radius:8px;font-size:14px;font-weight:bold;display:inline-block;">Acceder a mi cuenta</a>
</div>
<p style="margin:0 0 24px 0;">Quedamos a su disposición para cualquier consulta adicional.</p>
<div style="height:1px;background-color:#e5e7eb;margin:8px 0 22px;"></div>
<p style="margin:0 0 6px 0;font-weight:700;color:#111827;">Atentamente,</p>
<p style="margin:0;color:#4b5563;"><strong>Reglado Real Estate</strong><br>info@regladoconsultores.com</p>
HTML
);

$altBody = trim(
    "Estimado/a,\n\n" .
    "Le agradecemos su interés en formar parte de nuestra plataforma como usuario Premium.\n\n" .
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
    $errorId = logAndReferenceError('reject_user.mail', $e);
    http_response_code(500);
    echo "<h1 style='color:red;font-family:sans-serif;'>La solicitud se rechazó correctamente, pero falló el envío del correo. Referencia: " . htmlspecialchars($errorId, ENT_QUOTES, 'UTF-8') . "</h1>";
}