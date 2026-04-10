<?php
declare(strict_types=1);

function getCorsAllowedOrigins(): array
{
    $defaults = [
        'http://localhost:5175',
        'http://127.0.0.1:5175',
    ];

    $envRaw = getenv('CORS_ALLOWED_ORIGINS') ?: '';
    $extras = array_filter(array_map('trim', explode(',', $envRaw)), function ($value) {
        return $value !== '';
    });

    return array_values(array_unique(array_merge($defaults, $extras)));
}

function applyCors(): void
{
    $allowedOrigins = getCorsAllowedOrigins();
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if ($origin && in_array($origin, $allowedOrigins, true)) {
        header("Access-Control-Allow-Origin: $origin");
        header('Vary: Origin');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    }
}

function handlePreflight(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
