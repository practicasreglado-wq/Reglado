<?php

require_once dirname(__DIR__) . "/config/db.php";
require_once dirname(__DIR__) . "/config/auth.php";

applyAuthCors();
handlePreflight();

$context = requireAuthenticatedUser($pdo);
$local = $context["local"];

$userId = (int) ($local["iduser"] ?? 0);

if ($userId <= 0) {
    respondJson(401, ["success" => false, "message" => "Usuario no autenticado."]);
}

$stmt = $pdo->prepare("
    SELECT id, user_id, categoria, preferences, created_at
    FROM search_history
    WHERE user_id = :user_id
    ORDER BY created_at DESC, id DESC
");

$stmt->execute([
    "user_id" => $userId,
]);

$history = array_map(static function (array $row): array {
    return [
        "id" => (int) $row["id"],
        "user_id" => (int) $row["user_id"],
        "category" => $row["categoria"],
        "preferences" => json_decode((string) $row["preferences"], true) ?: [],
        "created_at" => $row["created_at"],
    ];
}, $stmt->fetchAll(PDO::FETCH_ASSOC));

respondJson(200, [
    "success" => true,
    "history" => $history,
]);
