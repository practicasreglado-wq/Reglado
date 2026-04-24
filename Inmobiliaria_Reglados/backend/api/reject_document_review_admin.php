<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once __DIR__ . '/../config/cors.php';
require_once dirname(__DIR__) . '/lib/env_loader.php';
require_once dirname(__DIR__) . '/lib/document_access.php';
require_once dirname(__DIR__) . '/lib/document_review.php';
require_once dirname(__DIR__) . '/lib/notifications.php';
require_once dirname(__DIR__) . '/lib/audit.php';
require_once dirname(__DIR__) . '/lib/email_layout.php';
require_once dirname(__DIR__) . '/lib/error_reporting.php';
require_once dirname(__DIR__) . '/lib/admin_password_check.php';
require_once dirname(__DIR__) . '/send_mail.php';

loadEnv(dirname(__DIR__) . '/.env');

applyCors();
handlePreflight();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondJson(405, ['success' => false, 'message' => 'Método no permitido.']);
}

$context = requireAuthenticatedUser($pdo);
$auth = $context['auth'] ?? [];
$role = strtolower((string) ($auth['role'] ?? ''));

if ($role !== 'admin') {
    respondJson(403, ['success' => false, 'message' => 'Acceso restringido. Solo administradores.']);
}

$input = json_decode(file_get_contents('php://input') ?: '{}', true);
$reviewId = (int) ($input['review_id'] ?? 0);
$adminPassword = (string) ($input['admin_password'] ?? '');

if ($reviewId <= 0) {
    respondJson(422, ['success' => false, 'message' => 'ID de revisión no válido.']);
}

requireAdminPasswordConfirmation(
    (int) ($auth['sub'] ?? 0),
    $adminPassword,
    'admin_document_reject'
);

if ($pdo instanceof PDO) {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

try {
    $pdo->beginTransaction();

    $reviewStmt = $pdo->prepare('
        SELECT id, property_id, buyer_user_id, reviewer_email, approved_at
        FROM signed_document_review_tokens
        WHERE id = :id
        LIMIT 1
    ');
    $reviewStmt->execute(['id' => $reviewId]);
    $review = $reviewStmt->fetch(PDO::FETCH_ASSOC);

    if (!$review) {
        $pdo->rollBack();
        respondJson(404, ['success' => false, 'message' => 'La revisión no existe.']);
    }

    if (!empty($review['approved_at'])) {
        $pdo->rollBack();
        respondJson(409, ['success' => false, 'message' => 'Esta revisión ya fue procesada.']);
    }

    $propertyId = (int) $review['property_id'];
    $buyerUserId = (int) $review['buyer_user_id'];

    $checkDocStmt = $pdo->prepare('
        SELECT id FROM documentos_firmados
        WHERE propiedad_id = :propiedad_id AND user_id = :user_id
        LIMIT 1
    ');
    $checkDocStmt->execute(['propiedad_id' => $propertyId, 'user_id' => $buyerUserId]);

    if ($checkDocStmt->fetch()) {
        $updateDocStmt = $pdo->prepare('
            UPDATE documentos_firmados
            SET validado_admin = -1, updated_at = NOW()
            WHERE propiedad_id = :propiedad_id AND user_id = :user_id
        ');
        $updateDocStmt->execute(['propiedad_id' => $propertyId, 'user_id' => $buyerUserId]);
    }

    updateBuyerPropertyAccess($pdo, $propertyId, $buyerUserId, [
        'nda_uploaded'     => 0,
        'loi_uploaded'     => 0,
        'nda_approved'     => 0,
        'loi_approved'     => 0,
        'dossier_unlocked' => 0,
    ]);

    $reviewerId = (int) ($auth['sub'] ?? 0);
    markDocumentReviewRejected($pdo, $reviewId, $reviewerId > 0 ? $reviewerId : 0);

    try {
        createUserNotificationRecord($pdo, [
            'user_id' => $buyerUserId,
            'title' => 'Documentación rechazada',
            'message' => 'Tu documentación ha sido rechazada. Revisa los documentos y vuelve a enviarlos.',
            'type' => 'document_rejected',
            'related_request_id' => $reviewId,
        ]);
    } catch (Throwable $e) {
        error_log('[reject_document_review_admin] notificación falló: ' . $e->getMessage());
    }

    $buyerStmt = $pdo->prepare('SELECT email FROM regladousers.users WHERE id = :id LIMIT 1');
    $buyerStmt->execute(['id' => $buyerUserId]);
    $buyerRow = $buyerStmt->fetch(PDO::FETCH_ASSOC);
    $buyerEmail = $buyerRow['email'] ?? null;

    $pdo->commit();

    if ($buyerEmail && filter_var($buyerEmail, FILTER_VALIDATE_EMAIL)) {
        $emailBody = renderEmailLayout(
            'Documentación rechazada',
            'La revisión administrativa no ha superado la validación',
            <<<'HTML'
<h3 style="margin:0 0 16px;color:#b91c1c;font-size:18px;">Documentación rechazada</h3>
<p style="margin:0 0 16px;">Tu documentación ha sido <strong>rechazada</strong>.</p>
<p style="margin:0 0 20px;">Por favor, revisa los documentos enviados y vuelve a subirlos desde tu panel.</p>
<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:16px;margin-bottom:24px;">
<p style="margin:0;color:#991b1b;font-size:14px;">✖ La documentación no ha superado la validación administrativa</p>
</div>
HTML
        );

        try {
            sendNotificationEmail($buyerEmail, 'Documentación rechazada', $emailBody);
        } catch (Throwable $e) {
            error_log('[reject_document_review_admin] email falló: ' . $e->getMessage());
        }
    }

    auditLog($pdo, 'document.signed.reject', array_merge(
        auditContextFromAuth($auth, $reviewerId),
        [
            'resource_type' => 'signed_document_review',
            'resource_id'   => (string) $reviewId,
            'metadata'      => ['property_id' => $propertyId, 'buyer_user_id' => $buyerUserId, 'via' => 'admin_panel']
        ]
    ));

    respondJson(200, ['success' => true, 'message' => 'Documentación rechazada correctamente.']);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $errorId = logAndReferenceError('reject_document_review_admin', $e);
    respondJson(500, [
        'success' => false,
        'message' => 'Error al rechazar. Referencia: ' . $errorId,
    ]);
}
