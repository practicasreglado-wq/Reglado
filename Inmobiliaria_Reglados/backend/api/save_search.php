<?php

require_once dirname(__DIR__) . "/config/db.php";
require_once dirname(__DIR__) . "/config/auth.php";

applyAuthCors();
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

$stmt = $pdo->prepare("
    INSERT INTO search_history (user_id, category, preferences_json)
    VALUES (:user_id, :category, :preferences_json)
");

$stmt->execute([
    "user_id" => $userId,
    "category" => $category,
    "preferences_json" => json_encode($preferences, JSON_UNESCAPED_UNICODE),
]);

respondJson(200, [
    "success" => true,
    "message" => "Búsqueda guardada correctamente.",
    "search_id" => (int) $pdo->lastInsertId(),
]);
