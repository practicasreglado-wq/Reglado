<?php
declare(strict_types=1);

function fetchUserNotifications(PDO $pdo, int $userId, int $limit = 30, int $offset = 0): array
{
    $stmt = $pdo->prepare(
        'SELECT id, title, message, type, is_read, related_request_id, created_at
         FROM notifications
         WHERE user_id = :user_id
         ORDER BY is_read ASC, created_at DESC
         LIMIT :limit
         OFFSET :offset'
    );

    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function countUserUnreadNotifications(PDO $pdo, int $userId): int
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM notifications
         WHERE user_id = :user_id
           AND is_read = 0'
    );

    $stmt->execute([':user_id' => $userId]);

    return (int) $stmt->fetchColumn();
}

function hasUserNotificationForRequest(PDO $pdo, int $userId, string $type, ?int $relatedRequestId): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM notifications
         WHERE user_id = :user_id
           AND type = :type
           AND related_request_id <=> :related_request_id'
    );

    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':type', $type, PDO::PARAM_STR);

    if ($relatedRequestId === null) {
        $stmt->bindValue(':related_request_id', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':related_request_id', $relatedRequestId, PDO::PARAM_INT);
    }

    $stmt->execute();

    return (int) $stmt->fetchColumn() > 0;
}

function createUserNotificationRecord(PDO $pdo, array $payload): int
{
    $userId = (int) ($payload['user_id'] ?? 0);
    $title = trim((string) ($payload['title'] ?? ''));
    $message = trim((string) ($payload['message'] ?? ''));
    $type = trim((string) ($payload['type'] ?? 'info'));
    $relatedRequestId = isset($payload['related_request_id']) && $payload['related_request_id'] !== ''
        ? (int) $payload['related_request_id']
        : null;

    if ($userId <= 0 || $title === '' || $message === '') {
        throw new InvalidArgumentException('Identificador de usuario, titulo y mensaje son obligatorios.');
    }

    if ($relatedRequestId !== null && hasUserNotificationForRequest($pdo, $userId, $type, $relatedRequestId)) {
        return 0;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO notifications (user_id, title, message, type, related_request_id, is_read, created_at, updated_at)
         VALUES (:user_id, :title, :message, :type, :related_request_id, 0, NOW(), NOW())'
    );

    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':title', $title, PDO::PARAM_STR);
    $stmt->bindValue(':message', $message, PDO::PARAM_STR);
    $stmt->bindValue(':type', $type, PDO::PARAM_STR);

    if ($relatedRequestId === null) {
        $stmt->bindValue(':related_request_id', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':related_request_id', $relatedRequestId, PDO::PARAM_INT);
    }

    $stmt->execute();

    return (int) $pdo->lastInsertId();
}

function markUserNotificationAsRead(PDO $pdo, int $userId, int $notificationId): bool
{
    $stmt = $pdo->prepare(
        'UPDATE notifications
         SET is_read = 1,
             updated_at = NOW()
         WHERE id = :id
           AND user_id = :user_id
           AND is_read = 0'
    );

    $stmt->execute([
        ':id' => $notificationId,
        ':user_id' => $userId,
    ]);

    return $stmt->rowCount() > 0;
}

function createNotification(PDO $pdo, int $userId, array $data): int
{
    return createUserNotificationRecord($pdo, [
        'user_id' => $userId,
        'title' => (string) ($data['title'] ?? 'Notificación'),
        'message' => (string) ($data['message'] ?? ''),
        'type' => (string) ($data['type'] ?? 'info'),
        'related_request_id' => $data['related_request_id'] ?? null,
    ]);
}

