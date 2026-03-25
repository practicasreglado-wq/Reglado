<?php
declare(strict_types=1);

class Repository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function insertReceivedAsset(
        string $origin,
        ?string $emailRemitente,
        string $texto,
        string $contentHash,
        ?string $messageId = null,
        ?array $metadata = null
    ): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO activos_recibidos (origen, email_remitente, texto_recibido, metadata, procesado, message_id, content_hash)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $origin,
            $emailRemitente,
            $texto,
            $metadata !== null ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null,
            'pendiente',
            $messageId,
            $contentHash,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function getReceivedAsset(int $assetId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM activos_recibidos WHERE id = ?');
        $stmt->execute([$assetId]);
        $asset = $stmt->fetch();

        if ($asset === false) {
            throw new RuntimeException("Activo recibido {$assetId} no encontrado");
        }

        return $asset;
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
        int $ownerUserId
    ): int {

        $ficha = $claudeData['ficha_web'] ?? [];
        $dossier = $claudeData['dossier_inversion'] ?? [];

        $tipo = $this->trimValue($ficha['tipo_propiedad'] ?? '');
        $ciudad = $this->trimValue($ficha['ciudad'] ?? '');
        $zona = $this->trimValue($ficha['zona'] ?? '');
        $direccion = $this->trimValue($ficha['direccion'] ?? '');
        $categoria = $this->trimValue($ficha['categoria'] ?? '');
        $metros = $this->intValue($ficha['metros_cuadrados'] ?? null);
        $precio = $this->floatValue($ficha['precio'] ?? null);

        // 🔥 FALLBACK (CLAVE → evita NULL y errores SQL)
        if ($tipo === '') $tipo = 'propiedad';
        if ($ciudad === '') $ciudad = 'Madrid';
        if ($zona === '') $zona = 'Centro';
        if ($metros === 0) $metros = 1;
        if ($precio === null) $precio = 0;

        if ($ownerUserId === null) {
            throw new RuntimeException('owner_user_id es obligatorio');
        }

        // 🔹 PREPARE
        $stmt = $this->pdo->prepare(
            'INSERT INTO propiedades (
                tipo_propiedad,
                ciudad,
                zona,
                metros_cuadrados,
                precio,
                direccion,
                categoria,
                captador_id,
                caracteristicas_json,
                owner_user_id
            ) VALUES (
                :tipo_propiedad,
                :ciudad,
                :zona,
                :metros_cuadrados,
                :precio,
                :direccion,
                :categoria,
                :captador_id,
                :caracteristicas_json,
                :owner_user_id
            )'
        );

        $params = [
            'tipo_propiedad' => $tipo,
            'ciudad' => $ciudad,
            'zona' => $zona,
            'metros_cuadrados' => $metros,
            'precio' => $precio,
            'direccion' => $direccion ?: null,
            'categoria' => $categoria !== '' ? $categoria : 'Captada',
            'captador_id' => $captadorId,
            'caracteristicas_json' => json_encode($dossier, JSON_UNESCAPED_UNICODE),
            'owner_user_id' => $ownerUserId,
        ];

        // 🔥 DEBUG REAL
        error_log('[INSERT PARAMS] ' . json_encode($params, JSON_UNESCAPED_UNICODE));

        try {
            $result = $stmt->execute($params);

            if ($result === false) {
                $errorInfo = $stmt->errorInfo();
                throw new RuntimeException('SQL ERROR: ' . implode(' | ', $errorInfo));
            }

        } catch (Throwable $e) {
            error_log('[INSERT ERROR] ' . $e->getMessage());
            throw $e;
        }

        $propertyId = (int) $this->pdo->lastInsertId();

        if ($propertyId === 0) {
            throw new RuntimeException('INSERT FALLÓ: lastInsertId = 0');
        }

        error_log('[INSERT OK] ID: ' . $propertyId);

        return $propertyId;
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
        $captador = $stmt->fetch();

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
        return is_numeric($value) ? (int)$value : 0;
    }

    private function floatValue(mixed $value): ?float
    {
        return is_numeric($value) ? (float)$value : null;
    }
}
