<?php

/**
 * Guarda una búsqueda hecha por el usuario en `search_history` para que
 * pueda volver a ella desde "Mis búsquedas recientes".
 *
 * Distinto de buyer_intents: las searches son consultas puntuales, los
 * intents son criterios persistentes que disparan notificaciones cuando
 * aparece una propiedad que matchea.
 */

require_once dirname(__DIR__) . "/config/db.php";
require_once dirname(__DIR__) . "/config/auth.php";
require_once __DIR__ . '/../config/cors.php';
applyCors();
handlePreflight();

$context = requireAuthenticatedUser($pdo);
$local = $context["local"];

$data = json_decode(file_get_contents("php://input"), true);

$userId = (int) ($data["user_id"] ?? ($local["iduser"] ?? 0));
$category = trim((string) ($data["category"] ?? ""));
$preferences = $data["preferences"] ?? null;

if ($userId <= 0 || $userId !== (int) ($local["iduser"] ?? 0)) {
    respondJson(403, ["success" => false, "message" => "Usuario no autorizado."]);
}

if ($category === "" || !is_array($preferences) || empty($preferences)) {
    respondJson(422, ["success" => false, "message" => "Datos de búsqueda incompletos."]);
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO search_history (user_id, categoria, preferences)
        VALUES (:user_id, :categoria, :preferences)
    ");

    $stmt->execute([
        "user_id" => $userId,
        "categoria" => $category,
        "preferences" => json_encode($preferences, JSON_UNESCAPED_UNICODE),
    ]);

    respondJson(200, [
        "success" => true,
        "message" => "Búsqueda guardada correctamente.",
        "search_id" => (int) $pdo->lastInsertId(),
    ]);

} catch (PDOException $e) {
    respondJson(500, [
        "success" => false, 
        "message" => "No se pudo guardar la búsqueda. Error interno en la base de datos."
    ]);
}
