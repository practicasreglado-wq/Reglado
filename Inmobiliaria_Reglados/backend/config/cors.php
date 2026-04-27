<?php
declare(strict_types=1);

/**
 * Política CORS y cabeceras de seguridad para todos los endpoints de api/.
 *
 * Uso típico al inicio de un endpoint:
 *
 *   require_once __DIR__ . '/../config/cors.php';
 *   applyCors();
 *   handlePreflight();
 *
 * `applyCors()` debe llamarse SIEMPRE — añade cabeceras de seguridad genéricas
 * (X-Content-Type-Options, X-Frame-Options, Referrer-Policy) aunque el origen
 * no esté permitido, para que las respuestas de error también estén
 * protegidas. `handlePreflight()` corta los OPTIONS con 204 antes de tocar
 * lógica de negocio.
 */

/**
 * Devuelve la lista de orígenes que pueden hacer peticiones CORS.
 *
 * Combina dos fuentes:
 *  1) Defaults de localhost (solo en entorno de desarrollo).
 *  2) Lo que haya en la variable de entorno CORS_ALLOWED_ORIGINS (lista
 *     separada por comas, sin barra final por origen).
 *
 * En producción real, si te olvidas de configurar CORS_ALLOWED_ORIGINS, la
 * lista queda vacía y el navegador rechaza cualquier origen (fail-closed).
 */
function getCorsAllowedOrigins(): array
{
    // Los defaults de localhost SOLO se aplican en desarrollo. Detectamos local
    // por dos vías independientes porque applyCors() suele ejecutarse antes de
    // loadEnv() y getenv('APP_ENV') puede no estar disponible todavía:
    //   1) APP_ENV=local explícito en el entorno.
    //   2) El Host con el que el navegador está accediendo al backend coincide
    //      con un patrón de desarrollo (localhost, 127.0.0.1, .test, .local).
    // En producción real ninguno de los dos aplica, así que si olvidas
    // CORS_ALLOWED_ORIGINS en el .env el servidor rechaza cualquier origen
    // (fail-closed).
    $appEnv = strtolower((string) (getenv('APP_ENV') ?: ''));
    $isLocalEnv = in_array($appEnv, ['local', 'dev', 'development'], true);

    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $hostWithoutPort = explode(':', $host)[0];
    $isLocalHost = (
        $hostWithoutPort === 'localhost'
        || $hostWithoutPort === '127.0.0.1'
        || str_ends_with($hostWithoutPort, '.local')
        || str_ends_with($hostWithoutPort, '.test')
    );

    $isLocal = $isLocalEnv || $isLocalHost;

    $defaults = $isLocal
        ? ['http://localhost:5175', 'http://127.0.0.1:5175']
        : [];

    $envRaw = getenv('CORS_ALLOWED_ORIGINS') ?: '';
    $extras = array_filter(array_map('trim', explode(',', $envRaw)), function ($value) {
        return $value !== '';
    });

    return array_values(array_unique(array_merge($defaults, $extras)));
}

/**
 * Aplica las cabeceras CORS al response actual + cabeceras de seguridad
 * genéricas. Solo añade Access-Control-Allow-Origin si el origen del cliente
 * está en la lista blanca; si no, las cabeceras CORS no se mandan y el
 * navegador bloquea la respuesta (comportamiento esperado).
 */
function applyCors(): void
{
    $allowedOrigins = getCorsAllowedOrigins();
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if ($origin && in_array($origin, $allowedOrigins, true)) {
        header("Access-Control-Allow-Origin: $origin");
        header('Vary: Origin');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    }

    // Cabeceras de seguridad genéricas (se aplican siempre, aunque el origen
    // no coincida, para que respuestas de error también estén protegidas).
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

/**
 * Corta peticiones OPTIONS (preflight CORS) devolviendo 204 sin cuerpo. Debe
 * llamarse JUSTO después de applyCors() para que el navegador reciba las
 * cabeceras de la política antes de hacer la petición real.
 */
function handlePreflight(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
