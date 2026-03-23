<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/cors.php';
applyCors();
handlePreflight();
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/lib/env_loader.php';
require_once dirname(__DIR__) . '/lib/pdf_utils.php';
require_once dirname(__DIR__) . '/../processing/Repository.php';
require_once dirname(__DIR__) . '/../processing/DossierService.php';
require_once dirname(__DIR__) . '/../processing/ClaudeClient.php';
require_once dirname(__DIR__) . '/../processing/PdfGenerator.php';
require_once dirname(__DIR__) . '/../processing/PropertyProcessor.php';


loadEnv(dirname(__DIR__) . '/.env');

$context = requireAuthenticatedUser($pdo);
$auth = $context['auth'] ?? [];

if (empty($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
    respondJson(422, ['success' => false, 'message' => 'PDF no fue enviado correctamente.']);
}

$pdfFile = $_FILES['pdf'];
$tempDir = __DIR__ . '/../uploads/pdf_inputs';

try {
    $saved = savePdfAttachment([
        'content' => base64_encode(file_get_contents($pdfFile['tmp_name'])),
        'filename' => $pdfFile['name'],
    ], $tempDir);
    $pdfText = extractPdfText($saved['path']);
} catch (Throwable $exception) {
    respondJson(422, ['success' => false, 'message' => 'No se pudo procesar el PDF: ' . $exception->getMessage()]);
}

if (trim($pdfText) === '') {
    respondJson(422, ['success' => false, 'message' => 'El PDF no contiene texto legible.']);
}

$normalized = normalizeText($pdfText);
$contentHash = hash('sha256', $normalized);

$duplicateStmt = $pdo->prepare('SELECT id FROM activos_recibidos WHERE content_hash = ? LIMIT 1');
$duplicateStmt->execute([$contentHash]);
if ($duplicateStmt->fetch()) {
    respondJson(200, ['success' => true, 'duplicate' => true]);
}

$repository = new Repository($pdo);
$assetId = null;

try {
    $metadata = [
        'origin' => 'web_pdf',
        'tipo_input' => 'pdf',
        'pdf_text' => $pdfText,
        'pdf_filename' => $saved['name'],
    ];

    $assetId = $repository->insertReceivedAsset(
        'web',
        filter_var($auth['email'] ?? '', FILTER_VALIDATE_EMAIL),
        $pdfText,
        $contentHash,
        null,
        $metadata
    );

    $claudeKey = getenv('ANTHROPIC_API_KEY') ?: '';
    $claudeModel = getenv('ANTHROPIC_MODEL') ?: 'claude-3-5-sonnet-20240620';
    $claudeEndpoint = 'https://api.anthropic.com/v1/messages';

    if ($claudeKey === '') {
        throw new RuntimeException('Claude API key no configurada');
    }

    $claudeClient = new ClaudeClient($claudeKey, $claudeEndpoint, $claudeModel);
    $pdfGenerator = new PdfGenerator(__DIR__ . '/../uploads');
    $dossierService = new DossierService(__DIR__ . '/../uploads');
    $processor = new PropertyProcessor($repository, $claudeClient, $pdfGenerator, $dossierService);

    $propertyId = $processor->process($assetId);

    respondJson(200, [
        'success' => true,
        'assetId' => $assetId,
        'propertyId' => $propertyId,
    ]);

} catch (Throwable $exception) {
    if ($assetId !== null) {
        $repository->updateReceivedAssetStatus($assetId, 'error');
    }

    respondJson(500, [
        'success' => false,
        'message' => 'No se pudo procesar el PDF: ' . $exception->getMessage(),
    ]);
}

function normalizeText(string $text): string
{
    $clean = strtolower($text);
    $clean = preg_replace('/\s+/', ' ', $clean);
    $clean = str_replace(["\r", "\n", "\t"], ' ', $clean);
    return trim($clean);
}
