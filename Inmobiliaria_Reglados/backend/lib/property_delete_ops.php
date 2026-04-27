<?php
declare(strict_types=1);

/**
 * Operaciones compartidas de borrado de propiedades. Usadas por:
 *  - api/delete_property.php          (borrado directo desde admin o dueño)
 *  - api/approve_property_deletion.php (admin aprueba una solicitud)
 */

if (!function_exists('getTableColumns')) {
    function getTableColumns(PDO $pdo, string $schema, string $table): array
    {
        $stmt = $pdo->prepare("
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = :schema
              AND TABLE_NAME = :table
        ");
        $stmt->execute(['schema' => $schema, 'table' => $table]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }
}

if (!function_exists('tableExists')) {
    function tableExists(PDO $pdo, string $schema, string $table): bool
    {
        $stmt = $pdo->prepare("
            SELECT 1 FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table LIMIT 1
        ");
        $stmt->execute(['schema' => $schema, 'table' => $table]);
        return (bool) $stmt->fetchColumn();
    }
}

if (!function_exists('columnExists')) {
    function columnExists(PDO $pdo, string $schema, string $table, string $column): bool
    {
        $stmt = $pdo->prepare("
            SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table AND COLUMN_NAME = :column LIMIT 1
        ");
        $stmt->execute(['schema' => $schema, 'table' => $table, 'column' => $column]);
        return (bool) $stmt->fetchColumn();
    }
}

if (!function_exists('getExistingColumn')) {
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
}

if (!function_exists('getSchemaTables')) {
    function getSchemaTables(PDO $pdo, string $schema): array
    {
        $stmt = $pdo->prepare("
            SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = :schema AND TABLE_TYPE = 'BASE TABLE'
        ");
        $stmt->execute(['schema' => $schema]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }
}

if (!function_exists('normalizeRelativeUploadPath')) {
    function normalizeRelativeUploadPath(?string $path): ?string
    {
        if (!is_string($path)) return null;
        $path = trim($path);
        if ($path === '') return null;
        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');
        $path = str_replace('../', '', $path);
        return $path !== '' ? $path : null;
    }
}

if (!function_exists('absoluteUploadPath')) {
    function absoluteUploadPath(string $relativePath): ?string
    {
        $uploadsBase = realpath(dirname(__DIR__) . '/uploads');
        if ($uploadsBase === false) return null;

        $fullPath = $uploadsBase . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $directory = realpath(dirname($fullPath));
        if ($directory === false) return null;
        if (!str_starts_with($directory, $uploadsBase)) return null;

        return $fullPath;
    }
}

if (!function_exists('collectFileForDeletion')) {
    function collectFileForDeletion(array &$collector, ?string $relativePath): void
    {
        $normalized = normalizeRelativeUploadPath($relativePath);
        if ($normalized !== null) {
            $collector[$normalized] = true;
        }
    }
}

if (!function_exists('deleteCollectedFiles')) {
    function deleteCollectedFiles(array $fileMap): void
    {
        foreach (array_keys($fileMap) as $relativePath) {
            $absolutePath = absoluteUploadPath($relativePath);
            if ($absolutePath === null) continue;
            if (is_file($absolutePath) && !unlink($absolutePath)) {
                error_log('[property_delete_ops] unlink falló: ' . $absolutePath);
            }
        }
    }
}

if (!function_exists('collectPropertyFiles')) {
    function collectPropertyFiles(PDO $pdo, int $propertyId): array
    {
        $files = [];

        $stmt = $pdo->prepare("
            SELECT dossier_file, confidentiality_file, intention_file
            FROM inmobiliaria.propiedades
            WHERE id = :id LIMIT 1
        ");
        $stmt->execute(['id' => $propertyId]);
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
                    $signedStmt->execute(['property_id' => $propertyId]);

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
}

if (!function_exists('deleteAllRelatedRows')) {
    function deleteAllRelatedRows(PDO $pdo, string $schema, int $propertyId): void
    {
        $tables = getSchemaTables($pdo, $schema);

        foreach ($tables as $table) {
            if ($table === 'propiedades') continue;
            if (in_array($table, ['activos_recibidos', 'notifications', 'signed_document_review_tokens'], true)) continue;

            $propertyColumn = getExistingColumn($pdo, $schema, $table, ['propiedad_id', 'property_id']);
            if ($propertyColumn === null) continue;

            $sql = "DELETE FROM {$schema}.{$table} WHERE {$propertyColumn} = :property_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['property_id' => $propertyId]);
        }
    }
}

/**
 * Flujo completo de borrado: recolecta archivos, borra relacionados, borra
 * tokens/notifs/activos, borra la propiedad, commit, borra ficheros del
 * disco. Devuelve ['owner_user_id' => int, 'activo_recibido_id' => int] para
 * que el caller pueda hacer audit log con el dato.
 *
 * Lanza Throwable si falla — el caller debe capturar y hacer rollback si
 * está dentro de su propia transacción.
 */
if (!function_exists('executePropertyDeletion')) {
    function executePropertyDeletion(PDO $pdo, int $propertyId): array
    {
        $selectFields = ['id', 'owner_user_id', 'captador_id'];
        if (columnExists($pdo, 'inmobiliaria', 'propiedades', 'activo_recibido_id')) {
            $selectFields[] = 'activo_recibido_id';
        }

        $stmt = $pdo->prepare("
            SELECT " . implode(', ', $selectFields) . "
            FROM inmobiliaria.propiedades
            WHERE id = :id LIMIT 1
        ");
        $stmt->execute(['id' => $propertyId]);
        $property = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$property) {
            throw new RuntimeException('La propiedad no existe.');
        }

        $ownerUserId = (int) ($property['owner_user_id'] ?? 0);
        $activoRecibidoId = (int) ($property['activo_recibido_id'] ?? 0);

        $filesToDelete = collectPropertyFiles($pdo, $propertyId);

        $pdo->beginTransaction();

        deleteAllRelatedRows($pdo, 'inmobiliaria', $propertyId);

        if (tableExists($pdo, 'inmobiliaria', 'notifications')) {
            $pdo->prepare('DELETE FROM inmobiliaria.notifications WHERE related_request_id = :p')
                ->execute(['p' => $propertyId]);
        }

        if (tableExists($pdo, 'inmobiliaria', 'signed_document_review_tokens')) {
            $pdo->prepare('DELETE FROM inmobiliaria.signed_document_review_tokens WHERE property_id = :p')
                ->execute(['p' => $propertyId]);
        }

        if ($activoRecibidoId > 0 && tableExists($pdo, 'inmobiliaria', 'activos_recibidos')) {
            $pdo->prepare('DELETE FROM inmobiliaria.activos_recibidos WHERE id = :id LIMIT 1')
                ->execute(['id' => $activoRecibidoId]);
        }

        $pdo->prepare('DELETE FROM inmobiliaria.propiedades WHERE id = :id LIMIT 1')
            ->execute(['id' => $propertyId]);

        $pdo->commit();

        deleteCollectedFiles($filesToDelete);

        return [
            'owner_user_id'      => $ownerUserId,
            'activo_recibido_id' => $activoRecibidoId,
        ];
    }
}
