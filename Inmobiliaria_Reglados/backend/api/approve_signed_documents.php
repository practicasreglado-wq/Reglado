<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/env_loader.php';
require_once __DIR__ . '/../lib/document_access.php';
require_once __DIR__ . '/../lib/document_review.php';
require_once __DIR__ . '/../config/cors.php';

applyCors();
handlePreflight();
loadEnv(__DIR__ . '/../.env');

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
approveDebug('REVIEW LOOKUP RESULT', $review);

if ($review === null) {
    approveDebug('TOKEN INVALIDO O CADUCADO');
    echo '<h4>Token inválido o caducado</h4><p>El enlace ya no es válido o ya fue utilizado.</p>';
    exit;
}

$propertyId = (int) ($review['property_id'] ?? 0);
$buyerUserId = (int) ($review['buyer_user_id'] ?? 0);
$reviewId = (int) ($review['id'] ?? 0);

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

    $verifyDocStmt = $pdo->prepare('
        SELECT *
        FROM documentos_firmados
        WHERE propiedad_id = :propiedad_id
          AND user_id = :user_id
        LIMIT 1
    ');
    $verifyDocStmt->execute([
        'propiedad_id' => $propertyId,
        'user_id' => $buyerUserId,
    ]);
    $updatedDocumentRow = $verifyDocStmt->fetch(PDO::FETCH_ASSOC);

    approveDebug('DOCUMENT ROW AFTER UPDATE', $updatedDocumentRow);

    approveDebug('ANTES DE updateBuyerPropertyAccess');
    $access = updateBuyerPropertyAccess($pdo, $propertyId, $buyerUserId, [
        'nda_approved' => 1,
        'loi_approved' => 1,
        'dossier_unlocked' => 1,
    ]);
    approveDebug('updateBuyerPropertyAccess OK', $access);

    approveDebug('ANTES DE markDocumentReviewApproved');
    markDocumentReviewApproved($pdo, $reviewId, 0);
    approveDebug('markDocumentReviewApproved OK', [
        'review_id' => $reviewId,
    ]);

    $verifyReviewStmt = $pdo->prepare('
        SELECT *
        FROM signed_document_review_tokens
        WHERE id = :id
        LIMIT 1
    ');
    $verifyReviewStmt->execute([
        'id' => $reviewId,
    ]);
    $reviewAfterApproval = $verifyReviewStmt->fetch(PDO::FETCH_ASSOC);

    approveDebug('TOKEN ROW AFTER APPROVAL', $reviewAfterApproval);

    $pdo->commit();
    approveDebug('TRANSACTION COMMIT OK');

    echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Documentación aprobada</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #1f2937;
            padding: 40px;
        }
        .card {
            max-width: 640px;
            margin: 40px auto;
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.08);
        }
        h4 {
            margin-top: 0;
            color: #0f172a;
            font-size: 24px;
        }
        p {
            line-height: 1.5;
        }
        .ok {
            color: #15803d;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="card">
        <h4>Documentación aprobada</h4>
        <p class="ok">Los documentos han sido aprobados correctamente.</p>
        <p>El dossier ha quedado desbloqueado para el comprador.</p>
        <p>Propiedad: #' . htmlspecialchars((string) $propertyId, ENT_QUOTES, 'UTF-8') . '</p>
        <p>Comprador: #' . htmlspecialchars((string) $buyerUserId, ENT_QUOTES, 'UTF-8') . '</p>
    </div>
</body>
</html>';
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
        approveDebug('TRANSACTION ROLLBACK');
    }

    approveDebug('ERROR', [
        'message' => $exception->getMessage(),
        'trace' => $exception->getTraceAsString(),
    ]);

    echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Error al aprobar</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #1f2937;
            padding: 40px;
        }
        .card {
            max-width: 640px;
            margin: 40px auto;
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.08);
        }
        h4 {
            margin-top: 0;
            color: #991b1b;
            font-size: 24px;
        }
        p {
            line-height: 1.5;
        }
        .error {
            color: #b91c1c;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="card">
        <h4>Error interno</h4>
        <p class="error">No se pudo aprobar la documentación.</p>
        <p>Detalle técnico: ' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>
    </div>
</body>
</html>';
}