<?php

declare(strict_types=1);

/*
<!--Envío de Formulario de contacto a Reglado Energy -->

<!-- Se envía la solicitud a la BBDD y al correo formulario@regladoenergy.com (se puede cambiar el correo) -->
*/

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';

$mailTo = getenv('CONTACT_MAIL_TO') ?: 'info@regladoenergy.com';
$mailFrom = getenv('CONTACT_MAIL_FROM') ?: 'info@regladoenergy.com';

applySecurityHeaders();
enforceProductionSecurity();
applyCorsHeaders(['POST', 'OPTIONS'], 'Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['ok' => false, 'message' => 'Metodo no permitido.']);
}

$nombre = trim((string) ($_POST['nombre'] ?? ''));
$telefono = trim((string) ($_POST['telefono'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$mensaje = trim((string) ($_POST['mensaje'] ?? ''));

if ($nombre === '' || $telefono === '' || $email === '') {
    respond(422, ['ok' => false, 'message' => 'Nombre, telefono y email son obligatorios.']);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(422, ['ok' => false, 'message' => 'El email no es valido.']);
}

if (!preg_match('/^\d{9}$/', $telefono)) {
    respond(422, ['ok' => false, 'message' => 'El telefono debe tener exactamente 9 digitos numericos.']);
}

$uploadAbsolutePath = null;
$uploadRelativePath = null;
$pdfOriginalName = null;
$pdfMime = null;
$pdfSize = null;

if (isset($_FILES['pdf']) && (int) $_FILES['pdf']['error'] !== UPLOAD_ERR_NO_FILE) {
    $pdf = $_FILES['pdf'];

    if ((int) $pdf['error'] !== UPLOAD_ERR_OK) {
        respond(400, ['ok' => false, 'message' => 'No se pudo subir el archivo de factura.']);
    }

    $maxBytes = 10 * 1024 * 1024;
    $fileSize = (int) $pdf['size'];
    if ($fileSize > $maxBytes) {
        respond(422, ['ok' => false, 'message' => 'El archivo supera el limite de 10 MB.']);
    }

    $tmpPath = (string) $pdf['tmp_name'];
    if (!is_uploaded_file($tmpPath)) {
        respond(400, ['ok' => false, 'message' => 'Archivo de subida invalido.']);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmpPath);
    $originalName = (string) ($pdf['name'] ?? '');
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    $allowedMimeMap = [
        'application/pdf' => ['pdf'],
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
        'image/gif' => ['gif'],
        'image/bmp' => ['bmp'],
        'image/tiff' => ['tif', 'tiff'],
        'image/heic' => ['heic'],
        'image/heif' => ['heif'],
    ];

    if (!isset($allowedMimeMap[$mime])) {
        respond(422, ['ok' => false, 'message' => 'Solo se permiten archivos PDF o imagen.']);
    }

    if ($extension === '' || !in_array($extension, $allowedMimeMap[$mime], true)) {
        respond(422, ['ok' => false, 'message' => 'La extension del archivo no coincide con su tipo real.']);
    }

    $uploadsDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
    if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0755, true) && !is_dir($uploadsDir)) {
        throw new RuntimeException('No se pudo crear la carpeta de uploads.');
    }

    $generatedFileName = date('Ymd_His') . '_' . bin2hex(random_bytes(16)) . '.' . extensionFromMime($mime);
    $absolutePath = $uploadsDir . DIRECTORY_SEPARATOR . $generatedFileName;

    if (!move_uploaded_file($tmpPath, $absolutePath)) {
        respond(500, ['ok' => false, 'message' => 'No se pudo guardar el archivo en el servidor.']);
    }

    $uploadAbsolutePath = $absolutePath;
    $uploadRelativePath = 'uploads/' . $generatedFileName;
    $pdfOriginalName = sanitizeStoredFileName($originalName !== '' ? $originalName : $generatedFileName);
    $pdfMime = $mime;
    $pdfSize = $fileSize;
}

try {
    $pdo = getPdo();

    $statement = $pdo->prepare(
        'INSERT INTO facturas (
            nombre,
            telefono,
            email,
            mensaje,
            pdf_nombre_original,
            pdf_ruta,
            pdf_mime,
            pdf_tamano_bytes
        ) VALUES (
            :nombre,
            :telefono,
            :email,
            :mensaje,
            :pdf_nombre_original,
            :pdf_ruta,
            :pdf_mime,
            :pdf_tamano_bytes
        )'
    );

    $statement->execute([
        ':nombre' => $nombre,
        ':telefono' => $telefono,
        ':email' => $email,
        ':mensaje' => $mensaje === '' ? null : $mensaje,
        ':pdf_nombre_original' => $pdfOriginalName,
        ':pdf_ruta' => $uploadRelativePath,
        ':pdf_mime' => $pdfMime,
        ':pdf_tamano_bytes' => $pdfSize,
    ]);

    $insertedId = (int) $pdo->lastInsertId();
    $mailSent = sendNotificationEmail(
        $mailTo,
        $mailFrom,
        $nombre,
        $telefono,
        $email,
        $mensaje,
        $insertedId,
        $uploadAbsolutePath,
        $pdfOriginalName,
        $pdfMime
    );

    if ($mailSent) {
        respond(201, [
            'ok' => true,
            'message' => 'Solicitud guardada y enviada al correo del equipo Reglado Energy.',
            'id' => $insertedId,
            'mail_sent' => true,
        ]);
    }

    error_log('No se pudo enviar el correo de notificacion para la solicitud ID ' . $insertedId);
    respond(201, [
        'ok' => true,
        'message' => 'Solicitud guardada, pero no se pudo enviar el correo del equipo Reglado Energy.',
        'id' => $insertedId,
        'mail_sent' => false,
    ]);
} catch (Throwable $exception) {
    if ($uploadAbsolutePath !== null && is_file($uploadAbsolutePath)) {
        @unlink($uploadAbsolutePath);
    }

    error_log('CONTACT_BACKEND_ERROR ip=' . getClientIpAddress() . ' message=' . $exception->getMessage());
    respond(500, [
        'ok' => false,
        'message' => 'Error interno guardando la solicitud.',
    ]);
}

function respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function sendNotificationEmail(
    string $to,
    string $from,
    string $nombre,
    string $telefono,
    string $email,
    string $mensaje,
    int $solicitudId,
    ?string $pdfAbsolutePath,
    ?string $pdfOriginalName,
    ?string $pdfMime
): bool {
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log('CONTACT_MAIL_TO no es un email valido: ' . $to);
        return false;
    }

    $safeFrom = sanitizeHeaderValue($from);
    if (!filter_var($safeFrom, FILTER_VALIDATE_EMAIL)) {
        $safeFrom = 'info@regladoenergy.com';
    }

    $safeReplyTo = sanitizeHeaderValue($email);
    if (!filter_var($safeReplyTo, FILTER_VALIDATE_EMAIL)) {
        $safeReplyTo = $safeFrom;
    }

    $subject = sprintf('Nueva solicitud de contacto #%d', $solicitudId);
    $mixedBoundary = 'mixed_' . bin2hex(random_bytes(12));
    $alternativeBoundary = 'alt_' . bin2hex(random_bytes(12));
    $timestamp = date('Y-m-d H:i:s');

    $textBody = implode("\r\n", [
        'Nueva solicitud recibida desde el formulario web.',
        '',
        'ID: ' . $solicitudId,
        'Nombre: ' . $nombre,
        'Telefono: ' . $telefono,
        'Email: ' . $email,
        'Factura adjuntada: ' . (($pdfAbsolutePath !== null && is_file($pdfAbsolutePath)) ? 'SI' : 'NO'),
        'Mensaje: ' . ($mensaje !== '' ? $mensaje : '(sin mensaje)'),
        'Fecha: ' . $timestamp,
    ]);
    $htmlBody = buildNotificationHtmlEmail(
        $solicitudId,
        $nombre,
        $telefono,
        $email,
        $mensaje,
        $timestamp,
        $pdfAbsolutePath !== null && is_file($pdfAbsolutePath)
    );
    $messageIdDomain = strtolower((string) preg_replace('/^.*@/', '', $safeFrom));
    if ($messageIdDomain === '') {
        $messageIdDomain = 'regladoenergy.com';
    }

    $headers = [
        'MIME-Version: 1.0',
        'From: Reglado Energy <' . $safeFrom . '>',
        'Reply-To: ' . $safeReplyTo,
        'Date: ' . date(DATE_RFC2822),
        'Message-ID: <contact-' . $solicitudId . '-' . bin2hex(random_bytes(8)) . '@' . $messageIdDomain . '>',
        'Content-Type: multipart/mixed; boundary="' . $mixedBoundary . '"',
        'X-Mailer: PHP/' . PHP_VERSION,
    ];

    $mailBody = '--' . $mixedBoundary . "\r\n";
    $mailBody .= 'Content-Type: multipart/alternative; boundary="' . $alternativeBoundary . '"' . "\r\n\r\n";

    $mailBody .= '--' . $alternativeBoundary . "\r\n";
    $mailBody .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $mailBody .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $mailBody .= $textBody . "\r\n\r\n";

    $mailBody .= '--' . $alternativeBoundary . "\r\n";
    $mailBody .= "Content-Type: text/html; charset=UTF-8\r\n";
    $mailBody .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $mailBody .= $htmlBody . "\r\n\r\n";

    $mailBody .= '--' . $alternativeBoundary . "--\r\n";

    if ($pdfAbsolutePath !== null && is_file($pdfAbsolutePath)) {
        $pdfContent = file_get_contents($pdfAbsolutePath);

        if ($pdfContent !== false) {
            $filename = $pdfOriginalName !== null && $pdfOriginalName !== ''
                ? $pdfOriginalName
                : basename($pdfAbsolutePath);

            $safeFilename = sanitizeHeaderValue($filename);
            $attachmentMime = $pdfMime !== null && $pdfMime !== '' ? $pdfMime : 'application/octet-stream';

            $mailBody .= '--' . $mixedBoundary . "\r\n";
            $mailBody .= 'Content-Type: ' . $attachmentMime . '; name="' . $safeFilename . '"' . "\r\n";
            $mailBody .= "Content-Transfer-Encoding: base64\r\n";
            $mailBody .= 'Content-Disposition: attachment; filename="' . $safeFilename . '"' . "\r\n\r\n";
            $mailBody .= chunk_split(base64_encode($pdfContent));
        }
    }

    $mailBody .= '--' . $mixedBoundary . "--\r\n";

    $headerString = implode("\r\n", $headers);
    $smtpConfig = getSmtpConfig();

    if ($smtpConfig !== null) {
        $sent = sendMailViaSmtp($smtpConfig, $safeFrom, $to, $safeReplyTo, $subject, $headerString, $mailBody);
    } else {
        $sent = @mail($to, $subject, $mailBody, $headerString);
    }

    if (!$sent) {
        error_log('No se pudo enviar el correo para la solicitud ID ' . $solicitudId);
    }

    return $sent;
}

