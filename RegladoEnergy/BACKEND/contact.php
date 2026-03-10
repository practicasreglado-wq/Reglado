<?php

declare(strict_types=1);

/*
<!--Envío de Formulario de contacto a Reglado Energy -->

<!-- Se envía la solicitud a la BBDD y al correo formulario@regladoenergy.com (se puede cambiar el correo) -->
*/

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';

$mailTo = getenv('CONTACT_MAIL_TO') ?: 'formulario@regladoenergy.com';
$mailFrom = getenv('CONTACT_MAIL_FROM') ?: 'no-reply@regladoenergy.com';

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
        $safeFrom = 'no-reply@regladoenergy.com';
    }

    $safeReplyTo = sanitizeHeaderValue($email);
    if (!filter_var($safeReplyTo, FILTER_VALIDATE_EMAIL)) {
        $safeReplyTo = $safeFrom;
    }

    $subject = sprintf('Nueva solicitud de contacto #%d', $solicitudId);
    $boundary = 'boundary_' . bin2hex(random_bytes(12));

    $textBody = implode("\r\n", [
        'Nueva solicitud recibida desde el formulario web.',
        '',
        'ID: ' . $solicitudId,
        'Nombre: ' . $nombre,
        'Telefono: ' . $telefono,
        'Email: ' . $email,
        'Mensaje: ' . ($mensaje !== '' ? $mensaje : '(sin mensaje)'),
        'Fecha: ' . date('Y-m-d H:i:s'),
    ]);

    $headers = [
        'MIME-Version: 1.0',
        'From: Reglado Energy <' . $safeFrom . '>',
        'Reply-To: ' . $safeReplyTo,
        'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
        'X-Mailer: PHP/' . PHP_VERSION,
    ];

    $mailBody = '--' . $boundary . "\r\n";
    $mailBody .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $mailBody .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $mailBody .= $textBody . "\r\n";

    if ($pdfAbsolutePath !== null && is_file($pdfAbsolutePath)) {
        $pdfContent = file_get_contents($pdfAbsolutePath);

        if ($pdfContent !== false) {
            $filename = $pdfOriginalName !== null && $pdfOriginalName !== ''
                ? $pdfOriginalName
                : basename($pdfAbsolutePath);

            $safeFilename = sanitizeHeaderValue($filename);
            $attachmentMime = $pdfMime !== null && $pdfMime !== '' ? $pdfMime : 'application/octet-stream';

            $mailBody .= '--' . $boundary . "\r\n";
            $mailBody .= 'Content-Type: ' . $attachmentMime . '; name="' . $safeFilename . '"' . "\r\n";
            $mailBody .= "Content-Transfer-Encoding: base64\r\n";
            $mailBody .= 'Content-Disposition: attachment; filename="' . $safeFilename . '"' . "\r\n\r\n";
            $mailBody .= chunk_split(base64_encode($pdfContent));
        }
    }

    $mailBody .= '--' . $boundary . "--\r\n";

    $sent = @mail($to, $subject, $mailBody, implode("\r\n", $headers));
    if (!$sent) {
        error_log('mail() devolvio false para la solicitud ID ' . $solicitudId);
    }

    return $sent;
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
