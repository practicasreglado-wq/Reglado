<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/session.php';

applyCors();
handlePreflight();

$userId = 0;

try {
    $context = requireAuthenticatedUser($pdo);

    $userId = (int) (
        $context['local']['id']
        ?? $context['local']['iduser']
        ?? $context['user']['id']
        ?? $context['user']['iduser']
        ?? 0
    );
} catch (\Throwable $error) {
    $userId = (int) (
        $_SESSION['user']['id']
        ?? $_SESSION['user']['iduser']
        ?? $_SESSION['id']
        ?? $_SESSION['iduser']
        ?? 0
    );
}

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
    $properties[] = [
        'id' => (int) $row['id'],
        'categoria' => $row['categoria'] ?? '',
        'titulo' => $row['titulo'] ?? $row['nombre'] ?? '',
        'ubicacion_general' => $row['ubicacion_general'] ?? $row['direccion'] ?? '',
        'tipo_propiedad' => $row['tipo_propiedad'] ?? '',
        'ciudad' => $row['ciudad'] ?? '',
        'zona' => $row['zona'] ?? '',
        'metros_cuadrados' => (int) ($row['metros_cuadrados'] ?? 0),
        'habitaciones' => (int) ($row['habitaciones'] ?? 0),
        'precio' => (float) ($row['precio'] ?? 0),
        'tipo_input' => $row['tipo_input'] ?? '',
        'precio_m2' => $row['precio_m2'] ?? null,
        'ingresos_actuales' => $row['ingresos_actuales'] ?? null,
        'ingresos_estimados' => $row['ingresos_estimados'] ?? null,
        'gastos_estimados' => $row['gastos_estimados'] ?? null,
        'EBITDA' => $row['EBITDA'] ?? null,
        'cash_flow' => $row['cash_flow'] ?? null,
        'rentabilidad_bruta' => $row['rentabilidad_bruta'] ?? null,
        'rentabilidad_neta' => $row['rentabilidad_neta'] ?? null,
        'cap_rate' => $row['cap_rate'] ?? null,
        'roi' => $row['roi'] ?? null,
        'payback' => $row['payback'] ?? null,
        'ocupacion' => $row['ocupacion'] ?? null,
        'ADR' => $row['ADR'] ?? null,
        'RevPAR' => $row['RevPAR'] ?? null,
        'analisis' => $row['analisis'] ?? '',
        'analisis_json' => $row['analisis_json'] ?? null,
        'caracteristicas' => !empty($row['caracteristicas_json'])
            ? json_decode((string) $row['caracteristicas_json'], true)
            : [],
        'dossier_file' => $row['dossier_file'] ?? '',
        'confidentiality_file' => $row['confidentiality_file'] ?? '',
        'intention_file' => $row['intention_file'] ?? '',
        'captador_id' => $row['captador_id'] ?? null,
        'created_at' => $row['created_at'] ?? null,
        'updated_at' => $row['updated_at'] ?? null,
        'favorited_at' => $row['favorited_at'] ?? null,
        'is_favorite' => !empty($row['favorited_at']),
    ];
}

respondJson(200, [
    'success' => true,
    'properties' => $properties,
]);