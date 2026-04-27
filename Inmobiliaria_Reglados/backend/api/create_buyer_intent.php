<?php
declare(strict_types=1);

/**
 * Crea un nuevo buyer_intent (criterios de búsqueda del comprador).
 *
 * Una vez creado, lib/buyer_intents.php notifica a otros usuarios para que
 * suban propiedades que encajen. Cuando alguien sube una que matchea con
 * estos criterios, el comprador recibe notificación + email.
 *
 * Audit log: 'buyer_intent.create'.
 */

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../lib/buyer_intents.php';
require_once __DIR__ . '/../lib/audit.php';
require_once __DIR__ . '/../lib/error_reporting.php';

applyCors();
handlePreflight();

$context = requireAuthenticatedUser($pdo);
$buyerUserId = (int) (
    $context['local']['iduser']
    ?? $context['local']['id']
    ?? $context['auth']['sub']
    ?? 0
);

if ($buyerUserId <= 0) {
    respondJson(401, ['success' => false, 'message' => 'Debes iniciar sesión.']);
}

$raw = file_get_contents('php://input') ?: '{}';
$data = json_decode($raw, true);

if (!is_array($data)) {
    respondJson(400, ['success' => false, 'message' => 'Solicitud no válida.']);
}

$criteria = [
    'category'         => trim((string) ($data['category'] ?? '')),
    'city'             => trim((string) ($data['city'] ?? '')),
    'max_price'        => $data['max_price'] ?? null,
    'min_m2'           => $data['min_m2'] ?? null,
    'criteria_display' => is_array($data['criteria_display'] ?? null)
        ? $data['criteria_display']
        : [],
];

// Mínimo una dimensión con contenido para no crear intents vacíos.
if ($criteria['category'] === '' && $criteria['city'] === '') {
    respondJson(422, [
        'success' => false,
        'message' => 'Indica al menos una categoría o ciudad para registrar la búsqueda.',
    ]);
}

try {
    $intentId = createBuyerIntent($pdo, $buyerUserId, $criteria);

    if ($intentId <= 0) {
        throw new RuntimeException('No se pudo crear el intent.');
    }

    $summary = buildIntentCriteriaSummary($criteria);
    $notified = notifyUsersAboutBuyerIntent(
        $pdo,
        $intentId,
        $buyerUserId,
        $summary,
        $criteria['category'] !== '' ? $criteria['category'] : null
    );

    auditLog($pdo, 'buyer_intent.create', array_merge(
        auditContextFromAuth($context['auth'] ?? [], $buyerUserId),
        [
            'resource_type' => 'buyer_intent',
            'resource_id'   => (string) $intentId,
            'metadata'      => [
                'summary'  => $summary,
                'notified' => $notified,
            ],
        ]
    ));

    respondJson(200, [
        'success'  => true,
        'intentId' => $intentId,
        'summary'  => $summary,
        'notified' => $notified,
    ]);
} catch (Throwable $e) {
    $errorId = logAndReferenceError('create_buyer_intent', $e);
    respondJson(500, [
        'success' => false,
        'message' => 'No se pudo registrar la búsqueda. Referencia: ' . $errorId,
    ]);
}
