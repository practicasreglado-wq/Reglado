<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../lib/env_loader.php';
require_once __DIR__ . '/../lib/pdf_signature.php';
require_once __DIR__ . '/../lib/document_access.php';
require_once __DIR__ . '/../send_mail.php';

applyAuthCors();
handlePreflight();
loadEnv(__DIR__ . '/../.env');

$context = requireAuthenticatedUser($pdo);
$buyerUserId = (int) (
    $context['local']['iduser']
    ?? $context['local']['id']
    ?? $context['auth']['id']
    ?? 0
);
$authUser = $context['auth'] ?? [];

$propertyId = (int) ($_POST['property_id'] ?? $_POST['propertyId'] ?? 0);
$files = [
    'nda' => $_FILES['signed_nda'] ?? null,
    'loi' => $_FILES['signed_loi'] ?? null,
];

if ($buyerUserId <= 0 || $propertyId <= 0) {
    respondJson(422, ['success' => false, 'message' => 'Propiedad o comprador inválidos.']);
}

$documents = array_filter($files);
if ($documents === []) {
    respondJson(422, ['success' => false, 'message' => 'No se detectaron documentos para subir.']);
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
        office_status
    ) VALUES (
        :property_id,
        :user_id,
        :document_type,
        :original_file,
        :signed_file,
        :signature_detected,
        :office_status
    )
');
try{
$pdo->beginTransaction();

$accessUpdates = [];
$uploadedDocuments = [];

foreach (['nda', 'loi'] as $type) {
    $file = $files[$type] ?? null;
    if ($file === null || $file['error'] === UPLOAD_ERR_NO_FILE) {
        continue;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $pdo->rollBack();
        respondJson(422, ['success' => false, 'message' => 'Error subiendo el archivo.']);
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($extension !== 'pdf') {
        $pdo->rollBack();
        respondJson(422, ['success' => false, 'message' => 'Solo se permiten archivos PDF.']);
    }

    $basename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
    $targetName = uniqid("signed_{$type}_", true) . '_' . $basename;
    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $targetName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        $pdo->rollBack();
        respondJson(500, ['success' => false, 'message' => 'No se pudo guardar el archivo.']);
    }

    $signatureDetected = pdfSeemsSigned($targetPath) ? 1 : 0;
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
        'office_status' => 'pendiente',
    ]);

    $accessUpdates[$type === 'nda' ? 'nda_uploaded' : 'loi_uploaded'] = 1;

    $uploadedDocuments[] = [
        'type' => $type,
        'file' => $relativePath,
        'signature_detected' => (bool) $signatureDetected,
    ];
}

if ($uploadedDocuments === []) {
    $pdo->rollBack();
    respondJson(422, ['success' => false, 'message' => 'No se subió ningún archivo válido.']);
}

$access = ensureBuyerPropertyAccess($pdo, $propertyId, $buyerUserId);
$access = updateBuyerPropertyAccess($pdo, $propertyId, $buyerUserId, $accessUpdates);
$pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    respondJson(500, [
        'success' => false,
        'message' => 'Error guardando la documentación.',
    ]);
}

$officeEmail = getenv('OFFICE_NOTIFICATION_EMAIL') ?: getenv('SMTP_FROM');
$buyerEmail = filter_var($authUser['email'] ?? '', FILTER_VALIDATE_EMAIL);
if ($officeEmail) {
    $documentNames = array_map(
        fn($doc) => sprintf(
            '%s: %s (%s)',
            strtoupper($doc['type']),
            $doc['file'],
            $doc['signature_detected'] ? 'firma detectada' : 'firma no detectada'
        ),
        $uploadedDocuments
    );
    $body = sprintf(
        "Ha llegado nueva documentación firmada para la propiedad %s (#%d).\n\nComprador: %s\nDocumentos:\n- %s\n\nVerifica en el panel administrativo.",
        $property['tipo_propiedad'] ?? 'Sin tipo',
        $propertyId,
        $buyerEmail ?: 'Usuario registrado',
        implode("\n- ", $documentNames)
    );

    try {
        sendNotificationEmail($officeEmail, 'Documentación firmada recibida', nl2br($body), $buyerEmail ?: null);
    } catch (Throwable $e) {
        error_log('No se pudo enviar notificación: ' . $e->getMessage());
    }
}

respondJson(200, [
    'success' => true,
    'message' => 'Documentos recibidos correctamente.',
    'documents' => $uploadedDocuments,
    'access' => $access,
]);
