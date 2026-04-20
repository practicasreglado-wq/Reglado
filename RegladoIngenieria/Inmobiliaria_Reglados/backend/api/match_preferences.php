<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/cors.php';
applyCors();
handlePreflight();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../lib/match_preferences.php';

$context = requireAuthenticatedUser($pdo);
$userId = (int) ($context['local']['iduser'] ?? 0);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    switch ($method) {
        case 'GET':
            $preferences = fetchUserMatchPreferences($pdo, $userId);
            respondJson(200, [
                'success' => true,
                'preferences' => [
                    'category' => $preferences['category'] ?? null,
                    'answers' => $preferences['answers'] ?? [],
                    'last_used_at' => $preferences['last_used_at'] ?? null,
                    'is_active' => $preferences['is_active'] ?? false,
                ],
            ]);
            break;

        case 'POST':
            $payload = json_decode(file_get_contents('php://input'), true) ?: [];
            $category =
                $payload['category'] ??
                $payload['categoria'] ??
                null;

            if (!is_string($category) || trim($category) === '') {
                respondJson(422, [
                    'success' => false,
                    'message' => 'La categoría es obligatoria.',
                ]);
            }

            $answers = $payload['answers'] ?? $payload['preferencias'] ?? [];
            if (!is_array($answers)) {
                $answers = [];
            }

            $saved = upsertUserMatchPreferences($pdo, $userId, $category, $answers);

            respondJson(200, [
                'success' => true,
                'preferences' => [
                    'category' => $saved['category'] ?? null,
                    'answers' => $saved['answers'] ?? [],
                    'last_used_at' => $saved['last_used_at'] ?? null,
                    'is_active' => $saved['is_active'] ?? false,
                ],
            ]);
            break;

        case 'DELETE':
            deactivateUserMatchPreferences($pdo, $userId);
            respondJson(200, [
                'success' => true,
                'message' => 'Preferencias de matching reiniciadas.',
            ]);
            break;

        default:
            respondJson(405, [
                'success' => false,
                'message' => "Método {$method} no permitido.",
            ]);
            break;
    }
} catch (InvalidArgumentException $exception) {
    respondJson(422, [
        'success' => false,
        'message' => $exception->getMessage(),
    ]);
}
