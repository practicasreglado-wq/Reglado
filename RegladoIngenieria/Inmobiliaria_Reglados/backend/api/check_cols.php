<?php
require_once "c:/xampp/htdocs/Reglado/Inmobiliaria_Reglados/backend/config/db.php";
$q = $pdo->query("DESCRIBE inmobiliaria");
$fields = [];
while($row = $q->fetch()) {
    $fields[] = $row['Field'];
}
echo implode(', ', $fields);
