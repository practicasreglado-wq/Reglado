<?php
declare(strict_types=1);

/**
 * Devuelve el estado actual del flujo de firma del comprador para una
 * propiedad. Lo consume el frontend (PropertyDetail.vue) para mostrar el
 * botón correcto (descargar NDA, subir firmado, descargar dossier...).
 *
 * Respuesta incluye flags como:
 *  - dossier_unlocked / contact_unlocked
 *  - nda_downloaded / loi_downloaded
 *  - status: 'pendiente' | 'firmado' | 'validado'
 */

require_once __DIR__ . '/../config/cors.php';
applyCors();
handlePreflight();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../lib/env_loader.php';
require_once __DIR__ . '/../lib/document_access.php';

loadEnv(dirname(__DIR__) . '/../.env');

$context = requireAuthenticatedUser($pdo);
$userId = (int) ($context['local']['iduser'] ?? 0);
$propertyId = (int) ($_POST['property_id'] ?? $_POST['propertyId'] ?? 0);

if ($userId <= 0 || $propertyId <= 0) {
    respondJson(422, [
        'success' => false,
        'message' => 'Faltan datos.',
    ]);
}

// Importante: consultar estado NO debe crear accesos persistentes.
$access = fetchBuyerPropertyAccess($pdo, $propertyId, $userId) ?? [];

$documentStmt = $pdo->prepare('
    SELECT validado_admin, nda_file_path, loi_file_path
    FROM documentos_firmados
    WHERE propiedad_id = :property_id
      AND user_id = :user_id
    LIMIT 1
');
$documentStmt->execute([
    'property_id' => $propertyId,
    'user_id' => $userId,
]);
$documentRow = $documentStmt->fetch(PDO::FETCH_ASSOC) ?: [];

$validadoAdmin = isset($documentRow['validado_admin'])
    ? (int) $documentRow['validado_admin']
    : 0;

$dossierUnlocked = (int) ($access['dossier_unlocked'] ?? 0) === 1;
$ndaUploaded = (int) ($access['nda_uploaded'] ?? 0) === 1;
$loiUploaded = (int) ($access['loi_uploaded'] ?? 0) === 1;

$status = 'pendiente';
$message = 'Documentos pendientes de firma.';

if ($validadoAdmin === -1) {
    $status = 'rejected';
    $message = 'La documentación ha sido rechazada. Debes volver a subir los documentos firmados.';
} elseif ($dossierUnlocked) {
    $status = 'validado';
    $message = 'Documentos validados. Ya puedes descargar el dossier.';
} elseif ($ndaUploaded || $loiUploaded) {
    $status = 'firmado';
    $message = 'Documentos recibidos. Esperando validación administrativa.';
}

respondJson(200, [
    'success' => true,
    'status' => $status,
    'message' => $message,
    'access' => [
        'nda_uploaded' => (int) ($access['nda_uploaded'] ?? 0),
        'loi_uploaded' => (int) ($access['loi_uploaded'] ?? 0),
        'nda_approved' => (int) ($access['nda_approved'] ?? 0),
        'loi_approved' => (int) ($access['loi_approved'] ?? 0),
        'dossier_unlocked' => (int) ($access['dossier_unlocked'] ?? 0),
        'validado_admin' => $validadoAdmin,
        'status' => $status,
    ],
]);
