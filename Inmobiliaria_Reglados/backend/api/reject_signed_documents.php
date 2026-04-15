<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/env_loader.php';
require_once __DIR__ . '/../lib/document_access.php';
require_once __DIR__ . '/../lib/document_review.php';
require_once __DIR__ . '/../lib/notifications.php';
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
        $emailBody = <<<'HTML'
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Documentación rechazada</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial, sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:30px 0;">
<tr>
<td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,0.1);">
<tr>
<td style="background:linear-gradient(135deg,#2563eb,#1e40af);padding:20px 24px;text-align:center;color:#ffffff;">
    <h2 style="margin:0;font-size:22px;line-height:1.3;font-weight:700;">
        Reglado Real Estate
    </h2>
</td>
</tr>
<tr>
<td style="padding:30px;color:#111827;">
    <h3 style="margin-top:0;color:#b91c1c;">Documentación rechazada</h3>
    <p style="font-size:15px;line-height:1.6;margin-bottom:16px;">
        Tu documentación ha sido <strong>rechazada</strong>.
    </p>
    <p style="font-size:15px;line-height:1.6;margin-bottom:20px;">
        Por favor, revisa los documentos enviados y vuelve a subirlos desde tu panel.
    </p>
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:16px;margin-bottom:24px;">
        <p style="margin:0;color:#991b1b;font-size:14px;">
            ✖ La documentación no ha superado la validación administrativa
        </p>
    </div>
</td>
</tr>
<tr>
<td style="padding:20px;text-align:center;font-size:12px;color:#9ca3af;border-top:1px solid #e5e7eb;">
    Reglado Real Estate · Sistema automatizado de inversión inmobiliaria
</td>
</tr>
</table>
</td>
</tr>
</table>
</body>
</html>
HTML;

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

    rejectDebug('ERROR', [
        'message' => $exception->getMessage(),
        'trace' => $exception->getTraceAsString(),
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
        <p>' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>
    </div>
</body>
</html>';
}