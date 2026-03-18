<?php
declare(strict_types=1);

class Repository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function insertReceivedAsset(string $origin, ?string $emailRemitente, string $texto, ?array $metadata = null): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO activos_recibidos (origen, email_remitente, texto_recibido, metadata, procesado)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $origin,
            $emailRemitente,
            $texto,
            $metadata !== null ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null,
            'pendiente',
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

    public function insertPropertyRecord(array $claudeData, string $dossierFile, string $confidentialityFile, string $intentionFile, ?int $captadorId): int
    {
        $tipo = trim((string) ($claudeData['tipo_propiedad'] ?? ''));
        $ciudad = trim((string) ($claudeData['ciudad'] ?? ''));
        $zona = trim((string) ($claudeData['zona'] ?? ''));
        $precio = isset($claudeData['precio']) ? (float) $claudeData['precio'] : 0.0;
        $metros = isset($claudeData['metros']) ? (int) $claudeData['metros'] : 0;
        $habitaciones = isset($claudeData['habitaciones']) ? (int) $claudeData['habitaciones'] : 0;
        $rentabilidad = isset($claudeData['rentabilidad']) ? trim((string) $claudeData['rentabilidad']) : null;
        $caracteristicas = $this->normalizeCaracteristicas($claudeData['caracteristicas'] ?? []);

        $locationParts = [];
        foreach ([$zona, $ciudad] as $value) {
            if ($value !== '') {
                $locationParts[] = $value;
            }
        }

        $locationLabel = $locationParts !== [] ? implode(', ', $locationParts) : 'Sin ubicación';
        $titulo = $tipo !== '' ? sprintf('%s en %s', $tipo, $locationLabel) : 'Activo captado';
        $ubicacionGeneral = $locationLabel;

        $stmt = $this->pdo->prepare(
            'INSERT INTO propiedades (
                categoria,
                titulo,
                tipo_propiedad,
                ciudad,
                zona,
                ubicacion_general,
                metros_cuadrados,
                habitaciones,
                precio,
                rentabilidad,
                dossier_file,
                confidentiality_file,
                intention_file,
                captador_id,
                caracteristicas_json
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            'Captada',
            $titulo,
            $tipo,
            $ciudad,
            $zona,
            $ubicacionGeneral,
            $metros,
            $habitaciones,
            $precio,
            $rentabilidad,
            $dossierFile,
            $confidentialityFile,
            $intentionFile,
            $captadorId,
            json_encode($caracteristicas, JSON_UNESCAPED_UNICODE),
        ]);

        $propertyId = (int) $this->pdo->lastInsertId();
        error_log(sprintf('[REPOSITORY] insert propiedad #%d', $propertyId));

        return $propertyId;
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
}