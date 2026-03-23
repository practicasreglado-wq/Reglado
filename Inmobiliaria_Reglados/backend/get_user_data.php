<?php
require_once __DIR__ . '/config/cors.php';
applyCors();
handlePreflight();

require_once "config/db.php";
require_once "config/auth.php";



$context = requireAuthenticatedUser($pdo);
$local = $context['local'];
$auth = $context['auth'];

$user = [
    'id' => (int) ($auth['sub'] ?? 0),
    'iduser' => (int) ($local['iduser'] ?? ($auth['sub'] ?? 0)),
    'nombre' => $auth['first_name'] ?? '',
    'apellidos' => $auth['last_name'] ?? '',
    'email' => $auth['email'] ?? '',
    'telefono' => $auth['phone'] ?? '',
    'nombre_usuario' => $auth['username'] ?? '',
    'categoria' => $local['categoria'] ?? null,
    'rol' => $auth['role'] ?? 'user',
    'preferencias' => !empty($local['preferencias']) ? json_decode((string) $local['preferencias'], true) : null,
];

respondJson(200, [
    'success' => true,
    'user' => $user,
]);
