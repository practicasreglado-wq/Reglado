<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/cors.php';
applyCors();
handlePreflight();
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/lib/env_loader.php';
require_once dirname(__DIR__) . '/../processing/Repository.php';
require_once dirname(__DIR__) . '/../processing/DossierService.php';
require_once dirname(__DIR__) . '/../processing/ClaudeClient.php';
require_once dirname(__DIR__) . '/../processing/PdfGenerator.php';
require_once dirname(__DIR__) . '/../processing/PropertyProcessor.php';


loadEnv(dirname(__DIR__) . '/.env');

$context = requireAuthenticatedUser($pdo);
$auth = $context['auth'] ?? [];

$data = json_decode(file_get_contents('php://input'), true);
$description = trim((string) ($data['descripcion'] ?? $data['description'] ?? ''));

if ($description === '') {
    respondJson(422, ['success' => false, 'message' => 'Descripción requerida.']);
}

$senderEmail = filter_var($auth['email'] ?? '', FILTER_VALIDATE_EMAIL);
$metadata = [
    'origin' => 'web_text',
    'user_sub' => $auth['sub'] ?? null,
    'tipo_input' => 'text',
    'original_email_text' => $description,
];

$normalized = normalizeText($description);
$contentHash = hash('sha256', $normalized);

$duplicateStmt = $pdo->prepare('SELECT id FROM activos_recibidos WHERE content_hash = ? LIMIT 1');
$duplicateStmt->execute([$contentHash]);

if ($duplicateStmt->fetch()) {
    respondJson(200, ['success' => true, 'duplicate' => true]);
}

$repository = new Repository($pdo);
$assetId = null;

try {
    $assetId = $repository->insertReceivedAsset('web', $senderEmail, $description, $contentHash, null, $metadata);

    $claudeKey = getenv('ANTHROPIC_API_KEY') ?: '';
    $claudeModel = getenv('ANTHROPIC_MODEL') ?: 'claude-3-5-sonnet-20240620';
    $claudeEndpoint = 'https://api.anthropic.com/v1/messages';

    if ($claudeKey === '') {
        throw new RuntimeException('Claude API key no configurada');
    }

    $claudeClient = new ClaudeClient($claudeKey, $claudeEndpoint, $claudeModel);
    $pdfGenerator = new PdfGenerator(__DIR__ . '/../uploads');
    $dossierService = new DossierService(__DIR__ . '/../uploads');
    $processor = new PropertyProcessor(
        $repository,
        $claudeClient,
        $pdfGenerator,
        $dossierService,
        $auth['id'] ?? null 
    );
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
        'message' => 'No se pudo procesar la propiedad. ' . $exception->getMessage(),
    ]);
}

function normalizeText(string $text): string
{
    $clean = strtolower($text);
    $clean = preg_replace('/\\s+/', ' ', $clean);
    $clean = str_replace(["\r", "\n", "\t"], ' ', $clean);
    return trim($clean);
}
