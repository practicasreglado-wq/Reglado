<?php
require_once "c:/xampp/htdocs/Reglado/Inmobiliaria_Reglados/backend/config/db.php";

$stmt = $pdo->query("DESCRIBE inmobiliaria");
$columns = $stmt->fetchAll();
print_r($columns);
