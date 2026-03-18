<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

if (!class_exists(\Dompdf\Dompdf::class)) {
    die('ERROR: Dompdf no cargado');
}
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/processing/Repository.php';
require_once __DIR__ . '/processing/ClaudeClient.php';
require_once __DIR__ . '/processing/PdfGenerator.php';
require_once __DIR__ . '/processing/PropertyProcessor.php';

header('Content-Type: application/json');

/**
 * 🔥 CARGAR .ENV
 */
loadEnv(__DIR__ . '/.env');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Solo POST']);
    exit;
}

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);

if (!is_array($payload)) {
    $payload = $_POST ?? [];
}

if (!is_array($payload)) {
    $payload = [];
}

if (empty($payload)) {
    http_response_code(400);
    echo json_encode(['error' => 'Payload inválido']);
    exit;
}

$text = extractEmailText($payload);
$sender = extractSenderEmail($payload);

if ($sender === '') {
    $sender = null;
}

error_log('[WEBHOOK] email recibido');
error_log(sprintf('[WEBHOOK] email remitente %s', $sender ?? 'null'));

if ($text === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Texto del email vacío']);
    exit;
}

/**
 * 🔥 EVITAR DUPLICADOS (Cloudmailin reintenta)
 */
$stmt = $pdo->prepare("
    SELECT id, procesado FROM activos_recibidos 
    WHERE texto_recibido = ? 
    ORDER BY id DESC LIMIT 1
");
$stmt->execute([$text]);
$exists = $stmt->fetch();

if ($exists && $exists['procesado'] === 'procesado') {
    error_log('[WEBHOOK] email duplicado ya procesado, ignorado');

    echo json_encode([
        'success' => true,
        'duplicate' => true
    ]);
    exit;
}

$repository = new Repository($pdo);

$metadata = [
    'raw_input' => $rawInput !== '' ? $rawInput : null,
    'post' => $_POST ?? [],
];

try {
    $assetId = $repository->insertReceivedAsset('email', $sender, $text, $metadata);
} catch (Throwable $exception) {
    error_log("ERROR INSERT: " . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $exception->getMessage()]);
    exit;
}

try {

    /**
     * 🔥 CLAUDE CONFIG CORRECTA
     */
    $claudeKey = getenv('ANTHROPIC_API_KEY') ?: '';
    $claudeModel = getenv('ANTHROPIC_MODEL') ?: 'claude-3-5-sonnet-20240620';
    $claudeEndpoint = 'https://api.anthropic.com/v1/messages';

    if ($claudeKey === '') {
        throw new RuntimeException('Claude API key no configurada');
    }

    $claudeClient = new ClaudeClient($claudeKey, $claudeEndpoint, $claudeModel);

    $pdfGenerator = new PdfGenerator(__DIR__ . '/uploads');

    $processor = new PropertyProcessor($repository, $claudeClient, $pdfGenerator);

    $propertyId = $processor->process($assetId);

    echo json_encode([
        'success' => true,
        'assetId' => $assetId,
        'propertyId' => $propertyId,
    ]);

} catch (Throwable $exception) {

    error_log('ERROR GLOBAL: ' . $exception->getMessage());

    if (isset($assetId)) {
        $stmt = $pdo->prepare('UPDATE activos_recibidos SET procesado = ?, processed_at = CURRENT_TIMESTAMP WHERE id = ?');
        $stmt->execute(['error', $assetId]);
    }

    http_response_code(500);

    echo json_encode([
        'error' => $exception->getMessage()
    ]);

    exit;
}

/**
 * ============================
 * FUNCIONES AUXILIARES
 * ============================
 */

function extractEmailText(array $payload): string
{
    $text = (string) ($payload['text'] ?? $payload['plain'] ?? $payload['body'] ?? $payload['html'] ?? '');

    if ($text === '' && !empty($payload['body_html'])) {
        $text = strip_tags((string) $payload['body_html']);
    }

    return trim($text);
}

function extractSenderEmail(array $payload): ?string
{
    $candidates = [
        $payload['from'] ?? null,
        $payload['sender'] ?? null,
        $payload['from_email'] ?? null,
        $payload['envelope']['from'] ?? null,
    ];

    foreach ($candidates as $candidate) {
        if (!is_string($candidate) || trim($candidate) === '') {
            continue;
        }

        $email = filter_var($candidate, FILTER_VALIDATE_EMAIL);
        if ($email !== false) {
            return normalizeEmail($email);
        }

        if (preg_match('/<([^>]+)>/', $candidate, $matches)) {
            $contained = $matches[1];
            if (filter_var($contained, FILTER_VALIDATE_EMAIL) !== false) {
                return normalizeEmail($contained);
            }
        }

        $clean = preg_replace('/.*<([^>]+)>/', '$1', $candidate);
        if (is_string($clean) && trim($clean) !== '') {
            return normalizeEmail($clean);
        }
    }

    return null;
}

function normalizeEmail(string $email): string
{
    $clean = preg_replace('/.*<([^>]+)>/', '$1', $email);
    return trim((string) $clean);
}

/**
 * 🔥 CARGADOR .ENV
 */
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }

        if (!str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);

        $name = trim($name);
        $value = trim($value);

        putenv("$name=$value");
        $_ENV[$name] = $value;
    }
}