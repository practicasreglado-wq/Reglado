<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/cors.php';
applyCors();
handlePreflight();

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../lib/env_loader.php';
require_once __DIR__ . '/../lib/pdf_signature.php';
require_once __DIR__ . '/../lib/document_access.php';
require_once __DIR__ . '/../lib/document_review.php';
require_once __DIR__ . '/../send_mail.php';

loadEnv(__DIR__ . '/../.env');

header('Content-Type: application/json; charset=utf-8');

if ($pdo instanceof PDO) {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

function uploadLog(string $message, array $context = []): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;

    if (!empty($context)) {
        $json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json !== false) {
            $line .= ' | ' . $json;
        }
    }

    file_put_contents(
        dirname(__DIR__) . '/upload_signed_flow_debug.txt',
        $line . PHP_EOL,
        FILE_APPEND
    );

    error_log('[upload_signed_documents] ' . $line);
}

function resolveOriginalDocument(string $relativeFile): ?string
{
    $clean = trim((string) $relativeFile);
    if ($clean === '') {
        return null;
    }

    $baseUploads = realpath(dirname(__DIR__) . '/uploads');
    if (!$baseUploads) {
        return null;
    }

    $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $clean);
    $normalized = ltrim($normalized, DIRECTORY_SEPARATOR);

    // Si ya viene como uploads/xxx quitamos el prefijo para no duplicar uploads/uploads/...
    if (stripos($normalized, 'uploads' . DIRECTORY_SEPARATOR) === 0) {
        $normalized = substr($normalized, strlen('uploads' . DIRECTORY_SEPARATOR));
    }

    $candidate = $baseUploads . DIRECTORY_SEPARATOR . $normalized;

    return is_file($candidate) ? $candidate : null;
}

$context = requireAuthenticatedUser($pdo);

uploadLog('HIT endpoint', [
    'post_keys' => array_keys($_POST ?? []),
    'files_keys' => array_keys($_FILES ?? []),
    'auth_keys' => array_keys($context['auth'] ?? []),
    'local_keys' => array_keys($context['local'] ?? []),
]);

$buyerUserId = (int) (
    $context['local']['iduser']
    ?? $context['local']['id']
    ?? $context['auth']['id']
    ?? 0
);

$authUser = $context['auth'] ?? [];
$propertyId = (int) ($_POST['property_id'] ?? $_POST['propertyId'] ?? 0);

uploadLog('Datos iniciales', [
    'buyerUserId' => $buyerUserId,
    'propertyId' => $propertyId,
]);

if ($buyerUserId <= 0 || $propertyId <= 0) {
    respondJson(422, [
        'success' => false,
        'message' => 'Propiedad o comprador inválidos.',
    ]);
}

$requiredFiles = [
    'nda' => 'signed_nda',
    'loi' => 'signed_loi',
];

foreach ($requiredFiles as $label => $field) {
    $file = $_FILES[$field] ?? null;

    uploadLog('Validando archivo recibido', [
        'type' => $label,
        'field' => $field,
        'exists' => is_array($file),
        'error' => $file['error'] ?? null,
        'name' => $file['name'] ?? null,
        'tmp_name' => $file['tmp_name'] ?? null,
        'size' => $file['size'] ?? null,
    ]);

    if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        respondJson(422, [
            'success' => false,
            'message' => sprintf('Falta el documento %s.', strtoupper($label)),
        ]);
    }
}

