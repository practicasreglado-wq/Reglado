<?php
declare(strict_types=1);

/**
 * Endpoint del enlace "Aprobar firma" del correo al revisor.
 *
 * El revisor llega aquí desde el email enviado por upload_signed_documents.php,
 * con un token en query string (?token=…). Si el token es válido y no está
 * caducado:
 *  1) Marca el token como aprobado (lib/document_review.php).
 *  2) Pone documentos_firmados.validado_admin = 1 para esa propiedad y comprador.
 *  3) Activa buyer_property_access.dossier_unlocked = 1 → el comprador ya
 *    puede descargar el dossier completo y agendar firma con notario.
 *  4) Notifica al comprador (in-app + email) que sus docs han sido validados.
 *
 * Devuelve HTML directo (no JSON) porque se accede desde el navegador del
 * revisor, no desde el frontend SPA.
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

function approveDebug(string $message, $data = null): void
{
    if ($data === null) {
        error_log('[approve_signed_documents] ' . $message);
        return;
    }

    error_log('[approve_signed_documents] ' . $message . ' => ' . print_r($data, true));
}

if ($pdo instanceof PDO) {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    approveDebug('PDO ERRMODE set to EXCEPTION');
}

approveDebug('START');
approveDebug('GET', $_GET);

$token = trim((string) ($_GET['token'] ?? ''));
approveDebug('TOKEN RAW', $token);

if ($token === '') {
    approveDebug('TOKEN VACIO');
    echo '<h4>Token inválido</h4><p>Falta el token de revisión.</p>';
    exit;
}

$review = fetchDocumentReviewByToken($pdo, $token);
$buyerUserRecord = null;
approveDebug('REVIEW LOOKUP RESULT', $review);

if ($review === null) {
    approveDebug('TOKEN INVALIDO');
    echo '<h4>Token inválido</h4><p>El enlace no es válido.</p>';
    exit;
}

$propertyId = (int) ($review['property_id'] ?? 0);
$buyerUserId = (int) ($review['buyer_user_id'] ?? 0);
$reviewId = (int) ($review['id'] ?? 0);

if (!empty($review['is_already_approved'])) {
    approveDebug('TOKEN YA UTILIZADO');
    echo '<h4>Enlace ya utilizado</h4><p>Este enlace ya ha sido utilizado.</p>';
    exit;
}

if (!empty($review['is_expired'])) {
    approveDebug('TOKEN CADUCADO');

    try {
        createUserNotificationRecord($pdo, [
            'user_id' => $buyerUserId,
            'title' => 'Solicitud caducada',
            'message' => 'El tiempo de espera para aprobar tus documentos ha caducado. Debes volver a subirlos.',
            'type' => 'document_expired',
            'related_request_id' => $reviewId,
        ]);
    } catch (Throwable $notificationException) {
        approveDebug('EXPIRED NOTIFICATION FAILED', [
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
            approveDebug('EXPIRED EMAIL FAILED', [
                'message' => $emailException->getMessage(),
            ]);
        }
    }

    echo '<h4>Token caducado</h4><p>El tiempo de espera para aprobar los documentos ha caducado.</p>';
    exit;
}

approveDebug('propertyId', $propertyId);
approveDebug('buyerUserId', $buyerUserId);
approveDebug('reviewId', $reviewId);

if ($propertyId <= 0 || $buyerUserId <= 0 || $reviewId <= 0) {
    approveDebug('DATOS REVIEW INVALIDOS', $review);
    echo '<h4>Error interno</h4><p>Los datos del enlace de revisión no son válidos.</p>';
    exit;
}

try {
    $pdo->beginTransaction();
    approveDebug('TRANSACTION START');

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

    approveDebug('DOCUMENT ROW BEFORE UPDATE', $documentRow);

    if (!$documentRow) {
        throw new RuntimeException('No existe registro en documentos_firmados para esta propiedad y comprador.');
    }

    $updateDocStmt = $pdo->prepare('
        UPDATE documentos_firmados
        SET validado_admin = 1,
            updated_at = NOW()
        WHERE propiedad_id = :propiedad_id
          AND user_id = :user_id
    ');
    $updateDocStmt->execute([
        'propiedad_id' => $propertyId,
        'user_id' => $buyerUserId,
    ]);

    approveDebug('UPDATE documentos_firmados OK', [
        'rowCount' => $updateDocStmt->rowCount(),
    ]);

    $userStmt = $pdo->prepare('
        SELECT 
            id,
            email,
            name,
            first_name,
            last_name
        FROM regladousers.users
        WHERE id = :id
        LIMIT 1
    ');
    $userStmt->execute(['id' => $buyerUserId]);
    $buyerUserRecord = $userStmt->fetch(PDO::FETCH_ASSOC);

    approveDebug('BUYER USER RECORD', $buyerUserRecord);

    $access = updateBuyerPropertyAccess($pdo, $propertyId, $buyerUserId, [
        'nda_approved' => 1,
        'loi_approved' => 1,
        'dossier_unlocked' => 1,
    ]);
    approveDebug('updateBuyerPropertyAccess OK', $access);

    $approverId = null;
    $reviewerEmailStored = (string) ($review['reviewer_email'] ?? '');
    if ($reviewerEmailStored !== '') {
        $approverStmt = $pdo->prepare('SELECT id FROM regladousers.users WHERE email = :email LIMIT 1');
        $approverStmt->execute(['email' => $reviewerEmailStored]);
        $approverRow = $approverStmt->fetch(PDO::FETCH_ASSOC);
        $approverId = $approverRow ? (int) $approverRow['id'] : null;
    }

    markDocumentReviewApproved($pdo, $reviewId, $approverId);
    approveDebug('markDocumentReviewApproved OK', [
        'review_id' => $reviewId,
    ]);

    auditLog($pdo, 'document.signed.approve', [
        'user_id'       => $approverId,
        'user_email'    => $reviewerEmailStored !== '' ? $reviewerEmailStored : null,
        'user_role'     => 'admin',
        'resource_type' => 'signed_document_review',
        'resource_id'   => (string) $reviewId,
        'metadata'      => ['property_id' => $propertyId, 'buyer_user_id' => $buyerUserId]
    ]);

    try {
        createUserNotificationRecord($pdo, [
            'user_id' => $buyerUserId,
            'title' => 'Documentacion aceptada',
            'message' => 'Tu solicitud ha sido aceptada. Ya puedes continuar con el siguiente paso del proceso.',
            'type' => 'document_approval',
            'related_request_id' => $reviewId,
        ]);

        approveDebug('NOTIFICATION CREATED', [
            'user_id' => $buyerUserId,
            'review_id' => $reviewId,
        ]);
    } catch (Throwable $notificationException) {
        approveDebug('NOTIFICATION CREATION FAILED', [
            'message' => $notificationException->getMessage(),
        ]);
    }

    $pdo->commit();
    approveDebug('TRANSACTION COMMIT OK');

    if (
        $buyerUserRecord &&
        !empty($buyerUserRecord['email']) &&
        filter_var($buyerUserRecord['email'], FILTER_VALIDATE_EMAIL)
    ) {
        $emailRecipient = $buyerUserRecord['email'];
        $emailSubject = 'Solicitud aceptada';
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
            sendNotificationEmail($emailRecipient, $emailSubject, $emailBody);
            approveDebug('NOTIFICATION EMAIL SENT', ['user_id' => $buyerUserId]);
        } catch (Throwable $emailException) {
            approveDebug('NOTIFICATION EMAIL FAILED', [
                'error' => $emailException->getMessage(),
            ]);
        }
    }

    echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Documentación aprobada</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #eefaf3, #e2f5ea);
        }
        .wrapper {
            padding: 40px 20px;
        }
        .card {
            max-width: 640px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 18px;
            padding: 36px 32px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.12);
            border: 1px solid #e5e7eb;
        }
        .header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #22c55e, #15803d);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 22px;
            font-weight: bold;
            margin-right: 14px;
            box-shadow: 0 8px 20px rgba(34,197,94,0.35);
        }
        h4 {
            margin: 0;
            font-size: 22px;
            color: #111827;
        }
        .subtitle {
            margin: 6px 0 20px;
            color: #6b7280;
            font-size: 14px;
        }
        .success-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 14px;
            padding: 18px;
            margin-bottom: 20px;
        }
        .ok {
            color: #15803d;
            font-weight: 700;
            font-size: 15px;
            margin: 0 0 8px;
        }
        .detail {
            font-size: 14px;
            color: #166534;
            line-height: 1.5;
        }
        .info {
            margin-top: 16px;
            padding: 14px 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 13px;
            color: #374151;
        }
        .info strong {
            color: #111827;
        }
        .footer {
            margin-top: 24px;
            font-size: 12px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <div class="icon">✓</div>
                <div>
                    <h4>Documentación aprobada</h4>
                    <p class="subtitle">Proceso completado correctamente</p>
                </div>
            </div>
            <div class="success-box">
                <p class="ok">Los documentos han sido aprobados correctamente</p>
                <p class="detail">
                    El dossier ha quedado desbloqueado y el comprador ya puede acceder a la información completa del activo.
                </p>
            </div>
            <div class="info">
                <p><strong>Propiedad:</strong> #' . htmlspecialchars((string) $propertyId, ENT_QUOTES, "UTF-8") . '</p>
                <p><strong>Comprador:</strong> #' . htmlspecialchars((string) $buyerUserId, ENT_QUOTES, "UTF-8") . '</p>
            </div>
            <div class="footer">
                Reglado Real Estate
            </div>
        </div>
    </div>
</body>
</html>';
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
        approveDebug('TRANSACTION ROLLBACK');
    }

    $errorId = logAndReferenceError('approve_signed_documents', $exception);

    approveDebug('ERROR', [
        'error_id' => $errorId,
        'message'  => $exception->getMessage(),
    ]);

    echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Error al aprobar</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #eef3f8, #e2e8f0);
        }
        .wrapper {
            padding: 40px 20px;
        }
        .card {
            max-width: 640px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 18px;
            padding: 36px 32px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.12);
            border: 1px solid #e5e7eb;
        }
        .header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #ef4444, #b91c1c);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 22px;
            font-weight: bold;
            margin-right: 14px;
            box-shadow: 0 8px 20px rgba(239,68,68,0.35);
        }
        h4 {
            margin: 0;
            font-size: 22px;
            color: #111827;
        }
        .subtitle {
            margin: 6px 0 20px;
            color: #6b7280;
            font-size: 14px;
        }
        .error-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 14px;
            padding: 18px;
            margin-bottom: 20px;
        }
        .error {
            color: #b91c1c;
            font-weight: 700;
            font-size: 15px;
            margin: 0 0 8px;
        }
        .detail {
            font-size: 13px;
            color: #7f1d1d;
            line-height: 1.5;
        }
        .footer {
            margin-top: 24px;
            font-size: 12px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <div class="icon">!</div>
                <div>
                    <h4>Error al aprobar documentación</h4>
                    <p class="subtitle">Se ha producido un error durante el proceso de validación</p>
                </div>
            </div>
            <div class="error-box">
                <p class="error">No se pudo aprobar la documentación</p>
                <p class="detail">
                    Se ha producido un error interno. Si el problema persiste, contacta con soporte indicando esta referencia: <strong>' . htmlspecialchars($errorId, ENT_QUOTES, "UTF-8") . '</strong>
                </p>
            </div>
            <div class="footer">
                Reglado Real Estate 
            </div>
        </div>
    </div>
</body>
</html>';
}