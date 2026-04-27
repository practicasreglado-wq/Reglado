<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/geocoding.php';
require_once dirname(__DIR__) . '/lib/address_hash.php';

/**
 * Capa de acceso a BD para el pipeline de procesado de propiedades entrantes
 * (vía email/CloudMailin → receive_email.php → PropertyProcessor).
 *
 * Centraliza todas las queries que necesita el pipeline:
 *  - insertReceivedAsset(): registra el correo entrante crudo en
 *    `activos_recibidos` con un hash de contenido para deduplicar.
 *  - createOrUpdateProperty / updateProperty / etc.: persisten la ficha
 *    estructurada que produce ClaudeClient.
 *  - findRegladoUserIdByEmail(): resuelve el dueño del activo si el email
 *    asignado por la IA pertenece a un usuario ya registrado.
 *
 * Hace cache en memoria de columnas existentes (`columnExistsCache`) para no
 * machacar INFORMATION_SCHEMA en runs largos. El cache vive solo durante una
 * petición/proceso, no entre invocaciones.
 */
class Repository
{
    private PDO $pdo;
    private array $columnExistsCache = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function insertReceivedAsset(
        string $origin,
        ?string $emailRemitente,
        string $texto,
        string $contentHash,
        ?string $messageId = null,
        ?array $metadata = null
    ): array {
        $stmt = $this->pdo->prepare(
            "INSERT INTO activos_recibidos (
                origen,
                email_remitente,
                texto_recibido,
                metadata,
                procesado,
                message_id,
                content_hash
            ) VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        try {
            $stmt->execute([
                $origin,
                $emailRemitente,
                $texto,
                $metadata !== null ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null,
                'pendiente',
                $messageId,
                $contentHash,
            ]);

            $insertedAssetId = (int) $this->pdo->lastInsertId();
            error_log('[ACTIVO RECIBIDO INSERT] id=' . $insertedAssetId . ' origin=' . $origin);

            return [
                'id' => $insertedAssetId,
                'is_duplicate' => false,
            ];
        } catch (PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                $existingId = $this->findExistingAssetId($messageId, $contentHash, $texto);

                if ($existingId !== null) {
                    return [
                        'id' => $existingId,
                        'is_duplicate' => true,
                    ];
                }
            }

            throw $e;
        }
    }

    public function findExistingAssetId(?string $messageId, string $contentHash, ?string $texto = null): ?int
    {
        if ($messageId !== null && trim($messageId) !== '') {
            $stmt = $this->pdo->prepare('SELECT id FROM activos_recibidos WHERE message_id = ? LIMIT 1');
            $stmt->execute([$messageId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && isset($row['id'])) {
                return (int) $row['id'];
            }
        }

        $stmt = $this->pdo->prepare('SELECT id FROM activos_recibidos WHERE content_hash = ? LIMIT 1');
        $stmt->execute([$contentHash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && isset($row['id'])) {
            return (int) $row['id'];
        }

        if ($texto !== null) {
            $stmt = $this->pdo->prepare('SELECT id FROM activos_recibidos WHERE texto_recibido = ? ORDER BY id DESC LIMIT 1');
            $stmt->execute([$texto]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && isset($row['id'])) {
                return (int) $row['id'];
            }
        }

        return null;
    }

    public function getReceivedAsset(int $assetId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM activos_recibidos WHERE id = ?');
        $stmt->execute([$assetId]);
        $asset = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($asset === false) {
            throw new RuntimeException("Activo recibido {$assetId} no encontrado");
        }

        return $asset;
    }

    public function getReceivedAssetStatus(int $assetId): ?string
        {
            $stmt = $this->pdo->prepare('SELECT procesado FROM activos_recibidos WHERE id = ? LIMIT 1');
            $stmt->execute([$assetId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row['procesado'] ?? null;
        }

        public function isAssetAlreadyProcessed(int $assetId): bool
        {
            return $this->getReceivedAssetStatus($assetId) === 'procesado';
        }

        public function updateReceivedAssetStatus(int $assetId, string $status, ?int $captadorId = null): void
        {
            $stmt = $this->pdo->prepare(
                'UPDATE activos_recibidos
                SET procesado = ?, processed_at = CURRENT_TIMESTAMP, captador_id = ?
                WHERE id = ?'
            );

            $result = $stmt->execute([
                $status,
                $captadorId,
                $assetId
            ]);

            error_log('[UPDATE ACTIVOS FIX] captador_id=' . json_encode($captadorId) . ' assetId=' . $assetId);

            if (!$result) {
                error_log('[UPDATE ERROR] ' . json_encode($stmt->errorInfo()));
            }
        }

    public function insertPropertyRecord(
        array $claudeData,
        string $tipoInput,
        ?string $analysisSummary,
        ?string $analysisJson,
        ?int $captadorId,
        ?int $ownerUserId,
        ?int $createdByUserId = null,
        ?string $ownerEmailPending = null,
        ?int $activoRecibidoId = null
    ): array {
        $ficha = $claudeData['ficha_web'] ?? [];
        $dossier = $claudeData['dossier_inversion'] ?? [];

        $tipo = $this->trimValue($ficha['tipo_propiedad'] ?? '');
        $ciudad = $this->trimValue($ficha['ciudad'] ?? '');
        $zona = $this->trimValue($ficha['zona'] ?? '');
        $direccion = $this->trimValue($ficha['direccion'] ?? '');
        $categoria = $this->trimValue($ficha['categoria'] ?? '');
        $provincia = $this->trimValue($ficha['provincia'] ?? '');
        $pais = $this->trimValue($ficha['pais'] ?? 'España');
        $metros = $this->intValue($ficha['metros_cuadrados'] ?? null);
        $precio = $this->floatValue($ficha['precio'] ?? null);

        if ($tipo === '') {
            $tipo = 'propiedad';
        }

        if ($ciudad === '') {
            $ciudad = 'Madrid';
        }

        if ($zona === '') {
            $zona = 'Centro';
        }

        if ($pais === '') {
            $pais = 'España';
        }

        if ($metros === 0) {
            $metros = 1;
        }

        if ($precio === null) {
            $precio = 0;
        }

        $effectiveCreatedByUserId = $createdByUserId ?? $ownerUserId;
        $normalizedOwnerEmailPending = null;
        if ($ownerEmailPending !== null) {
            $normalized = strtolower(trim($ownerEmailPending));
            if ($normalized !== '' && filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
                $normalizedOwnerEmailPending = $normalized;
            }
        }

        $direccionCompleta = $this->trimValue($dossier['ubicacion_completa'] ?? '');
        $codigoPostal = $this->trimValue($dossier['codigo_postal'] ?? '');
        $direccionCorta = $this->trimValue($ficha['direccion'] ?? '');

        // Dedup semántica por dirección: si ya existe una propiedad con el
        // mismo address_hash, no creamos una nueva; devolvemos la existente.
        $addressHash = buildPropertyAddressHash([
            'direccion'     => $direccionCorta,
            'ubicacion'     => $direccionCompleta,
            'ciudad'        => $ciudad,
            'provincia'     => $provincia,
            'pais'          => $pais,
            'codigo_postal' => $codigoPostal,
        ]);

        if ($addressHash !== null && $this->columnExists('propiedades', 'address_hash')) {
            $dupStmt = $this->pdo->prepare('SELECT id FROM propiedades WHERE address_hash = :h LIMIT 1');
            $dupStmt->execute(['h' => $addressHash]);
            $existingId = $dupStmt->fetchColumn();
            if ($existingId !== false) {
                error_log('[PROP DUP SKIP] address_hash coincide con propiedad existente id=' . (int) $existingId);
                return ['id' => (int) $existingId, 'duplicate' => true];
            }
        }

        $geo = geocodeApproximateLocation([
            'direccion_completa' => $direccionCompleta,
            'direccion' => $direccionCorta,
            'codigo_postal' => $codigoPostal,
            'ciudad' => $ciudad,
            'provincia' => $provincia,
            'pais' => $pais,
        ]);

        $latitud = $geo['latitud'] ?? null;
        $longitud = $geo['longitud'] ?? null;

        error_log('[GEOCODING INPUT] ' . json_encode([
            'direccion_completa' => $direccionCompleta,
            'direccion' => $direccionCorta,
            'codigo_postal' => $codigoPostal,
            'ciudad' => $ciudad,
            'provincia' => $provincia,
            'pais' => $pais,
            'latitud' => $latitud,
            'longitud' => $longitud,
            'query' => $geo['query'] ?? null,
        ], JSON_UNESCAPED_UNICODE));

        $columns = [
            'tipo_propiedad',
            'ciudad',
            'zona',
            'metros_cuadrados',
            'precio',
            'direccion',
            'categoria',
            'latitud',
            'longitud',
            'captador_id',
            'caracteristicas_json',
            'owner_user_id',
        ];

        $placeholders = [
            ':tipo_propiedad',
            ':ciudad',
            ':zona',
            ':metros_cuadrados',
            ':precio',
            ':direccion',
            ':categoria',
            ':latitud',
            ':longitud',
            ':captador_id',
            ':caracteristicas_json',
            ':owner_user_id',
        ];

        $params = [
            'tipo_propiedad' => $tipo,
            'ciudad' => $ciudad,
            'zona' => $zona,
            'metros_cuadrados' => $metros,
            'precio' => $precio,
            'direccion' => $direccion !== '' ? $direccion : null,
            'categoria' => $categoria !== '' ? $categoria : 'Captada',
            'latitud' => $latitud,
            'longitud' => $longitud,
            'captador_id' => $captadorId,
            'caracteristicas_json' => json_encode($dossier, JSON_UNESCAPED_UNICODE),
            'owner_user_id' => $ownerUserId,
        ];

        if ($activoRecibidoId !== null) {
            if ($activoRecibidoId <= 0) {
                throw new InvalidArgumentException('activo_recibido_id invalido para insertPropertyRecord');
            }

            if (!$this->columnExists('propiedades', 'activo_recibido_id')) {
                throw new RuntimeException('La columna propiedades.activo_recibido_id es obligatoria en este flujo.');
            }

            $columns[] = 'activo_recibido_id';
            $placeholders[] = ':activo_recibido_id';
            $params['activo_recibido_id'] = $activoRecibidoId;
        }

        // Campos nuevos (opcionales) para no romper instalaciones que aun no han migrado la tabla.
        if ($effectiveCreatedByUserId !== null && $this->columnExists('propiedades', 'created_by_user_id')) {
            $columns[] = 'created_by_user_id';
            $placeholders[] = ':created_by_user_id';
            $params['created_by_user_id'] = $effectiveCreatedByUserId;
        }

        if ($this->columnExists('propiedades', 'owner_email_pending')) {
            $columns[] = 'owner_email_pending';
            $placeholders[] = ':owner_email_pending';
            $params['owner_email_pending'] = $normalizedOwnerEmailPending;
        }

        if ($addressHash !== null && $this->columnExists('propiedades', 'address_hash')) {
            $columns[] = 'address_hash';
            $placeholders[] = ':address_hash';
            $params['address_hash'] = $addressHash;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO propiedades (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')'
        );

        error_log('[PROP INSERT LINK] activo_recibido_id=' . json_encode($params['activo_recibido_id'] ?? null));
        error_log('[INSERT PARAMS] ' . json_encode($params, JSON_UNESCAPED_UNICODE));

        try {
            $result = $stmt->execute($params);

            if ($result === false) {
                $errorInfo = $stmt->errorInfo();
                throw new RuntimeException('SQL ERROR: ' . implode(' | ', $errorInfo));
            }
        } catch (PDOException $e) {
            // Race condition: otro proceso insertó la misma propiedad entre
            // nuestro SELECT de dedup y este INSERT. Recuperamos el id
            // existente y devolvemos ese.
            if ((string) $e->getCode() === '23000' && $addressHash !== null) {
                $recoverStmt = $this->pdo->prepare('SELECT id FROM propiedades WHERE address_hash = :h LIMIT 1');
                $recoverStmt->execute(['h' => $addressHash]);
                $existingId = $recoverStmt->fetchColumn();
                if ($existingId !== false) {
                    error_log('[PROP DUP RACE] address_hash colisionó en INSERT, devolviendo id=' . (int) $existingId);
                    return ['id' => (int) $existingId, 'duplicate' => true];
                }
            }
            error_log('[INSERT ERROR] ' . $e->getMessage());
            throw $e;
        } catch (Throwable $e) {
            error_log('[INSERT ERROR] ' . $e->getMessage());
            throw $e;
        }

        $propertyId = (int) $this->pdo->lastInsertId();

        if ($propertyId === 0) {
            throw new RuntimeException('INSERT FALLÓ: lastInsertId = 0');
        }

        error_log('[INSERT OK] ID: ' . $propertyId);

        return ['id' => $propertyId, 'duplicate' => false];
    }

    public function findRegladoUserIdByEmail(string $email): ?int
    {
        $normalized = strtolower(trim($email));

        if ($normalized === '' || !filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT id FROM regladousers.users WHERE LOWER(email) = :email LIMIT 1');
        $stmt->execute(['email' => $normalized]);
        $id = $stmt->fetchColumn();

        if ($id === false || $id === null) {
            return null;
        }

        return (int) $id;
    }

    public function updatePropertyDocuments(int $propertyId, array $documents): void
    {
        $fields = [];
        $params = ['id' => $propertyId];

        foreach (['dossier_file', 'confidentiality_file', 'intention_file'] as $field) {
            if (array_key_exists($field, $documents) && $documents[$field] !== null) {
                $fields[] = "$field = :$field";
                $params[$field] = $documents[$field];
            }
        }

        if ($fields === []) {
            error_log('[UPDATE DOCS] Nada que actualizar');
            return;
        }

        $sql = 'UPDATE propiedades SET ' . implode(', ', $fields) . ' WHERE id = :id';

        error_log('[UPDATE SQL] ' . $sql);
        error_log('[UPDATE PARAMS] ' . json_encode($params));

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        error_log('[UPDATE OK] propiedad ' . $propertyId);
    }

    public function getOrCreateCaptador(string $email): int
    {
        $normalized = mb_strtolower(trim($email));
        if ($normalized === '') {
            throw new RuntimeException('Email de captador vacío');
        }

        $stmt = $this->pdo->prepare('SELECT id FROM captadores WHERE email = ?');
        $stmt->execute([$normalized]);
        $captador = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($captador !== false) {
            return (int) $captador['id'];
        }

        $insert = $this->pdo->prepare('INSERT INTO captadores (email) VALUES (?)');
        $insert->execute([$normalized]);

        return (int) $this->pdo->lastInsertId();
    }

    private function trimValue(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    private function intValue(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function floatValue(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function columnExists(string $table, string $column): bool
    {
        $cacheKey = $table . '.' . $column;
        if (array_key_exists($cacheKey, $this->columnExistsCache)) {
            return (bool) $this->columnExistsCache[$cacheKey];
        }

        $stmt = $this->pdo->prepare('
            SELECT 1
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table
              AND COLUMN_NAME = :column
            LIMIT 1
        ');

        $stmt->execute([
            'table' => $table,
            'column' => $column,
        ]);

        $exists = (bool) $stmt->fetchColumn();
        $this->columnExistsCache[$cacheKey] = $exists;
        return $exists;
    }
}
