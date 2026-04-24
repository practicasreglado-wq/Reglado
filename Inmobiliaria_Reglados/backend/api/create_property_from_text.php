<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/cors.php';
applyCors();
handlePreflight();

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/lib/env_loader.php';
require_once dirname(__DIR__) . '/processing/Repository.php';
require_once dirname(__DIR__) . '/processing/DossierService.php';
require_once dirname(__DIR__) . '/processing/ClaudeClient.php';
require_once dirname(__DIR__) . '/processing/PdfGenerator.php';
require_once dirname(__DIR__) . '/processing/PropertyProcessor.php';
require_once dirname(__DIR__) . '/lib/audit.php';
require_once dirname(__DIR__) . '/lib/error_reporting.php';

loadEnv(dirname(__DIR__) . '/.env');

$context = requireAuthenticatedUser($pdo);
$createdByUserId = (int) ($context['local']['iduser'] ?? $context['auth']['sub'] ?? 0);
$senderEmailRaw = $context['auth']['email'] ?? null;
$senderEmail = filter_var($senderEmailRaw, FILTER_VALIDATE_EMAIL) ?: null;

if ($createdByUserId <= 0) {
    respondJson(401, ['success' => false, 'message' => 'Usuario no autenticado']);
}

$data = json_decode(file_get_contents('php://input') ?: '{}', true);
$description = trim((string) ($data['descripcion'] ?? $data['description'] ?? ''));

if ($description === '') {
    respondJson(422, [
        'success' => false,
        'message' => 'Descripción requerida.'
    ]);
}

$metadata = [
    'origin' => 'web_text',
    'user_sub' => $jwtUser['sub'] ?? $createdByUserId,
    'tipo_input' => 'text',
    'original_email_text' => $description,
];

$normalized = normalizeText($description);
$contentHash = hash('sha256', $normalized);

$repository = new Repository($pdo);
$assetId = null;

try {
    $existingAssetId = $repository->findExistingAssetId(null, $contentHash, $description);

    if ($existingAssetId !== null && $repository->isAssetAlreadyProcessed($existingAssetId)) {
        respondJson(200, [
            'success' => true,
            'duplicate' => true,
            'assetId' => $existingAssetId,
        ]);
    }

    $insertResult = $repository->insertReceivedAsset(
        'web',
        $senderEmail,
        $description,
        $contentHash,
        null,
        $metadata
    );

    $assetId = (int) $insertResult['id'];
    $isDuplicateInsert = (bool) $insertResult['is_duplicate'];

    if ($repository->isAssetAlreadyProcessed($assetId)) {
        respondJson(200, [
            'success' => true,
            'duplicate' => true,
            'assetId' => $assetId,
        ]);
    }

    error_log('[WEB TEXT] assetId=' . $assetId . ' duplicate=' . json_encode($isDuplicateInsert));

    $claudeKey = getenv('ANTHROPIC_API_KEY') ?: '';
    $claudeModel = getenv('ANTHROPIC_MODEL') ?: 'claude-3-5-sonnet-20240620';
    $claudeEndpoint = 'https://api.anthropic.com/v1/messages';

    if ($claudeKey === '') {
        throw new RuntimeException('Claude API key no configurada');
    }

    $claudeClient = new ClaudeClient($claudeKey, $claudeEndpoint, $claudeModel);
    $pdfGenerator = new PdfGenerator(dirname(__DIR__) . '/uploads');
    $dossierService = new DossierService(dirname(__DIR__) . '/uploads');

    $processor = new PropertyProcessor(
        $repository,
        $claudeClient,
        $pdfGenerator,
        $dossierService,
        $createdByUserId
    );

    $propertyId = (int) $processor->process($assetId);

    if ($propertyId <= 0) {
        throw new RuntimeException('No se pudo obtener el ID de la propiedad creada.');
    }

    auditLog($pdo, 'property.create_from_text', array_merge(
        auditContextFromAuth($context['auth'] ?? [], $createdByUserId),
        [
            'resource_type' => 'property',
            'resource_id'   => (string) $propertyId,
            'metadata'      => [
                'asset_id'       => $assetId,
                'duplicate_asset' => $isDuplicateInsert ?? null,
            ],
        ]
    ));

    respondJson(200, [
        'success' => true,
        'assetId' => $assetId,
        'propertyId' => $propertyId,
    ]);

} catch (Throwable $exception) {
    if ($assetId !== null) {
        $repository->updateReceivedAssetStatus($assetId, 'error');
    }

    $errorId = logAndReferenceError('create_property_from_text', $exception);

    respondJson(500, [
        'success' => false,
        'message' => 'No se pudo procesar la solicitud. Referencia: ' . $errorId,
    ]);
}

function normalizeText(string $text): string
{
    $clean = strtolower($text);
    $clean = str_replace(["\r", "\n", "\t"], ' ', $clean);
    $clean = preg_replace('/\s+/', ' ', $clean);
    return trim((string) $clean);
}

function getUserFromJWT(): ?array
{
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return null;
    }

    try {
        return verifyJwt($matches[1]);
    } catch (Throwable $e) {
        return null;
    }
}
