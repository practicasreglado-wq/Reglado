<?php
declare(strict_types=1);

/**
 * Endpoint del enlace "Rechazar firma" del correo al revisor.
 *
 * Mismo flujo que approve_signed_documents.php pero negativo:
 *  1) Marca el token como rechazado.
 *  2) Pone documentos_firmados.firmado_valido = 0 (los docs quedan inválidos).
 *  3) NO desbloquea el dossier — buyer_property_access queda como estaba.
 *  4) Notifica al comprador (in-app + email) que debe volver a subir docs
 *    correctos.
 *
 * Devuelve HTML directo (no JSON) porque se accede desde el navegador del
 * revisor.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/env_loader.php';
require_once __DIR__ . '/../lib/document_access.php';
require_once __DIR__ . '/../lib/document_review.php';
require_once __DIR__ . '/../lib/notifications.php';
require_once __DIR__ . '/../lib/audit.php';
require_once __DIR__ . '/../lib/email_layout.php';
require_once __DIR__ . '/../lib/error_reporting.php';
require_once __DIR__ . '/../config/cors.php';

applyCors();
handlePreflight();
loadEnv(__DIR__ . '/../.env');
require_once __DIR__ . '/../send_mail.php';

header('Content-Type: text/html; charset=utf-8');

function rejectDebug(string $message, $data = null): void
{
    if ($data === null) {
        error_log('[reject_signed_documents] ' . $message);
        return;
    }

    error_log('[reject_signed_documents] ' . $message . ' => ' . print_r($data, true));
}

if ($pdo instanceof PDO) {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

$token = trim((string) ($_GET['token'] ?? ''));

if ($token === '') {
    echo '<h4>Token inválido</h4><p>Falta el token de revisión.</p>';
    exit;
}

$review = fetchDocumentReviewByToken($pdo, $token);
$buyerUserRecord = null;

if ($review === null) {
    echo '<h4>Token inválido o no encontrado</h4><p>El enlace no es válido.</p>';
    exit;
}

$propertyId = (int) ($review['property_id'] ?? 0);
$buyerUserId = (int) ($review['buyer_user_id'] ?? 0);
$reviewId = (int) ($review['id'] ?? 0);

if ($propertyId <= 0 || $buyerUserId <= 0 || $reviewId <= 0) {
    echo '<h4>Error interno</h4><p>Los datos del enlace de revisión no son válidos.</p>';
    exit;
}

if (!empty($review['is_already_approved'])) {
    echo '<h4>Enlace ya utilizado</h4><p>Este enlace ya ha sido utilizado.</p>';
    exit;
}

if (!empty($review['is_expired'])) {
    try {
        createUserNotificationRecord($pdo, [
            'user_id' => $buyerUserId,
            'title' => 'Solicitud caducada',
            'message' => 'El tiempo de espera para aprobar tus documentos ha caducado. Debes volver a subirlos.',
            'type' => 'document_expired',
            'related_request_id' => $reviewId,
        ]);
    } catch (Throwable $notificationException) {
        rejectDebug('EXPIRED NOTIFICATION FAILED', [
            'message' => $notificationException->getMessage(),
        ]);
    }

    $stmtUser = $pdo->prepare('
        SELECT id, email, name, first_name, last_name
        FROM regladousers.users
        WHERE id = :id
        LIMIT 1
    ');
    $stmtUser->execute(['id' => $buyerUserId]);
    $buyerUserRecord = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (
        $buyerUserRecord &&
        !empty($buyerUserRecord['email']) &&
        filter_var($buyerUserRecord['email'], FILTER_VALIDATE_EMAIL)
    ) {
        try {
            sendNotificationEmail(
                $buyerUserRecord['email'],
                'Solicitud caducada',
                '<p>El tiempo de espera para aprobar tus documentos ha caducado. Por favor, vuelve a subirlos.</p>'
            );
        } catch (Throwable $emailException) {
            rejectDebug('EXPIRED EMAIL FAILED', [
                'message' => $emailException->getMessage(),
            ]);
        }
    }

    echo '<h4>Token caducado</h4><p>El tiempo de espera para aprobar los documentos ha caducado.</p>';
    exit;
}

try {
    $pdo->beginTransaction();

    $checkDocStmt = $pdo->prepare('
        SELECT *
        FROM documentos_firmados
        WHERE propiedad_id = :propiedad_id
          AND user_id = :user_id
        LIMIT 1
    ');
    $checkDocStmt->execute([
        'propiedad_id' => $propertyId,
        'user_id' => $buyerUserId,
    ]);
    $documentRow = $checkDocStmt->fetch(PDO::FETCH_ASSOC);

    if (!$documentRow) {
        throw new RuntimeException('No existe registro en documentos_firmados para esta propiedad y comprador.');
    }

    $updateDocStmt = $pdo->prepare('
        UPDATE documentos_firmados
        SET validado_admin = -1,
            updated_at = NOW()
        WHERE propiedad_id = :propiedad_id
          AND user_id = :user_id
    ');
    $updateDocStmt->execute([
        'propiedad_id' => $propertyId,
        'user_id' => $buyerUserId,
    ]);

    updateBuyerPropertyAccess($pdo, $propertyId, $buyerUserId, [
        'nda_uploaded' => 0,
        'loi_uploaded' => 0,
        'nda_approved' => 0,
        'loi_approved' => 0,
        'dossier_unlocked' => 0,
    ]);

    markDocumentReviewRejected($pdo, $reviewId, 0);

    auditLog($pdo, 'document.signed.reject', [
        'user_email'    => (string) ($review['reviewer_email'] ?? '') ?: null,
        'user_role'     => 'admin',
        'resource_type' => 'signed_document_review',
        'resource_id'   => (string) $reviewId,
        'metadata'      => ['property_id' => $propertyId, 'buyer_user_id' => $buyerUserId]
    ]);

    try {
        createUserNotificationRecord($pdo, [
            'user_id' => $buyerUserId,
            'title' => 'Documentación rechazada',
            'message' => 'Tu documentación ha sido rechazada. Revisa los documentos y vuelve a enviarlos.',
            'type' => 'document_rejected',
            'related_request_id' => $reviewId,
        ]);
    } catch (Throwable $notificationException) {
        rejectDebug('REJECT NOTIFICATION FAILED', [
            'message' => $notificationException->getMessage(),
        ]);
    }

    $userStmt = $pdo->prepare('
        SELECT id, email, name, first_name, last_name
        FROM regladousers.users
        WHERE id = :id
        LIMIT 1
    ');
    $userStmt->execute(['id' => $buyerUserId]);
    $buyerUserRecord = $userStmt->fetch(PDO::FETCH_ASSOC);

    $pdo->commit();

    if (
        $buyerUserRecord &&
        !empty($buyerUserRecord['email']) &&
        filter_var($buyerUserRecord['email'], FILTER_VALIDATE_EMAIL)
    ) {
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
            sendNotificationEmail(
                $buyerUserRecord['email'],
                'Documentación rechazada',
                $emailBody
            );
        } catch (Throwable $emailException) {
            rejectDebug('REJECT EMAIL FAILED', [
                'message' => $emailException->getMessage(),
            ]);
        }
    }

    echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Documentación rechazada</title>
</head>
<body style="font-family:Arial,sans-serif;background:#f8fafc;padding:40px;">
    <div style="max-width:640px;margin:0 auto;background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:32px;">
        <h2 style="margin-top:0;color:#b91c1c;">Documentación rechazada</h2>
        <p>La documentación del comprador ha sido rechazada correctamente.</p>
        <p><strong>Propiedad:</strong> #' . htmlspecialchars((string) $propertyId, ENT_QUOTES, 'UTF-8') . '</p>
        <p><strong>Comprador:</strong> #' . htmlspecialchars((string) $buyerUserId, ENT_QUOTES, 'UTF-8') . '</p>
    </div>
</body>
</html>';
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $errorId = logAndReferenceError('reject_signed_documents', $exception);

    rejectDebug('ERROR', [
        'error_id' => $errorId,
        'message'  => $exception->getMessage(),
    ]);

    echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Error al rechazar</title>
</head>
<body style="font-family:Arial,sans-serif;background:#f8fafc;padding:40px;">
    <div style="max-width:640px;margin:0 auto;background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:32px;">
        <h2 style="margin-top:0;color:#b91c1c;">Error al rechazar documentación</h2>
        <p>No se pudo completar el proceso.</p>
        <p>Si el problema persiste, contacta con soporte indicando esta referencia: <strong>' . htmlspecialchars($errorId, ENT_QUOTES, 'UTF-8') . '</strong></p>
    </div>
</body>
</html>';
}