<?php
declare(strict_types=1);

class PropertyProcessor
{
    private Repository $repository;
    private ClaudeClient $claudeClient;
    private PdfGenerator $pdfGenerator;

    public function __construct(Repository $repository, ClaudeClient $claudeClient, PdfGenerator $pdfGenerator)
    {
        $this->repository = $repository;
        $this->claudeClient = $claudeClient;
        $this->pdfGenerator = $pdfGenerator;
    }

    public function process(int $assetId): int
    {
        $asset = $this->repository->getReceivedAsset($assetId);
        error_log(sprintf('[PROCESSOR] asset cargado #%d', $assetId));

        $claudeData = $this->claudeClient->analyzeText((string) $asset['texto_recibido']);
        error_log(sprintf('[PROCESSOR] datos Claude %s', json_encode($claudeData, JSON_UNESCAPED_UNICODE)));

try {
    $this->validateClaudeData($claudeData);
} catch (Throwable $e) {
    error_log('[WARNING] Claude incompleto: ' . $e->getMessage());
}
        $captadorId = null;
        $emailRemitente = trim((string) ($asset['email_remitente'] ?? ''));
        if ($emailRemitente !== '') {
            $captadorId = $this->repository->getOrCreateCaptador($emailRemitente);
        }

        error_log(sprintf('[PROCESSOR] captadorId %s', $captadorId !== null ? (string) $captadorId : 'null'));

        // ✅ CORREGIDO AQUÍ
        $files = $this->pdfGenerator->generateDocuments($claudeData, $assetId);

        $propertyId = $this->repository->insertPropertyRecord(
            $claudeData,
            $files['dossier'],
            $files['confidentiality'],
            $files['intention'],
            $captadorId
        );

        $this->repository->updateReceivedAssetStatus($assetId, 'procesado', $captadorId);

        return $propertyId;
    }

    private function validateClaudeData(array $data): void
    {
        $required = ['tipo_propiedad', 'ciudad', 'zona', 'metros', 'habitaciones', 'precio'];

        foreach ($required as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
                throw new RuntimeException(sprintf('Dato obligatorio ausente: %s', $field));
            }
        }

        foreach (['metros', 'habitaciones', 'precio'] as $numericField) {
            if (!is_numeric($data[$numericField])) {
                throw new RuntimeException(sprintf('El campo %s debe ser numérico', $numericField));
            }
        }
    }
}