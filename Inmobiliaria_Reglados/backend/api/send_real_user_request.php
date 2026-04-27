<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once __DIR__ . '/../config/cors.php';
require_once dirname(__DIR__) . '/lib/email_layout.php';
require_once dirname(__DIR__) . '/lib/error_reporting.php';
require_once dirname(__DIR__) . '/lib/audit.php';

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

// Rate limit: máx. 3 solicitudes de promoción a Real por usuario cada 24h.
// Una solicitud legítima se hace una vez y se espera la resolución del admin;
// spam de solicitudes no aporta nada al usuario y solo genera ruido al admin.
try {
    $rlPdo = new PDO(
        sprintf(
            'mysql:host=%s;port=%s;dbname=regladousers;charset=utf8mb4',
            (string) getenv('DB_HOST'),
            (string) getenv('DB_PORT')
        ),
        (string) getenv('DB_USER'),
        (string) getenv('DB_PASS'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );

    $rateScope = 'role_promotion_request';
    $rateKeyHash = hash('sha256', $rateScope . '|' . $userId);
    $rateWindowSeconds = 86400;
    $rateMaxAttempts = 3;

    $rlRead = $rlPdo->prepare('SELECT id, attempts, updated_at FROM rate_limits WHERE key_hash = ? AND scope_name = ? LIMIT 1');
    $rlRead->execute([$rateKeyHash, $rateScope]);
    $rlRow = $rlRead->fetch();

    $nowTs = time();
    $withinWindow = $rlRow && (strtotime((string) $rlRow['updated_at']) ?: 0) >= $nowTs - $rateWindowSeconds;

    if ($withinWindow && (int) $rlRow['attempts'] >= $rateMaxAttempts) {
        respondJson(429, [
            'success' => false,
            'message' => 'Has alcanzado el límite de solicitudes diarias. Espera la revisión de la anterior antes de volver a enviar.',
        ]);
    }

    if (!$rlRow) {
        $rlPdo->prepare('INSERT INTO rate_limits(key_hash, scope_name, attempts, updated_at, created_at) VALUES(?, ?, 1, NOW(), NOW())')
              ->execute([$rateKeyHash, $rateScope]);
    } elseif (!$withinWindow) {
        $rlPdo->prepare('UPDATE rate_limits SET attempts = 1, updated_at = NOW() WHERE id = ?')
              ->execute([(int) $rlRow['id']]);
    } else {
        $rlPdo->prepare('UPDATE rate_limits SET attempts = attempts + 1, updated_at = NOW() WHERE id = ?')
              ->execute([(int) $rlRow['id']]);
    }
} catch (Throwable $e) {
    // Fail-open: el endpoint ya exige JWT válido.
    error_log('[send_real_user_request] rate limit falló: ' . $e->getMessage());
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

$subject = 'Solicitud de Usuario promocionar a Premium';
$recipient = trim((string) getenv('ADMIN_NOTIFICATIONS_EMAIL'));

if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
    respondJson(500, ['success' => false, 'message' => 'No hay un destinatario administrativo configurado para esta notificación.']);
}

$safeFirstName = htmlspecialchars($firstName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeLastName = htmlspecialchars($lastName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeUsername = htmlspecialchars($username, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeEmail = htmlspecialchars($userEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

$token = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $token);

try {
    $stmt = $pdo->prepare("
        INSERT INTO role_promotion_requests (
            user_id,
            user_email,
            first_name,
            last_name,
            username,
            message,
            token_hash,
            status,
            created_at
        ) VALUES (
            :user_id,
            :user_email,
            :first_name,
            :last_name,
            :username,
            :message,
            :token_hash,
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
        ':token_hash' => $tokenHash,
    ]);
} catch (PDOException $e) {
    $errorId = logAndReferenceError('send_real_user_request', $e);
    respondJson(500, [
        'success' => false,
        'message' => 'No se pudo registrar la solicitud. Referencia: ' . $errorId,
    ]);
}

$approveUrl = "http://localhost/Reglado/Inmobiliaria_Reglados/backend/api/approve_real_role.php?token=" . rawurlencode($token);
$rejectUrl = "http://localhost/Reglado/Inmobiliaria_Reglados/backend/api/reject_user.php?token=" . rawurlencode($token);

$body = renderEmailLayout(
    'Nueva solicitud de promoción',
    'Solicitud para promocionar un usuario al rol Premium',
    <<<HTML
<div style="display:inline-block;background-color:#eef4ff;color:#0b3d91;font-size:13px;font-weight:700;padding:8px 14px;border-radius:999px;margin-bottom:20px;">Revisión administrativa</div>

<p style="margin:0 0 18px 0;">Se ha recibido una nueva solicitud para promocionar a un usuario al perfil <strong>Real</strong>.</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e5e7eb;border-radius:16px;background-color:#f9fafb;margin:0 0 24px 0;">
  <tr>
    <td style="padding:24px;">
      <p style="margin:0 0 14px 0;font-size:15px;line-height:1.6;color:#111827;font-weight:700;">Datos del solicitante</p>
      <p style="margin:0 0 10px 0;font-size:15px;line-height:1.6;color:#4b5563;"><strong>Nombre:</strong> {$safeFirstName} {$safeLastName}</p>
      <p style="margin:0 0 10px 0;font-size:15px;line-height:1.6;color:#4b5563;"><strong>Username:</strong> {$safeUsername}</p>
      <p style="margin:0 0 10px 0;font-size:15px;line-height:1.6;color:#4b5563;"><strong>Correo de cuenta:</strong> {$safeEmail}</p>
      <p style="margin:0;font-size:15px;line-height:1.6;color:#4b5563;"><strong>Estado:</strong> Interesado en ser usuario Premium</p>
    </td>
  </tr>
</table>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e5e7eb;border-radius:16px;background-color:#ffffff;margin:0 0 28px 0;">
  <tr>
    <td style="padding:24px;">
      <p style="margin:0 0 12px 0;font-size:15px;line-height:1.6;color:#111827;font-weight:700;">Motivo de la solicitud</p>
      <p style="margin:0;font-size:15px;line-height:1.8;color:#4b5563;">{$safeMessage}</p>
    </td>
  </tr>
</table>

<p style="margin:0 0 18px 0;font-weight:600;text-align:center;color:#374151;">Seleccione una acción para gestionar esta solicitud:</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td align="center" style="padding:0 0 10px 0;">
      <table role="presentation" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td align="center" style="border-radius:10px;background-color:#0b3d91;">
            <a href="{$approveUrl}" style="display:inline-block;padding:14px 24px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:10px;">Aprobar y asignar rol Premium</a>
          </td>
          <td style="width:12px;"></td>
          <td align="center" style="border-radius:10px;background-color:#d32f2f;">
            <a href="{$rejectUrl}" style="display:inline-block;padding:14px 24px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:10px;">Rechazar solicitud</a>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>

<p style="margin:22px 0 0 0;font-size:13px;line-height:1.7;color:#6b7280;text-align:center;">Si los botones no funcionan en su cliente de correo, puede copiar y pegar manualmente los enlaces desde la versión de texto del mensaje.</p>
HTML
);

$altBody = trim(
    "El solicitante: {$firstName} {$lastName}\n" .
    "Username: {$username}\n" .
    "Correo de cuenta: {$userEmail}\n" .
    "Esta interesado en ser usuario Premium.\n" .
    "Motivo: {$message}\n\n" .
    "Enlace para aprobar: {$approveUrl}\n" .
    "Enlace para rechazar: {$rejectUrl}"
);

try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = (string) getenv('SMTP_HOST');
    $mail->SMTPAuth = true;
    $mail->Username = (string) getenv('SMTP_USER');
    $mail->Password = (string) getenv('SMTP_PASS');
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = (int) getenv('SMTP_PORT');

    $mail->setFrom((string) getenv('SMTP_FROM'), (string) getenv('SMTP_FROM_NAME'));
    $mail->addAddress($recipient);

    $replyToName = trim("{$firstName} {$lastName}");
    $mail->addReplyTo($userEmail, $replyToName !== '' ? $replyToName : $userEmail);

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->AltBody = $altBody;

    $mail->send();

    auditLog($pdo, 'role.promotion_requested', array_merge(
        auditContextFromAuth($auth, $userId),
        [
            'resource_type' => 'user',
            'resource_id'   => (string) $userId,
            'metadata'      => [
                'requester_email' => $userEmail,
                'message_length'  => mb_strlen($message),
            ],
        ]
    ));

    respondJson(200, ['success' => true, 'message' => 'Solicitud enviada correctamente']);
} catch (Exception $exception) {
    $errorId = logAndReferenceError('send_real_user_request.mail', $exception);
    respondJson(500, [
        'success' => false,
        'message' => 'No se pudo enviar la solicitud. Referencia: ' . $errorId,
    ]);
}