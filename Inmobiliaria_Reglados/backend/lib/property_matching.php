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

function calculatePropertyMatch(array $preferences, array $features): array
{
    $details = [];
    $matches = 0;

    foreach ($preferences as $key => $value) {

        $match = false;

        if (isset($features[$key])) {

            if (is_array($value)) {
                $match = in_array($features[$key], $value);
            } else {
                $match = $features[$key] == $value;
            }

        }

        if ($match) {
            $matches++;
        }

        $details[] = [
    "label" => is_string($value) ? $value : ucfirst(str_replace("_"," ",$key)),
    "match" => $match
];
    }

    $total = count($preferences);

    $percentage = $total > 0
        ? round(($matches / $total) * 100)
        : 0;

    return [
        "percentage" => $percentage,
        "matches" => $matches,
        "total" => $total,
        "details" => $details
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
