<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../lib/env_loader.php';
require_once __DIR__ . '/../lib/document_access.php';
require_once __DIR__ . '/../lib/audit.php';

applyAuthCors();
handlePreflight();
loadEnv(__DIR__ . '/../.env');

$context = requireAuthenticatedUser($pdo);
$auth = $context['auth'] ?? [];
$reviewerId = (int) (
    $context['local']['iduser']
    ?? $context['local']['id']
    ?? $context['auth']['id']
    ?? $context['auth']['sub']
    ?? 0
);
$reviewerRole = strtolower((string) ($auth['role'] ?? ''));

// Solo admins pueden aprobar/rechazar documentos firmados de otros usuarios.
// Sin este check, cualquier usuario autenticado podría alterar el estado de
// las firmas de cualquier otro usuario y desbloquear dossiers ajenos.
if ($reviewerRole !== 'admin') {
    respondJson(403, [
        'success' => false,
        'message' => 'Acceso restringido. Solo administradores pueden revisar documentos firmados.',
    ]);
}

$propertyId = (int) ($_POST['property_id'] ?? 0);
$buyerUserId = (int) ($_POST['buyer_user_id'] ?? 0);
$documentType = strtolower(trim((string) ($_POST['document_type'] ?? '')));
$action = strtolower(trim((string) ($_POST['action'] ?? '')));

$allowedActions = ['accept', 'reject'];
$allowedDocuments = ['nda', 'loi'];

if ($propertyId <= 0 || $buyerUserId <= 0 || $reviewerId <= 0) {
    respondJson(422, ['success' => false, 'message' => 'Datos incompletos.']);
}

if (!in_array($action, $allowedActions, true) || !in_array($documentType, $allowedDocuments, true)) {
    respondJson(422, ['success' => false, 'message' => 'Parámetros inválidos.']);
}

$stmt = $pdo->prepare('
    SELECT id FROM documentos_firmados
    WHERE property_id = :property_id
      AND user_id = :user_id
      AND document_type = :document_type
    ORDER BY created_at DESC
    LIMIT 1
');
$stmt->execute([
    'property_id' => $propertyId,
    'user_id' => $buyerUserId,
    'document_type' => $documentType,
]);
$document = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$document) {
    respondJson(404, ['success' => false, 'message' => 'Documento no encontrado.']);
}

$pdo->beginTransaction();

$updateDocStmt = $pdo->prepare('
    UPDATE documentos_firmados
    SET office_status = :status,
        office_reviewed_by = :reviewed_by,
        office_reviewed_at = CURRENT_TIMESTAMP
    WHERE id = :id
');
$newStatus = $action === 'accept' ? 'aceptado' : 'rechazado';
$updateDocStmt->execute([
    'status' => $newStatus,
    'reviewed_by' => $reviewerId,
    'id' => (int) $document['id'],
]);

$updates = [];
$updates[$documentType === 'nda' ? 'nda_approved' : 'loi_approved'] = $action === 'accept' ? 1 : 0;
$access = ensureBuyerPropertyAccess($pdo, $propertyId, $buyerUserId);
$access = updateBuyerPropertyAccess($pdo, $propertyId, $buyerUserId, $updates);

$ndaApproved = (int) ($access['nda_approved'] ?? 0) === 1;
$loiApproved = (int) ($access['loi_approved'] ?? 0) === 1;
$dossierUnlocked = ($ndaApproved && $loiApproved) ? 1 : 0;
if ((int) ($access['dossier_unlocked'] ?? 0) !== $dossierUnlocked) {
    $access = updateBuyerPropertyAccess($pdo, $propertyId, $buyerUserId, [
        'dossier_unlocked' => $dossierUnlocked,
    ]);
}

$pdo->commit();

auditLog($pdo, 'document.review.' . $action, array_merge(
    auditContextFromAuth($auth, $reviewerId),
    [
        'resource_type' => 'document',
        'resource_id'   => $documentType . ':' . $propertyId,
        'metadata'      => [
            'document_type'      => $documentType,
            'property_id'        => $propertyId,
            'buyer_user_id'      => $buyerUserId,
            'new_status'         => $newStatus,
            'dossier_unlocked'   => (int) ($access['dossier_unlocked'] ?? 0) === 1,
        ],
    ]
));

respondJson(200, [
    'success' => true,
    'message' => 'Estado actualizado.',
    'access' => $access,
]);
