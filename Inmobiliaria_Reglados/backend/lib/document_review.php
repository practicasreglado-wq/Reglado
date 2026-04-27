<?php
declare(strict_types=1);

/**
 * Tokens "magic link" para que el revisor (admin/legal) apruebe o rechace
 * los documentos firmados desde su correo, sin tener que loguearse.
 *
 * Flujo:
 *  1) Comprador sube docs firmados → upload_signed_documents.php genera un
 *     token con createDocumentReviewToken() y lo manda por email al revisor.
 *  2) Revisor hace click en "Aprobar" o "Rechazar" → llega a
 *     approve_signed_documents.php / reject_signed_documents.php?token=…
 *  3) El endpoint llama a fetchDocumentReviewByToken() para validar y luego
 *     a markDocumentReview{Approved,Rejected}().
 *
 * Seguridad: el token plano va por correo pero en BD solo se guarda su
 * SHA-256 — si la BD se filtra, los enlaces ya enviados no son utilizables.
 *
 * Cron cron/notify_expired_review_tokens.php se encarga de avisar al usuario
 * cuando un token caduca sin haber sido usado (default: 7 días).
 */

/**
 * Crea un nuevo token de revisión y devuelve el valor en claro (que se mete
 * en el enlace del correo). En BD solo queda el hash. Caduca en 7 días por
 * defecto, configurable con $expiresAt.
 */
function createDocumentReviewToken(PDO $pdo, int $propertyId, int $buyerUserId, string $reviewerEmail = '', ?DateTimeImmutable $expiresAt = null): string
{
    $token = bin2hex(random_bytes(24));
    $hash = hash('sha256', $token);
    $expires = $expiresAt ?? (new DateTimeImmutable('+7 days'));

    $stmt = $pdo->prepare('
        INSERT INTO signed_document_review_tokens (
            property_id,
            buyer_user_id,
            reviewer_email,
            token_hash,
            expires_at
        ) VALUES (
            :property_id,
            :buyer_user_id,
            :reviewer_email,
            :token_hash,
            :expires_at
        )
    ');
    $stmt->execute([
        'property_id'    => $propertyId,
        'buyer_user_id'  => $buyerUserId,
        'reviewer_email' => $reviewerEmail !== '' ? $reviewerEmail : null,
        'token_hash'     => $hash,
        'expires_at'     => $expires->format('Y-m-d H:i:s'),
    ]);

    return $token;
}

/**
 * Busca un token (vía hash, no en claro) y lo devuelve enriquecido con dos
 * flags útiles para el endpoint que lo recibe:
 *   - is_expired         → expires_at < ahora.
 *   - is_already_approved → approved_at no es nulo (para evitar doble click).
 *
 * Devuelve null si no se encuentra el token (no existe o nunca existió).
 */
function fetchDocumentReviewByToken(PDO $pdo, string $token): ?array
{
    $hash = hash('sha256', $token);

    $stmt = $pdo->prepare('
        SELECT *
        FROM signed_document_review_tokens
        WHERE token_hash = :hash
        LIMIT 1
    ');
    $stmt->execute(['hash' => $hash]);

    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        return null;
    }

    $record['is_expired'] = false;
    $record['is_already_approved'] = !empty($record['approved_at']);

    $expiresAt = isset($record['expires_at']) ? strtotime((string) $record['expires_at']) : null;
    if ($expiresAt !== null && $expiresAt < time()) {
        $record['is_expired'] = true;
    }

    return $record;
}

/**
 * Marca el token como aprobado (sella approved_at + approved_by). Tras esto
 * el endpoint de aprobación procede a cambiar el estado de la propiedad y a
 * notificar al comprador.
 */
function markDocumentReviewApproved(PDO $pdo, int $tokenId, ?int $approverId = null): void
{
    $stmt = $pdo->prepare('
        UPDATE signed_document_review_tokens
        SET approved_at = CURRENT_TIMESTAMP,
            approved_by = :approver
        WHERE id = :id
    ');
    $stmt->execute([
        'approver' => $approverId,
        'id' => $tokenId,
    ]);
}

/**
 * Marca el token como rechazado. Reusa la columna `approved_at` para sellar
 * la fecha de decisión — el flag de "rechazo vs aprobación" se distingue en
 * otra parte del flujo (tabla documentos_firmados o estado de propiedad).
 */
function markDocumentReviewRejected(PDO $pdo, int $tokenId, ?int $reviewerId = null): void
{
    $stmt = $pdo->prepare('
        UPDATE signed_document_review_tokens
        SET approved_at = CURRENT_TIMESTAMP,
            approved_by = :reviewer
        WHERE id = :id
    ');
    $stmt->execute([
        'reviewer' => $reviewerId,
        'id' => $tokenId,
    ]);
}

/**
 * Construye la URL absoluta del enlace "Aprobar" del correo. Usa
 * BACKEND_APPROVAL_URL del .env si está, si no la deriva del Host de la
 * petición actual (útil en local; en producción es importante setearla en el
 * .env porque la generación del correo puede ocurrir desde un cron sin Host).
 */
function buildReviewApprovalLink(string $token, string $reviewerEmail = ''): string
{
    $base = getenv('BACKEND_APPROVAL_URL');
    if (!is_string($base) || trim($base) === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = "{$scheme}://{$host}/Reglado/Inmobiliaria_Reglados/backend";
    }

    return rtrim($base, '/') . '/api/approve_signed_documents.php?token=' . rawurlencode($token);
}

/**
 * Igual que buildReviewApprovalLink pero apunta al endpoint de rechazo.
 */
function buildReviewRejectLink(string $token, string $reviewerEmail = ''): string
{
    $base = getenv('BACKEND_APPROVAL_URL');
    if (!is_string($base) || trim($base) === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = "{$scheme}://{$host}/Reglado/Inmobiliaria_Reglados/backend";
    }

    return rtrim($base, '/') . '/api/reject_signed_documents.php?token=' . rawurlencode($token);
}