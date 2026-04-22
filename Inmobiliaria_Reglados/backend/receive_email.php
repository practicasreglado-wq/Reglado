<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

function bootLog(string $message, array $context = []): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;

    if (!empty($context)) {
        $json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json !== false) {
            $line .= ' | ' . $json;
        }
    }

    error_log('[boot] ' . $line);
}

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null) {
        bootLog('SHUTDOWN ERROR', $error);
    } else {
        bootLog('SHUTDOWN OK');
    }
});

bootLog('BOOT 1 entra al archivo', [
    'file' => __FILE__,
    'dir' => __DIR__,
]);

bootLog('BOOT 2 antes vendor/autoload.php');
require_once __DIR__ . '/../vendor/autoload.php';
bootLog('BOOT 3 despues vendor/autoload.php');

bootLog('BOOT 4 antes env_loader.php');
require_once __DIR__ . '/lib/env_loader.php';
bootLog('BOOT 5 despues env_loader.php');

bootLog('BOOT 6 antes check Dompdf');
if (!class_exists(\Dompdf\Dompdf::class)) {
    bootLog('BOOT ERROR Dompdf no cargado');
    http_response_code(500);
    die('ERROR: Dompdf no cargado');
}
bootLog('BOOT 7 Dompdf OK');

bootLog('BOOT 8 antes db.php');
require_once __DIR__ . '/config/db.php';
bootLog('BOOT 9 despues db.php');

bootLog('BOOT 10 antes Repository.php');
require_once __DIR__ . '/processing/Repository.php';
bootLog('BOOT 11 despues Repository.php');

bootLog('BOOT 12 antes DossierService.php');
require_once __DIR__ . '/processing/DossierService.php';
bootLog('BOOT 13 despues DossierService.php');

bootLog('BOOT 14 antes ClaudeClient.php');
require_once __DIR__ . '/processing/ClaudeClient.php';
bootLog('BOOT 15 despues ClaudeClient.php');

bootLog('BOOT 16 antes PdfGenerator.php');
require_once __DIR__ . '/processing/PdfGenerator.php';
bootLog('BOOT 17 despues PdfGenerator.php');

bootLog('BOOT 18 antes PropertyProcessor.php');
require_once __DIR__ . '/processing/PropertyProcessor.php';
bootLog('BOOT 19 despues PropertyProcessor.php');

bootLog('BOOT 20 antes utils.php');
require_once __DIR__ . '/lib/utils.php';
bootLog('BOOT 21 despues utils.php');

header('Content-Type: application/json; charset=UTF-8');

bootLog('BOOT 22 antes loadEnv');
loadEnv(__DIR__ . '/.env');
bootLog('BOOT 23 despues loadEnv');

bootLog('BOOT 24 antes send_mail.php');
require_once __DIR__ . '/send_mail.php';
bootLog('BOOT 25 despues send_mail.php');

function webhookLog(string $message, array $context = []): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;

    if (!empty($context)) {
        $json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json !== false) {
            $line .= ' | ' . $json;
        }
    }

    error_log($line);
}

function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function getPayloadFromRequest(string $rawInput): array
{
    $payload = json_decode($rawInput, true);

    webhookLog('PASO 1 payload recibido', [
        'raw_len' => strlen((string) $rawInput),
        'post_keys' => array_keys($_POST ?? []),
        'json_ok' => is_array($payload),
        'payload_keys' => is_array($payload) ? array_keys($payload) : [],
    ]);

    if (is_array($payload)) {
        return $payload;
    }

    if (!empty($_POST) && is_array($_POST)) {
        webhookLog('PASO 1B usando $_POST', [
            'post_keys' => array_keys($_POST),
        ]);
        return $_POST;
    }

    return [];
}

function extractEmailText(array $payload): string
{
    $candidates = [
        'text' => $payload['text'] ?? null,
        'plain' => $payload['plain'] ?? null,
        'body' => $payload['body'] ?? null,
        'body_plain' => $payload['body_plain'] ?? null,
        'message' => $payload['message'] ?? null,
        'html' => $payload['html'] ?? null,
        'body_html' => $payload['body_html'] ?? null,
    ];

    foreach ($candidates as $key => $candidate) {
        if (!is_string($candidate) || trim($candidate) === '') {
            continue;
        }

        $value = trim($candidate);

        if ($key === 'html' || $key === 'body_html') {
            $value = trim(strip_tags($candidate));
        }

        if ($value !== '') {
            webhookLog('PASO 2A texto encontrado', [
                'source_key' => $key,
                'text_len' => mb_strlen($value),
                'text_preview' => mb_substr($value, 0, 500),
            ]);
            return $value;
        }
    }

    if (isset($payload['headers']) && is_array($payload['headers'])) {
        foreach (['body', 'text', 'plain'] as $key) {
            if (isset($payload['headers'][$key]) && is_string($payload['headers'][$key])) {
                $value = trim($payload['headers'][$key]);
                if ($value !== '') {
                    webhookLog('PASO 2B texto encontrado en headers', [
                        'source_key' => 'headers.' . $key,
                        'text_len' => mb_strlen($value),
                        'text_preview' => mb_substr($value, 0, 500),
                    ]);
                    return $value;
                }
            }
        }
    }

    webhookLog('PASO 2C no se encontró texto útil');
    return '';
}

