<?php

require_once "config/db.php";
require_once "config/auth.php";

applyAuthCors();
handlePreflight();

$context = requireAuthenticatedUser($pdo);
$local = $context['local'];

$data = json_decode(file_get_contents("php://input"), true);
$categoria = $data["categoria"] ?? null;
$preferencias = $data["preferencias"] ?? null;

if (!$categoria || !isset($preferencias)) {
    respondJson(422, ["success" => false, "message" => "Datos incompletos"]);
}

$stmt = $pdo->prepare("
    UPDATE inmobiliaria
    SET categoria = :categoria,
        preferencias = :preferencias
    WHERE iduser = :iduser
");

$stmt->execute([
    "categoria" => $categoria,
    "preferencias" => json_encode($preferencias, JSON_UNESCAPED_UNICODE),
    "iduser" => $local["iduser"],
]);

respondJson(200, ["success" => true]);
