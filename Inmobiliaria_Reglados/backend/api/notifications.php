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

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

switch ($method) {
    case 'GET':
        $limit = (int) ($_GET['limit'] ?? 30);
        $offset = (int) ($_GET['offset'] ?? 0);

        if ($limit < 1) {
            $limit = 30;
        }

        $limit = min(100, $limit);
        $offset = max(0, $offset);

        $notifications = fetchUserNotifications($pdo, $userId, $limit, $offset);
        $unread = countUserUnreadNotifications($pdo, $userId);

        respondJson(200, [
            'success' => true,
            'notifications' => $notifications,
            'unread' => $unread,
        ]);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input') ?: '[]', true);

        if (!is_array($input)) {
            respondJson(400, [
                'success' => false,
                'message' => 'Payload incorrecto.'
            ]);
        }

        $action = $input['action'] ?? '';

        if ($action === 'mark_read') {
            $notificationId = (int) ($input['notification_id'] ?? 0);

            if ($notificationId <= 0) {
                respondJson(422, [
                    'success' => false,
                    'message' => 'Identificador de notificación inválido.'
                ]);
            }

            $updated = markUserNotificationAsRead($pdo, $userId, $notificationId);
            $unread = countUserUnreadNotifications($pdo, $userId);

            respondJson(200, [
                'success' => true,
                'updated' => $updated,
                'unread' => $unread,
            ]);
        }

        respondJson(400, [
            'success' => false,
            'message' => 'Acción no válida.'
        ]);
        break;

    default:
        respondJson(405, [
            'success' => false,
            'message' => 'Método no permitido.'
        ]);
}