function extractSenderEmail(array $payload): ?string
{
    $candidates = [
        $payload['from'] ?? null,
        $payload['sender'] ?? null,
        $payload['from_email'] ?? null,
        $payload['envelope']['from'] ?? null,
        $payload['headers']['from'] ?? null,
        $payload['headers']['From'] ?? null,
    ];

    foreach ($candidates as $candidate) {
        if (!is_string($candidate) || trim($candidate) === '') {
            continue;
        }

        $candidate = trim($candidate);

        $email = filter_var($candidate, FILTER_VALIDATE_EMAIL);
        if ($email !== false) {
            return normalizeEmail($email);
        }

        if (preg_match('/<([^>]+)>/', $candidate, $matches)) {
            $contained = trim($matches[1]);
            if (filter_var($contained, FILTER_VALIDATE_EMAIL) !== false) {
                return normalizeEmail($contained);
            }
        }

        $clean = preg_replace('/.*<([^>]+)>.*/', '$1', $candidate);
        if (is_string($clean) && trim($clean) !== '') {
            $clean = trim($clean);
            if (filter_var($clean, FILTER_VALIDATE_EMAIL) !== false) {
                return normalizeEmail($clean);
            }
        }
    }

    return null;
}

function normalizeEmail(string $email): string
{
    $clean = preg_replace('/.*<([^>]+)>.*/', '$1', $email);
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
        $payload['messageId'] ?? null,
        $headers['message_id'] ?? null,
        $headers['Message-ID'] ?? null,
        $headers['messageId'] ?? null,
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
    $clean = preg_replace('/\s+/', ' ', $clean);
    return trim((string) $clean);
}

function notifySenderProcessingResult(?string $sender, bool $success): void
{
    if (!is_string($sender) || trim($sender) === '') {
        return;
    }

    $sender = trim($sender);

    $subject = $success
        ? 'Propiedad recibida correctamente'
        : 'Tu propiedad no ha podido ser procesada';

    $message = $success
        ? 'Tu propiedad ha sido procesada correctamente y añadida al sistema.'
        : 'Tu propiedad no ha podido ser procesada porque faltan datos necesarios o se produjo un error interno al procesarla.';

    $status = $success
        ? 'Procesado correctamente'
        : 'Error en el procesamiento';

    $color = $success
        ? '#16a34a'
        : '#dc2626';

    $html = "
    <!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>{$subject}</title>
    </head>
    <body style='margin:0; padding:0; background-color:#f4f6f8; font-family:Arial, sans-serif;'>
        <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color:#f4f6f8; padding:24px 0;'>
            <tr>
                <td align='center'>
                    <table width='520' cellpadding='0' cellspacing='0' border='0' style='max-width:520px; width:100%; background:#ffffff; border-radius:10px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,0.06);'>
                        <tr>
                            <td style='background:#111827; color:#ffffff; text-align:center; padding:18px 24px; font-size:20px; font-weight:bold;'>
                                Reglado Real State
                            </td>
                        </tr>
                        <tr>
                            <td style='padding:30px 28px; color:#333333;'>
                                <p style='margin:0 0 14px 0; font-size:15px;'>
                                    Hola,
                                </p>

                                <p style='margin:0 0 18px 0; font-size:15px; line-height:1.6; color:#374151;'>
                                    {$message}
                                </p>

                                <div style='margin:0 0 20px 0; padding:12px 16px; border-radius:8px; background:#f9fafb; border-left:4px solid {$color};'>
                                    <span style='font-size:14px; font-weight:bold; color:{$color};'>
                                        {$status}
                                    </span>
                                </div>

                                <p style='margin:0; font-size:13px; line-height:1.5; color:#6b7280;'>
                                    Este es un mensaje automático generado por la plataforma.
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td style='background:#f9fafb; text-align:center; padding:14px 20px; font-size:12px; color:#9ca3af;'>
                                © " . date('Y') . " Reglado Real State
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>
    ";

    try {
        sendNotificationEmail($sender, $subject, $html);
    } catch (Throwable $mailException) {
        webhookLog('ERROR EN ENVÍO DE EMAIL', [
            'message' => $mailException->getMessage(),
            'sender' => $sender,
        ]);
    }
}

