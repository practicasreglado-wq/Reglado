<?php
declare(strict_types=1);

/**
 * Comprueba si el usuario autenticado tiene una cita pendiente para una
 * propiedad concreta. Lo usa el frontend para decidir si mostrar
 * "Solicitar cita" o "Ver mi cita pendiente" en PropertyDetail.vue.
 */

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once __DIR__ . '/../config/cors.php';

applyCors();
handlePreflight();

$context = requireAuthenticatedUser($pdo);
$userId = (int) ($context['local']['iduser'] ?? $context['local']['id'] ?? $context['auth']['id'] ?? 0);
$propertyId = (int) ($_GET['property_id'] ?? 0);

if ($userId <= 0 || $propertyId <= 0) {
    respondJson(422, ['success' => false, 'message' => 'Datos inválidos.']);
}

$stmt = $pdo->prepare('
    SELECT id, appointment_date, status
    FROM purchase_appointments
    WHERE user_id = :uid
      AND property_id = :pid
      AND status = "scheduled"
    LIMIT 1
');
$stmt->execute(['uid' => $userId, 'pid' => $propertyId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

respondJson(200, [
    'success'       => true,
    'has_pending'   => (bool) $row,
    'appointment'   => $row ?: null,
]);
