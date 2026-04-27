<?php

declare(strict_types=1);

/**
 * Utilidades generales para la aplicación Reglado.
 * (Funciones extraídas tras la eliminación del sistema de matching)
 */

/**
 * Decodifica una cadena JSON garantizando que el resultado es siempre un
 * array. Si la entrada es null/vacía/JSON inválido devuelve [] en vez de
 * propagar un error — útil para deserializar columnas TEXT que pueden venir
 * sucias desde versiones antiguas del esquema.
 */
function decodeJsonArray(?string $json): array
{
    if (!is_string($json) || trim($json) === '') {
        return [];
    }

    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : [];
}

/**
 * Genera la URL absoluta para una imagen de propiedad.
 */
function propertyImageUrl(?string $imageName): ?string
{
    if (!is_string($imageName) || trim($imageName) === '') {
        return null;
    }

    // Ajustamos para que funcione con el servidor de desarrollo o producción
    // En XAMPP suele ser relativo a la raíz del htdocs o mediante el origin del frontend
    $origin = $_SERVER['HTTP_ORIGIN'] ?? 'http://localhost:5175';
    return rtrim($origin, '/') . '/src/assets/' . ltrim($imageName, '/');
}
