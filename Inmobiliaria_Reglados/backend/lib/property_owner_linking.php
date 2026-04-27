<?php
declare(strict_types=1);

/**
 * Enlace tardío de propiedades a su dueño.
 *
 * Caso de uso: un agente externo manda una propiedad por email al webhook
 * (receive_email.php) usando un email de un usuario que TODAVÍA no se ha
 * registrado. La propiedad se guarda con `owner_user_id = NULL` y
 * `owner_email_pending = '<email>'`. Cuando ese email finalmente se
 * registra y hace login por primera vez, esta función vincula
 * automáticamente todas las propiedades pendientes a su user_id.
 *
 * Se llama desde `requireAuthenticatedUser()` en config/auth.php tras validar
 * el JWT, así que no hace falta invocarlo manualmente.
 */

/**
 * Normaliza un email para comparación: lowercase + trim. Devuelve null si
 * está vacío o no es un email válido (filter_var FILTER_VALIDATE_EMAIL).
 */
function normalizeEmailForPropertyOwnerLinking(mixed $email): ?string
{
    $clean = strtolower(trim((string) ($email ?? '')));
    if ($clean === '') {
        return null;
    }

    return filter_var($clean, FILTER_VALIDATE_EMAIL) ? $clean : null;
}

/**
 * Comprueba en INFORMATION_SCHEMA si una columna existe. Sirve para no
 * fallar en despliegues donde aún no se ha aplicado la migración que añade
 * `owner_user_id` / `owner_email_pending` a `propiedades`.
 */
function propertyOwnerLinkingColumnExists(PDO $pdo, string $schema, string $table, string $column): bool
{
    $stmt = $pdo->prepare("
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = :schema
          AND TABLE_NAME = :table
          AND COLUMN_NAME = :column
        LIMIT 1
    ");

    $stmt->execute([
        'schema' => $schema,
        'table' => $table,
        'column' => $column,
    ]);

    return (bool) $stmt->fetchColumn();
}

/**
 * Vincula propiedades pendientes por email al usuario autenticado.
 *
 * - Solo toca filas con owner_user_id IS NULL
 * - Matchea por owner_email_pending normalizado (lowercase + trim)
 */
function linkPendingPropertiesToUser(PDO $pdo, int $userId, string $email): int
{
    if ($userId <= 0) {
        return 0;
    }

    $normalizedEmail = normalizeEmailForPropertyOwnerLinking($email);
    if ($normalizedEmail === null) {
        return 0;
    }

    // Evita romper si aun no existen las columnas de la migracion.
    if (!propertyOwnerLinkingColumnExists($pdo, 'inmobiliaria', 'propiedades', 'owner_email_pending')) {
        return 0;
    }

    if (!propertyOwnerLinkingColumnExists($pdo, 'inmobiliaria', 'propiedades', 'owner_user_id')) {
        return 0;
    }

    $stmt = $pdo->prepare("
        UPDATE inmobiliaria.propiedades
        SET owner_user_id = :user_id,
            owner_email_pending = NULL
        WHERE owner_user_id IS NULL
          AND owner_email_pending = :email
    ");

    $stmt->execute([
        'user_id' => $userId,
        'email' => $normalizedEmail,
    ]);

    return (int) $stmt->rowCount();
}

