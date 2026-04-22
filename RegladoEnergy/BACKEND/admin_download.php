<?php

declare(strict_types=1);

// Genera y descarga un ZIP con datos generales y carpetas por cliente (CSV propio + archivos de factura).
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

applySecurityHeaders();
enforceProductionSecurity();
applyCorsHeaders(['GET', 'POST', 'OPTIONS'], 'Content-Type, Authorization', false);
header('Access-Control-Expose-Headers: Content-Disposition');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    respondJson(405, ['ok' => false, 'message' => 'Metodo no permitido.']);
}

requireAdminAuth();

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput ?: '', true);

$ids = [];
if (is_array($payload) && isset($payload['ids']) && is_array($payload['ids'])) {
    foreach ($payload['ids'] as $id) {
        $intId = (int) $id;
        if ($intId > 0) {
            $ids[] = $intId;
        }
    }
}

$ids = array_values(array_unique($ids));

if (count($ids) === 0) {
    respondJson(422, ['ok' => false, 'message' => 'Selecciona al menos una solicitud.']);
}

if (count($ids) > 200) {
    respondJson(422, ['ok' => false, 'message' => 'Has seleccionado demasiadas solicitudes.']);
}

if (!class_exists('ZipArchive')) {
    respondJson(500, ['ok' => false, 'message' => 'ZipArchive no esta disponible en PHP.']);
}

try {
    $pdo = getPdo();
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $statement = $pdo->prepare(
        'SELECT
            id,
            nombre,
            telefono,
            email,
            mensaje,
            pdf_nombre_original,
            pdf_ruta,
            creado_en
        FROM facturas
        WHERE id IN (' . $placeholders . ')
        ORDER BY creado_en DESC, id DESC'
    );
    $statement->execute($ids);
    $rows = $statement->fetchAll();
} catch (Throwable $exception) {
    error_log('ADMIN_DOWNLOAD_ERROR ip=' . getClientIpAddress() . ' message=' . $exception->getMessage());
    respondJson(500, ['ok' => false, 'message' => 'No se pudieron cargar las solicitudes seleccionadas.']);
}

if (count($rows) === 0) {
    respondJson(404, ['ok' => false, 'message' => 'No se encontraron solicitudes para descargar.']);
}

$tmpZipPath = tempnam(sys_get_temp_dir(), 'reglado_admin_');
if ($tmpZipPath === false) {
    respondJson(500, ['ok' => false, 'message' => 'No se pudo crear el archivo temporal del ZIP.']);
}

$zip = new ZipArchive();
if ($zip->open($tmpZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    @unlink($tmpZipPath);
    respondJson(500, ['ok' => false, 'message' => 'No se pudo crear el ZIP.']);
}

$columns = ['id', 'fecha', 'nombre', 'telefono', 'email', 'mensaje', 'factura_ruta'];
$generalCsvHandle = fopen('php://temp', 'r+');
if ($generalCsvHandle === false) {
    $zip->close();
    @unlink($tmpZipPath);
    respondJson(500, ['ok' => false, 'message' => 'No se pudo generar el CSV general.']);
}

fputcsv($generalCsvHandle, $columns, ';');

$uploadsRoot = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'private_storage' . DIRECTORY_SEPARATOR . 'uploads');
$clients = [];
$usedFolderNames = [];
$totalFilesAdded = 0;

foreach ($rows as $row) {
    $csvRow = [
        (string) $row['id'],
        (string) $row['creado_en'],
        (string) $row['nombre'],
        (string) $row['telefono'],
        (string) $row['email'],
        (string) ($row['mensaje'] ?? ''),
        (string) ($row['pdf_ruta'] ?? ''),
    ];
    fputcsv($generalCsvHandle, $csvRow, ';');

    $clientKey = buildClientKey((string) $row['nombre'], (string) $row['email']);
    if (!isset($clients[$clientKey])) {
        $baseFolderName = sanitizeFolderName((string) $row['nombre']);
        $folderName = uniqueFolderName($baseFolderName, $usedFolderNames);

        $clients[$clientKey] = [
            'folder' => $folderName,
            'rows' => [],
            'files' => [],
        ];
    }

    $clients[$clientKey]['rows'][] = $csvRow;

    $pdfRelativePath = (string) ($row['pdf_ruta'] ?? '');
    if ($pdfRelativePath === '' || $uploadsRoot === false) {
        continue;
    }

    // Corregimos la resolución de la ruta absoluta para el almacenamiento privado
    $absolutePdfPath = realpath($uploadsRoot . DIRECTORY_SEPARATOR . basename($pdfRelativePath));

    if ($absolutePdfPath === false || !str_starts_with($absolutePdfPath, $uploadsRoot) || !is_file($absolutePdfPath)) {
        continue;
    }

    $originalName = (string) ($row['pdf_nombre_original'] ?? '');
    $safeOriginal = sanitizeFileName($originalName !== '' ? $originalName : basename($absolutePdfPath));

    $clients[$clientKey]['files'][] = [
        'id' => (int) $row['id'],
        'path' => $absolutePdfPath,
        'name' => $safeOriginal,
    ];
}

