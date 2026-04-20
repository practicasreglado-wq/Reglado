<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once __DIR__ . '/../config/cors.php';

applyCors();
handlePreflight();

$context = requireAuthenticatedUser($pdo);
$auth = $context['auth'];

$role = strtolower((string) ($auth['role'] ?? ''));

if (!in_array($role, ['admin', 'real'], true)) {
    respondJson(403, [
        'success' => false,
        'message' => 'Acceso restringido. Esta sección es solo para administradores.'
    ]);
}

try {
    $query = "
        SELECT 
            p.id,
            p.tipo_propiedad,
            p.ciudad,
            p.zona,
            p.metros_cuadrados,
            p.precio,
            p.direccion,
            p.categoria,
            p.caracteristicas_json,
            p.dossier_file,
            p.confidentiality_file,
            p.intention_file,
            p.captador_id,
            p.owner_user_id,
            p.created_at,
            p.updated_at,
            u.id AS owner_id,
            u.username AS owner_username,
            u.email AS owner_email,
            u.first_name AS owner_first_name,
            u.last_name AS owner_last_name
        FROM inmobiliaria.propiedades p
        LEFT JOIN regladousers.users u 
            ON p.owner_user_id = u.id
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
            $ownerFullName = (string) ($row['owner_username'] ?? 'Sistema / Sin asignar');
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

        $properties[] = [
            'id' => (int) ($row['id'] ?? 0),

            // Título visual para el frontend
            'titulo' => (string) (
                $row['tipo_propiedad']
                ?? $row['categoria']
                ?? ('Propiedad #' . (int) ($row['id'] ?? 0))
            ),

            'tipo_propiedad' => (string) ($row['tipo_propiedad'] ?? ''),
            'categoria' => (string) ($row['categoria'] ?? ''),
            'ciudad' => (string) ($row['ciudad'] ?? ''),
            'zona' => (string) ($row['zona'] ?? ''),
            'direccion' => (string) ($row['direccion'] ?? ''),
            'ubicacion_general' => $ubicacionGeneral,

            'precio' => (float) ($row['precio'] ?? 0),
            'metros_cuadrados' => (int) ($row['metros_cuadrados'] ?? 0),

            // No existe en tu tabla, lo dejamos nulo para no romper
            'imagen_principal' => null,

            'caracteristicas' => $caracteristicas,

            'owner_user_id' => !empty($row['owner_user_id']) ? (int) $row['owner_user_id'] : null,
            'captador_id' => !empty($row['captador_id']) ? (int) $row['captador_id'] : null,

            'owner' => [
                'id' => !empty($row['owner_id']) ? (int) $row['owner_id'] : null,
                'nombre' => $ownerFullName,
                'email' => !empty($row['owner_email']) ? (string) $row['owner_email'] : '-',
                'username' => !empty($row['owner_username']) ? (string) $row['owner_username'] : null,
            ],

            // Compatibilidad extra por si alguna vista usa estos campos planos
            'owner_id' => !empty($row['owner_id']) ? (int) $row['owner_id'] : null,
            'owner_name' => $ownerFullName,
            'owner_email' => !empty($row['owner_email']) ? (string) $row['owner_email'] : '-',

            'dossier_file' => $row['dossier_file'] ?? null,
            'confidentiality_file' => $row['confidentiality_file'] ?? null,
            'intention_file' => $row['intention_file'] ?? null,

            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? ($row['created_at'] ?? null),

            'estado_publicacion' => 'Publicado',
        ];
    }

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