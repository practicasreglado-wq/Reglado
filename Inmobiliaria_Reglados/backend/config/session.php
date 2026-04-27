<?php

// Auto-detecta si la petición llegó por HTTPS (incluye el caso de reverse
// proxy via X-Forwarded-Proto, que es como operan Hostinger / Cloudflare).
$autoHttps = (
    (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') ||
    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
    (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
);

// Override explícito: en producción define SESSION_COOKIE_SECURE=true en el
// .env para forzar Secure aunque la auto-detección falle (p. ej. hosts que no
// propagan X-Forwarded-Proto correctamente).
$envOverride = strtolower((string) getenv('SESSION_COOKIE_SECURE'));
$forceSecure = $envOverride === 'true' || $envOverride === '1';

$useSecure = $forceSecure || $autoHttps;

ini_set('session.cookie_samesite', 'Strict');
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', $useSecure ? 1 : 0);

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => $useSecure,
    'httponly' => true,
    'samesite' => 'Strict',
]);

session_start();
