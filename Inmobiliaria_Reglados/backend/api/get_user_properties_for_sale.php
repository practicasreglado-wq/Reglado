<?php
require_once __DIR__ . '/../config/cors.php';
applyCors();
handlePreflight();
require_once "../config/session.php";


require_once "../config/db.php";

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(["error" => "Usuario no autenticado"]);
    exit;
}

$userId = (int) $_SESSION['user']['id'];

$stmt = $pdo->prepare("
    SELECT
        id,
        titulo AS nombre,
        ubicacion_general AS ubicacion,
        precio,
        categoria AS tipo
    FROM propiedades
    WHERE owner_user_id = ?
    AND titulo IS NOT NULL
    AND titulo != ''
    ORDER BY created_at DESC, id DESC
");
$stmt->execute([$userId]);

$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($properties);
