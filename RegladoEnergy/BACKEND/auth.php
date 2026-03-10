<?php

declare(strict_types=1);

require_once __DIR__ . '/security.php';

function requireAdminAuth(): array
{
    $token = extractBearerToken();
    if ($token === null) {
        error_log('ADMIN_AUTH_FAIL ip=' . getClientIpAddress() . ' reason=missing_token');
        respondUnauthorized('Falta el token de autorizacion.');
    }

    $payload = verifyJwt($token);

    if (($payload['role'] ?? null) !== 'admin') {
        error_log('ADMIN_AUTH_FAIL ip=' . getClientIpAddress() . ' sub=' . (string) ($payload['sub'] ?? '') . ' reason=forbidden_role');
        respondForbidden('Acceso restringido a administradores.');
    }

    return $payload;
}

function extractBearerToken(): ?string
{
    $headers = getHeadersLower();
    $authorization = $headers['authorization'] ?? null;

    if (!is_string($authorization)) {
        return null;
    }

    if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
        return null;
    }

    $token = trim($matches[1]);
    return $token !== '' ? $token : null;
}

function verifyJwt(string $token): array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        respondUnauthorized('Token invalido.');
    }

    [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

    $header = json_decode(base64UrlDecode($encodedHeader), true);
    $payload = json_decode(base64UrlDecode($encodedPayload), true);

    if (!is_array($header) || !is_array($payload)) {
        error_log('ADMIN_AUTH_FAIL ip=' . getClientIpAddress() . ' reason=invalid_structure');
        respondUnauthorized('Token invalido.');
    }

    if (($header['alg'] ?? null) !== 'HS256') {
        respondUnauthorized('Algoritmo no soportado.');
    }

    $secret = getJwtSecret();
    $expectedSignature = base64UrlEncode(hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $secret, true));

    if (!hash_equals($expectedSignature, $encodedSignature)) {
        error_log('ADMIN_AUTH_FAIL ip=' . getClientIpAddress() . ' reason=invalid_signature');
        respondUnauthorized('Firma de token invalida.');
    }

    $now = time();
    if (isset($payload['nbf']) && (int) $payload['nbf'] > $now) {
        respondUnauthorized('Token aun no valido.');
    }

    if (isset($payload['exp']) && (int) $payload['exp'] < $now) {
        error_log('ADMIN_AUTH_FAIL ip=' . getClientIpAddress() . ' reason=expired');
        respondUnauthorized('Token expirado.');
    }

    return $payload;
}

function getJwtSecret(): string
{
    static $secret = null;

    if (is_string($secret) && $secret !== '') {
        return $secret;
    }

    $secret = getenv('JWT_SECRET') ?: '';
    if ($secret !== '') {
        return $secret;
    }

    $localEnvPath = __DIR__ . DIRECTORY_SEPARATOR . '.env';
    if (is_file($localEnvPath)) {
        $values = parseEnvFile($localEnvPath);
        $secret = $values['JWT_SECRET'] ?? '';
        if ($secret !== '') {
            return $secret;
        }
    }

    $authEnvPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'ApiLoging' . DIRECTORY_SEPARATOR . '.env';
    if (is_file($authEnvPath)) {
        $values = parseEnvFile($authEnvPath);
        $secret = $values['JWT_SECRET'] ?? '';
        if ($secret !== '') {
            return $secret;
        }
    }

    respondUnauthorized('JWT_SECRET no configurado en el backend.');
}

function parseEnvFile(string $path): array
{
    $result = [];
    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return $result;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $position = strpos($line, '=');
        if ($position === false) {
            continue;
        }

        $key = trim(substr($line, 0, $position));
        $value = trim(substr($line, $position + 1));
        $result[$key] = $value;
    }

    return $result;
}

function getHeadersLower(): array
{
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        $normalized = [];
        foreach ($headers as $key => $value) {
            $normalized[strtolower((string) $key)] = $value;
        }

        return $normalized;
    }

    $headers = [];
    foreach ($_SERVER as $key => $value) {
        if (strpos($key, 'HTTP_') === 0) {
            $name = strtolower(str_replace('_', '-', substr($key, 5)));
            $headers[$name] = $value;
        }
    }

    return $headers;
}

function base64UrlDecode(string $value): string
{
    $remainder = strlen($value) % 4;
    if ($remainder > 0) {
        $value .= str_repeat('=', 4 - $remainder);
    }

    $decoded = base64_decode(strtr($value, '-_', '+/'), true);
    if ($decoded === false) {
        respondUnauthorized('Token malformado.');
    }

    return $decoded;
}

function base64UrlEncode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function respondUnauthorized(string $message): void
{
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function respondForbidden(string $message): void
{
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}