$propertyStmt = $pdo->prepare('
    SELECT id, tipo_propiedad, ciudad, zona, confidentiality_file, intention_file
    FROM propiedades
    WHERE id = :id
    LIMIT 1
');
$propertyStmt->execute(['id' => $propertyId]);
$property = $propertyStmt->fetch(PDO::FETCH_ASSOC);

uploadLog('Propiedad cargada', [
    'property_found' => (bool) $property,
    'property' => $property ?: null,
]);

if (!$property) {
    respondJson(404, [
        'success' => false,
        'message' => 'Propiedad no encontrada.',
    ]);
}

$uploadDir = dirname(__DIR__) . '/uploads/signed_docs';

if (!is_dir($uploadDir)) {
    $created = mkdir($uploadDir, 0775, true);
    uploadLog('Creando directorio signed_docs', [
        'uploadDir' => $uploadDir,
        'created' => $created,
    ]);
}

if (!is_dir($uploadDir)) {
    respondJson(500, [
        'success' => false,
        'message' => 'No se pudo crear el directorio de subida.',
    ]);
}

uploadLog('Directorio de subida listo', [
    'uploadDir' => $uploadDir,
]);

$ndaDetails = [];
$loiDetails = [];
$attachments = [];
$uploadedAbsolutePaths = [];

try {
    $pdo->beginTransaction();
    uploadLog('BEGIN TRANSACTION');

    foreach ($requiredFiles as $type => $field) {
        $file = $_FILES[$field];

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException(sprintf('Error subiendo el documento %s.', strtoupper($type)));
        }

        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            throw new RuntimeException('Solo se permiten archivos PDF.');
        }

        $cleanName = preg_replace('/[^a-zA-Z0-9._-]/', '_', (string) $file['name']);
        $targetName = uniqid("signed_{$type}_", true) . '_' . $cleanName;
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $targetName;

        uploadLog('Antes de mover archivo', [
            'type' => $type,
            'tmp_name' => $file['tmp_name'],
            'targetPath' => $targetPath,
            'targetName' => $targetName,
        ]);

        if (!move_uploaded_file((string) $file['tmp_name'], $targetPath)) {
            throw new RuntimeException('No se pudo guardar el archivo subido.');
        }

        $uploadedAbsolutePaths[] = $targetPath;

        uploadLog('Archivo movido correctamente', [
            'type' => $type,
            'targetPath' => $targetPath,
            'exists_after_move' => is_file($targetPath),
        ]);

        $originalPath = resolveOriginalDocument(
            $type === 'nda'
                ? (string) ($property['confidentiality_file'] ?? '')
                : (string) ($property['intention_file'] ?? '')
        );

        uploadLog('Documento original resuelto', [
            'type' => $type,
            'stored_relative' => $type === 'nda'
                ? ($property['confidentiality_file'] ?? '')
                : ($property['intention_file'] ?? ''),
            'originalPath' => $originalPath,
        ]);

        $detection = pdfSeemsSigned($targetPath, $originalPath);

        uploadLog('Resultado validación firma', [
            'type' => $type,
            'accepted' => $detection['accepted'] ?? null,
            'reason' => $detection['reason'] ?? null,
        ]);

        if (empty($detection['accepted'])) {
            @unlink($targetPath);

            throw new RuntimeException(sprintf(
                'El %s no parece firmado: %s',
                strtoupper($type),
                $detection['reason'] ?? 'sin detalle'
            ));
        }

        $relativePath = 'signed_docs/' . $targetName;

        $details = [
            'type' => $type,
            'relative' => $relativePath,
            'absolute' => $targetPath,
            'reason' => $detection['reason'] ?? 'aceptado',
        ];

        if ($type === 'nda') {
            $ndaDetails = $details;
        } else {
            $loiDetails = $details;
        }

        $attachments[] = [
            'path' => $targetPath,
            'name' => sprintf('%s_%s', strtoupper($type), $cleanName),
        ];
    }

    $checkStmt = $pdo->prepare('
        SELECT id
        FROM documentos_firmados
        WHERE user_id = :user_id
          AND propiedad_id = :propiedad_id
        LIMIT 1
    ');
    $checkStmt->execute([
        'user_id' => $buyerUserId,
        'propiedad_id' => $propertyId,
    ]);
    $existingRow = $checkStmt->fetch(PDO::FETCH_ASSOC);

    $insertParams = [
        'user_id' => $buyerUserId,
        'propiedad_id' => $propertyId,
        'nda_file_path' => $ndaDetails['relative'] ?? null,
        'loi_file_path' => $loiDetails['relative'] ?? null,
        'nda_valido' => 1,
        'loi_valido' => 1,
    ];

    if ($existingRow) {
        $updateStmt = $pdo->prepare('
            UPDATE documentos_firmados
            SET
                nda_file_path = :nda_file_path,
                loi_file_path = :loi_file_path,
                nda_subido_at = NOW(),
                loi_subido_at = NOW(),
                nda_valido = :nda_valido,
                loi_valido = :loi_valido,
                validado_admin = 0,
                updated_at = NOW()
            WHERE id = :id
        ');

        $updateParams = $insertParams;
        $updateParams['id'] = $existingRow['id'];
        $updateStmt->execute($updateParams);

        uploadLog('documentos_firmados actualizado', $updateParams);
    } else {
        $insertStmt = $pdo->prepare('
            INSERT INTO documentos_firmados (
                user_id,
                propiedad_id,
                nda_file_path,
                loi_file_path,
                nda_subido_at,
                loi_subido_at,
                nda_valido,
                loi_valido,
                validado_admin
            ) VALUES (
                :user_id,
                :propiedad_id,
                :nda_file_path,
                :loi_file_path,
                NOW(),
                NOW(),
                :nda_valido,
                :loi_valido,
                0
            )
        ');
        $insertStmt->execute($insertParams);

        uploadLog('documentos_firmados insertado', [
            'lastId' => $pdo->lastInsertId(),
            'params' => $insertParams,
        ]);
    }

    ensureBuyerPropertyAccess($pdo, $propertyId, $buyerUserId);

    $access = updateBuyerPropertyAccess($pdo, $propertyId, $buyerUserId, [
        'nda_uploaded' => 1,
        'loi_uploaded' => 1,
        'nda_approved' => 0,
        'loi_approved' => 0,
        'dossier_unlocked' => 0,
    ]);

    uploadLog('buyer_property_access actualizado', [
        'access' => $access,
    ]);

    $token = createDocumentReviewToken($pdo, $propertyId, $buyerUserId);

    uploadLog('Token de revisión creado', [
        'token' => $token,
    ]);

    $pdo->commit();
    uploadLog('COMMIT OK');

} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
        uploadLog('ROLLBACK ejecutado');
    }

    foreach ($uploadedAbsolutePaths as $path) {
        if (is_file($path)) {
            @unlink($path);
            uploadLog('Archivo limpiado tras error', ['path' => $path]);
        }
    }

    uploadLog('Error general', [
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
    ]);

    respondJson(500, [
        'success' => false,
        'message' => 'No se pudo procesar la documentación.',
        'detail' => $exception->getMessage(),
    ]);
}

