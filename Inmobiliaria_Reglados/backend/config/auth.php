<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/lib/env_loader.php';
require_once dirname(__DIR__) . '/lib/property_owner_linking.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

loadEnv(dirname(__DIR__) . '/.env');

function applyAuthCors(): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? null;

    $allowed = [
        'http://localhost:5175',
        'http://127.0.0.1:5175'
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

    $userId = (int) ($payload['sub'] ?? 0);

    try {
        $email = (string) ($payload['email'] ?? '');
        if ($userId > 0 && $email !== '') {
            linkPendingPropertiesToUser($pdo, $userId, $email);
        }
    } catch (Throwable $e) {
        // Silencioso: la autenticacion debe seguir funcionando aunque el enlace falle.
    }

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
    $secret = $_ENV['JWT_SECRET'];

    if ($secret === '') {
        respondJson(500, ["success" => false, "message" => "Configuración JWT incompleta"]);
    }

    try {
        $decoded = JWT::decode($token, new Key($secret, 'HS256'));
        return (array) $decoded;
    } catch (\Throwable $e) {
        respondJson(401, ["success" => false, "message" => "Token inválido"]);
        return [];
    }
}

function respondJson($code, $data)
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}