function getSmtpConfig(): ?array
{
    $host = trim((string) (getenv('SMTP_HOST') ?: ''));
    if ($host === '') {
        return null;
    }

    $port = (int) (getenv('SMTP_PORT') ?: 587);
    $secure = strtolower(trim((string) (getenv('SMTP_SECURE') ?: 'tls')));
    $username = trim((string) (getenv('SMTP_USER') ?: ''));
    $password = (string) (getenv('SMTP_PASS') ?: '');
    $timeout = (int) (getenv('SMTP_TIMEOUT') ?: 15);

    if ($port <= 0) {
        $port = 587;
    }

    if (!in_array($secure, ['tls', 'ssl', 'none'], true)) {
        $secure = 'tls';
    }

    return [
        'host' => $host,
        'port' => $port,
        'secure' => $secure,
        'username' => $username,
        'password' => $password,
        'timeout' => max(5, $timeout),
    ];
}

function sendMailViaSmtp(
    array $smtpConfig,
    string $from,
    string $to,
    string $replyTo,
    string $subject,
    string $headerString,
    string $body
): bool {
    $transportHost = $smtpConfig['host'];
    if ($smtpConfig['secure'] === 'ssl') {
        $transportHost = 'ssl://' . $transportHost;
    }

    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false,
        ],
    ]);

    $socket = @stream_socket_client(
        $transportHost . ':' . $smtpConfig['port'],
        $errorCode,
        $errorMessage,
        $smtpConfig['timeout'],
        STREAM_CLIENT_CONNECT,
        $context
    );

    if (!is_resource($socket)) {
        error_log('SMTP connect failed: ' . $errorMessage . ' (' . $errorCode . ')');
        return false;
    }

    stream_set_timeout($socket, $smtpConfig['timeout']);

    try {
        smtpExpect($socket, [220]);
        smtpCommand($socket, 'EHLO regladoenergy.com', [250]);

        if ($smtpConfig['secure'] === 'tls') {
            smtpCommand($socket, 'STARTTLS', [220]);

            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('No se pudo activar STARTTLS.');
            }

            smtpCommand($socket, 'EHLO regladoenergy.com', [250]);
        }

        if ($smtpConfig['username'] !== '') {
            smtpCommand($socket, 'AUTH LOGIN', [334]);
            smtpCommand($socket, base64_encode($smtpConfig['username']), [334]);
            smtpCommand($socket, base64_encode($smtpConfig['password']), [235]);
        }

        smtpCommand($socket, 'MAIL FROM:<' . $from . '>', [250]);
        smtpCommand($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
        smtpCommand($socket, 'DATA', [354]);

        $message = buildSmtpMessage($from, $to, $replyTo, $subject, $headerString, $body);
        fwrite($socket, $message . "\r\n.\r\n");
        smtpExpect($socket, [250]);
        smtpCommand($socket, 'QUIT', [221]);

        return true;
    } catch (Throwable $exception) {
        error_log('SMTP send failed: ' . $exception->getMessage());
        return false;
    } finally {
        fclose($socket);
    }
}

