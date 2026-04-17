<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../lib/notifications.php';

applyCors();
handlePreflight();

$context = requireAuthenticatedUser($pdo);
$userId = (int) ($context['local']['iduser'] ?? $context['auth']['sub'] ?? 0);

if ($userId <= 0) {
    respondJson(401, [
        'success' => false,
        'message' => 'Usuario no identificado.'
    ]);
}

header('Content-Type: application/json; charset=utf-8');

$unread = countUserUnreadNotifications($pdo, $userId);

respondJson(200, [
    'success' => true,
    'unread' => $unread,
]);
