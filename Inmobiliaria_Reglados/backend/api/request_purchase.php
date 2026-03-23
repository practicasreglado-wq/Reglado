<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/cors.php';
applyCors();
handlePreflight();
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/lib/env_loader.php';
require_once dirname(__DIR__) . '/lib/signature_access.php';
require_once dirname(__DIR__) . '/send_mail.php';

loadEnv(dirname(__DIR__) . '/.env');

$context = requireAuthenticatedUser($pdo);
$auth = $context['auth'] ?? [];

$data = json_decode(file_get_contents('php://input'), true);
$propertyId = (int) ($data['property_id'] ?? 0);

if ($propertyId <= 0) {
    respondJson(422, ['success' => false, 'message' => 'Propiedad no especificada.']);
}

$stmt = $pdo->prepare('SELECT id, titulo, ciudad, zona, precio, metros_cuadrados, habitaciones, rentabilidad, caracteristicas_json FROM propiedades WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $propertyId]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    respondJson(404, ['success' => false, 'message' => 'Propiedad no encontrada.']);
}

$summary = fetchSignatureSummary($pdo, $userId, $propertyId);
if ($summary['status'] !== 'validado') {
    respondJson(403, [
        'success' => false,
        'message' => 'Debes completar la firma y validación administrativa para solicitar la compra.',
        'status' => $summary['status'],
    ]);
}

$userNameParts = array_filter([
    $auth['nombre'] ?? $auth['first_name'] ?? '',
    $auth['apellidos'] ?? $auth['last_name'] ?? '',
    $auth['name'] ?? '',
]);
$userEmail = $auth['email'] ?? '';
$userPhone = $auth['phone'] ?? '';
$userName = trim(implode(' ', $userNameParts));

$characteristics = json_decode($property['caracteristicas_json'] ?? '[]', true);
if (!is_array($characteristics)) {
    $characteristics = [];
}

$subject = "Solicitud de compra propiedad #{$propertyId}";
$body = "<h2>Solicitud de compra registrada</h2>";
$body .= "<p><strong>Usuario:</strong></p><ul>";
$body .= "<li>Nombre: " . htmlspecialchars($userName ?: 'Sin nombre') . "</li>";
$body .= "<li>Email: " . htmlspecialchars($userEmail ?: 'Sin email') . "</li>";
$body .= "<li>Teléfono: " . htmlspecialchars($userPhone ?: 'Sin teléfono') . "</li>";
$body .= "</ul>";
$body .= "<p><strong>Propiedad:</strong></p><ul>";
$body .= "<li>ID: {$propertyId}</li>";
$body .= "<li>Título: " . htmlspecialchars($property['titulo'] ?? 'Sin título') . "</li>";
$body .= "<li>Ciudad: " . htmlspecialchars($property['ciudad'] ?? 'No especificado') . "</li>";
$body .= "<li>Zona: " . htmlspecialchars($property['zona'] ?? 'No especificada') . "</li>";
$body .= "<li>Precio: " . number_format((float) ($property['precio'] ?? 0), 2, ',', '.') . " €</li>";
$body .= "<li>Metros cuadrados: " . ((int) ($property['metros_cuadrados'] ?? 0)) . " m²</li>";
$body .= "<li>Habitaciones: " . ((int) ($property['habitaciones'] ?? 0)) . "</li>";
$body .= "<li>Rentabilidad: " . htmlspecialchars($property['rentabilidad'] ?? 'No disponible') . "</li>";
$body .= "</ul>";

if ($characteristics) {
    $body .= "<p><strong>Características destacadas:</strong></p><ul>";
    foreach ($characteristics as $key => $value) {
        $body .= "<li>" . htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) . ": " . htmlspecialchars((string) $value) . "</li>";
    }
    $body .= "</ul>";
}

try {
    sendNotificationEmail(
        'realstate@regladoconsultores.com',
        $subject,
        $body,
        $userEmail ?: null
    );

    respondJson(200, [
        'success' => true,
        'message' => 'Solicitud enviada. Nuestro equipo te contactará pronto.',
        'access_status' => $summary['status'],
    ]);

} catch (Throwable $exception) {
    respondJson(500, [
        'success' => false,
        'message' => 'No se pudo enviar la solicitud: ' . $exception->getMessage(),
    ]);
}
