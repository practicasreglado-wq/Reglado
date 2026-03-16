<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';

applyAuthCors();
handlePreflight();

// Verificar autenticación
$context = requireAuthenticatedUser($pdo);
$auth = $context['auth'];

// Verificar rol ADMIN
if (($auth['role'] ?? '') !== 'admin') {
    respondJson(403, [
        'success' => false,
        'message' => 'Acceso restringido. Esta sección es solo para administradores.'
    ]);
}

try {
    // Consulta para obtener todas las propiedades con info del propietario
    // Asumimos que la base de datos regladousers está en el mismo servidor
    $query = "
        SELECT 
            p.*,
            u.id as owner_id,
            u.username as owner_name,
            u.email as owner_email
        FROM propiedades p
        LEFT JOIN regladousers.users u ON p.owner_user_id = u.id
        ORDER BY p.created_at DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $properties = [];
    foreach ($rows as $row) {
        $properties[] = [
            'id' => (int) $row['id'],
            'categoria' => $row['categoria'],
            'titulo' => $row['titulo'],
            'ubicacion_general' => $row['ubicacion_general'],
            'precio' => (float) $row['precio'],
            'metros_cuadrados' => (int) $row['metros_cuadrados'],
            'imagen_principal' => $row['imagen_principal'],
            'caracteristicas' => !empty($row['caracteristicas_json']) ? json_decode($row['caracteristicas_json'], true) : null,
            'owner' => [
                'id' => $row['owner_id'] ? (int) $row['owner_id'] : null,
                'nombre' => $row['owner_name'] ?? 'Sistema / Sin asignar',
                'email' => $row['owner_email'] ?? '-'
            ],
            'created_at' => $row['created_at'],
            'updated_at' => $row['created_at'], // Usamos created_at como fallback si no hay updated_at
            'estado_publicacion' => 'Publicado' // Por defecto para el MVP
        ];
    }

    respondJson(200, [
        'success' => true,
        'properties' => $properties
    ]);

} catch (PDOException $e) {
    respondJson(500, [
        'success' => false,
        'message' => 'Error al obtener las propiedades: ' . $e->getMessage()
    ]);
}
