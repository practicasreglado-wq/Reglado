<?php
declare(strict_types=1);

/**
 * Devuelve los horarios YA reservados (status='scheduled') para una fecha.
 *
 * El frontend (PropertyDetail.vue) lo consume al elegir hora para una cita
 * y, con la lógica de bloqueo de 3h, deshabilita los slots dentro de
 * [hora ± 180min] de cualquier cita ya reservada.
 *
 * La validación final está duplicada server-side en request_purchase.php
 * (defense-in-depth: que un usuario no se salte el filtro del frontend
 * con una petición manual).
 */

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once __DIR__ . '/../config/cors.php';

applyCors();
handlePreflight();

requireAuthenticatedUser($pdo);

$date = trim((string) ($_GET['date'] ?? ''));

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    respondJson(422, ['success' => false, 'message' => 'Fecha inválida.']);
}

try {
    // Todas las citas activas del día (cualquier propiedad, cualquier
    // usuario). Se usa para bloquear slots en conflicto: si alguien ya
    // firma a las 15:00, nadie más puede agendar en las 3 horas siguientes.
    $stmt = $pdo->prepare('
        SELECT DATE_FORMAT(appointment_date, "%H:%i") AS booked_time
        FROM purchase_appointments
        WHERE DATE(appointment_date) = :d
          AND status = "scheduled"
        ORDER BY appointment_date ASC
    ');
    $stmt->execute(['d' => $date]);
    $times = array_map(
        static fn($row) => (string) $row['booked_time'],
        $stmt->fetchAll(PDO::FETCH_ASSOC)
    );

    respondJson(200, [
        'success'      => true,
        'date'         => $date,
        'booked_times' => $times,
    ]);
} catch (Throwable $e) {
    respondJson(500, [
        'success' => false,
        'message' => 'No se pudieron consultar los horarios.',
    ]);
}
