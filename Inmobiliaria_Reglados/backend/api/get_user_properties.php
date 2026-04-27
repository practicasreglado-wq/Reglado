<?php
declare(strict_types=1);

/**
 * Devuelve las propiedades de las que el usuario autenticado es DUEÑO
 * (filtrado por owner_user_id == auth.sub). Lo usa la vista "Mis propiedades"
 * del perfil del usuario.
 *
 * Incluye coordenadas desplazadas (lib/privacy_map.php) por consistencia
 * con get_properties.php, aunque el dueño podría ver las reales.
 */

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../lib/privacy_map.php';

applyCors();
handlePreflight();

$context = requireAuthenticatedUser($pdo);
$userId = (int) (
    $context['local']['id']
    ?? $context['local']['iduser']
    ?? $context['auth']['sub']
    ?? 0
);

if ($userId <= 0) {
    respondJson(401, [
        'success' => false,
        'message' => 'Debes iniciar sesión'
    ]);
}

$stmt = $pdo->prepare('
    SELECT
        p.*,
        pf.created_at AS favorited_at
    FROM propiedades p
    LEFT JOIN propiedades_favoritas pf
        ON pf.propiedad_id = p.id
        AND pf.user_id = :user_id
    WHERE p.owner_user_id = :user_id
    ORDER BY p.created_at DESC, p.id DESC
');

$stmt->execute([
    'user_id' => $userId,
]);

$properties = [];

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $properties[] = hydrateMyPropertyCard($row);
}

respondJson(200, [
    'success' => true,
    'properties' => $properties,
]);

function hydrateMyPropertyCard(array $row): array
{
    $privacyMap = buildPrivacyMapCoordinates($row);

    $caracteristicasRaw = !empty($row['caracteristicas_json'])
        ? (json_decode((string) $row['caracteristicas_json'], true) ?: [])
        : [];

    $caracteristicas = [
        'uso_principal' => $caracteristicasRaw['uso_principal'] ?? '',
        'uso_alternativo' => $caracteristicasRaw['uso_alternativo'] ?? '',
        'propiedad_tipo' => $caracteristicasRaw['propiedad_tipo'] ?? '',
        'superficie_construida' => $caracteristicasRaw['superficie_construida'] ?? null,
    ];

    return [
        'id' => (int) $row['id'],
        'categoria' => $row['categoria'] ?? '',
        'estado' => !empty($row['estado']) ? (string) $row['estado'] : 'disponible',
        'titulo' => $row['titulo'] ?? $row['nombre'] ?? '',
        'ubicacion_general' => $row['ubicacion_general'] ?? '',
        'tipo_propiedad' => $row['tipo_propiedad'] ?? '',
        'subtipo' => $row['subtipo'] ?? '',
        'ciudad' => $row['ciudad'] ?? '',
        'zona' => $row['zona'] ?? '',
        'map_latitud' => $privacyMap['map_latitud'],
        'map_longitud' => $privacyMap['map_longitud'],
        'metros_cuadrados' => isset($row['metros_cuadrados']) ? (int) $row['metros_cuadrados'] : 0,
        'habitaciones' => isset($row['habitaciones']) ? (int) $row['habitaciones'] : 0,
        'precio' => isset($row['precio']) ? (float) $row['precio'] : 0,
        'tipo_input' => $row['tipo_input'] ?? '',
        'precio_m2' => $row['precio_m2'] ?? null,
        'ingresos_actuales' => $row['ingresos_actuales'] ?? null,
        'analisis' => $row['analisis'] ?? '',
        'estado_ocupacion' => $row['estado_ocupacion'] ?? '',
        'disponibilidad' => $row['disponibilidad'] ?? '',
        'situacion' => $row['situacion'] ?? '',
        'caracteristicas' => $caracteristicas,
        'imagen_principal' => $row['imagen_principal'] ?? '',
        'favorited_at' => $row['favorited_at'] ?? null,
        'is_favorite' => !empty($row['favorited_at']),
    ];
}