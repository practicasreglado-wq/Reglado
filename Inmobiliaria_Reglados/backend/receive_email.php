<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/lib/env_loader.php';

if (!class_exists(\Dompdf\Dompdf::class)) {
    die('ERROR: Dompdf no cargado');
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/processing/Repository.php';
require_once __DIR__ . '/../processing/DossierService.php';
require_once __DIR__ . '/processing/ClaudeClient.php';
require_once __DIR__ . '/../processing/PdfGenerator.php';
require_once __DIR__ . '/../processing/PropertyProcessor.php';
require_once __DIR__ . '/../lib/pdf_utils.php';

header('Content-Type: application/json');

/**
 * 🔥 CARGAR .ENV
 */
loadEnv(__DIR__ . '/.env');
require_once __DIR__ . '/send_mail.php';

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

$plainText = extractEmailText($payload);
$pdfText = null;
$pdfFilename = null;
$pdfRelativePath = null;
$tipoInput = 'text';

$pdfAttachment = findPdfAttachmentInPayload($payload);
if ($pdfAttachment !== null) {
    try {
        $uploadDir = __DIR__ . '/uploads/pdf_inputs';
        $saved = savePdfAttachment($pdfAttachment, $uploadDir);
        $extracted = extractPdfText($saved['path']);

        if (trim($extracted) !== '') {
            $tipoInput = 'pdf';
            $pdfText = $extracted;
            $pdfFilename = $saved['name'];
            $pdfRelativePath = str_replace('\\', '/', preg_replace('#^' . preg_quote(__DIR__, '#') . '#', '', $saved['path']));
        }
    } catch (Throwable $exception) {
        error_log('[PDF] ' . $exception->getMessage());
    }
}

$text = $pdfText !== null && trim($pdfText) !== '' ? $pdfText : $plainText;
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

$messageId = extractMessageId($payload);
 $normalizedInput = normalizeText($text);
 $contentHash = hash('sha256', $normalizedInput);

 if ($messageId !== null) {
    $duplicateStmt = $pdo->prepare('SELECT id FROM activos_recibidos WHERE message_id = ? LIMIT 1');
    $duplicateStmt->execute([$messageId]);
    if ($duplicateStmt->fetch()) {
        echo json_encode(['success' => true, 'duplicate' => true]);
        exit;
    }
}

$hashStmt = $pdo->prepare('SELECT id FROM activos_recibidos WHERE content_hash = ? LIMIT 1');
$hashStmt->execute([$contentHash]);
if ($hashStmt->fetch()) {
    echo json_encode(['success' => true, 'duplicate' => true]);
    exit;
}


/**
 * 🔥 EVITAR DUPLICADOS
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
     'message_id' => $messageId,
     'content_hash' => $contentHash,
     'tipo_input' => $tipoInput,
     'original_email_text' => $plainText !== '' ? $plainText : null,
     'pdf_text' => $pdfText,
     'pdf_filename' => $pdfFilename,
     'pdf_path' => $pdfRelativePath,
 ];

try {
    // 🔥 INSERTAR SOLO PARA PROCESAR (TEMPORAL)
    $assetId = $repository->insertReceivedAsset('email', $sender, $text, $contentHash, $messageId, $metadata);

    /**
     * 🔥 CLAUDE CONFIG
     */
    $claudeKey = getenv('ANTHROPIC_API_KEY') ?: '';
    $claudeModel = getenv('ANTHROPIC_MODEL') ?: 'claude-3-5-sonnet-20240620';
    $claudeEndpoint = 'https://api.anthropic.com/v1/messages';

    if ($claudeKey === '') {
        throw new RuntimeException('Claude API key no configurada');
    }

    $claudeClient = new ClaudeClient($claudeKey, $claudeEndpoint, $claudeModel);
    $pdfGenerator = new PdfGenerator(__DIR__ . '/uploads');
    $dossierService = new DossierService(__DIR__ . '/uploads');
    $processor = new PropertyProcessor($repository, $claudeClient, $pdfGenerator, $dossierService);

    // 🔥 PROCESAR
    $propertyId = $processor->process($assetId);

    // 🔥 EMAIL OK
    notifySenderProcessingResult($sender, true);

    echo json_encode([
        'success' => true,
        'propertyId' => $propertyId,
    ]);

} catch (Throwable $exception) {

    error_log('ERROR GLOBAL: ' . $exception->getMessage());

    // 🔥 🔴 BORRAR TODO (CLAVE)
    if (isset($assetId)) {
        try {
            $stmt = $pdo->prepare("DELETE FROM activos_recibidos WHERE id = ?");
            $stmt->execute([$assetId]);
        } catch (Throwable $e) {
            error_log('ERROR BORRANDO ACTIVO: ' . $e->getMessage());
        }
    }

    http_response_code(500);

    // 🔥 EMAIL ERROR
    notifySenderProcessingResult($sender, false);

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

function extractMessageId(array $payload): ?string
{
    $headers = [];
    if (isset($payload['headers']) && is_array($payload['headers'])) {
        $headers = $payload['headers'];
    }

    $candidates = [
        $payload['message_id'] ?? null,
        $payload['Message-ID'] ?? null,
        $headers['message_id'] ?? null,
        $headers['Message-ID'] ?? null,
    ];

    foreach ($candidates as $candidate) {
        if (!is_string($candidate)) {
            continue;
        }

        $trimmed = trim($candidate);
        if ($trimmed === '') {
            continue;
        }

        $clean = trim($trimmed, '<>');
        if ($clean !== '') {
            return $clean;
        }
    }

    return null;
}

function normalizeText(string $text): string
{
    $clean = strtolower($text);
    $clean = preg_replace('/[\r\n\t]+/', ' ', $clean);
    $clean = preg_replace('/\\s+/', ' ', $clean);
    return trim($clean);
}

function notifySenderProcessingResult(?string $sender, bool $success): void
{
    if (!is_string($sender) || trim($sender) === '') {
        return;
    }

    $sender = trim($sender);

    $subject = $success
        ? 'Propiedad recibida correctamente'
        : 'Tu propiedad no ha podido ser procesada porque faltan datos necesarios.';

    $message = $success
        ? 'Tu propiedad ha sido procesada correctamente y añadida al sistema.'
        : 'Tu propiedad no ha podido ser procesada porque faltan datos necesarios.';

    try {
        sendNotificationEmail($sender, $subject, "<p>{$message}</p>");
    } catch (Throwable $mailException) {
        error_log("ERROR EN ENVÍO DE EMAIL: " . $mailException->getMessage());
    }
}
