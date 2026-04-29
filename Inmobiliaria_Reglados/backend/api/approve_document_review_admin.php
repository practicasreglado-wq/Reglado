<?php
declare(strict_types=1);

/**
 * Endpoint para que un admin AUTENTICADO apruebe la revisión de documentos
 * firmados desde el panel admin (NO desde el correo).
 *
 * Diferencias con approve_signed_documents.php:
 *  - Aquel se accede desde el navegador del revisor con token (sin login).
 *  - Este se llama desde la SPA del admin con JWT + confirmación de pwd.
 *
 * Comparten misma lógica de fondo: marca docs como validados, desbloquea
 * dossier en buyer_property_access y notifica al comprador.
 */

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
require_once dirname(__DIR__) . '/lib/apiloging_client.php';
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
    'admin_document_approve'
);

if ($pdo instanceof PDO) {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

try {
    $pdo->beginTransaction();

    $reviewStmt = $pdo->prepare('
        SELECT id, property_id, buyer_user_id, reviewer_email, approved_at, expires_at
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
    if (!$checkDocStmt->fetch()) {
        $pdo->rollBack();
        respondJson(404, ['success' => false, 'message' => 'No existen documentos firmados asociados a esta revisión.']);
    }

    $updateDocStmt = $pdo->prepare('
        UPDATE documentos_firmados
        SET validado_admin = 1, updated_at = NOW()
        WHERE propiedad_id = :propiedad_id AND user_id = :user_id
    ');
    $updateDocStmt->execute(['propiedad_id' => $propertyId, 'user_id' => $buyerUserId]);

    updateBuyerPropertyAccess($pdo, $propertyId, $buyerUserId, [
        'nda_approved'     => 1,
        'loi_approved'     => 1,
        'dossier_unlocked' => 1,
    ]);

    $approverId = (int) ($auth['sub'] ?? 0);
    markDocumentReviewApproved($pdo, $reviewId, $approverId > 0 ? $approverId : null);

    try {
        createUserNotificationRecord($pdo, [
            'user_id' => $buyerUserId,
            'title' => 'Documentación aceptada',
            'message' => 'Tu solicitud ha sido aceptada. Ya puedes continuar con el siguiente paso del proceso.',
            'type' => 'document_approval',
            'related_request_id' => $reviewId,
        ]);
    } catch (Throwable $e) {
        error_log('[approve_document_review_admin] notificación falló: ' . $e->getMessage());
    }

    $buyerRow = apilogingFindUserById($buyerUserId);
    $buyerEmail = $buyerRow['email'] ?? null;

    $pdo->commit();

    if ($buyerEmail && filter_var($buyerEmail, FILTER_VALIDATE_EMAIL)) {
        $panelUrl = htmlspecialchars(
            rtrim((string) (getenv('FRONTEND_URL') ?: 'http://localhost:5175'), '/') . '/profile/properties-for-sale',
            ENT_QUOTES,
            'UTF-8'
        );
        $emailBody = renderEmailLayout(
            'Solicitud aceptada',
            'Tu documentación ha sido validada',
            <<<HTML
<h3 style="margin:0 0 16px;color:#15803d;font-size:18px;">Documentación aprobada</h3>
<p style="margin:0 0 16px;">Nos complace informarte de que tu solicitud ha sido <strong>aprobada correctamente</strong>.</p>
<p style="margin:0 0 20px;">La documentación ha sido validada y ya puedes continuar con el siguiente paso del proceso desde tu panel.</p>
<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:16px;margin-bottom:24px;">
<p style="margin:0;color:#166534;font-size:14px;">✔ Acceso desbloqueado al dossier del activo<br>✔ Proceso validado por el equipo administrativo</p>
</div>
<div style="text-align:center;margin-top:20px;">
<a href="{$panelUrl}" target="_blank" rel="noopener" style="background:#0b3d91;color:#ffffff;padding:12px 24px;text-decoration:none;border-radius:8px;font-size:14px;font-weight:bold;display:inline-block;">Acceder a mi panel</a>
</div>
HTML
        );

        try {
            sendNotificationEmail($buyerEmail, 'Solicitud aceptada', $emailBody);
        } catch (Throwable $e) {
            error_log('[approve_document_review_admin] email falló: ' . $e->getMessage());
        }
    }

    auditLog($pdo, 'document.signed.approve', array_merge(
        auditContextFromAuth($auth, $approverId),
        [
            'resource_type' => 'signed_document_review',
            'resource_id'   => (string) $reviewId,
            'metadata'      => ['property_id' => $propertyId, 'buyer_user_id' => $buyerUserId, 'via' => 'admin_panel']
        ]
    ));

    respondJson(200, ['success' => true, 'message' => 'Documentación aprobada correctamente.']);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $errorId = logAndReferenceError('approve_document_review_admin', $e);
    respondJson(500, [
        'success' => false,
        'message' => 'Error al aprobar. Referencia: ' . $errorId,
    ]);
}
