<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';

applySecurityHeaders();
applyCorsHeaders(['POST', 'OPTIONS']);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondJson(405, ['ok' => false, 'message' => 'Método no permitido.']);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);

if (!is_array($data)) {
    respondJson(400, ['ok' => false, 'message' => 'Datos inválidos.']);
}

$nombre   = trim((string)($data['nombre']   ?? ''));
$email    = trim((string)($data['email']    ?? ''));
$telefono = trim((string)($data['telefono'] ?? ''));
$empresa  = trim((string)($data['empresa']  ?? ''));
$mensaje  = trim((string)($data['mensaje']  ?? ''));

if ($nombre === '' || $email === '' || $mensaje === '') {
    respondJson(422, ['ok' => false, 'message' => 'Faltan campos obligatorios.']);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respondJson(422, ['ok' => false, 'message' => 'El email no es válido.']);
}

if (mb_strlen($nombre) > 100 || mb_strlen($email) > 150 || mb_strlen($mensaje) > 3000) {
    respondJson(422, ['ok' => false, 'message' => 'Algún campo supera la longitud permitida.']);
}

try {
    $pdo = getPdo();
    $stmt = $pdo->prepare(
        'INSERT INTO consultas (nombre, email, telefono, empresa, mensaje) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$nombre, $email, $telefono, $empresa, $mensaje]);
} catch (Exception $e) {
    error_log('CONTACT_DB_ERROR: ' . $e->getMessage());
    respondJson(500, ['ok' => false, 'message' => 'Error al guardar la consulta.']);
}

sendNotificationEmail($nombre, $email, $telefono, $empresa, $mensaje);

respondJson(200, ['ok' => true, 'message' => 'Consulta recibida correctamente.']);

function sendNotificationEmail(string $nombre, string $email, string $telefono, string $empresa, string $mensaje): void
{
    $mailTo       = getenv('MAIL_TO')        ?: 'info@regladoingenieria.com';
    $mailFrom     = getenv('MAIL_FROM')      ?: 'info@regladoingenieria.com';
    $mailFromName = getenv('MAIL_FROM_NAME') ?: 'Reglado Ingeniería';

    $subject = '=?UTF-8?B?' . base64_encode('Nueva consulta de ' . $nombre) . '?=';

    $html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head><body style="font-family:Arial,sans-serif;color:#1a1f2e">'
        . '<h2 style="color:#4a9eff">Nueva consulta — Reglado Ingeniería</h2>'
        . '<table style="border-collapse:collapse;width:100%">'
        . tableRow('Nombre', htmlspecialchars($nombre))
        . tableRow('Email', htmlspecialchars($email))
        . tableRow('Teléfono', htmlspecialchars($telefono ?: '—'))
        . tableRow('Empresa', htmlspecialchars($empresa ?: '—'))
        . tableRow('Mensaje', nl2br(htmlspecialchars($mensaje)))
        . '</table></body></html>';

    $boundary = 'boundary_' . bin2hex(random_bytes(8));
    $headers = implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        'From: ' . $mailFromName . ' <' . $mailFrom . '>',
        'Reply-To: ' . $email,
        'X-Mailer: PHP/' . PHP_VERSION,
    ]);

    $body = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n"
        . "Nueva consulta de: {$nombre}\nEmail: {$email}\nTeléfono: {$telefono}\nEmpresa: {$empresa}\n\nMensaje:\n{$mensaje}\r\n"
        . "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n{$html}\r\n"
        . "--{$boundary}--";

    @mail($mailTo, $subject, $body, $headers);
}

function tableRow(string $label, string $value): string
{
    return '<tr><td style="padding:8px 12px;border:1px solid #e0e4ea;font-weight:600;width:120px">'
        . $label . '</td><td style="padding:8px 12px;border:1px solid #e0e4ea">' . $value . '</td></tr>';
}
