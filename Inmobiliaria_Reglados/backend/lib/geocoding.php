<?php
declare(strict_types=1);

function normalizeGeoText(mixed $value): string
{
    $text = trim((string) ($value ?? ''));

    if ($text === '') {
        return '';
    }

    $lower = mb_strtolower($text, 'UTF-8');

    if (in_array($lower, ['null', 'undefined', 'n/a', '-', 'sin definir'], true)) {
        return '';
    }

    return $text;
}

function addUniquePart(array &$parts, string $value): void
{
    $value = trim($value);

    if ($value === '') {
        return;
    }

    $normalized = mb_strtolower($value, 'UTF-8');

    foreach ($parts as $existing) {
        if (mb_strtolower((string) $existing, 'UTF-8') === $normalized) {
            return;
        }
    }

    $parts[] = $value;
}

function buildGeocodingQueries(array $property): array
{
    $direccionCompleta = normalizeGeoText($property['direccion_completa'] ?? '');
    $direccion = normalizeGeoText($property['direccion'] ?? '');
    $codigoPostal = normalizeGeoText($property['codigo_postal'] ?? $property['cp'] ?? '');
    $ciudad = normalizeGeoText($property['ciudad'] ?? '');
    $provincia = normalizeGeoText($property['provincia'] ?? '');
    $pais = normalizeGeoText($property['pais'] ?? 'España');

    $queries = [];

    $build = static function (array $segments): string {
        $parts = [];
        foreach ($segments as $segment) {
            addUniquePart($parts, $segment);
        }
        return implode(', ', $parts);
    };

    if ($direccionCompleta !== '') {
        $queries[] = $build([$direccionCompleta, $pais]);
    }

    if ($direccion !== '' && $codigoPostal !== '' && $ciudad !== '') {
        $queries[] = $build([$direccion, $codigoPostal, $ciudad, $pais]);
    }

    if ($direccion !== '' && $ciudad !== '') {
        $queries[] = $build([$direccion, $ciudad, $pais]);
    }

    if ($direccion !== '' && $provincia !== '' && $ciudad !== '') {
        $queries[] = $build([$direccion, $ciudad, $provincia, $pais]);
    }

    if ($codigoPostal !== '' && $ciudad !== '') {
        $queries[] = $build([$codigoPostal, $ciudad, $pais]);
    }

    if ($ciudad !== '') {
        $queries[] = $build([$ciudad, $provincia, $pais]);
    }

    $queries = array_values(array_unique(array_filter($queries, static fn($q) => trim($q) !== '')));

    return $queries;
}

function callNominatim(string $query): ?array
{
    $params = [
        'format' => 'jsonv2',
        'limit' => 1,
        'q' => $query,
        'countrycodes' => 'es',
        'accept-language' => 'es',
        'addressdetails' => 1,
    ];

    $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query(
        $params,
        '',
        '&',
        PHP_QUERY_RFC3986
    );

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 12,
            'header' => implode("\r\n", [
                'User-Agent: RegladoRealEstate/1.0 (geocoding)',
                'Accept: application/json',
                'Accept-Language: es',
            ]),
        ],
    ]);

    error_log('[GEOCODING URL] ' . $url);
    error_log('[GEOCODING QUERY FINAL] ' . $query);

    $response = @file_get_contents($url, false, $context);

    if ($response === false || $response === '') {
        error_log('[GEOCODING RESPONSE RAW] false_or_empty');
        return null;
    }

    error_log('[GEOCODING RESPONSE RAW] ' . $response);

    $data = json_decode($response, true);

    if (!is_array($data) || empty($data) || !isset($data[0])) {
        return null;
    }

    $first = $data[0];

    $lat = isset($first['lat']) ? (float) $first['lat'] : null;
    $lon = isset($first['lon']) ? (float) $first['lon'] : null;

    if (!is_float($lat) || !is_float($lon)) {
        return null;
    }

    if (!is_finite($lat) || !is_finite($lon)) {
        return null;
    }

    return [
        'latitud' => $lat,
        'longitud' => $lon,
        'query' => $query,
        'display_name' => (string) ($first['display_name'] ?? ''),
    ];
}

function geocodeApproximateLocation(array $property): ?array
{
    $queries = buildGeocodingQueries($property);

    if ($queries === []) {
        error_log('[GEOCODING] No hay queries válidas');
        return null;
    }

    foreach ($queries as $query) {
        $result = callNominatim($query);
        if ($result !== null) {
            return $result;
        }
    }

    return null;
}