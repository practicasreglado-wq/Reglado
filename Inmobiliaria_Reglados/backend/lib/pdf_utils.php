<?php
declare(strict_types=1);

require_once __DIR__ . '/../processing/PdfTextExtractor.php';

function extractPdfText(string $filePath): string
{
    if (!is_file($filePath) || !is_readable($filePath)) {
        throw new RuntimeException('PDF no disponible para extraer texto');
    }

    $pdftotext = findPdftotext();

    if ($pdftotext !== null) {
        $cmd = escapeshellarg($pdftotext) . ' -layout -enc UTF-8 ' . escapeshellarg($filePath) . ' -';
        try {
            $output = shell_exec($cmd);
            if (is_string($output)) {
                $trimmed = trim($output);
                if ($trimmed !== '') {
                    return $trimmed;
                }
            }
        } catch (Throwable $exception) {
            error_log('Error ejecutando pdftotext: ' . $exception->getMessage());
        }
    }

    return PdfTextExtractor::extractTextFromFile($filePath);
}

function findPdftotext(): ?string
{
    static $cached = null;

    if ($cached !== null) {
        return $cached;
    }

    $commands = ['pdftotext'];
    if (stripos(PHP_OS, 'WIN') === 0) {
        $commands[] = 'where pdftotext';
    }

    foreach ($commands as $command) {
        $output = null;
        $return = null;
        @exec($command . ' 2>NUL', $output, $return);
        if ($return === 0 && !empty($output[0])) {
            $cached = trim($output[0]);
            return $cached;
        }
    }

    $cached = null;
    return null;
}

function savePdfAttachment(array $attachment, string $uploadDir): array
{
    ensureDirectoryExists($uploadDir);

    $content = $attachment['content'] ?? $attachment['body'] ?? $attachment['data'] ?? '';
    $filename = $attachment['filename'] ?? $attachment['name'] ?? 'attachment.pdf';
    $filename = sanitizeFilename($filename);

    $decoded = base64_decode(str_replace(["\r", "\n", ' '], '', $content), true);
    if ($decoded === false) {
        throw new RuntimeException('El contenido del PDF adjunto no es válido');
    }

    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . uniqid('pdf_', true) . '_' . $filename;

    if (file_put_contents($targetPath, $decoded) === false) {
        throw new RuntimeException('No se pudo guardar el PDF adjunto');
    }

    return [
        'path' => $targetPath,
        'name' => $filename,
    ];
}

function ensureDirectoryExists(string $dir): void
{
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

function sanitizeFilename(string $name): string
{
    $clean = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
    return $clean === '' ? 'attachment.pdf' : $clean;
}

function findPdfAttachmentInPayload(array $payload): ?array
{
    $lists = [];
    foreach (['attachments', 'files', 'attachments_data', 'documents'] as $key) {
        if (!empty($payload[$key]) && is_array($payload[$key])) {
            $lists[] = $payload[$key];
        }
    }

    foreach ($lists as $list) {
        foreach ($list as $item) {
            if (!is_array($item)) {
                continue;
            }

            $contentType = strtolower((string) ($item['content_type'] ?? $item['type'] ?? ''));
            $filename = strtolower((string) ($item['filename'] ?? $item['name'] ?? ''));

            if (strpos($contentType, 'pdf') === false && substr($filename, -4) !== '.pdf') {
                continue;
            }

            $content = $item['content'] ?? $item['body'] ?? $item['data'] ?? '';
            if (!is_string($content) || trim($content) === '') {
                continue;
            }

            return [
                'content' => $content,
                'filename' => $item['filename'] ?? $item['name'] ?? ($filename ?: 'adjunto.pdf'),
            ];
        }
    }

    return null;
}
