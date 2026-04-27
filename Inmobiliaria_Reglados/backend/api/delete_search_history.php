<?php

/**
 * Borra una entrada (o todas) del historial de búsquedas del usuario
 * autenticado. Solo afecta a search_history del propio user — no a
 * buyer_intents.
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
$searchId = (int) ($data["search_id"] ?? 0);

if ($userId <= 0 || $userId !== (int) ($local["iduser"] ?? 0)) {
    respondJson(403, ["success" => false, "message" => "Usuario no autorizado."]);
}

if ($searchId <= 0) {
    respondJson(422, ["success" => false, "message" => "Búsqueda no válida."]);
}

$stmt = $pdo->prepare("
    DELETE FROM search_history
    WHERE id = :id AND user_id = :user_id
    LIMIT 1
");

$stmt->execute([
    "id" => $searchId,
    "user_id" => $userId,
]);

if ($stmt->rowCount() === 0) {
    respondJson(404, ["success" => false, "message" => "No se encontró la búsqueda."]);
}

respondJson(200, [
    "success" => true,
    "message" => "Búsqueda eliminada correctamente.",
]);
