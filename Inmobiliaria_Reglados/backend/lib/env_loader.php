<?php
declare(strict_types=1);

/**
 * Cargador de variables de entorno desde un archivo `.env` plano.
 *
 * Implementación mínima propia (sin dependencias) — equivalente a vlucas/phpdotenv
 * pero sin el peso del paquete. Se llama al inicio de cualquier punto de
 * entrada (config/db.php, config/auth.php, crons, webhooks) antes de leer
 * variables con getenv().
 *
 * Soporta:
 *  - Comentarios con #
 *  - Líneas en blanco
 *  - Valores entre comillas simples o dobles (las quita)
 *
 * NO soporta:
 *  - Interpolación tipo ${OTRA_VAR}
 *  - Multi-línea
 *  - Escapes con backslash
 *
 * Si necesitas algo de eso, considera migrar a vlucas/phpdotenv vía composer.
 */

/**
 * Lee el archivo .env de la ruta indicada y publica cada par KEY=VALUE en
 * putenv() / $_ENV / $_SERVER. Si el archivo no existe o no es legible, no
 * hace nada (no lanza excepción) — útil en tests o scripts que pueden vivir
 * sin .env.
 */
function loadEnv(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (!str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);

        $name = trim($name);
        $value = trim($value);

        if ($name === '') {
            continue;
        }

        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

/**
 * Devuelve el nombre real de la BD principal (inmobiliaria) según el entorno.
 * En local suele ser 'inmobiliaria'; en producción Hostinger lleva prefijo
 * tipo 'u123456_inmobiliaria'. Se usa para construir DSN PDO y para resolver
 * nombres en SQL que usen el alias `inmobiliaria.` (ver DbAliasPdo en
 * config/db.php).
 */
if (!function_exists('dbNameInmobiliaria')) {
    function dbNameInmobiliaria(): string
    {
        return (string) (getenv('DB_NAME') ?: 'inmobiliaria');
    }
}