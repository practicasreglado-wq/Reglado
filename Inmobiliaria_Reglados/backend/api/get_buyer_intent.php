<?php
declare(strict_types=1);

/**
 * Devuelve los buyer_intents activos del usuario autenticado (matchmaking
 * comprador → propiedad). Los muestra el frontend en su sección "Mis
 * búsquedas activas".
 *
 * Para crear uno: create_buyer_intent.php. Lógica de matching y
 * notificaciones: lib/buyer_intents.php.
 */

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../lib/buyer_intents.php';
require_once __DIR__ . '/../lib/error_reporting.php';

applyCors();
handlePreflight();

// Requiere auth — solo usuarios logueados pueden ver detalles de intents
// (para que aparezcan en el modal del vendedor al abrir la notificación).
requireAuthenticatedUser($pdo);

$intentId = (int) ($_GET['id'] ?? 0);
if ($intentId <= 0) {
    respondJson(422, ['success' => false, 'message' => 'Identificador inválido.']);
}

try {
    $intent = fetchBuyerIntentDetails($pdo, $intentId);

    if ($intent === null) {
        respondJson(404, ['success' => false, 'message' => 'Solicitud no encontrada.']);
    }

    respondJson(200, [
        'success' => true,
        'intent'  => $intent,
    ]);
} catch (Throwable $e) {
    $errorId = logAndReferenceError('get_buyer_intent', $e);
    respondJson(500, [
        'success' => false,
        'message' => 'No se pudo cargar la solicitud. Referencia: ' . $errorId,
    ]);
}
