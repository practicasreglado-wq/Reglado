<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../lib/env_loader.php';
require_once __DIR__ . '/../lib/document_access.php';

applyAuthCors();
handlePreflight();
loadEnv(__DIR__ . '/../.env');

$context = requireAuthenticatedUser($pdo);
$reviewerId = (int) (
    $context['local']['iduser']
    ?? $context['local']['id']
    ?? $context['auth']['id']
    ?? $_SESSION['user']['id']
    ?? 0
);
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

$dossierUnlocked = ($access['nda_approved'] && $access['loi_approved']) ? 1 : 0;
if ((int) ($access['dossier_unlocked'] ?? 0) !== $dossierUnlocked) {
    $access = updateBuyerPropertyAccess($pdo, $propertyId, $buyerUserId, [
        'dossier_unlocked' => $dossierUnlocked,
    ]);
}

$pdo->commit();

respondJson(200, [
    'success' => true,
    'message' => 'Estado actualizado.',
    'access' => $access,
]);
