<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/env_loader.php';
require_once dirname(__DIR__) . '/lib/notifications_helper.php';
require_once dirname(__DIR__) . '/lib/audit.php';
require_once dirname(__DIR__) . '/lib/email_layout.php';
require_once dirname(__DIR__) . '/lib/error_reporting.php';

loadEnv(dirname(__DIR__) . '/.env');

$token = trim((string) ($_GET['token'] ?? ''));

if ($token === '') {
    http_response_code(400);
    echo "<h1 style='color:red;font-family:sans-serif;'>Error: Token no válido.</h1>";
    exit;
}

$host = (string) getenv('DB_HOST');
$port = (string) getenv('DB_PORT');
$user = (string) getenv('DB_USER');
$pass = (string) getenv('DB_PASS');

$pdoInmo = null;
$pdoAuth = null;
$stmtUpdateUserRowCount = 0;

try {
    $pdoInmo = new PDO(
        "mysql:host={$host};port={$port};dbname=inmobiliaria;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $pdoAuth = new PDO(
        "mysql:host={$host};port={$port};dbname=regladousers;charset=utf8mb4",
        $user,
        $pass,
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

    $email = $request ? (string) $request['user_email'] : '';

    if (!$request) {
        $pdoAuth->rollBack();
        $pdoInmo->rollBack();

        echo "<h1 style='color:orange;font-family:sans-serif;'>Esta solicitud ya ha sido procesada o el enlace no es válido.</h1>";
        exit;
    }

    $stmtUser = $pdoAuth->prepare("
        SELECT id, role, email
        FROM users
        WHERE email = ?
        LIMIT 1
    ");
    $stmtUser->execute([$email]);
    $userRow = $stmtUser->fetch();

    if (!$userRow) {
        $pdoAuth->rollBack();
        $pdoInmo->rollBack();

        http_response_code(404);
        echo "<h1 style='color:red;font-family:sans-serif;'>No se ha encontrado el usuario en la base de datos de autenticación.</h1>";
        exit;
    }

    $stmtUpdateUser = $pdoAuth->prepare("
        UPDATE users
        SET role = 'real'
        WHERE email = ?
    ");
    $stmtUpdateUser->execute([$email]);
    $stmtUpdateUserRowCount = $stmtUpdateUser->rowCount();

    $stmtMarkResolved = $pdoInmo->prepare("
        UPDATE role_promotion_requests
        SET status = 'approved',
            resolved_at = NOW()
        WHERE id = ?
    ");
    $stmtMarkResolved->execute([$request['id']]);

    createUserNotification($pdoInmo, [
        'user_id'    => (int) $userRow['id'],
        'user_email' => $email,
        'title'      => 'Solicitud aprobada',
        'message'    => 'Tu solicitud para acceder como usuario Premium ha sido aprobada. Ya puedes acceder a las funciones habilitadas para este perfil.',
        'type'       => 'success',
        'link'       => '/profile',
    ]);

    $pdoAuth->commit();
    $pdoInmo->commit();

    auditLog($pdoInmo, 'role.promotion.approve', [
        'user_email'    => $email,
        'resource_type' => 'role_promotion_request',
        'resource_id'   => (string) ($request['id'] ?? ''),
        'metadata'      => ['target_user_id' => (int) ($userRow['id'] ?? 0)]
    ]);

} catch (Throwable $e) {
    if ($pdoAuth instanceof PDO && $pdoAuth->inTransaction()) {
        $pdoAuth->rollBack();
    }

    if ($pdoInmo instanceof PDO && $pdoInmo->inTransaction()) {
        $pdoInmo->rollBack();
    }

    $errorId = logAndReferenceError('approve_real_role.db', $e);
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

$subject = 'Solicitud de acceso Premium - Aprobada';

$panelUrl = htmlspecialchars(
    rtrim((string) (getenv('FRONTEND_URL') ?: 'http://localhost:5175'), '/') . '/dashboard',
    ENT_QUOTES,
    'UTF-8'
);

$body = renderEmailLayout(
    'Solicitud aprobada',
    'Su acceso como usuario Premium ha sido activado correctamente',
    <<<HTML
<div style="display:inline-block;background-color:#e9f9ef;color:#1f7a3d;font-size:13px;font-weight:700;padding:8px 14px;border-radius:999px;margin-bottom:20px;">Acceso aprobado</div>
<p style="margin:0 0 16px 0;"><strong>Estimado/a,</strong></p>
<p style="margin:0 0 16px 0;">Nos complace informarle de que su solicitud para acceder como <strong>usuario Premium</strong> ha sido <strong>aprobada correctamente</strong>.</p>
<p style="margin:0 0 16px 0;">A partir de este momento ya puede acceder a las funcionalidades habilitadas para este perfil dentro de la plataforma.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0;background-color:#f9fafb;border:1px solid #e5e7eb;border-radius:14px;">
  <tr>
    <td style="padding:20px 22px;">
      <p style="margin:0 0 10px 0;font-size:15px;line-height:1.6;color:#111827;font-weight:700;">¿Qué puede hacer ahora?</p>
      <p style="margin:0;font-size:15px;line-height:1.7;color:#4b5563;">Ya puede iniciar sesión y utilizar las opciones y accesos reservados para usuarios reales dentro de su cuenta.</p>
    </td>
  </tr>
</table>
<div style="text-align:center;margin:24px 0;">
<a href="{$panelUrl}" target="_blank" rel="noopener" style="background:#0b3d91;color:#ffffff;padding:12px 24px;text-decoration:none;border-radius:8px;font-size:14px;font-weight:bold;display:inline-block;">Acceder al panel</a>
</div>
<p style="margin:0 0 24px 0;">Si necesita ayuda o desea realizar cualquier consulta, nuestro equipo estará encantado de atenderle.</p>
<div style="height:1px;background-color:#e5e7eb;margin:8px 0 22px;"></div>
<p style="margin:0 0 6px 0;font-weight:700;color:#111827;">Atentamente,</p>
<p style="margin:0;color:#4b5563;"><strong>Reglado Real Estate</strong><br>info@regladoconsultores.com</p>
HTML
);

$altBody = trim(
    "Estimado/a,\n\n" .
    "Nos complace informarle de que su solicitud para acceder como usuario Premium ha sido aprobada correctamente.\n\n" .
    "A partir de este momento ya puede acceder a las funcionalidades habilitadas para este perfil dentro de la plataforma.\n\n" .
    "Si necesita ayuda o desea realizar cualquier consulta, puede ponerse en contacto con nosotros en info@regladoconsultores.com.\n\n" .
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
    $mail->addAddress($email);
    $mail->addReplyTo((string) getenv('SMTP_FROM'), (string) getenv('SMTP_FROM_NAME'));

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->AltBody = $altBody;

    $mail->send();

    $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

    echo "<div style='font-family:sans-serif;text-align:center;margin-top:50px;'>";
    if ($stmtUpdateUserRowCount > 0) {
        echo "<h1 style='color:#4CAF50;'>Solicitud aprobada correctamente</h1>";
        echo "<p>El usuario con correo <strong>{$safeEmail}</strong> ha sido actualizado al rol Premium.</p>";
    } else {
        echo "<h1 style='color:#2563eb;'>Solicitud procesada correctamente</h1>";
        echo "<p>El usuario <strong>{$safeEmail}</strong> ya tenía el rol Premium, pero la solicitud ha quedado resuelta.</p>";
    }
    echo "<p>Además, se ha creado una notificación interna y se ha enviado el correo de aprobación al usuario.</p>";
    echo "</div>";

} catch (Throwable $e) {
    $errorId = logAndReferenceError('approve_real_role.mail', $e);
    http_response_code(500);
    echo "<h1 style='color:red;font-family:sans-serif;'>La solicitud se aprobó correctamente, pero falló el envío del correo. Referencia: " . htmlspecialchars($errorId, ENT_QUOTES, 'UTF-8') . "</h1>";
}