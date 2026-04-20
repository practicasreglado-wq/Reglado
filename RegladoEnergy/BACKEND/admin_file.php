<?php

declare(strict_types=1);

/**
 * Script para descargar archivos de factura de forma segura.
 * Solo accesible para administradores autenticados.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

applySecurityHeaders();
enforceProductionSecurity();
applyCorsHeaders(['GET', 'OPTIONS'], 'Content-Type, Authorization', false);
header('Access-Control-Expose-Headers: Content-Disposition');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    respondError(405, 'Metodo no permitido.');
}

// Verificar autenticación
requireAdminAuth();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    respondError(422, 'ID de solicitud invalido.');
}

try {
    $pdo = getPdo();
    $statement = $pdo->prepare(
        'SELECT id, pdf_nombre_original, pdf_ruta, pdf_mime FROM facturas WHERE id = :id LIMIT 1'
    );
    $statement->execute([':id' => $id]);
    $row = $statement->fetch();

    if (!$row) {
        respondError(404, 'Solicitud no encontrada.');
    }

    $pdfRelativePath = (string) ($row['pdf_ruta'] ?? '');
    if ($pdfRelativePath === '') {
        respondError(404, 'Esta solicitud no tiene un archivo adjunto.');
    }

    // Resolver la ruta absoluta en el almacenamiento privado
    $uploadsRoot = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'private_storage' . DIRECTORY_SEPARATOR . 'uploads');
    $absolutePath = realpath($uploadsRoot . DIRECTORY_SEPARATOR . basename($pdfRelativePath));

    if ($absolutePath === false || !is_file($absolutePath) || !str_starts_with($absolutePath, $uploadsRoot)) {
        respondError(404, 'El archivo fisico no existe en el almacenamiento seguro.');
    }

    $filename = $row['pdf_nombre_original'] ?: basename($absolutePath);
    $mime = $row['pdf_mime'] ?: 'application/octet-stream';

    // Limpiar cualquier salida previa
    if (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . str_replace('"', '_', $filename) . '"');
    header('Content-Length: ' . filesize($absolutePath));
    header('Cache-Control: private, must-revalidate');
    header('Pragma: public');

    readfile($absolutePath);
    exit;

} catch (Throwable $exception) {
    error_log('ADMIN_FILE_DOWNLOAD_ERROR id=' . $id . ' message=' . $exception->getMessage());
    respondError(500, 'Error procesando la descarga.');
}

function respondError(int $status, string $message): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}
