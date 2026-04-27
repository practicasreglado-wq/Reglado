<?php
declare(strict_types=1);

/**
 * Descarga de NDA o LOI por parte del comprador (paso PREVIO a firmarlos).
 *
 * No requiere haber firmado nada — es la primera descarga del flujo. Tras
 * descargarlo, el comprador firma fuera de la plataforma y vuelve para
 * subir el PDF firmado en upload_signed_documents.php.
 *
 * Marca el progreso en buyer_property_document_download_progress (NDA o LOI
 * descargado), que el resto del flujo usa para gating.
 *
 * Audit log: 'document.legal.download'.
 */

require_once __DIR__ . '/../config/cors.php';
applyCors();
handlePreflight();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../lib/env_loader.php';
require_once __DIR__ . '/../lib/document_access.php';
require_once __DIR__ . '/../lib/audit.php';

loadEnv(__DIR__ . '/../.env');

function resolveUploadPdfPath(string $relative): string
{
    $clean = trim($relative);
    if ($clean === '') {
        throw new RuntimeException('Archivo no indicado.');
    }

    $clean = str_replace('\\', '/', $clean);
    $clean = ltrim($clean, '/');

    $baseDir = realpath(__DIR__ . '/../uploads');
    if ($baseDir === false) {
        throw new RuntimeException('Directorio base no disponible.');
    }

    $filePath = realpath($baseDir . DIRECTORY_SEPARATOR . $clean);

    // Fallback por si en la BD viene "dossiers/archivo.pdf" pero el archivo real está en la raíz de uploads, o viceversa.
    if ($filePath === false) {
        $filePath = realpath($baseDir . DIRECTORY_SEPARATOR . basename($clean));
    }

    if ($filePath === false || !is_file($filePath) || !is_readable($filePath)) {
        throw new RuntimeException('Archivo no encontrado.');
    }

    if (!str_starts_with($filePath, $baseDir . DIRECTORY_SEPARATOR)) {
        throw new RuntimeException('Acceso denegado.');
    }

    return $filePath;
}

$context = requireAuthenticatedUser($pdo);
$buyerUserId = (int) ($context['local']['iduser'] ?? $context['local']['id'] ?? $context['auth']['id'] ?? 0);
$propertyId = (int) ($_GET['property_id'] ?? $_GET['propertyId'] ?? 0);
$type = strtolower(trim((string) ($_GET['type'] ?? '')));

if ($buyerUserId <= 0) {
    http_response_code(401);
    exit('Debes iniciar sesión.');
}

if ($propertyId <= 0 || !in_array($type, ['nda', 'loi'], true)) {
    http_response_code(422);
    exit('Parámetros inválidos.');
}

$stmt = $pdo->prepare('
    SELECT confidentiality_file, intention_file
    FROM propiedades
    WHERE id = :id
    LIMIT 1
');
$stmt->execute(['id' => $propertyId]);
$property = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$relative = $type === 'nda'
    ? (string) ($property['confidentiality_file'] ?? '')
    : (string) ($property['intention_file'] ?? '');

if (trim($relative) === '') {
    http_response_code(404);
    exit('Documento no disponible.');
}

try {
    if ($pdo instanceof PDO) {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    $pdo->beginTransaction();

    $progress = markBuyerPropertyLegalDocumentDownloaded($pdo, $propertyId, $buyerUserId, $type);
    if (buyerHasDownloadedBothLegalDocuments($progress)) {
        ensureBuyerPropertyAccess($pdo, $propertyId, $buyerUserId);
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    exit('No se pudo registrar la descarga.');
}

try {
    $filePath = resolveUploadPdfPath($relative);
} catch (Throwable $e) {
    http_response_code(404);
    exit($e->getMessage());
}

$filename = basename($filePath);

if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Description: File Transfer');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: public');
header('Expires: 0');
header('Content-Length: ' . (string) filesize($filePath));

auditLog($pdo, 'document.legal.download', array_merge(
    auditContextFromAuth($context['auth'] ?? [], $buyerUserId),
    [
        'resource_type' => 'document',
        'resource_id'   => $type . ':' . $propertyId,
        'metadata'      => ['file' => $filename, 'type' => $type, 'property_id' => $propertyId]
    ]
));

readfile($filePath);
exit;

