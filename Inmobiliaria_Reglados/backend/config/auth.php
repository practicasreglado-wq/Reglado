<?php

declare(strict_types=1);

function applyAuthCors(): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
    $allowed = [
        'http://localhost:5173',
        'http://localhost:5174',
        'http://localhost:5175',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:5174',
        'http://127.0.0.1:5175',
    ];

    if (is_string($origin) && $origin !== '') {
        if (!in_array($origin, $allowed, true)) {
            respondJson(403, ['success' => false, 'message' => 'Origen no permitido.']);
        }

        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    }

    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Content-Type: application/json; charset=utf-8');
}

function handlePreflight(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function requireAuthenticatedUser(PDO $pdo): array
{
    $token = extractBearerToken();
    if ($token === null) {
        respondJson(401, ['success' => false, 'message' => 'Debes iniciar sesión.']);
    }

    $payload = verifyJwt($token);
    $local = ensureLocalUserRecord($pdo, $payload);

    return [
        'auth' => $payload,
        'local' => $local,
    ];
}

function verifyJwt(string $token): array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        respondJson(401, ['success' => false, 'message' => 'Token no válido.']);
    }

    [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

    $header = json_decode(base64UrlDecode($encodedHeader), true);
    $payload = json_decode(base64UrlDecode($encodedPayload), true);

    if (!is_array($header) || !is_array($payload) || ($header['alg'] ?? null) !== 'HS256') {
        respondJson(401, ['success' => false, 'message' => 'Token no válido.']);
    }

    $secret = getJwtSecret();
    $expected = base64UrlEncode(hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $secret, true));

    if (!hash_equals($expected, $encodedSignature)) {
        respondJson(401, ['success' => false, 'message' => 'Token no válido.']);
    }

    $now = time();
    if (isset($payload['exp']) && (int) $payload['exp'] < $now) {
        respondJson(401, ['success' => false, 'message' => 'La sesión ha caducado.']);
    }

    return $payload;
}

function ensureLocalUserRecord(PDO $pdo, array $authUser): array
{
    $authUserId = (int) ($authUser['sub'] ?? 0);
    if ($authUserId <= 0) {
        respondJson(401, ['success' => false, 'message' => 'Token no válido.']);
    }

    $stmt = $pdo->prepare('SELECT * FROM inmobiliaria WHERE iduser = :iduser LIMIT 1');
    $stmt->execute(['iduser' => $authUserId]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($record) {
        return $record;
    }

    $pdo->prepare(
        'INSERT INTO inmobiliaria (iduser, categoria, preferencias) VALUES (:iduser, NULL, NULL)'
    )->execute([
        'iduser' => $authUserId,
    ]);

    $stmt->execute(['iduser' => $authUserId]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        respondJson(500, ['success' => false, 'message' => 'No se pudo crear el perfil local.']);
    }

    return $record;
}

function extractBearerToken(): ?string
{
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $authorization = $headers['Authorization'] ?? $headers['authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? null);

    if (!is_string($authorization)) {
        return null;
    }

    if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
        return null;
    }

    return trim($matches[1]);
}

function getJwtSecret(): string
{
    static $secret = null;

    if (is_string($secret) && $secret !== '') {
        return $secret;
    }

    $localEnv = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
    if (is_file($localEnv)) {
        $values = parseEnvFile($localEnv);
        $secret = $values['JWT_SECRET'] ?? '';
    }

    if (!$secret) {
        $authEnv = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'ApiLoging' . DIRECTORY_SEPARATOR . '.env';
        if (is_file($authEnv)) {
            $values = parseEnvFile($authEnv);
            $secret = $values['JWT_SECRET'] ?? '';
        }
    }

    if (!$secret) {
        respondJson(500, ['success' => false, 'message' => 'JWT_SECRET no configurado.']);
    }

    return $secret;
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

function base64UrlDecode(string $value): string
{
    $remainder = strlen($value) % 4;
    if ($remainder > 0) {
        $value .= str_repeat('=', 4 - $remainder);
    }

    $decoded = base64_decode(strtr($value, '-_', '+/'), true);
    if ($decoded === false) {
        respondJson(401, ['success' => false, 'message' => 'Token no válido.']);
    }

    return $decoded;
}

function base64UrlEncode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function respondJson(int $status, array $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}
