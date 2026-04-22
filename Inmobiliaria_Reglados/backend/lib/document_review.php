<?php
declare(strict_types=1);

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