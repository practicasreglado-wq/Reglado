<?php
declare(strict_types=1);

$relative = $_GET['file'] ?? '';
$relative = trim((string) $relative);

if ($relative === '') {
    http_response_code(400);
    exit('Archivo no indicado.');
}

$relative = str_replace('\\', '/', $relative);
$relative = ltrim($relative, '/');
$relative = str_replace('../', '', $relative);

$baseDir = realpath(__DIR__ . '/../uploads');

if ($baseDir === false) {
    http_response_code(500);
    exit('Directorio base no disponible.');
}

$filePath = realpath($baseDir . DIRECTORY_SEPARATOR . $relative);

if ($filePath === false || !is_file($filePath)) {
    http_response_code(404);
    exit('Archivo no encontrado.');
}

if (!str_starts_with($filePath, $baseDir)) {
    http_response_code(403);
    exit('Acceso denegado.');
}

$filename = basename($filePath);
$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

$contentType = match ($extension) {
    'pdf' => 'application/pdf',
    default => 'application/octet-stream',
};

if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Description: File Transfer');
header('Content-Type: ' . $contentType);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: public');
header('Expires: 0');
header('Content-Length: ' . (string) filesize($filePath));

readfile($filePath);
exit;