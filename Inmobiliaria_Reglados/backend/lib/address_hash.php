<?php
declare(strict_types=1);

/**
 * Construye un hash determinista y normalizado de una dirección para
 * deduplicar propiedades a nivel de base de datos.
 *
 * Se devuelve SHA-256 de la concatenación de las partes normalizadas. El
 * hash es estable ante: mayúsculas, espacios repetidos, puntuación común
 * (comas, puntos, guiones), y acentos.
 *
 * Devuelve null si no hay información suficiente para dedup fiable (p. ej.
 * solo la ciudad). En ese caso NO se bloquea la inserción: la columna
 * address_hash queda a NULL y MySQL permite múltiples NULLs en el UNIQUE.
 *
 * Reglas mínimas: se necesita al menos
 *   - direccion (street-level) + ciudad   → dedup fino
 *   - O bien ubicacion + ciudad           → dedup medio
 *   - O bien codigo_postal + ciudad       → dedup por zona postal
 */
function buildPropertyAddressHash(array $parts): ?string
{
    $direccion     = normalizeAddressPart($parts['direccion']       ?? '');
    $ubicacion     = normalizeAddressPart($parts['ubicacion']       ?? '');
    $ciudad        = normalizeAddressPart($parts['ciudad']          ?? '');
    $provincia     = normalizeAddressPart($parts['provincia']       ?? '');
    $pais          = normalizeAddressPart($parts['pais']            ?? '');
    $codigoPostal  = normalizeAddressPart($parts['codigo_postal']   ?? '');

    $streetLevel = $direccion !== '' ? $direccion : $ubicacion;

    if ($ciudad === '') {
        return null;
    }

    if ($streetLevel === '' && $codigoPostal === '') {
        return null;
    }

    $signature = implode('|', [
        $streetLevel,
        $codigoPostal,
        $ciudad,
        $provincia,
        $pais,
    ]);

    return hash('sha256', $signature);
}

function normalizeAddressPart(mixed $value): string
{
    $s = (string) ($value ?? '');
    if ($s === '') {
        return '';
    }

    $s = mb_strtolower($s, 'UTF-8');

    $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    if (is_string($transliterated) && $transliterated !== '') {
        $s = $transliterated;
    }

    $s = (string) preg_replace('/[^a-z0-9\s]/i', ' ', $s);
    $s = (string) preg_replace('/\s+/', ' ', $s);

    return trim($s);
}