function getDefaultOwnerUserId(): ?int
{
    $value = getenv('DEFAULT_OWNER_USER_ID') ?: '';
    if ($value === '' || !is_numeric($value)) {
        return null;
    }

    return (int) $value;
}

function buildProcessor(
    Repository $repository,
    ClaudeClient $claudeClient,
    PdfGenerator $pdfGenerator,
    DossierService $dossierService,
    ?int $ownerUserId
): object {
    try {
        $reflection = new ReflectionClass(PropertyProcessor::class);
        $constructor = $reflection->getConstructor();
        $paramCount = $constructor ? $constructor->getNumberOfParameters() : 0;

        if ($paramCount >= 5) {
            return $reflection->newInstance(
                $repository,
                $claudeClient,
                $pdfGenerator,
                $dossierService,
                $ownerUserId
            );
        }

        return $reflection->newInstance(
            $repository,
            $claudeClient,
            $pdfGenerator,
            $dossierService
        );
    } catch (Throwable $e) {
        throw new RuntimeException('No se pudo instanciar PropertyProcessor: ' . $e->getMessage(), 0, $e);
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['error' => 'Solo POST'], 405);
}

$rawInput = file_get_contents('php://input');
$payload = getPayloadFromRequest($rawInput);

webhookLog('PASO 1C webhook recibido', [
    'method' => $_SERVER['REQUEST_METHOD'] ?? null,
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? null),
    'raw_len' => strlen((string) $rawInput),
    'payload_keys' => is_array($payload) ? array_keys($payload) : [],
]);

if (empty($payload)) {
    webhookLog('Payload inválido o vacío');
    jsonResponse(['error' => 'Payload inválido'], 400);
}

$plainText = extractEmailText($payload);
$pdfText = null;
$pdfFilename = null;
$pdfRelativePath = null;
$tipoInput = 'text';

webhookLog('PASO 2D procesamiento PDF desactivado temporalmente');

$text = (string) $plainText;
$sender = extractSenderEmail($payload);

if ($sender === '') {
    $sender = null;
}

webhookLog('PASO 2 texto extraído', [
    'sender' => $sender,
    'tipo_input' => $tipoInput,
    'text_len' => mb_strlen($text),
    'text_preview' => mb_substr($text, 0, 700),
]);

if (trim($text) === '') {
    webhookLog('Texto del email vacío tras extracción', [
        'sender' => $sender,
    ]);
    jsonResponse(['error' => 'Texto del email vacío'], 400);
}

$messageId = extractMessageId($payload);
$normalizedInput = normalizeText($text);
$contentHash = hash('sha256', $normalizedInput);

webhookLog('PASO 3 antes duplicados', [
    'message_id' => $messageId,
    'content_hash' => $contentHash,
]);

$assetId = null;

