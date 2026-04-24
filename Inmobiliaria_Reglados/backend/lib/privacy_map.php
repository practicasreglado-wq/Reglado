<?php
declare(strict_types=1);

/**
 * Devuelve coordenadas "aproximadas" para mostrar en el mapa público de la web.
 *
 * Las coordenadas reales de Nominatim viven en la BD pero NUNCA se exponen al
 * cliente; el front consume estas coordenadas desplazadas para que nadie pueda
 * deducir la ubicación exacta del activo hasta firmar el NDA/LOI.
 *
 * El desplazamiento es **determinista por id de propiedad** (la misma
 * propiedad siempre aparece en el mismo punto del mapa en cada recarga), pero
 * distribuido ampliamente en distancia y ángulo para que sea prácticamente
 * imposible detectar patrones desde fuera:
 *
 *   - Distancia: 50–300 metros (rango de 250 m — ancho suficiente para que
 *     cada propiedad caiga en una manzana distinta).
 *   - Ángulo: 0–359° (cualquier dirección del compás).
 *   - Dos mitades independientes de un HMAC-SHA256 del id alimentan distancia
 *     y ángulo por separado, evitando que estén correlacionados.
 *
 * La clave del HMAC vive en el servidor (`PRIVACY_MAP_SECRET`, con fallback a
 * `JWT_SECRET`) y no se expone al cliente. Esto hace que el offset NO sea
 * reversible aunque un atacante conozca el algoritmo completo: sin la clave
 * no puede reproducir ni invertir el desplazamiento.
 *
 * Si no hay ninguna clave configurada, se devuelve null en las coordenadas —
 * fail-closed: preferible no mostrar mapa a exponer un desplazamiento
 * trivialmente reversible.
 */
function buildPrivacyMapCoordinates(array $row): array
{
    $lat = isset($row['latitud']) ? (float) $row['latitud'] : null;
    $lon = isset($row['longitud']) ? (float) $row['longitud'] : null;

    if (!is_float($lat) || !is_float($lon)) {
        return ['map_latitud' => null, 'map_longitud' => null];
    }

    if (!is_finite($lat) || !is_finite($lon)) {
        return ['map_latitud' => null, 'map_longitud' => null];
    }

    // Coordenadas vacías o 0,0 (null tras geocoding fallido): no mostrar nada.
    if (abs($lat) < 0.000001 || abs($lon) < 0.000001) {
        return ['map_latitud' => null, 'map_longitud' => null];
    }

    $secret = (string) ($_ENV['PRIVACY_MAP_SECRET'] ?? $_ENV['JWT_SECRET'] ?? '');
    if ($secret === '') {
        error_log('[privacy_map] PRIVACY_MAP_SECRET y JWT_SECRET vacíos — no se exponen coordenadas.');
        return ['map_latitud' => null, 'map_longitud' => null];
    }

    $seed = (string) ($row['id'] ?? '');
    $hash = hash_hmac('sha256', $seed, $secret);

    // Primeros 32 bits del HMAC → distancia entre 50 y 300 metros.
    $distancePart = hexdec(substr($hash, 0, 8));
    $offsetMeters = 50 + ($distancePart % 251);

    // Siguientes 32 bits del HMAC → ángulo entre 0 y 359 grados.
    $anglePart = hexdec(substr($hash, 8, 8));
    $angleDeg = $anglePart % 360;
    $angleRad = deg2rad((float) $angleDeg);

    $earthRadius = 6378137.0; // metros

    $dLat = ($offsetMeters * cos($angleRad)) / $earthRadius * (180 / M_PI);
    $dLon = ($offsetMeters * sin($angleRad)) / ($earthRadius * cos(deg2rad($lat))) * (180 / M_PI);

    return [
        'map_latitud'  => round($lat + $dLat, 7),
        'map_longitud' => round($lon + $dLon, 7),
    ];
}