rewind($generalCsvHandle);
$generalCsvContent = stream_get_contents($generalCsvHandle);
fclose($generalCsvHandle);

if (!is_string($generalCsvContent)) {
    $zip->close();
    @unlink($tmpZipPath);
    respondJson(500, ['ok' => false, 'message' => 'No se pudo leer el CSV general generado.']);
}

$zip->addFromString('solicitudes.csv', $generalCsvContent);

foreach ($clients as $clientData) {
    $folderName = (string) $clientData['folder'];

    $clientCsvHandle = fopen('php://temp', 'r+');
    if ($clientCsvHandle === false) {
        $zip->close();
        @unlink($tmpZipPath);
        respondJson(500, ['ok' => false, 'message' => 'No se pudo generar el CSV de un cliente.']);
    }

    fputcsv($clientCsvHandle, $columns, ';');
    foreach ($clientData['rows'] as $clientRow) {
        fputcsv($clientCsvHandle, $clientRow, ';');
    }

    rewind($clientCsvHandle);
    $clientCsvContent = stream_get_contents($clientCsvHandle);
    fclose($clientCsvHandle);

    if (!is_string($clientCsvContent)) {
        $zip->close();
        @unlink($tmpZipPath);
        respondJson(500, ['ok' => false, 'message' => 'No se pudo leer el CSV de un cliente.']);
    }

    $zip->addFromString('clientes/' . $folderName . '/datos_cliente.csv', $clientCsvContent);

    foreach ($clientData['files'] as $file) {
        $zipPath = sprintf(
            'clientes/%s/%d_%s',
            $folderName,
            (int) $file['id'],
            (string) $file['name']
        );

        if ($zip->addFile((string) $file['path'], $zipPath)) {
            $totalFilesAdded++;
        }
    }
}

$summary = sprintf(
    "Solicitudes seleccionadas: %d\nClientes incluidos: %d\nArchivos de factura incluidos: %d\nGenerado: %s\n",
    count($rows),
    count($clients),
    $totalFilesAdded,
    date('Y-m-d H:i:s')
);
$zip->addFromString('resumen_descarga.txt', $summary);
$zip->close();

$downloadName = 'solicitudes_' . date('Ymd_His') . '.zip';
$zipSize = filesize($tmpZipPath);

if ($zipSize === false) {
    @unlink($tmpZipPath);
    respondJson(500, ['ok' => false, 'message' => 'No se pudo leer el tamano del ZIP.']);
}

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . (string) $zipSize);
readfile($tmpZipPath);
@unlink($tmpZipPath);
exit;

function respondJson(int $status, array $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function sanitizeFileName(string $value): string
{
    $sanitized = preg_replace('/[^A-Za-z0-9._-]/', '_', $value);
    $sanitized = is_string($sanitized) ? trim($sanitized, '._') : '';

    if ($sanitized === '') {
        return 'factura.bin';
    }

    return $sanitized;
}

function sanitizeFolderName(string $value): string
{
    $trimmed = trim($value);
    if ($trimmed === '') {
        return 'cliente_sin_nombre';
    }

    $sanitized = preg_replace('/[^A-Za-z0-9_-]/', '_', $trimmed);
    $sanitized = is_string($sanitized) ? trim($sanitized, '._') : '';

    if ($sanitized === '') {
        return 'cliente_sin_nombre';
    }

    return $sanitized;
}

function buildClientKey(string $name, string $email): string
{
    return strtolower(trim($name)) . '|' . strtolower(trim($email));
}

function uniqueFolderName(string $base, array &$usedFolderNames): string
{
    $candidate = $base;
    $index = 2;

    while (isset($usedFolderNames[$candidate])) {
        $candidate = $base . '_' . $index;
        $index++;
    }

    $usedFolderNames[$candidate] = true;
    return $candidate;
}