try {
    if ($messageId !== null) {
        $duplicateStmt = $pdo->prepare('SELECT id FROM activos_recibidos WHERE message_id = ? LIMIT 1');
        $duplicateStmt->execute([$messageId]);
        $existingByMessageId = $duplicateStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingByMessageId) {
            webhookLog('Duplicado por message_id', [
                'message_id' => $messageId,
                'existing_asset_id' => $existingByMessageId['id'] ?? null,
                'procesado' => $existingByMessageId['procesado'] ?? null
            ]);

            jsonResponse([
                'success' => true,
                'duplicate' => true,
                'reason' => 'message_id',
            ]);
        }
    }

    $hashStmt = $pdo->prepare('SELECT id FROM activos_recibidos WHERE content_hash = ? LIMIT 1');
    $hashStmt->execute([$contentHash]);
    $existingByHash = $hashStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingByHash) {
        webhookLog('Duplicado por content_hash', [
            'content_hash' => $contentHash,
            'existing_asset_id' => $existingByHash['id'] ?? null,
        ]);

        jsonResponse([
            'success' => true,
            'duplicate' => true,
            'reason' => 'content_hash',
        ]);
    }

    $stmt = $pdo->prepare("
        SELECT id, procesado
        FROM activos_recibidos
        WHERE texto_recibido = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([$text]);
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($exists && (($exists['procesado'] ?? null) === 'procesado')) {
        webhookLog('Duplicado por texto_recibido ya procesado', [
            'existing_asset_id' => $exists['id'] ?? null,
        ]);

        jsonResponse([
            'success' => true,
            'duplicate' => true,
            'reason' => 'texto_recibido',
        ]);
    }

    $pdo->beginTransaction();

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
        'sender' => $sender,
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? null),
        'received_at' => date('c'),
    ];

    $insertResult = $repository->insertReceivedAsset(
        'email',
        $sender,
        $text,
        $contentHash,
        $messageId,
        $metadata
    );

    $assetId = (int) $insertResult['id'];
    $isDuplicateInsert = (bool) $insertResult['is_duplicate'];

    webhookLog('PASO 4 activo insertado o recuperado', [
        'asset_id' => $assetId,
        'sender' => $sender,
        'is_duplicate_insert' => $isDuplicateInsert,
    ]);

    if ($repository->isAssetAlreadyProcessed($assetId)) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        webhookLog('DUPLICADO: activo ya procesado, se corta el flujo', [
            'asset_id' => $assetId,
        ]);

        jsonResponse([
            'success' => true,
            'duplicate' => true,
            'reason' => 'already_processed',
            'asset_id' => $assetId,
        ]);
    }

    if ($isDuplicateInsert) {
        webhookLog('DUPLICADO: activo existente recuperado pero aún no procesado', [
            'asset_id' => $assetId,
        ]);
    }

    $claudeKey = getenv('ANTHROPIC_API_KEY') ?: '';
    $claudeModel = getenv('ANTHROPIC_MODEL') ?: 'claude-3-5-sonnet-20240620';
    $claudeEndpoint = 'https://api.anthropic.com/v1/messages';

    if ($claudeKey === '') {
        throw new RuntimeException('Claude API key no configurada');
    }

    $defaultOwnerUserId = getDefaultOwnerUserId();
    if ($defaultOwnerUserId === null) {
        throw new RuntimeException('DEFAULT_OWNER_USER_ID no configurado en .env');
    }

    webhookLog('PASO 5 antes de crear processor', [
        'default_owner_user_id' => $defaultOwnerUserId,
    ]);

    $claudeClient = new ClaudeClient($claudeKey, $claudeEndpoint, $claudeModel);
    $pdfGenerator = new PdfGenerator(__DIR__ . '/uploads');
    $dossierService = new DossierService(__DIR__ . '/uploads');
    $processor = buildProcessor(
        $repository,
        $claudeClient,
        $pdfGenerator,
        $dossierService,
        $defaultOwnerUserId
    );

    webhookLog('PASO 6 antes process()', [
        'asset_id' => $assetId,
    ]);

    $propertyId = $processor->process($assetId);

    webhookLog('PASO 6B resultado process()', [
        'asset_id' => $assetId,
        'property_id_raw' => $propertyId,
        'property_id_type' => gettype($propertyId),
    ]);

    if (!is_numeric($propertyId) || (int) $propertyId <= 0) {
        throw new RuntimeException('La propiedad no se creó correctamente.');
    }

    $propertyId = (int) $propertyId;

    webhookLog('PASO 7 propiedad creada correctamente', [
        'asset_id' => $assetId,
        'property_id' => $propertyId,
    ]);

    if ($pdo->inTransaction()) {
        webhookLog('PASO 8 antes commit', [
            'asset_id' => $assetId,
            'property_id' => $propertyId,
        ]);

        $pdo->commit();

        webhookLog('PASO 9 commit realizado', [
            'asset_id' => $assetId,
            'property_id' => $propertyId,
        ]);
    }

    webhookLog('PASO 10 antes email OK', [
        'sender' => $sender,
        'property_id' => $propertyId,
    ]);

    notifySenderProcessingResult($sender, true);

    webhookLog('PASO 11 después email OK', [
        'sender' => $sender,
        'property_id' => $propertyId,
    ]);

    jsonResponse([
        'success' => true,
        'propertyId' => $propertyId,
    ]);

} catch (Throwable $exception) {
    if ($assetId !== null) {
    try {
            $stmt = $pdo->prepare("
                UPDATE activos_recibidos 
                SET procesado = 'error', processed_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$assetId]);
        } catch (Throwable $e) {
            webhookLog('ERROR marcando como error', [
                'asset_id' => $assetId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    webhookLog('ERROR GLOBAL', [
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'asset_id' => $assetId,
        'message_id' => $messageId ?? null,
        'sender' => $sender ?? null,
    ]);

    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();

        webhookLog('ROLLBACK EJECUTADO', [
            'asset_id' => $assetId,
        ]);
    }

    notifySenderProcessingResult($sender ?? null, false);

    jsonResponse([
        'success' => false,
        'error' => $exception->getMessage(),
    ], 200);
}