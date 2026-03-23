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

        $stmt->bindValue(1, $status, PDO::PARAM_STR);
        $stmt->bindValue(2, $captadorId, $captadorId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(3, $assetId, PDO::PARAM_INT);

        $stmt->execute();
    }

    public function insertPropertyRecord(
        array $claudeData,
        string $tipoInput,
        ?string $analysisSummary,
        ?string $analysisJson,
        ?int $captadorId
    ): int {
        $tipo = $this->trimValue($claudeData['tipo_propiedad'] ?? '');
        $subtipo = $this->trimValue($claudeData['subtipo'] ?? '');
        $ciudad = $this->trimValue($claudeData['ciudad'] ?? '');
        $zona = $this->trimValue($claudeData['zona'] ?? '');
        $direccion = $this->trimValue($claudeData['direccion'] ?? '');
        $metros = $this->intValue($claudeData['metros_cuadrados'] ?? $claudeData['metros'] ?? null);
        $habitaciones = $this->intValue($claudeData['habitaciones'] ?? null);
        $estado = $this->trimValue($claudeData['estado_activo'] ?? null);
        $precio = $this->floatValue($claudeData['precio'] ?? null);

        $locationParts = array_filter([$zona, $ciudad]);
        $ubicacionGeneral = $locationParts ? implode(', ', $locationParts) : 'Sin ubicación';
        $titulo = $subtipo !== '' ? sprintf('%s - %s', $subtipo, $tipo ?: 'Activo') : ($tipo ?: 'Activo captado');

        $stmt = $this->pdo->prepare(
            'INSERT INTO propiedades (
                categoria,
                titulo,
                ubicacion_general,
                tipo_input,
                tipo_propiedad,
                subtipo,
                ciudad,
                zona,
                direccion,
                metros_cuadrados,
                habitaciones,
                estado_activo,
                precio,
                precio_m2,
                ingresos_actuales,
                ingresos_estimados,
                gastos_estimados,
                EBITDA,
                cash_flow,
                rentabilidad_bruta,
                rentabilidad_neta,
                cap_rate,
                roi,
                payback,
                ocupacion,
                ADR,
                RevPAR,
                analisis,
                analisis_json,
                captador_id,
                caracteristicas_json
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $tipo ?: 'Captada',
            $titulo,
            $ubicacionGeneral,
            $tipoInput,
            $tipo,
            $subtipo,
            $ciudad,
            $zona,
            $direccion,
            $metros,
            $habitaciones,
            $estado,
            $precio,
            $this->floatValue($claudeData['precio_m2'] ?? null),
            $this->floatValue($claudeData['ingresos_actuales'] ?? null),
            $this->floatValue($claudeData['ingresos_estimados'] ?? null),
            $this->floatValue($claudeData['gastos_estimados'] ?? null),
            $this->floatValue($claudeData['EBITDA'] ?? null),
            $this->floatValue($claudeData['cash_flow'] ?? null),
            $this->valueOrNull($claudeData['rentabilidad_bruta'] ?? null),
            $this->valueOrNull($claudeData['rentabilidad_neta'] ?? null),
            $this->valueOrNull($claudeData['cap_rate'] ?? null),
            $this->valueOrNull($claudeData['roi'] ?? null),
            $this->valueOrNull($claudeData['payback'] ?? null),
            $this->valueOrNull($claudeData['ocupacion'] ?? null),
            $this->valueOrNull($claudeData['ADR'] ?? null),
            $this->valueOrNull($claudeData['RevPAR'] ?? null),
            $analysisSummary,
            $analysisJson,
            $captadorId,
            json_encode($claudeData['caracteristicas'] ?? [], JSON_UNESCAPED_UNICODE),
        ]);

        $propertyId = (int) $this->pdo->lastInsertId();
        error_log(sprintf('[REPOSITORY] insert propiedad #%d', $propertyId));

        return $propertyId;
    }

    public function updatePropertyDocuments(int $propertyId, array $documents): void
    {
        $fields = [];
        $params = ['id' => $propertyId];

        foreach (['dossier_file', 'confidentiality_file', 'intention_file'] as $field) {
            if (!empty($documents[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $documents[$field];
            }
        }

        if ($fields === []) {
            return;
        }

        $sql = 'UPDATE propiedades SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
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

    private function normalizeCaracteristicas(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if ($raw instanceof stdClass) {
            return (array) $raw;
        }

        return [];
    }

    private function trimValue(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    private function intValue(mixed $value): int
    {
        if ($this->isNumeric($value)) {
            return (int) $value;
        }

        return 0;
    }

    private function floatValue(mixed $value): ?float
    {
        if ($this->isNumeric($value)) {
            return (float) $value;
        }

        return null;
    }

    private function valueOrNull(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return trim((string) $value);
    }

    private function isNumeric(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        return is_numeric($value);
    }
}
