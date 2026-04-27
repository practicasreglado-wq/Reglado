<?php
declare(strict_types=1);

/**
 * Rate limit genérico por clave (IP, user id, email, etc.).
 *
 * Usa la tabla `regladousers.rate_limits` (la misma que ya consume el sistema
 * de auth y los otros endpoints que aplican rate limit). La tabla tiene la
 * forma: (key_hash, scope_name, attempts, updated_at, created_at).
 *
 * - $scope: nombre único de la acción (ej. 'property_listing',
 *   'purchase_request'). Permite contadores independientes por acción.
 * - $identifier: valor que identifica al usuario (IP, email, user id…). Se
 *   hashea con SHA-256 antes de guardarlo — nunca se almacena en claro.
 * - $maxAttempts: número máximo de peticiones dentro de la ventana.
 * - $windowSeconds: duración de la ventana en segundos.
 * - $failOpen: si true, fallos de BD se ignoran (defense-in-depth). Si false,
 *   fallos de BD responden 500 (fail-closed, para operaciones sensibles).
 *
 * Cuando se supera el límite → respondJson(429) + exit. Si el caller debe
 * seguir ejecutando (contador incrementado, dentro del límite), vuelve
 * normalmente.
 */
function enforceIpRateLimit(string $scope, string $identifier, int $maxAttempts, int $windowSeconds, bool $failOpen = true): void
{
    if ($identifier === '') {
        // Sin identificador no podemos limitar — fail-open silencioso, el
        // caller ya debería haber comprobado que tiene un identificador.
        return;
    }

    try {
        $rlPdo = new PDO(
            sprintf(
                'mysql:host=%s;port=%s;dbname=regladousers;charset=utf8mb4',
                (string) getenv('DB_HOST'),
                (string) getenv('DB_PORT')
            ),
            (string) getenv('DB_USER'),
            (string) getenv('DB_PASS'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );

        $keyHash = hash('sha256', $scope . '|' . $identifier);

        $read = $rlPdo->prepare('SELECT id, attempts, updated_at FROM rate_limits WHERE key_hash = ? AND scope_name = ? LIMIT 1');
        $read->execute([$keyHash, $scope]);
        $row = $read->fetch();

        $nowTs = time();
        $withinWindow = $row && (strtotime((string) $row['updated_at']) ?: 0) >= $nowTs - $windowSeconds;

        if ($withinWindow && (int) $row['attempts'] >= $maxAttempts) {
            respondJson(429, [
                'success' => false,
                'message' => 'Demasiadas solicitudes. Espera un momento antes de volver a intentarlo.',
            ]);
        }

        if (!$row) {
            $rlPdo->prepare('INSERT INTO rate_limits(key_hash, scope_name, attempts, updated_at, created_at) VALUES(?, ?, 1, NOW(), NOW())')
                  ->execute([$keyHash, $scope]);
        } elseif (!$withinWindow) {
            $rlPdo->prepare('UPDATE rate_limits SET attempts = 1, updated_at = NOW() WHERE id = ?')
                  ->execute([(int) $row['id']]);
        } else {
            $rlPdo->prepare('UPDATE rate_limits SET attempts = attempts + 1, updated_at = NOW() WHERE id = ?')
                  ->execute([(int) $row['id']]);
        }
    } catch (Throwable $e) {
        if ($failOpen) {
            error_log('[rate_limit:' . $scope . '] check falló (fail-open): ' . $e->getMessage());
            return;
        }
        respondJson(500, [
            'success' => false,
            'message' => 'No se pudo validar la solicitud. Inténtalo de nuevo en unos segundos.',
        ]);
    }
}

/**
 * Devuelve la IP real del cliente. Usa REMOTE_ADDR por defecto (seguro).
 *
 * NOTA: si el backend está detrás de un reverse proxy (Cloudflare, nginx,
 * Hostinger edge) REMOTE_ADDR será la IP del proxy, no del cliente. Para esos
 * casos habría que leer `X-Forwarded-For` o `CF-Connecting-IP`, pero esas
 * cabeceras son spoofables si no se valida que la petición venga del proxy.
 * No se implementa automáticamente para no abrir una vía de bypass.
 */
function clientIp(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '');
}
