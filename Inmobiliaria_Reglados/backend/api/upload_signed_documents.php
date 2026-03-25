<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../lib/env_loader.php';
require_once __DIR__ . '/../lib/pdf_signature.php';
require_once __DIR__ . '/../lib/document_access.php';
require_once __DIR__ . '/../lib/document_review.php';
require_once __DIR__ . '/../send_mail.php';

applyAuthCors();
handlePreflight();
loadEnv(__DIR__ . '/../.env');

header('Content-Type: application/json; charset=utf-8');

if ($pdo instanceof PDO) {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

$context = requireAuthenticatedUser($pdo);
$buyerUserId = (int) (
    $context['local']['iduser']
    ?? $context['local']['id']
    ?? $context['auth']['id']
    ?? 0
);
$authUser = $context['auth'] ?? [];

$propertyId = (int) ($_POST['property_id'] ?? $_POST['propertyId'] ?? 0);

if ($buyerUserId <= 0 || $propertyId <= 0) {
    respondJson(422, ['success' => false, 'message' => 'Propiedad o comprador inválidos.']);
}

$requiredFiles = [
    'nda' => 'signed_nda',
    'loi' => 'signed_loi',
];

foreach ($requiredFiles as $label => $field) {
    $file = $_FILES[$field] ?? null;
    if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        respondJson(422, ['success' => false, 'message' => sprintf('Falta el documento %s.', strtoupper($label))]);
    }
}

$stmt = $pdo->prepare('
    SELECT id, tipo_propiedad, ciudad, zona, confidentiality_file, intention_file
    FROM propiedades
    WHERE id = :id
    LIMIT 1
');
$stmt->execute(['id' => $propertyId]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    respondJson(404, ['success' => false, 'message' => 'Propiedad no encontrada.']);
}

$uploadDir = __DIR__ . '/../uploads/signed_docs';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
    respondJson(500, ['success' => false, 'message' => 'No se pudo crear el directorio de subida.']);
}

$insertStmt = $pdo->prepare('
    INSERT INTO documentos_firmados (
        property_id,
        user_id,
        document_type,
        original_file,
        signed_file,
        signature_detected,
        document_valid,
        office_status
    ) VALUES (
        :property_id,
        :user_id,
        :document_type,
        :original_file,
        :signed_file,
        :signature_detected,
        :document_valid,
        :office_status
    )
');

$attachments = [];
$uploadedDocuments = [];
$accessUpdates = [
    'nda_uploaded' => 0,
    'loi_uploaded' => 0,
];

try {
    $pdo->beginTransaction();

    foreach ($requiredFiles as $type => $field) {
        $file = $_FILES[$field];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException(sprintf('Error subiendo el documento %s.', strtoupper($type)));
        }

        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            throw new RuntimeException('Solo se permiten PDF.');
        }

        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', (string) $file['name']);
        $targetName = uniqid("signed_{$type}_", true) . '_' . $safeName;
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $targetName;

        if (!move_uploaded_file((string) $file['tmp_name'], $targetPath)) {
            throw new RuntimeException('No se pudo guardar el archivo.');
        }

        $signatureDetected = pdfSeemsSigned($targetPath) ? 1 : 0;
        if ($signatureDetected === 0) {
            @unlink($targetPath);
            respondJson(422, [
                'success' => false,
                'message' => sprintf(
                    'El %s no parece contener una firma o trazo añadido. Intenta subir un documento firmado.',
                    strtoupper($type)
                ),
            ]);
        }

        $relativePath = 'signed_docs/' . $targetName;
        $originalFile = $type === 'nda'
            ? $property['confidentiality_file']
            : $property['intention_file'];

        $insertStmt->execute([
            'property_id' => $propertyId,
            'user_id' => $buyerUserId,
            'document_type' => $type,
            'original_file' => $originalFile,
            'signed_file' => $relativePath,
            'signature_detected' => $signatureDetected,
            'document_valid' => 1,
            'office_status' => 'pendiente',
        ]);

        $accessUpdates[$type . '_uploaded'] = 1;
        $uploadedDocuments[$type] = [
            'type' => $type,
            'path' => $relativePath,
            'absolute' => $targetPath,
            'signature_detected' => true,
        ];

        $attachments[] = [
            'path' => $targetPath,
            'name' => sprintf('%s_%s.pdf', strtoupper($type), preg_replace('/[^a-zA-Z0-9._-]/', '_', $property['tipo_propiedad'] ?? 'propiedad')),
        ];
    }

    $access = ensureBuyerPropertyAccess($pdo, $propertyId, $buyerUserId);
    $access = updateBuyerPropertyAccess($pdo, $propertyId, $buyerUserId, [
        'nda_uploaded' => 1,
        'loi_uploaded' => 1,
    ]);

    $token = createDocumentReviewToken($pdo, $propertyId, $buyerUserId);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    respondJson(500, ['success' => false, 'message' => 'No se pudo procesar la documentación.', 'detail' => $e->getMessage()]);
}

$officeEmail = 'practicasreglado@gmail.com';
$buyerEmail = filter_var($authUser['email'] ?? '', FILTER_VALIDATE_EMAIL);
$approvalLink = buildReviewApprovalLink($token);

$documentDetails = array_map(
    fn($doc) => sprintf('%s: %s (firma detectada)', strtoupper($doc['type']), $doc['path']),
    $uploadedDocuments
);

$emailBody = sprintf(
    '<p>Documentación firmada pendiente de revisión para la propiedad %s (#%d).</p>
    <p>Comprador: %s</p>
    <p>Ciudad: %s · Zona: %s</p>
    <p>Documentos recibidos:</p>
    <ul><li>%s</li></ul>
    <p><a href="%s" style="display:inline-block;padding:14px 22px;border-radius:12px;background:#0b4fff;color:#fff;text-decoration:none;">Aprobar documentos y desbloquear dossier</a></p>',
    $property['tipo_propiedad'] ?? 'Activo',
    $propertyId,
    $buyerEmail ?: 'Usuario registrado',
    $property['ciudad'] ?? 'Ciudad desconocida',
    $property['zona'] ?? 'Zona desconocida',
    implode('</li><li>', $documentDetails),
    htmlspecialchars($approvalLink, ENT_QUOTES, 'UTF-8')
);

try {
    sendNotificationEmail($officeEmail, "Documentación firmada pendiente de revisión - Propiedad #{$propertyId}", $emailBody, $buyerEmail ?: null, $attachments);
} catch (Throwable $e) {
    error_log('No se pudo enviar el correo de revisión: ' . $e->getMessage());
}

respondJson(200, [
    'success' => true,
    'message' => 'Documentos recibidos y enviados a revisión.',
    'access' => $access,
    'approval_link' => $approvalLink,
]);
