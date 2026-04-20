<?php

declare(strict_types=1);

// Devuelve en JSON el listado de solicitudes guardadas, ordenadas por fecha para el panel de admin.
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

applySecurityHeaders();
enforceProductionSecurity();
applyCorsHeaders(['GET', 'POST', 'OPTIONS'], 'Content-Type, Authorization');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    respondJson(405, ['ok' => false, 'message' => 'Metodo no permitido.']);
}

requireAdminAuth();

try {
    $pdo = getPdo();
    $statement = $pdo->query(
        'SELECT
            id,
            nombre,
            telefono,
            email,
            mensaje,
            pdf_nombre_original,
            pdf_ruta,
            pdf_mime,
            pdf_tamano_bytes,
            creado_en
        FROM facturas
        ORDER BY creado_en DESC, id DESC'
    );

    $rows = $statement->fetchAll();

    foreach ($rows as &$row) {
        $row['has_pdf'] = $row['pdf_ruta'] !== null && $row['pdf_ruta'] !== '';
    }
    unset($row);

    respondJson(200, ['ok' => true, 'items' => $rows]);
} catch (Throwable $exception) {
    error_log('ADMIN_LIST_ERROR ip=' . getClientIpAddress() . ' message=' . $exception->getMessage());
    respondJson(500, ['ok' => false, 'message' => 'No se pudieron cargar las solicitudes.']);
}

function respondJson(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}
