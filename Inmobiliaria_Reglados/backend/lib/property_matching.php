<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/auth.php';

function decodeJsonArray(?string $json): array
{
    if (!is_string($json) || trim($json) === '') {
        return [];
    }

    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : [];
}

function calculatePropertyMatch(array $preferences, array $characteristics): array
{
    $normalizedPreferences = [];

    foreach ($preferences as $key => $value) {
        if (is_string($value) && trim($value) !== '') {
            $normalizedPreferences[(string) $key] = trim($value);
        }
    }

    if ($normalizedPreferences === []) {
        return [
            'percentage' => 0,
            'matches' => 0,
            'total' => 0,
        ];
    }

    $matches = 0;

    foreach ($normalizedPreferences as $key => $preferenceValue) {
        $propertyValue = $characteristics[$key] ?? null;
        if (!is_string($propertyValue)) {
            continue;
        }

        if (mb_strtolower(trim($propertyValue)) === mb_strtolower($preferenceValue)) {
            $matches++;
        }
    }

    $total = count($normalizedPreferences);

    return [
        'percentage' => (int) round(($matches / max($total, 1)) * 100),
        'matches' => $matches,
        'total' => $total,
    ];
}

function propertyImageUrl(?string $imageName): ?string
{
    if (!is_string($imageName) || trim($imageName) === '') {
        return null;
    }

    $origin = $_SERVER['HTTP_ORIGIN'] ?? 'http://localhost:5173';
    return rtrim($origin, '/') . '/src/assets/' . ltrim($imageName, '/');
}