$buyerFullName = trim(sprintf(
    '%s %s',
    $authUser['first_name'] ?? $authUser['name'] ?? '',
    $authUser['last_name'] ?? ''
));
$buyerFirstName = $authUser['first_name'] ?? '';
$buyerLastName = $authUser['last_name'] ?? '';
$buyerEmail = $authUser['email'] ?? 'No disponible';
$buyerPhone = $authUser['phone'] ?? 'No disponible';
$buyerUsername = $authUser['username'] ?? $authUser['sub'] ?? '—';
$buyerId = (string) ($authUser['sub'] ?? $authUser['id'] ?? '—');
$approvalLink = buildReviewApprovalLink($token);

$documentList = implode('</li><li>', array_map(
    fn($doc) => htmlspecialchars(
        sprintf('%s: %s (%s)', strtoupper((string) $doc['type']), (string) $doc['relative'], (string) $doc['reason']),
        ENT_QUOTES,
        'UTF-8'
    ),
    [$ndaDetails, $loiDetails]
));

$emailBody = sprintf(
    '<h2>Documentación firmada pendiente de revisión</h2>
    <p><strong>Propiedad:</strong> %s | <strong>ID:</strong> %d | <strong>Ciudad · Zona:</strong> %s · %s</p>
    <h3>Comprador</h3>
    <ul>
        <li>Nombre completo: %s</li>
        <li>Nombre: %s</li>
        <li>Apellidos: %s</li>
        <li>Email: %s</li>
        <li>Teléfono: %s</li>
        <li>Username: %s</li>
        <li>ID: %s</li>
    </ul>
    <h3>Documentos recibidos</h3>
    <ul><li>%s</li></ul>
    <p style="margin-top:18px;"><a href="%s" style="display:inline-flex;padding:14px 26px;background:#1f2937;color:#fff;border-radius:14px;text-decoration:none;font-weight:600;">Aprobar documentos y desbloquear dossier</a></p>',
    htmlspecialchars($property['tipo_propiedad'] ?? 'Activo', ENT_QUOTES, 'UTF-8'),
    $propertyId,
    htmlspecialchars($property['ciudad'] ?? 'Ciudad desconocida', ENT_QUOTES, 'UTF-8'),
    htmlspecialchars($property['zona'] ?? 'Zona desconocida', ENT_QUOTES, 'UTF-8'),
    htmlspecialchars($buyerFullName ?: 'Comprador no identificado', ENT_QUOTES, 'UTF-8'),
    htmlspecialchars($buyerFirstName ?: '—', ENT_QUOTES, 'UTF-8'),
    htmlspecialchars($buyerLastName ?: '—', ENT_QUOTES, 'UTF-8'),
    htmlspecialchars((string) $buyerEmail, ENT_QUOTES, 'UTF-8'),
    htmlspecialchars((string) $buyerPhone, ENT_QUOTES, 'UTF-8'),
    htmlspecialchars((string) $buyerUsername, ENT_QUOTES, 'UTF-8'),
    htmlspecialchars((string) $buyerId, ENT_QUOTES, 'UTF-8'),
    $documentList,
    htmlspecialchars($approvalLink, ENT_QUOTES, 'UTF-8')
);

$mailSent = false;
$mailErrorMessage = null;

try {
    sendNotificationEmail(
        'practicasreglado@gmail.com',
        sprintf(
            'Documentación firmada pendiente de revisión - Propiedad #%d - Comprador %s',
            $propertyId,
            $buyerFullName ?: 'sin nombre'
        ),
        $emailBody,
        filter_var((string) $buyerEmail, FILTER_VALIDATE_EMAIL) ?: null,
        $attachments
    );

    $mailSent = true;

    uploadLog('Correo de revisión enviado', [
        'approval_link' => $approvalLink,
        'attachments' => $attachments,
    ]);
} catch (Throwable $mailError) {
    $mailErrorMessage = $mailError->getMessage();

    uploadLog('Error enviando correo', [
        'message' => $mailErrorMessage,
        'approval_link' => $approvalLink,
        'attachments' => $attachments,
    ]);
}

respondJson(200, [
    'success' => true,
    'message' => $mailSent
        ? 'Documentos recibidos y enviados a revisión.'
        : 'Documentos recibidos, pero el correo de revisión no pudo enviarse.',
    'mail_sent' => $mailSent,
    'mail_error' => $mailErrorMessage,
    'access' => $access,
    'approval_link' => $approvalLink,
    'saved_files' => [
        'nda' => $ndaDetails['relative'] ?? null,
        'loi' => $loiDetails['relative'] ?? null,
    ],
]);