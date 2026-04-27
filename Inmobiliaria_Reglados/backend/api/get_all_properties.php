<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once __DIR__ . '/../config/cors.php';
require_once dirname(__DIR__) . '/lib/audit.php';

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
    $query = "
        SELECT
            p.*,

            owner.id AS owner_id,
            owner.username AS owner_username,
            owner.email AS owner_email,
            owner.first_name AS owner_first_name,
            owner.last_name AS owner_last_name,
            owner.phone AS owner_phone,

            creator.id AS creator_id,
            creator.username AS creator_username,
            creator.email AS creator_email,
            creator.first_name AS creator_first_name,
            creator.last_name AS creator_last_name,
            creator.phone AS creator_phone

        FROM inmobiliaria.propiedades p
        LEFT JOIN regladousers.users owner
            ON p.owner_user_id = owner.id
        LEFT JOIN regladousers.users creator
            ON p.created_by_user_id = creator.id
        ORDER BY p.created_at DESC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    respondJson(500, [
        'success' => false,
        'message' => 'Error al obtener las propiedades: ' . $e->getMessage()
    ]);
}