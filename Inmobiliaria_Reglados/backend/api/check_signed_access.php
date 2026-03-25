<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/cors.php';
applyCors();
handlePreflight();
require_once __DIR__. '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../lib/env_loader.php';
require_once __DIR__ . '/../lib/document_access.php';

loadEnv(dirname(__DIR__) . '/../.env');

$context = requireAuthenticatedUser($pdo);
$userId = (int) ($context['local']['iduser'] ?? 0);
$propertyId = (int) ($_POST['property_id'] ?? $_POST['propertyId'] ?? 0);
if ($userId <= 0 || $propertyId <= 0) {
    respondJson(422, ['success' => false, 'message' => 'Faltan datos.']);
}

$access = ensureBuyerPropertyAccess($pdo, $propertyId, $userId);

$dossierUnlocked = (int) ($access['dossier_unlocked'] ?? 0) === 1;
$status = 'pendiente';
$message = 'Documentos pendientes de firma.';

if ($dossierUnlocked) {
    $status = 'validado';
    $message = 'Documentos validados. Ya puedes descargar el dossier.';
} elseif ((int) ($access['nda_uploaded'] ?? 0) === 1 || (int) ($access['loi_uploaded'] ?? 0) === 1) {
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
    ],
]);
