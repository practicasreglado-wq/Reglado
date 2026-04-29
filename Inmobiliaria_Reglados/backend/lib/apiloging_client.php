<?php
declare(strict_types=1);

/**
 * Cliente HTTP único para hablar con ApiLogin.
 *
 * Uso típico desde un endpoint:
 *
 *   require_once __DIR__ . '/../lib/apiloging_client.php';
 *   $user = apilogingFindUserById(42);
 *
 * Auth: Service JWT firmado con SERVICE_JWT_SECRET (separado del JWT_SECRET
 * de usuarios humanos), claim sub=service:inmobiliaria, role=service, TTL
 * 5 min. ApiLogin lo verifica con su propia copia de SERVICE_JWT_SECRET y
 * salta los chequeos de estado de usuario humano (revoked_tokens,
 * password_changed_at, etc.).
 *
 * Aislar el secreto del de usuarios es defensa en profundidad: si este
 * servidor se ve comprometido, el atacante NO puede mintear JWTs de
 * usuarios humanos en ApiLogin (no tiene JWT_SECRET), solo Service JWTs
 * con alcance acotado a /auth/admin/*.
 *
 * Errores: lanza ApilogingClientException con httpStatus para que el caller
 * pueda diferenciar 404 (no existe) de 5xx (ApiLogin caído). Los helpers
 * de alto nivel ya convierten 404 a `null` cuando es semánticamente correcto.
 *
 * Variables de entorno requeridas:
 *   APILOGING_BASE_URL   Base sin barra final, p.ej. https://regladogroup.com
 *   SERVICE_JWT_SECRET   Secreto compartido con ApiLogin SOLO para service JWTs
 *   JWT_ISSUER           (opcional) Mismo que ApiLogin, default 'reglado-auth'
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once __DIR__ . '/env_loader.php';

use Firebase\JWT\JWT;

class ApilogingClientException extends RuntimeException
{
    public int $httpStatus = 0;
}

function apilogingBaseUrl(): string
{
    return rtrim((string) (getenv('APILOGING_BASE_URL') ?: ''), '/');
}

/**
 * Mintea un Service JWT efímero para autenticarse contra ApiLogin.
 *
 * Caduca en 5 minutos: ventana suficiente para una request completa y corta
 * para limitar replay si el token se filtrase. El claim `sub: service:inmobiliaria`
 * identifica al servicio caller — no es un user_id real.
 *
 * Firma con SERVICE_JWT_SECRET (separado del JWT_SECRET que firma JWTs de
 * usuarios humanos). Si este servidor se compromete, el atacante no puede
 * suplantar admins en ApiLogin — solo puede llamar /auth/admin/*.
 */
function apilogingMintServiceJwt(): string
{
    $secret = (string) (getenv('SERVICE_JWT_SECRET') ?: '');
    if ($secret === '') {
        throw new RuntimeException('SERVICE_JWT_SECRET no configurado en inmobiliaria');
    }
    $issuer = (string) (getenv('JWT_ISSUER') ?: 'reglado-auth');

    $now = time();
    $payload = [
        'iss'  => $issuer,
        'iat'  => $now,
        'exp'  => $now + 300,
        'sub'  => 'service:inmobiliaria',
        'role' => 'service',
    ];

    return JWT::encode($payload, $secret, 'HS256');
}

/**
 * Hace una request HTTP a ApiLogin con Service JWT y devuelve el body
 * decodificado (asumiendo respuesta JSON).
 *
 * - Timeout 5s: ApiLogin está al lado en el mismo datacenter; si tarda más
 *   es que algo raro pasa, mejor fail-closed que dejar al cliente esperando.
 * - Lanza ApilogingClientException con httpStatus en cualquier respuesta
 *   no-2xx; los helpers de alto nivel deciden qué statuses convertir a null.
 */
function apilogingHttpRequest(string $method, string $path, ?array $body = null, ?array $query = null): array
{
    $base = apilogingBaseUrl();
    if ($base === '') {
        throw new RuntimeException('APILOGING_BASE_URL no configurado');
    }

    $url = $base . '/' . ltrim($path, '/');
    if ($query !== null && $query !== []) {
        $url .= '?' . http_build_query($query);
    }

    $token = apilogingMintServiceJwt();
    $headers = [
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
    ];

    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_CUSTOMREQUEST  => strtoupper($method),
        CURLOPT_HTTPHEADER     => $headers,
    ];

    if ($body !== null) {
        $json = json_encode($body, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('No se pudo serializar el body');
        }
        $opts[CURLOPT_POSTFIELDS] = $json;
        $opts[CURLOPT_HTTPHEADER] = array_merge($headers, ['Content-Type: application/json']);
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, $opts);
    $response = curl_exec($ch);
    $info     = curl_getinfo($ch);
    $errno    = curl_errno($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($errno !== 0) {
        error_log('[apiloging_client] cURL error ' . $errno . ' calling ' . $method . ' ' . $url . ': ' . $error);
        $ex = new ApilogingClientException('ApiLogin HTTP error: ' . $error);
        throw $ex;
    }

    $status = (int) ($info['http_code'] ?? 0);
    $decoded = $response !== false ? json_decode((string) $response, true) : null;
    if (!is_array($decoded)) {
        // Body no parseable: lo guardamos crudo para debug pero seguimos.
        $decoded = ['raw' => is_string($response) ? $response : null];
    }

    if ($status >= 200 && $status < 300) {
        return $decoded;
    }

    error_log('[apiloging_client] ' . $status . ' ' . $method . ' ' . $url . ' → ' . json_encode($decoded));

    $ex = new ApilogingClientException(
        'ApiLogin respondió ' . $status . ': ' . (string) ($decoded['error'] ?? 'unknown')
    );
    $ex->httpStatus = $status;
    throw $ex;
}

