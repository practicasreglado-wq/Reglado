<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once __DIR__ . '/../config/cors.php';
applyCors();
handlePreflight();

$context = requireAuthenticatedUser($pdo);
$local = $context['local'];
$userId = (int) ($local['iduser'] ?? 0);

if ($userId <= 0) {
    respondJson(401, ['success' => false, 'message' => 'Usuario no autenticado.']);
}

$stmt = $pdo->prepare('
    SELECT 
        p.*,
        pf.created_at AS favorited_at
    FROM propiedades_favoritas pf
    INNER JOIN propiedades p ON p.id = pf.property_id
    WHERE pf.user_id = :user_id
    ORDER BY pf.created_at DESC
');

$stmt->execute(['user_id' => $userId]);

$properties = [];

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $properties[] = [
        'id' => (int) $row['id'],
        'categoria' => $row['categoria'],
        'titulo' => $row['titulo'],
        'ubicacion_general' => $row['ubicacion_general'],
        'tipo_propiedad' => $row['tipo_propiedad'],
        'ciudad' => $row['ciudad'],
        'zona' => $row['zona'],
        'metros_cuadrados' => (int) $row['metros_cuadrados'],
        'habitaciones' => (int) $row['habitaciones'],
        'precio' => (float) $row['precio'],
        'tipo_input' => $row['tipo_input'],
        'precio_m2' => $row['precio_m2'],
        'ingresos_actuales' => $row['ingresos_actuales'],
        'ingresos_estimados' => $row['ingresos_estimados'],
        'gastos_estimados' => $row['gastos_estimados'],
        'EBITDA' => $row['EBITDA'],
        'cash_flow' => $row['cash_flow'],
        'rentabilidad_bruta' => $row['rentabilidad_bruta'],
        'rentabilidad_neta' => $row['rentabilidad_neta'],
        'cap_rate' => $row['cap_rate'],
        'roi' => $row['roi'],
        'payback' => $row['payback'],
        'ocupacion' => $row['ocupacion'],
        'ADR' => $row['ADR'],
        'RevPAR' => $row['RevPAR'],
        'analisis' => $row['analisis'],
        'analisis_json' => $row['analisis_json'],
        'caracteristicas' => !empty($row['caracteristicas_json']) ? json_decode($row['caracteristicas_json'], true) : [],
        'dossier_file' => $row['dossier_file'],
        'confidentiality_file' => $row['confidentiality_file'],
        'intention_file' => $row['intention_file'],
        'captador_id' => $row['captador_id'],
        'created_at' => $row['created_at'],
        'favorited_at' => $row['favorited_at'],
        'is_favorite' => true,
    ];
}

respondJson(200, [
    'success' => true,
    'properties' => $properties,
]);
