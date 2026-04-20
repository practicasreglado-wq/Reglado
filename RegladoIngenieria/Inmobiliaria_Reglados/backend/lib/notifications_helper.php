<?php
declare(strict_types=1);

function notificationColumnExists(PDO $pdo, string $table, string $column): bool
{
    $sql = "
        SELECT COUNT(*) 
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :table
          AND COLUMN_NAME = :column
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':table' => $table,
        ':column' => $column,
    ]);

    return (int) $stmt->fetchColumn() > 0;
}

function createUserNotification(PDO $pdoInmo, array $payload): void
{
    $table = 'notifications';

    $columns = [];
    $values = [];
    $params = [];

    if (isset($payload['user_id']) && notificationColumnExists($pdoInmo, $table, 'user_id')) {
        $columns[] = 'user_id';
        $values[] = ':user_id';
        $params[':user_id'] = (int) $payload['user_id'];
    }

    if (!empty($payload['user_email'])) {
        if (notificationColumnExists($pdoInmo, $table, 'user_email')) {
            $columns[] = 'user_email';
            $values[] = ':user_email';
            $params[':user_email'] = (string) $payload['user_email'];
        } elseif (notificationColumnExists($pdoInmo, $table, 'email')) {
            $columns[] = 'email';
            $values[] = ':user_email';
            $params[':user_email'] = (string) $payload['user_email'];
        }
    }

    if (notificationColumnExists($pdoInmo, $table, 'title')) {
        $columns[] = 'title';
        $values[] = ':title';
        $params[':title'] = (string) ($payload['title'] ?? 'Notificación');
    }

    if (notificationColumnExists($pdoInmo, $table, 'message')) {
        $columns[] = 'message';
        $values[] = ':message';
        $params[':message'] = (string) ($payload['message'] ?? '');
    } elseif (notificationColumnExists($pdoInmo, $table, 'body')) {
        $columns[] = 'body';
        $values[] = ':message';
        $params[':message'] = (string) ($payload['message'] ?? '');
    }

    if (notificationColumnExists($pdoInmo, $table, 'type')) {
        $columns[] = 'type';
        $values[] = ':type';
        $params[':type'] = (string) ($payload['type'] ?? 'info');
    }

    if (
        !empty($payload['link']) &&
        notificationColumnExists($pdoInmo, $table, 'link')
    ) {
        $columns[] = 'link';
        $values[] = ':link';
        $params[':link'] = (string) $payload['link'];
    } elseif (
        !empty($payload['link']) &&
        notificationColumnExists($pdoInmo, $table, 'action_url')
    ) {
        $columns[] = 'action_url';
        $values[] = ':link';
        $params[':link'] = (string) $payload['link'];
    }

    if (notificationColumnExists($pdoInmo, $table, 'is_read')) {
        $columns[] = 'is_read';
        $values[] = '0';
    }

    if (notificationColumnExists($pdoInmo, $table, 'created_at')) {
        $columns[] = 'created_at';
        $values[] = 'NOW()';
    }

    if (notificationColumnExists($pdoInmo, $table, 'updated_at')) {
        $columns[] = 'updated_at';
        $values[] = 'NOW()';
    }

    if (empty($columns)) {
        throw new RuntimeException('No se han encontrado columnas válidas para insertar en notifications.');
    }

    $sql = sprintf(
        'INSERT INTO %s (%s) VALUES (%s)',
        $table,
        implode(', ', $columns),
        implode(', ', $values)
    );

    $stmt = $pdoInmo->prepare($sql);
    $stmt->execute($params);
}