<?php
declare(strict_types=1);

$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';

if ($email === '' || $token === '') {
    http_response_code(400);
    echo "<h1>Error: Email o Token no proporcionado.</h1>";
    exit;
}

try {
    // 1. Conexión a 'inmobiliaria' para validar el token de la solicitud
    $host = '127.0.0.1';
    $port = '3306';
    $user = 'root';
    $pass = '';

    $pdoInmo = new PDO("mysql:host={$host};port={$port};dbname=inmobiliaria;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // 2. Conexión a 'regladousers' para actualizar el rol del usuario
    $pdoAuth = new PDO("mysql:host={$host};port={$port};dbname=regladousers;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Verificar si la solicitud existe y está pendiente en 'inmobiliaria'
    $stmtCheck = $pdoInmo->prepare("SELECT id FROM role_promotion_requests WHERE user_email = ? AND token = ? AND status = 'pending' LIMIT 1");
    $stmtCheck->execute([$email, $token]);
    $request = $stmtCheck->fetch();

    if (!$request) {
        echo "<h1 style='color: orange; font-family: sans-serif;'>Esta solicitud ya ha sido procesada o el enlace no es válido.</h1>";
        exit;
    }

    // Actualizar el rol del usuario en 'regladousers'
    $stmtUpdateUser = $pdoAuth->prepare("UPDATE users SET role = 'real' WHERE email = ?");
    $stmtUpdateUser->execute([$email]);

    // Marcar la solicitud como aprobada en 'inmobiliaria'
    $stmtMarkResolved = $pdoInmo->prepare("UPDATE role_promotion_requests SET status = 'approved', resolved_at = NOW() WHERE id = ?");
    $stmtMarkResolved->execute([$request['id']]);

    if ($stmtUpdateUser->rowCount() > 0) {
        echo "<h1 style='color: green; font-family: sans-serif;'>El usuario con correo {$email} ha sido actualizado al rol Real exitosamente.</h1>";
    } else {
        echo "<h1 style='color: blue; font-family: sans-serif;'>El usuario ya tenía el rol Real, solicitud marcada como resuelta.</h1>";
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo "<h1 style='color: red; font-family: sans-serif;'>Error de base de datos: " . htmlspecialchars($e->getMessage()) . "</h1>";
}