function buildSmtpMessage(
    string $from,
    string $to,
    string $replyTo,
    string $subject,
    string $headerString,
    string $body
): string {
    $headers = explode("\r\n", $headerString);
    $filteredHeaders = [];

    foreach ($headers as $header) {
        $normalized = strtolower($header);
        if (str_starts_with($normalized, 'from:')
            || str_starts_with($normalized, 'reply-to:')
        ) {
            continue;
        }

        $filteredHeaders[] = $header;
    }

    array_unshift($filteredHeaders, 'Reply-To: ' . $replyTo);
    array_unshift($filteredHeaders, 'From: Reglado Energy <' . $from . '>');
    array_unshift($filteredHeaders, 'To: ' . $to);
    array_unshift($filteredHeaders, 'Subject: ' . encodeMimeHeader($subject));

    return implode("\r\n", $filteredHeaders) . "\r\n\r\n" . normalizeSmtpBody($body);
}

function normalizeSmtpBody(string $body): string
{
    $body = str_replace(["\r\n", "\r"], "\n", $body);
    $lines = explode("\n", $body);

    foreach ($lines as &$line) {
        if (str_starts_with($line, '.')) {
            $line = '.' . $line;
        }
    }

    return implode("\r\n", $lines);
}

function smtpCommand($socket, string $command, array $expectedCodes): string
{
    fwrite($socket, $command . "\r\n");
    return smtpExpect($socket, $expectedCodes);
}

function smtpExpect($socket, array $expectedCodes): string
{
    $response = '';

    while (($line = fgets($socket, 515)) !== false) {
        $response .= $line;

        if (strlen($line) < 4) {
            continue;
        }

        if ($line[3] === ' ') {
            break;
        }
    }

    if ($response === '') {
        throw new RuntimeException('Sin respuesta del servidor SMTP.');
    }

    $code = (int) substr($response, 0, 3);
    if (!in_array($code, $expectedCodes, true)) {
        throw new RuntimeException('SMTP response ' . $code . ': ' . trim($response));
    }

    return $response;
}

function encodeMimeHeader(string $value): string
{
    if (preg_match('/^[\x20-\x7E]*$/', $value) === 1) {
        return $value;
    }

    return '=?UTF-8?B?' . base64_encode($value) . '?=';
}

function sanitizeHeaderValue(string $value): string
{
    return str_replace(["\r", "\n"], '', trim($value));
}

function sanitizeStoredFileName(string $value): string
{
    $sanitized = preg_replace('/[^A-Za-z0-9._-]/', '_', $value);
    $sanitized = is_string($sanitized) ? trim($sanitized, '._') : '';
    return $sanitized !== '' ? $sanitized : 'adjunto.bin';
}

