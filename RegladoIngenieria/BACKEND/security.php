<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function applySecurityHeaders(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

function applyCorsHeaders(array $methods, string $headers = 'Content-Type, Authorization', bool $json = true): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
    if (is_string($origin) && isAllowedOrigin($origin)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    } elseif (is_string($origin) && $origin !== '') {
        respondJson(403, ['ok' => false, 'message' => 'Origen no permitido.']);
    }

    header('Access-Control-Allow-Methods: ' . implode(', ', $methods));
    header('Access-Control-Allow-Headers: ' . $headers);
    if ($json) header('Content-Type: application/json; charset=utf-8');
}

function isAllowedOrigin(string $origin): bool
{
    $allowed = parseCsvEnv('CORS_ALLOWED_ORIGINS');
    if ($allowed === []) {
        $allowed = [
            'http://localhost:5173',
            'http://localhost:5174',
            'http://127.0.0.1:5173',
            'http://127.0.0.1:5174',
        ];
    }
    return in_array($origin, $allowed, true);
}

function parseCsvEnv(string $key): array
{
    $value = trim((string)(getenv($key) ?: ''));
    if ($value === '') return [];
    $parts = array_map('trim', explode(',', $value));
    return array_values(array_unique(array_filter($parts, fn($i) => $i !== '')));
}

function getClientIp(): string
{
    $candidate = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    if (!is_string($candidate) || trim($candidate) === '') return 'unknown';
    foreach (array_map('trim', explode(',', $candidate)) as $part) {
        if (filter_var($part, FILTER_VALIDATE_IP)) return $part;
    }
    return 'unknown';
}

function respondJson(int $status, array $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}
