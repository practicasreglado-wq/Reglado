<?php

declare(strict_types=1);

/**
 * Listado completo de propiedades para el panel del admin (incluye
 * inactivas, vendidas, pendientes de revisión, etc.).
 *
 * Solo accesible para role=admin. Audit log: 'admin.list_all_properties'.
 *
 * Para el listado público (compradores) ver get_properties.php — ese filtra
 * por status='activa' y oculta coordenadas reales.
 */

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once __DIR__ . '/../config/cors.php';
require_once dirname(__DIR__) . '/lib/audit.php';
require_once dirname(__DIR__) . '/lib/error_reporting.php';
require_once dirname(__DIR__) . '/lib/apiloging_client.php';

applyCors();
handlePreflight();

$context = requireAuthenticatedUser($pdo);
$auth = $context['auth'];

$role = strtolower((string) ($auth['role'] ?? ''));

if ($role !== 'admin') {
    respondJson(403, [
        'success' => false,
        'message' => 'Acceso restringido. Esta sección es solo para administradores.'
    ]);
}

try {
    // Lectura de propiedades (BD local) + JOIN cross-service con users vía
    // ApiLogin. Se hace en dos pasos: primero traemos propiedades, luego
    // resolvemos los user_ids únicos (owner + creator) en una sola batch
    // request a ApiLogin, y finalmente joinamos en PHP.
    $stmt = $pdo->prepare('SELECT p.* FROM propiedades p ORDER BY p.created_at DESC');
    $stmt->execute();
    $rawProps = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $userIds = [];
    foreach ($rawProps as $row) {
        if (!empty($row['owner_user_id']))      $userIds[] = (int) $row['owner_user_id'];
        if (!empty($row['created_by_user_id'])) $userIds[] = (int) $row['created_by_user_id'];
    }
    $usersById = $userIds === [] ? [] : apilogingFindManyUsersIndexedById(array_unique($userIds));

    // Pre-procesa cada fila añadiéndole los campos owner_* y creator_* que el
    // resto del bloque (sin cambios) espera consumir.
    $rows = [];
    foreach ($rawProps as $row) {
        $owner   = $usersById[(int) ($row['owner_user_id'] ?? 0)] ?? null;
        $creator = $usersById[(int) ($row['created_by_user_id'] ?? 0)] ?? null;

        $row['owner_id']         = $owner['id'] ?? null;
        $row['owner_username']   = $owner['username'] ?? null;
        $row['owner_email']      = $owner['email'] ?? null;
        $row['owner_first_name'] = $owner['first_name'] ?? null;
        $row['owner_last_name']  = $owner['last_name'] ?? null;
        $row['owner_phone']      = $owner['phone'] ?? null;

        $row['creator_id']         = $creator['id'] ?? null;
        $row['creator_username']   = $creator['username'] ?? null;
        $row['creator_email']      = $creator['email'] ?? null;
        $row['creator_first_name'] = $creator['first_name'] ?? null;
        $row['creator_last_name']  = $creator['last_name'] ?? null;
        $row['creator_phone']      = $creator['phone'] ?? null;

        $rows[] = $row;
    }

    $properties = [];

    foreach ($rows as $row) {
        $caracteristicas = null;

        if (!empty($row['caracteristicas_json'])) {
            $decoded = json_decode((string) $row['caracteristicas_json'], true);
            $caracteristicas = is_array($decoded) ? $decoded : null;
        }

        $ownerFullName = trim(
            (string) ($row['owner_first_name'] ?? '') . ' ' . (string) ($row['owner_last_name'] ?? '')
        );
        if ($ownerFullName === '') {
            $ownerFullName = (string) ($row['owner_username'] ?? '');
        }
        if ($ownerFullName === '') {
            $ownerFullName = 'Sistema / Sin asignar';
        }

        $creatorFullName = trim(
            (string) ($row['creator_first_name'] ?? '') . ' ' . (string) ($row['creator_last_name'] ?? '')
        );
        if ($creatorFullName === '') {
            $creatorFullName = (string) ($row['creator_username'] ?? '');
        }
        if ($creatorFullName === '') {
            $creatorFullName = 'Sistema';
        }

        $ubicacionGeneral = trim(
            implode(' - ', array_filter([
                (string) ($row['ciudad'] ?? ''),
                (string) ($row['zona'] ?? '')
            ]))
        );

        if ($ubicacionGeneral === '') {
            $ubicacionGeneral = (string) ($row['direccion'] ?? '');
        }

        $prop = $row;

        $prop['id'] = isset($row['id']) ? (int) $row['id'] : 0;
        $prop['precio'] = isset($row['precio']) ? (float) $row['precio'] : 0;
        $prop['metros_cuadrados'] = isset($row['metros_cuadrados']) ? (int) $row['metros_cuadrados'] : 0;
        $prop['estado'] = !empty($row['estado']) ? (string) $row['estado'] : 'disponible';
        $prop['titulo'] = (string) (
            $row['titulo']
            ?? $row['tipo_propiedad']
            ?? $row['categoria']
            ?? ('Propiedad #' . (int) ($row['id'] ?? 0))
        );

        $prop['ubicacion_general'] = $ubicacionGeneral;
        $prop['caracteristicas'] = $caracteristicas;

        $prop['owner'] = [
            'id' => !empty($row['owner_id']) ? (int) $row['owner_id'] : null,
            'nombre' => $ownerFullName,
            'email' => !empty($row['owner_email']) ? (string) $row['owner_email'] : '-',
            'username' => !empty($row['owner_username']) ? (string) $row['owner_username'] : null,
            'first_name' => !empty($row['owner_first_name']) ? (string) $row['owner_first_name'] : null,
            'last_name' => !empty($row['owner_last_name']) ? (string) $row['owner_last_name'] : null,
            'phone' => !empty($row['owner_phone']) ? (string) $row['owner_phone'] : null,
        ];

        $prop['creator'] = [
            'id' => !empty($row['creator_id']) ? (int) $row['creator_id'] : null,
            'nombre' => $creatorFullName,
            'email' => !empty($row['creator_email']) ? (string) $row['creator_email'] : '-',
            'username' => !empty($row['creator_username']) ? (string) $row['creator_username'] : null,
            'first_name' => !empty($row['creator_first_name']) ? (string) $row['creator_first_name'] : null,
            'last_name' => !empty($row['creator_last_name']) ? (string) $row['creator_last_name'] : null,
            'phone' => !empty($row['creator_phone']) ? (string) $row['creator_phone'] : null,
        ];

        $prop['owner_id'] = !empty($row['owner_id']) ? (int) $row['owner_id'] : null;
        $prop['owner_name'] = $ownerFullName;
        $prop['owner_email'] = !empty($row['owner_email']) ? (string) $row['owner_email'] : '-';

        $prop['creator_id'] = !empty($row['creator_id']) ? (int) $row['creator_id'] : null;
        $prop['creator_name'] = $creatorFullName;
        $prop['creator_email'] = !empty($row['creator_email']) ? (string) $row['creator_email'] : '-';

        $properties[] = $prop;
    }

    auditLog($pdo, 'admin.list_all_properties', array_merge(
        auditContextFromAuth($auth),
        ['metadata' => ['total' => count($properties)]]
    ));

    respondJson(200, [
        'success' => true,
        'properties' => $properties
    ]);
} catch (Throwable $e) {
    $errorId = logAndReferenceError('get_all_properties', $e);
    respondJson(500, [
        'success' => false,
        'message' => 'Error al obtener las propiedades. Referencia: ' . $errorId,
    ]);
}