function buildNotificationHtmlEmail(
    int $solicitudId,
    string $nombre,
    string $telefono,
    string $email,
    string $mensaje,
    string $timestamp,
    bool $hasAttachment
): string {
    $safeNombre = escapeHtml($nombre);
    $safeTelefono = escapeHtml($telefono);
    $safeEmail = escapeHtml($email);
    $safeMensaje = nl2br(escapeHtml($mensaje !== '' ? $mensaje : '(sin mensaje)'), false);
    $safeTimestamp = escapeHtml($timestamp);
    $attachmentStatusHtml = $hasAttachment
        ? '<span style="color:#1f8f4e;font-weight:bold;">&#10003; SI</span>'
        : '<span style="color:#c0392b;font-weight:bold;">&#10007; NO</span>';

    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nueva solicitud de contacto</title>
</head>
<body style="margin:0;padding:24px;background-color:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#0b0d10;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:680px;border-collapse:collapse;background-color:#ffffff;border:1px solid #d9e0e8;border-radius:18px;overflow:hidden;">
          <tr>
            <td style="padding:24px 32px;background:linear-gradient(135deg,#0b0d10 0%,#1b5c8e 100%);text-align:center;">
              <div style="font-size:12px;letter-spacing:2px;font-weight:bold;color:#f2c53d;">REGLADO ENERGY</div>
              <div style="margin-top:8px;font-size:24px;line-height:1.3;font-weight:bold;color:#ffffff;">Nueva solicitud de contacto</div>
            </td>
          </tr>
          <tr>
            <td style="padding:28px 32px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                <tr>
                  <td style="padding:0 0 18px 0;font-size:14px;line-height:1.6;color:#445062;">
                    Se ha recibido una nueva solicitud desde el formulario web.
                  </td>
                </tr>
              </table>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:separate;border-spacing:0;background:#fbfcfd;border:1px solid #e5eaef;border-radius:14px;">
                <tr>
                  <td style="padding:14px 18px;width:160px;font-size:13px;font-weight:bold;color:#1b5c8e;border-bottom:1px solid #e5eaef;">ID</td>
                  <td style="padding:14px 18px;font-size:14px;color:#0b0d10;border-bottom:1px solid #e5eaef;">#$solicitudId</td>
                </tr>
                <tr>
                  <td style="padding:14px 18px;width:160px;font-size:13px;font-weight:bold;color:#1b5c8e;border-bottom:1px solid #e5eaef;">Nombre</td>
                  <td style="padding:14px 18px;font-size:14px;color:#0b0d10;border-bottom:1px solid #e5eaef;">{$safeNombre}</td>
                </tr>
                <tr>
                  <td style="padding:14px 18px;width:160px;font-size:13px;font-weight:bold;color:#1b5c8e;border-bottom:1px solid #e5eaef;">Telefono</td>
                  <td style="padding:14px 18px;font-size:14px;color:#0b0d10;border-bottom:1px solid #e5eaef;">{$safeTelefono}</td>
                </tr>
                <tr>
                  <td style="padding:14px 18px;width:160px;font-size:13px;font-weight:bold;color:#1b5c8e;border-bottom:1px solid #e5eaef;">Email</td>
                  <td style="padding:14px 18px;font-size:14px;color:#0b0d10;border-bottom:1px solid #e5eaef;">{$safeEmail}</td>
                </tr>
                <tr>
                  <td style="padding:14px 18px;width:160px;font-size:13px;font-weight:bold;color:#1b5c8e;border-bottom:1px solid #e5eaef;">Fecha</td>
                  <td style="padding:14px 18px;font-size:14px;color:#0b0d10;border-bottom:1px solid #e5eaef;">{$safeTimestamp}</td>
                </tr>
                <tr>
                  <td style="padding:14px 18px;width:160px;font-size:13px;font-weight:bold;color:#1b5c8e;border-bottom:1px solid #e5eaef;">Factura adjuntada</td>
                  <td style="padding:14px 18px;font-size:14px;color:#0b0d10;border-bottom:1px solid #e5eaef;">{$attachmentStatusHtml}</td>
                </tr>
                <tr>
                  <td style="padding:14px 18px;width:160px;font-size:13px;font-weight:bold;color:#1b5c8e;vertical-align:top;">Mensaje</td>
                  <td style="padding:14px 18px;font-size:14px;line-height:1.7;color:#0b0d10;">{$safeMensaje}</td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding:18px 32px;background-color:#0b0d10;border-top:4px solid #f2c53d;font-size:12px;line-height:1.6;color:#c8d1dc;text-align:center;">
              Notificacion automatica del formulario web de Reglado Energy.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
}

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function extensionFromMime(string $mime): string
{
    return match ($mime) {
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/bmp' => 'bmp',
        'image/tiff' => 'tiff',
        'image/heic' => 'heic',
        'image/heif' => 'heif',
        default => 'bin',
    };
}
