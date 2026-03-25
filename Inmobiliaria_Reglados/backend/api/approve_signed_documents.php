<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/env_loader.php';
require_once __DIR__ . '/../lib/document_access.php';
require_once __DIR__ . '/../lib/document_review.php';

loadEnv(__DIR__ . '/../.env');

header('Content-Type: text/html; charset=utf-8');

$token = trim((string) ($_GET['token'] ?? ''));
if ($token === '') {
    echo '<h4>Token inválido</h4><p>Falta el token de revisión.</p>';
    exit;
}

$review = fetchDocumentReviewByToken($pdo, $token);
if ($review === null) {
    echo '<h4>Token inválido o caducado</h4><p>El enlace ya no es válido o ya fue utilizado.</p>';
    exit;
}

$propertyId = (int) $review['property_id'];
$buyerUserId = (int) $review['buyer_user_id'];

try {
    $pdo->beginTransaction();

    $updateDocStmt = $pdo->prepare('
        UPDATE documentos_firmados
        SET office_status = :status,
            office_reviewed_at = CURRENT_TIMESTAMP,
            office_reviewed_by = :reviewed_by
        WHERE property_id = :property_id
          AND user_id = :user_id
          AND document_type = :document_type
        ORDER BY created_at DESC
        LIMIT 1
    ');

    foreach (['nda', 'loi'] as $type) {
        $updateDocStmt->execute([
            'status' => 'aceptado',
            'reviewed_by' => 0,
            'property_id' => $propertyId,
            'user_id' => $buyerUserId,
            'document_type' => $type,
        ]);
    }

    updateBuyerPropertyAccess($pdo, $propertyId, $buyerUserId, [
        'nda_approved' => 1,
        'loi_approved' => 1,
        'dossier_unlocked' => 1,
    ]);

    markDocumentReviewApproved($pdo, (int) $review['id'], 0);

    $pdo->commit();

    echo '<h4>Documentación aprobada</h4><p>Los documentos han sido aprobados y el dossier ha quedado desbloqueado.</p>';
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo '<h4>Error interno</h4><p>No se pudo aprobar la documentación. Inténtalo de nuevo.</p>';
}
