<?php
require_once __DIR__ . '/config/cors.php';
applyCors();
handlePreflight();

require_once "config/db.php";
require_once "config/auth.php";
require_once __DIR__ . "/lib/match_preferences.php";


$context = requireAuthenticatedUser($pdo);
$local = $context['local'];
$auth = $context['auth'];
$userId = (int) ($local['iduser'] ?? ($auth['sub'] ?? 0));

$matchPreferences = fetchUserMatchPreferences($pdo, $userId);

$user = [
    'id' => (int) ($auth['sub'] ?? 0),
    'iduser' => $userId,
    'nombre' => $auth['first_name'] ?? '',
    'apellidos' => $auth['last_name'] ?? '',
    'email' => $auth['email'] ?? '',
    'telefono' => $auth['phone'] ?? '',
    'nombre_usuario' => $auth['username'] ?? '',
    'categoria' => $matchPreferences['category'] ?? null,
    'rol' => $auth['role'] ?? 'user',
    'preferencias' => !empty($matchPreferences['answers']) ? $matchPreferences['answers'] : null,
    'match_preferences_last_used' => $matchPreferences['last_used_at'] ?? null,
];

respondJson(200, [
    'success' => true,
    'user' => $user,
]);
