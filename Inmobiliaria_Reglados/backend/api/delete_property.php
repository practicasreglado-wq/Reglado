<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once __DIR__ . '/../config/cors.php';
require_once dirname(__DIR__) . '/lib/audit.php';

applyCors();
handlePreflight();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondJson(405, [
        'success' => false,
        'message' => 'Método no permitido.'
    ]);
}

function getTableColumns(PDO $pdo, string $schema, string $table): array
{
    $stmt = $pdo->prepare("
        SELECT COLUMN_NAME
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = :schema
          AND TABLE_NAME = :table
    ");
    $stmt->execute([
        'schema' => $schema,
        'table' => $table,
    ]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

function tableExists(PDO $pdo, string $schema, string $table): bool
{
    $stmt = $pdo->prepare("
        SELECT 1
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = :schema
          AND TABLE_NAME = :table
        LIMIT 1
    ");
    $stmt->execute([
        'schema' => $schema,
        'table' => $table,
    ]);

    return (bool) $stmt->fetchColumn();
}

function columnExists(PDO $pdo, string $schema, string $table, string $column): bool
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

function getExistingColumn(PDO $pdo, string $schema, string $table, array $candidates): ?string
{
    $columns = getTableColumns($pdo, $schema, $table);

    foreach ($candidates as $candidate) {
        if (in_array($candidate, $columns, true)) {
            return $candidate;
        }
    }

    return null;
}

function getSchemaTables(PDO $pdo, string $schema): array
{
    $stmt = $pdo->prepare("
        SELECT TABLE_NAME
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = :schema
          AND TABLE_TYPE = 'BASE TABLE'
    ");
    $stmt->execute([
        'schema' => $schema
    ]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

function normalizeRelativeUploadPath(?string $path): ?string
{
    if (!is_string($path)) {
        return null;
    }

    $path = trim($path);
    if ($path === '') {
        return null;
    }

    $path = str_replace('\\', '/', $path);
    $path = ltrim($path, '/');
    $path = str_replace('../', '', $path);

    return $path !== '' ? $path : null;
}

function absoluteUploadPath(string $relativePath): ?string
{
    $uploadsBase = realpath(dirname(__DIR__) . '/uploads');
    if ($uploadsBase === false) {
        return null;
    }

    $fullPath = $uploadsBase . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    $directory = realpath(dirname($fullPath));

    if ($directory === false) {
        return null;
    }

    if (!str_starts_with($directory, $uploadsBase)) {
        return null;
    }

    return $fullPath;
}

function collectFileForDeletion(array &$collector, ?string $relativePath): void
{
    $normalized = normalizeRelativeUploadPath($relativePath);
    if ($normalized === null) {
        return;
    }

    $collector[$normalized] = true;
}

function deleteCollectedFiles(array $fileMap): void
{
    foreach (array_keys($fileMap) as $relativePath) {
        $absolutePath = absoluteUploadPath($relativePath);

        if ($absolutePath === null) {
            continue;
        }

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }
}

function collectPropertyFiles(PDO $pdo, int $propertyId): array
{
    $files = [];

    $stmt = $pdo->prepare("
        SELECT
            dossier_file,
            confidentiality_file,
            intention_file
        FROM inmobiliaria.propiedades
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([
        'id' => $propertyId
    ]);

    $property = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    collectFileForDeletion($files, $property['dossier_file'] ?? null);
    collectFileForDeletion($files, $property['confidentiality_file'] ?? null);
    collectFileForDeletion($files, $property['intention_file'] ?? null);

    if (tableExists($pdo, 'inmobiliaria', 'documentos_firmados')) {
        $docColumns = getTableColumns($pdo, 'inmobiliaria', 'documentos_firmados');
        $fileColumns = array_values(array_filter($docColumns, static function ($column) {
            return str_ends_with($column, '_file_path') || str_ends_with($column, '_file');
        }));

        if (!empty($fileColumns)) {
            $selectCols = implode(', ', array_map(static fn($c) => "`$c`", $fileColumns));
            $propertyColumn = getExistingColumn($pdo, 'inmobiliaria', 'documentos_firmados', ['propiedad_id', 'property_id']);

            if ($propertyColumn !== null) {
                $signedStmt = $pdo->prepare("
                    SELECT {$selectCols}
                    FROM inmobiliaria.documentos_firmados
                    WHERE {$propertyColumn} = :property_id
                ");
                $signedStmt->execute([
                    'property_id' => $propertyId
                ]);

                foreach ($signedStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    foreach ($fileColumns as $column) {
                        collectFileForDeletion($files, $row[$column] ?? null);
                    }
                }
            }
        }
    }

    return $files;
}

function deleteAllRelatedRows(PDO $pdo, string $schema, int $propertyId): void
{
    $tables = getSchemaTables($pdo, $schema);

    foreach ($tables as $table) {
        if ($table === 'propiedades') {
            continue;
        }

        if (in_array($table, ['activos_recibidos', 'notifications', 'signed_document_review_tokens'], true)) {
            continue;
        }

        $propertyColumn = getExistingColumn($pdo, $schema, $table, ['propiedad_id', 'property_id']);
        if ($propertyColumn === null) {
            continue;
        }

        $sql = "DELETE FROM {$schema}.{$table} WHERE {$propertyColumn} = :property_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'property_id' => $propertyId
        ]);
    }
}

$context = requireAuthenticatedUser($pdo);
$auth = $context['auth'] ?? [];

$userId = (int) ($context['local']['iduser'] ?? 0);
$role = strtolower((string) ($auth['role'] ?? ''));
$isAdmin = ($role === 'admin');

$input = json_decode(file_get_contents('php://input') ?: '{}', true);

if (!is_array($input)) {
    respondJson(400, [
        'success' => false,
        'message' => 'Solicitud no válida.'
    ]);
}

$propertyId = (int) ($input['property_id'] ?? 0);

if ($userId <= 0) {
    respondJson(401, [
        'success' => false,
        'message' => 'Usuario no autenticado.'
    ]);
}

if ($propertyId <= 0) {
    respondJson(400, [
        'success' => false,
        'message' => 'ID de propiedad no válido.'
    ]);
}

try {
    $selectFields = ['id', 'owner_user_id', 'captador_id'];

    if (columnExists($pdo, 'inmobiliaria', 'propiedades', 'activo_recibido_id')) {
        $selectFields[] = 'activo_recibido_id';
    }

    $stmt = $pdo->prepare("
        SELECT " . implode(', ', $selectFields) . "
        FROM inmobiliaria.propiedades
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([
        'id' => $propertyId
    ]);

    $property = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$property) {
        respondJson(404, [
            'success' => false,
            'message' => 'La propiedad no existe.'
        ]);
    }

    $ownerUserId = (int) ($property['owner_user_id'] ?? 0);
    $activoRecibidoId = (int) ($property['activo_recibido_id'] ?? 0);

    if (!$isAdmin && $ownerUserId !== $userId) {
        respondJson(403, [
            'success' => false,
            'message' => 'No tienes permisos para eliminar esta propiedad.'
        ]);
    }

    $filesToDelete = collectPropertyFiles($pdo, $propertyId);

    $pdo->beginTransaction();

    deleteAllRelatedRows($pdo, 'inmobiliaria', $propertyId);

    if (tableExists($pdo, 'inmobiliaria', 'notifications')) {
        $stmtDeleteNotifications = $pdo->prepare("
            DELETE FROM inmobiliaria.notifications
            WHERE related_request_id = :property_id
        ");
        $stmtDeleteNotifications->execute([
            'property_id' => $propertyId
        ]);
    }

    if (tableExists($pdo, 'inmobiliaria', 'signed_document_review_tokens')) {
        $stmtDeleteReviewTokens = $pdo->prepare("
            DELETE FROM inmobiliaria.signed_document_review_tokens
            WHERE property_id = :property_id
        ");
        $stmtDeleteReviewTokens->execute([
            'property_id' => $propertyId
        ]);
    }

    if ($activoRecibidoId > 0 && tableExists($pdo, 'inmobiliaria', 'activos_recibidos')) {
        $stmtDeleteActivo = $pdo->prepare("
            DELETE FROM inmobiliaria.activos_recibidos
            WHERE id = :id
            LIMIT 1
        ");
        $stmtDeleteActivo->execute([
            'id' => $activoRecibidoId
        ]);
    }

    $deleteProperty = $pdo->prepare("
        DELETE FROM inmobiliaria.propiedades
        WHERE id = :id
        LIMIT 1
    ");
    $deleteProperty->execute([
        'id' => $propertyId
    ]);

    $pdo->commit();

    deleteCollectedFiles($filesToDelete);

    auditLog($pdo, 'property.delete', array_merge(
        auditContextFromAuth($auth, $userId),
        [
            'resource_type' => 'property',
            'resource_id'   => (string) $propertyId,
            'metadata'      => ['owner_user_id' => $ownerUserId, 'as_admin' => $isAdmin]
        ]
    ));

    respondJson(200, [
        'success' => true,
        'message' => 'Propiedad, registros relacionados y archivos asociados eliminados correctamente.'
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    respondJson(500, [
        'success' => false,
        'message' => 'Error al eliminar la propiedad: ' . $e->getMessage()
    ]);
}