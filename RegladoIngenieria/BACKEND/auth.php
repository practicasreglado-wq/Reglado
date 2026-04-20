<?php

declare(strict_types=1);

require_once __DIR__ . '/security.php';

function requireAuth(): array
{
    $token = extractBearerToken();
    if ($token === null) {
        respondJson(401, ['ok' => false, 'message' => 'Falta el token de autorización.']);
    }
    return verifyJwt($token);
}

function extractBearerToken(): ?string
{
    $headers = getHeadersLower();
    $authorization = $headers['authorization'] ?? null;
    if (!is_string($authorization)) return null;
    if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) return null;
    $token = trim($matches[1]);
    return $token !== '' ? $token : null;
}

function verifyJwt(string $token): array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) respondJson(401, ['ok' => false, 'message' => 'Token inválido.']);

    [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

    $header  = json_decode(base64UrlDecode($encodedHeader), true);
    $payload = json_decode(base64UrlDecode($encodedPayload), true);

    if (!is_array($header) || !is_array($payload)) {
        respondJson(401, ['ok' => false, 'message' => 'Token inválido.']);
    }

    if (($header['alg'] ?? null) !== 'HS256') {
        respondJson(401, ['ok' => false, 'message' => 'Algoritmo no soportado.']);
    }

    $secret = getJwtSecret();
    $expected = base64UrlEncode(hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $secret, true));

    if (!hash_equals($expected, $encodedSignature)) {
        error_log('AUTH_FAIL ip=' . getClientIp() . ' reason=invalid_signature');
        respondJson(401, ['ok' => false, 'message' => 'Firma de token inválida.']);
    }

    $now = time();
    if (isset($payload['nbf']) && (int)$payload['nbf'] > $now) {
        respondJson(401, ['ok' => false, 'message' => 'Token aún no válido.']);
    }
    if (isset($payload['exp']) && (int)$payload['exp'] < $now) {
        error_log('AUTH_FAIL ip=' . getClientIp() . ' reason=expired');
        respondJson(401, ['ok' => false, 'message' => 'Token expirado.']);
    }

    return $payload;
}

function requireAdminAuth(): array
{
    $payload = requireAuth();
    if (($payload['role'] ?? null) !== 'admin') {
        error_log('AUTH_FAIL ip=' . getClientIp() . ' reason=forbidden_role');
        respondJson(403, ['ok' => false, 'message' => 'Acceso restringido a administradores.']);
    }
    return $payload;
}

function getJwtSecret(): string
{
    static $secret = null;
    if (is_string($secret) && $secret !== '') return $secret;

    $secret = getenv('JWT_SECRET') ?: '';
    if ($secret !== '') return $secret;

    $localEnv = __DIR__ . DIRECTORY_SEPARATOR . '.env';
    if (is_file($localEnv)) {
        $values = parseEnvFile($localEnv);
        $secret = $values['JWT_SECRET'] ?? '';
        if ($secret !== '') return $secret;
    }

    $apiLogingEnv = dirname(__DIR__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'ApiLoging' . DIRECTORY_SEPARATOR . '.env';
    if (is_file($apiLogingEnv)) {
        $values = parseEnvFile($apiLogingEnv);
        $secret = $values['JWT_SECRET'] ?? '';
        if ($secret !== '') return $secret;
    }

    respondJson(500, ['ok' => false, 'message' => 'JWT_SECRET no configurado.']);
}

function parseEnvFile(string $path): array
{
    $result = [];
    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) return $result;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        $pos = strpos($line, '=');
        if ($pos === false) continue;
        $result[trim(substr($line, 0, $pos))] = trim(substr($line, $pos + 1));
    }
    return $result;
}

function getHeadersLower(): array
{
    if (function_exists('getallheaders')) {
        return array_combine(
            array_map('strtolower', array_keys(getallheaders())),
            array_values(getallheaders())
        );
    }
    $headers = [];
    foreach ($_SERVER as $key => $value) {
        if (str_starts_with($key, 'HTTP_')) {
            $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = $value;
        }
    }
    return $headers;
}

function base64UrlDecode(string $value): string
{
    $remainder = strlen($value) % 4;
    if ($remainder > 0) $value .= str_repeat('=', 4 - $remainder);
    $decoded = base64_decode(strtr($value, '-_', '+/'), true);
    if ($decoded === false) respondJson(401, ['ok' => false, 'message' => 'Token malformado.']);
    return $decoded;
}

function base64UrlEncode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}
