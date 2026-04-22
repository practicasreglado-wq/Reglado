<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

// Este archivo contiene funciones para aplicar cabeceras de seguridad y CORS.
// Se asegura de que las solicitudes cumplan con las políticas de seguridad definidas.

function applySecurityHeaders(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
    header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
}

function applyCorsHeaders(array $methods, string $headers = 'Content-Type, Authorization', bool $json = true): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
    if (is_string($origin) && $origin !== '') {
        if (isAllowedOrigin($origin)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
            header('Access-Control-Allow-Credentials: true');
        } else {
            respondSecurityJson(403, ['ok' => false, 'message' => 'Origen no permitido: ' . $origin]);
        }
    }

    header('Access-Control-Allow-Methods: ' . implode(', ', $methods));
    header('Access-Control-Allow-Headers: ' . $headers);
    if ($json) {
        header('Content-Type: application/json; charset=utf-8');
    }
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
    $value = trim((string) (getenv($key) ?: ''));
    if ($value === '') {
        return [];
    }

    $parts = array_map('trim', explode(',', $value));
    $parts = array_values(array_filter($parts, static fn ($item) => $item !== ''));
    return array_values(array_unique($parts));
}

function getClientIpAddress(): string
{
    $candidate = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    if (!is_string($candidate) || trim($candidate) === '') {
        return 'unknown';
    }

    foreach (array_map('trim', explode(',', $candidate)) as $part) {
        if (filter_var($part, FILTER_VALIDATE_IP)) {
            return $part;
        }
    }

    return 'unknown';
}

function enforceProductionSecurity(): void
{
    $env = strtolower((string) (getenv('APP_ENV') ?: 'local'));
    if (!in_array($env, ['production', 'prod'], true)) {
        return;
    }

    if (!isHttpsRequest()) {
        respondSecurityJson(400, ['ok' => false, 'message' => 'HTTPS obligatorio.']);
    }

    $secret = trim((string) (getenv('JWT_SECRET') ?: ''));
    if ($secret === '' || $secret === 'change-this-secret' || strlen($secret) < 32) {
        respondSecurityJson(500, ['ok' => false, 'message' => 'JWT_SECRET inseguro.']);
    }
}

function isHttpsRequest(): bool
{
    $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
    if ($https === 'on' || $https === '1') {
        return true;
    }

    $scheme = strtolower((string) ($_SERVER['REQUEST_SCHEME'] ?? ''));
    if ($scheme === 'https') {
        return true;
    }

    return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

function respondSecurityJson(int $status, array $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}
