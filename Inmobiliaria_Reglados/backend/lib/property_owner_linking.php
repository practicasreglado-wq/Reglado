<?php
declare(strict_types=1);

function normalizeEmailForPropertyOwnerLinking(mixed $email): ?string
{
    $clean = strtolower(trim((string) ($email ?? '')));
    if ($clean === '') {
        return null;
    }

    return filter_var($clean, FILTER_VALIDATE_EMAIL) ? $clean : null;
}

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

