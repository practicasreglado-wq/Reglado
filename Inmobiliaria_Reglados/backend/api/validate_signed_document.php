<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/pdf_signature.php';

applyCors();
handlePreflight();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_FILES['file'])) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'No se recibió ningún archivo.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$file = $_FILES['file'];
$type = trim((string) ($_POST['type'] ?? ''));
$propertyId = (int) ($_POST['property_id'] ?? 0);

if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Archivo no válido.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$tmpPath = (string) ($file['tmp_name'] ?? '');
$originalName = (string) ($file['name'] ?? '');

if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'No se pudo procesar el archivo subido.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!in_array($type, ['nda', 'loi'], true)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Tipo de documento no válido.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$originalPath = null;

if ($propertyId > 0) {
    $stmt = $pdo->prepare("
        SELECT confidentiality_file, intention_file
        FROM inmobiliaria.propiedades
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $propertyId]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($property) {
        $relativeFile = $type === 'nda'
            ? (string) ($property['confidentiality_file'] ?? '')
            : (string) ($property['intention_file'] ?? '');

        if ($relativeFile !== '') {
            $relativeFile = str_replace('\\', '/', $relativeFile);
            $relativeFile = ltrim($relativeFile, '/');

            $uploadsBase = realpath(__DIR__ . '/../uploads');
            if ($uploadsBase !== false) {
                $candidate = realpath($uploadsBase . DIRECTORY_SEPARATOR . $relativeFile);
                if ($candidate !== false && is_file($candidate) && str_starts_with($candidate, $uploadsBase)) {
                    $originalPath = $candidate;
                }
            }
        }
    }
}

$result = pdfSeemsSigned($tmpPath, $originalPath);

echo json_encode([
    'success' => true,
    'accepted' => (bool) ($result['accepted'] ?? false),
    'reason' => (string) ($result['reason'] ?? ''),
    'file_name' => $originalName,
], JSON_UNESCAPED_UNICODE);
exit;