<?php

/**
 * Autenticación basada en JWT compartida con ApiLoging.
 *
 * Todos los endpoints de api/ que requieren login incluyen este archivo y
 * llaman a `requireAuthenticatedUser($pdo)` al principio para validar el
 * Bearer token y obtener los datos del usuario autenticado.
 *
 * El JWT lo emite ApiLoging y se firma con HS256 usando JWT_SECRET; este
 * secreto DEBE ser idéntico aquí y en ApiLoging — si se desincronizan, todos
 * los logins fallan con 401.
 *
 * Como efecto secundario al validar el token, intenta enlazar propiedades
 * pendientes (creadas antes de que el usuario tuviera cuenta) con el user_id
 * recién autenticado. Ver `linkPendingPropertiesToUser()` en
 * lib/property_owner_linking.php.
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/lib/env_loader.php';
require_once dirname(__DIR__) . '/lib/property_owner_linking.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

loadEnv(dirname(__DIR__) . '/.env');

/**
 * Versión LEGACY de CORS para endpoints antiguos.
 *
 * Tiene la lista de orígenes hardcodeada a localhost — solo sirve en
 * desarrollo. Para endpoints nuevos usa siempre `applyCors()` de
 * config/cors.php, que respeta CORS_ALLOWED_ORIGINS del .env.
 */
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

/**
 * Exige que la petición venga con un Bearer JWT válido.
 *
 * Si falla (sin token, token mal formado, firma inválida) responde 401 + exit
 * automáticamente — el caller no necesita comprobar nada. Si todo va bien
 * devuelve un array con dos claves:
 *
 *   - 'auth':  payload del JWT decodificado (sub, email, role, exp...).
 *   - 'local': pequeño helper con 'iduser' = sub, por compatibilidad con
 *              endpoints viejos que esperaban ese formato.
 *
 * Como bonus, intenta enlazar al usuario con propiedades huérfanas creadas a
 * su email antes de tener cuenta. Si ese enlace falla NO se aborta la
 * autenticación — el login es prioritario.
 */
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

/**
 * Extrae el token de la cabecera "Authorization: Bearer <token>".
 * Devuelve null si la cabecera no existe o no tiene el formato esperado.
 */
function extractBearerToken(): ?string
{
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? '';

    if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
        return $matches[1];
    }

    return null;
}

/**
 * Decodifica y valida la firma de un JWT con el secreto del .env.
 *
 * Si JWT_SECRET no está configurado responde 500 (configuración rota); si la
 * firma o expiración no validan responde 401. En ambos casos hace exit, así
 * que solo retorna cuando el token es legítimo.
 */
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

/**
 * Helper global para responder JSON + status code y terminar la petición.
 * Lo usan TODOS los endpoints — si lo modificas, revisa primero qué afecta.
 */
function respondJson($code, $data)
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}
