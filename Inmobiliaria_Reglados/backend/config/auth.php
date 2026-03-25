<?php

function applyAuthCors(): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? null;

    $allowed = [
        'http://localhost:5173',
        'http://127.0.0.1:5173'
    ];

    if ($origin && in_array($origin, $allowed, true)) {
        header("Access-Control-Allow-Origin: $origin");
        header("Vary: Origin");
    }

    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Content-Type: application/json");
}

function requireAuthenticatedUser(PDO $pdo): array
{
    $token = extractBearerToken();

    if (!$token) {
        respondJson(401, ["success" => false, "message" => "Debes iniciar sesión"]);
    }

    $payload = verifyJwt($token);

    return [
        'auth' => $payload,
        'local' => [
            'iduser' => (int) ($payload['sub'] ?? 0)
        ]
    ];
}

function extractBearerToken(): ?string
{
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? '';

    if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
        return $matches[1];
    }

    return null;
}

function verifyJwt(string $token): array
{
    $parts = explode('.', $token);

    if (count($parts) !== 3) {
        respondJson(401, ["success" => false, "message" => "Token inválido"]);
    }

    [$header, $payload, $signature] = $parts;

    $secret = "change-this-secret"; // 🔥 mismo que login externo

    $validSignature = base64UrlEncode(
        hash_hmac('sha256', "$header.$payload", $secret, true)
    );

    if (!hash_equals($validSignature, $signature)) {
        respondJson(401, ["success" => false, "message" => "Token inválido"]);
    }

    $decoded = json_decode(base64UrlDecode($payload), true);

    if (!$decoded) {
        respondJson(401, ["success" => false, "message" => "Token inválido"]);
    }

    if (isset($decoded['exp']) && $decoded['exp'] < time()) {
        respondJson(401, ["success" => false, "message" => "Token expirado"]);
    }

    return $decoded;
}

function base64UrlDecode($data)
{
    return base64_decode(strtr($data, '-_', '+/'));
}

function base64UrlEncode($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function respondJson($code, $data)
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}