// ─── Helpers de alto nivel ─────────────────────────────────────────────

/**
 * Resuelve un usuario por id. Devuelve null si no existe (404 silencioso).
 * Otros errores se propagan.
 */
function apilogingFindUserById(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    try {
        $resp = apilogingHttpRequest('GET', '/auth/admin/users/by-id', null, ['id' => $id]);
        return $resp['user'] ?? null;
    } catch (ApilogingClientException $e) {
        if ($e->httpStatus === 404) {
            return null;
        }
        throw $e;
    }
}

/**
 * Resuelve un usuario por email. Devuelve null si no existe.
 */
function apilogingFindUserByEmail(string $email): ?array
{
    $email = trim($email);
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }
    try {
        $resp = apilogingHttpRequest('GET', '/auth/admin/users/by-email', null, ['email' => $email]);
        return $resp['user'] ?? null;
    } catch (ApilogingClientException $e) {
        if ($e->httpStatus === 404) {
            return null;
        }
        throw $e;
    }
}

/**
 * Resuelve varios usuarios en una sola petición. Útil para JOINs:
 * el caller tiene N user_ids en una tabla local y necesita info de
 * perfil de todos sin hacer N round-trips.
 *
 * Devuelve un array de filas user (sin garantía de orden ni completitud).
 * Si necesitas hacer lookup por id, usa apilogingFindManyUsersIndexedById.
 *
 * @param int[]    $ids
 * @param string[] $emails
 * @return array<int, array>
 */
function apilogingFindManyUsers(array $ids = [], array $emails = []): array
{
    if ($ids === [] && $emails === []) {
        return [];
    }
    $resp = apilogingHttpRequest('POST', '/auth/admin/users-batch', [
        'ids'    => array_values(array_unique(array_map('intval', $ids))),
        'emails' => array_values(array_unique($emails)),
    ]);
    return $resp['users'] ?? [];
}

/**
 * Misma función que apilogingFindManyUsers pero devuelve un mapa
 * id => user para hacer lookup O(1) durante un JOIN local.
 *
 * @param int[] $ids
 * @return array<int, array>
 */
function apilogingFindManyUsersIndexedById(array $ids): array
{
    $byId = [];
    foreach (apilogingFindManyUsers($ids) as $u) {
        $byId[(int) $u['id']] = $u;
    }
    return $byId;
}

/**
 * Lista todos los usuarios. Equivalente a un SELECT sin filtro sobre la
 * tabla de usuarios — pensado para vistas de admin que necesitan ver
 * todo el censo (panel "Gestión de usuarios", notificaciones masivas,
 * resolución de admins por rol).
 */
function apilogingListAllUsers(): array
{
    $resp = apilogingHttpRequest('GET', '/auth/admin/users');
    return $resp['users'] ?? [];
}

/**
 * Verifica la contraseña de un usuario (típicamente el admin que confirma
 * una acción sensible). Devuelve true si es correcta, false si no.
 *
 * NO lanza excepción en 401/422 (password incorrecta o inputs inválidos)
 * — el caller puede contar intentos fallidos contra el rate limit local.
 * Sí propaga 5xx y errores de red.
 */
function apilogingVerifyUserPassword(int $userId, string $password): bool
{
    try {
        $resp = apilogingHttpRequest('POST', '/auth/admin/verify-password', [
            'user_id'  => $userId,
            'password' => $password,
        ]);
        return (bool) ($resp['valid'] ?? false);
    } catch (ApilogingClientException $e) {
        if ($e->httpStatus === 401 || $e->httpStatus === 422) {
            return false;
        }
        throw $e;
    }
}

/**
 * Actualiza el rol de un usuario en ApiLogin. Pensado para flujos del
 * backend de inmobiliaria que ya autenticaron a su propio caller (sea por
 * admin password reauth, sea por token de email firmado).
 *
 * Roles válidos: 'user', 'real', 'admin'.
 *
 * Devuelve true si el cambio aplicó. Lanza ApilogingClientException si
 * ApiLogin rechaza la operación (404 user no existe, 409 último admin,
 * 5xx, etc.) — el caller puede capturar httpStatus para diferenciar.
 */
function apilogingUpdateUserRole(int $userId, string $role): bool
{
    apilogingHttpRequest('POST', '/auth/admin/update-role', [
        'user_id' => $userId,
        'role'    => $role,
    ]);
    return true;
}
