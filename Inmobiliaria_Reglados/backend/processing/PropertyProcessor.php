<?php
declare(strict_types=1);

class PropertyProcessor
{
    private Repository $repository;
    private ClaudeClient $claudeClient;
    private PdfGenerator $pdfGenerator;
    private DossierService $dossierService;
    private ?int $ownerUserId;

    public function __construct(
        Repository $repository,
        ClaudeClient $claudeClient,
        PdfGenerator $pdfGenerator,
        DossierService $dossierService,
        ?int $ownerUserId
    ) {
        $this->repository = $repository;
        $this->claudeClient = $claudeClient;
        $this->pdfGenerator = $pdfGenerator;
        $this->dossierService = $dossierService;
        $this->ownerUserId = $ownerUserId;
    }

    public function process(int $assetId): int
    {
        $asset = $this->repository->getReceivedAsset($assetId);
        $text = trim((string) ($asset['texto_recibido'] ?? ''));

        if ($text === '') {
            throw new RuntimeException('Texto vacío para procesar');
        }

        if ($this->ownerUserId === null) {
            throw new RuntimeException('owner_user_id no resuelto en PropertyProcessor');
        }

        $claudeData = $this->claudeClient->analyzeStructuredPropertyText($text);

        $this->validateClaudeResponse($claudeData);

        $email = trim((string) ($asset['email_remitente'] ?? ''));
        $captadorId = $this->resolveCaptador($email);

        $propertyId = $this->repository->insertPropertyRecord(
            $claudeData,
            'text',
            null,
            json_encode($claudeData, JSON_UNESCAPED_UNICODE),
            $captadorId,
            $this->ownerUserId
        );

        $documents = $this->pdfGenerator->generateDocuments(
            $claudeData,
            $propertyId
        );

        $dossierFile = null;
        if (
            isset($claudeData['dossier_inversion']) &&
            is_array($claudeData['dossier_inversion']) &&
            $this->dossierHasEnoughData($claudeData['dossier_inversion'])
        ) {
            $dossierFile = $this->dossierService->generateDossierPDF(
                $propertyId,
                $claudeData['dossier_inversion'],
                $claudeData['ficha_web']
            );
        }

        $this->repository->updatePropertyDocuments($propertyId, [
            'confidentiality_file' => $documents['confidentiality_file'] ?? null,
            'intention_file' => $documents['intention_file'] ?? null,
            'dossier_file' => $dossierFile,
        ]);

        $this->repository->updateReceivedAssetStatus($assetId, 'procesado', $captadorId);

        return $propertyId;
    }

    private function resolveCaptador(string $email): ?int
    {
        if (trim($email) === '') {
            return null;
        }

        return $this->repository->getOrCreateCaptador($email);
    }

    private function validateClaudeResponse(array $data): void
    {
        if (!isset($data['ficha_web']) || !is_array($data['ficha_web'])) {
            throw new RuntimeException('Claude no devolvió el bloque ficha_web');
        }

        if (!isset($data['dossier_inversion']) || !is_array($data['dossier_inversion'])) {
            throw new RuntimeException('Claude no devolvió el bloque dossier_inversion');
        }

        $required = [
            'tipo_propiedad',
            'categoria',
            'ciudad',
            'zona',
            'direccion',
            'metros_cuadrados',
            'precio',
        ];

        foreach ($required as $field) {
            if (!array_key_exists($field, $data['ficha_web'])) {
                throw new RuntimeException("Falta el campo obligatorio ficha_web.$field");
            }

            $value = $data['ficha_web'][$field];

            if ($value === null || $value === '') {
                throw new RuntimeException("El campo obligatorio ficha_web.$field viene vacío");
            }
        }

        if (!is_numeric($data['ficha_web']['metros_cuadrados'])) {
            throw new RuntimeException('ficha_web.metros_cuadrados debe ser numérico');
        }

        if (!is_numeric($data['ficha_web']['precio'])) {
            throw new RuntimeException('ficha_web.precio debe ser numérico');
        }
    }

    private function dossierHasEnoughData(array $dossier): bool
    {
        $count = 0;

        foreach ($dossier as $value) {
            if (is_array($value)) {
                if (!empty($value)) {
                    $count++;
                }
                continue;
            }

            if ($value !== null && $value !== '') {
                $count++;
            }
        }

        return $count >= 5;
    }
}