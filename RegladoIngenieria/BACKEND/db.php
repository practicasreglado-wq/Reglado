<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function getPdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = (int)(getenv('DB_PORT') ?: 3306);
    $name = getenv('DB_NAME') ?: 'ingenieria';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $name);

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        throw new RuntimeException('No se pudo conectar con la base de datos.', 0, $e);
    }

    return $pdo;
}
