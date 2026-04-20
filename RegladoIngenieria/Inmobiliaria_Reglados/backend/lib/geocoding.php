<?php
declare(strict_types=1);

function normalizeGeoText($value): string
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

function cityLooksGenericForGeo(string $city): bool
{
    $city = mb_strtolower(trim($city), 'UTF-8');

    return in_array($city, [
        'tenerife',
        'gran canaria',
        'fuerteventura',
        'lanzarote',
        'la palma',
        'la gomera',
        'el hierro',
        'ibiza',
        'mallorca',
        'menorca',
        'canarias',
        'islas canarias',
        'islas baleares',
    ], true);
}

function addLocationSegment(array &$parts, string $value): void
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

function buildStreetLine(array $property): string
{
    $direccionCompleta = normalizeGeoText($property['direccion_completa'] ?? '');
    if ($direccionCompleta !== '') {
        return $direccionCompleta;
    }

    $direccion = normalizeGeoText($property['direccion'] ?? '');
    if ($direccion !== '') {
        return $direccion;
    }

    $calle = normalizeGeoText($property['calle'] ?? '');
    $numero = normalizeGeoText($property['numero'] ?? '');

    $street = trim($calle . ' ' . $numero);

    return $street;
}

function buildApproximateGeocodingQuery(array $property): string
{
    $direccion = buildStreetLine($property);
    $codigoPostal = normalizeGeoText($property['codigo_postal'] ?? $property['cp'] ?? '');
    $zona = normalizeGeoText($property['zona'] ?? '');
    $ciudad = normalizeGeoText($property['ciudad'] ?? '');
    $provincia = normalizeGeoText($property['provincia'] ?? '');
    $pais = normalizeGeoText($property['pais'] ?? 'España');

    $parts = [];
    $genericCity = $ciudad !== '' && cityLooksGenericForGeo($ciudad);

    if ($direccion !== '') {
        addLocationSegment($parts, $direccion);
    }

    if ($codigoPostal !== '') {
        addLocationSegment($parts, $codigoPostal);
    }

    if ($zona !== '') {
        addLocationSegment($parts, $zona);
    }

    if ($genericCity) {
        if ($provincia !== '') {
            addLocationSegment($parts, $provincia);
        } elseif ($ciudad !== '') {
            addLocationSegment($parts, $ciudad);
        }
    } else {
        if ($ciudad !== '') {
            addLocationSegment($parts, $ciudad);
        }

        if ($provincia !== '') {
            addLocationSegment($parts, $provincia);
        }
    }

    if ($parts === []) {
        if ($ciudad !== '') {
            addLocationSegment($parts, $ciudad);
        }

        if ($provincia !== '') {
            addLocationSegment($parts, $provincia);
        }

        if ($zona !== '') {
            addLocationSegment($parts, $zona);
        }
    }

    if ($parts === []) {
        return '';
    }

    addLocationSegment($parts, $pais !== '' ? $pais : 'España');

    return implode(', ', array_values(array_filter(
        $parts,
        static fn($item) => trim((string) $item) !== ''
    )));
}

function geocodeApproximateLocation(array $property): ?array
{
    $query = buildApproximateGeocodingQuery($property);

    if ($query === '') {
        return null;
    }

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

    $response = @file_get_contents($url, false, $context);

    if ($response === false || $response === '') {
        return null;
    }

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