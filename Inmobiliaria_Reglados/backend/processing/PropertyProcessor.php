<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/lib/buyer_intents.php';

/**
 * Orquestador del pipeline de procesado de propiedades entrantes.
 *
 * Cuando llega un correo a CloudMailin → receive_email.php, se crea un
 * "activo recibido" en BD y se invoca a este processor con su id. El flujo:
 *
 *  1) Lee el texto crudo del activo desde Repository.
 *  2) Llama a ClaudeClient para extraer la ficha estructurada (tipo, ciudad,
 *     dirección, precio, dossier_inversion...).
 *  3) Resuelve el dueño:
 *      - Si la IA asignó un email_usuario que existe → owner_user_id real.
 *      - Si el email no está registrado → owner_user_id NULL +
 *        owner_email_pending para que se enlace cuando ese email se registre
 *        (ver lib/property_owner_linking.php).
 *      - Si no hay email asignado → fallback al usuario que disparó la
 *        petición (createdByUserId).
 *  4) Crea/actualiza la propiedad en BD vía Repository.
 *  5) Genera el dossier PDF (PdfGenerator + DossierService).
 *  6) Notifica matches con buyer_intents pendientes (lib/buyer_intents.php).
 *
 * Si cualquier paso falla, el activo queda marcado con error en BD y un
 * admin debe revisarlo a mano — no se crean propiedades parciales.
 */
class PropertyProcessor
{
    private Repository $repository;
    private ClaudeClient $claudeClient;
    private PdfGenerator $pdfGenerator;
    private DossierService $dossierService;
    private ?int $createdByUserId;

    public function __construct(
        Repository $repository,
        ClaudeClient $claudeClient,
        PdfGenerator $pdfGenerator,
        DossierService $dossierService,
        ?int $createdByUserId
    ) {
        $this->repository = $repository;
        $this->claudeClient = $claudeClient;
        $this->pdfGenerator = $pdfGenerator;
        $this->dossierService = $dossierService;
        $this->createdByUserId = $createdByUserId;
    }

    public function process(int $assetId): int
    {
        if ($assetId <= 0) {
            throw new InvalidArgumentException('assetId invalido para procesar propiedad');
        }

        $asset = $this->repository->getReceivedAsset($assetId);
        $text = trim((string) ($asset['texto_recibido'] ?? ''));

        if ($text === '') {
            throw new RuntimeException('Texto vacío para procesar');
        }

        if ($this->createdByUserId === null) {
            throw new RuntimeException('created_by_user_id no resuelto en PropertyProcessor');
        }

        $claudeData = $this->claudeClient->analyzeStructuredPropertyText($text);

        $this->validateClaudeResponse($claudeData);

        $assignment = $claudeData['asignacion_usuario'] ?? null;
        $rawOwnerEmail = is_array($assignment) ? ($assignment['email_usuario'] ?? null) : null;
        $normalizedOwnerEmail = $this->normalizeEmail($rawOwnerEmail);

        $resolvedOwnerUserId = null;
        $ownerEmailPending = null;

        if ($normalizedOwnerEmail === null) {
            $resolvedOwnerUserId = $this->createdByUserId;
        } else {
            $resolvedOwnerUserId = $this->repository->findRegladoUserIdByEmail($normalizedOwnerEmail);

            if ($resolvedOwnerUserId === null) {
                $ownerEmailPending = $normalizedOwnerEmail;
            }
        }

        $email = trim((string) ($asset['email_remitente'] ?? ''));
        $captadorId = $this->resolveCaptador($email);

        $insertResult = $this->repository->insertPropertyRecord(
            $claudeData,
            'text',
            null,
            json_encode($claudeData, JSON_UNESCAPED_UNICODE),
            $captadorId,
            $resolvedOwnerUserId,
            $this->createdByUserId,
            $ownerEmailPending,
            $assetId
        );

        $propertyId = (int) $insertResult['id'];
        $isDuplicate = (bool) ($insertResult['duplicate'] ?? false);

        // Si la propiedad ya existía (dedup por address_hash), no regeneramos
        // documentos — los que hay son válidos. Solo marcamos el activo como
        // procesado y devolvemos el id existente.
        if ($isDuplicate) {
            error_log('[PROP PROCESSOR] Propiedad duplicada detectada, se salta generación de PDFs. property_id=' . $propertyId);
            $this->repository->updateReceivedAssetStatus($assetId, 'procesado', $captadorId);
            return $propertyId;
        }

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

        $ficha = $claudeData['ficha_web'] ?? [];
        processNewPropertyMatching($this->repository->getPdo(), $propertyId, [
            'categoria'        => $ficha['categoria'] ?? '',
            'ciudad'           => $ficha['ciudad'] ?? '',
            'precio'           => $ficha['precio'] ?? null,
            'metros_cuadrados' => $ficha['metros_cuadrados'] ?? null,
        ]);

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

    private function normalizeEmail(mixed $email): ?string
    {
        $clean = strtolower(trim((string) ($email ?? '')));
        if ($clean === '') {
            return null;
        }

        return filter_var($clean, FILTER_VALIDATE_EMAIL) ? $clean : null;
    }
}
