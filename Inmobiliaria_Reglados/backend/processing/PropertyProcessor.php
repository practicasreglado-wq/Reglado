<?php
declare(strict_types=1);

class PropertyProcessor
{
    private Repository $repository;
    private ClaudeClient $claudeClient;
    private PdfGenerator $pdfGenerator;
    private DossierService $dossierService;

    public function __construct(
        Repository $repository,
        ClaudeClient $claudeClient,
        PdfGenerator $pdfGenerator,
        DossierService $dossierService
    ) {
        $this->repository = $repository;
        $this->claudeClient = $claudeClient;
        $this->pdfGenerator = $pdfGenerator;
        $this->dossierService = $dossierService;
    }

    public function process(int $assetId): int
    {
        $asset = $this->repository->getReceivedAsset($assetId);
        $metadata = $this->parseMetadata($asset['metadata'] ?? null);
        $tipoInput = $metadata['tipo_input'] ?? 'text';

        $text = trim((string) ($metadata['pdf_text'] ?? $asset['texto_recibido'] ?? ''));

        if ($text === '') {
            throw new RuntimeException('Texto vacío para procesar');
        }

        $claudeData = $tipoInput === 'pdf'
            ? $this->claudeClient->analyzeAdvancedDocument($text)
            : $this->claudeClient->analyzeSimpleDocument($text);

        $analysisJson = json_encode($claudeData, JSON_UNESCAPED_UNICODE);
        $analysisSummary = $this->extractSummary($claudeData, $tipoInput);

        $captadorId = $this->resolveCaptador(trim((string) ($asset['email_remitente'] ?? '')));

        $propertyId = $this->repository->insertPropertyRecord(
            $claudeData,
            $tipoInput,
            $analysisSummary,
            $analysisJson,
            $captadorId
        );

        $documents = $this->pdfGenerator->generateDocuments($claudeData, $propertyId);
        $updatePayload = [
            'confidentiality_file' => $documents['confidentiality_file'],
            'intention_file' => $documents['intention_file'],
        ];

        if ($tipoInput === 'pdf') {
            $dossierFile = $this->dossierService->generateDossierPDF($propertyId, $claudeData);
            $updatePayload['dossier_file'] = $dossierFile;
        }

        $this->repository->updatePropertyDocuments($propertyId, $updatePayload);
        $this->repository->updateReceivedAssetStatus($assetId, 'procesado', $captadorId);

        return $propertyId;
    }

    private function parseMetadata(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function resolveCaptador(string $email): ?int
    {
        if ($email === '') {
            return null;
        }

        return $this->repository->getOrCreateCaptador($email);
    }

    private function extractSummary(array $data, string $tipoInput): ?string
    {
        if ($tipoInput === 'pdf' && !empty($data['analisis']['resumen'])) {
            return trim((string) $data['analisis']['resumen']);
        }

        if ($tipoInput === 'text') {
            return sprintf(
                'Propiedad en %s (%s) por %s',
                $data['ciudad'] ?? 'Ciudad no definida',
                $data['zona'] ?? 'Zona no definida',
                $this->value($data['precio'] ?? null)
            );
        }

        return null;
    }

    private function value(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'No disponible';
        }

        if (is_numeric($value)) {
            return number_format((float) $value, 2, ',', '.') . ' €';
        }

        return (string) $value;
    }
}
