<?php
declare(strict_types=1);

/**
 * Inicializa la conexión PDO global $pdo contra la BD `inmobiliaria`.
 *
 * Carga el .env primero (idempotente: si ya estaba cargado, no rehace nada),
 * y abre la conexión usando DB_HOST / DB_PORT / DB_NAME / DB_USER / DB_PASS
 * del entorno. Esto es lo que se incluye desde casi todos los endpoints de
 * api/, los crons y los lib/ que necesitan PDO.
 *
 * Si necesitas conectar a la otra BD (`regladousers`) abre un PDO aparte
 * (ver patrón en backend/lib/rate_limit.php o backend/api/reject_user.php).
 */

require_once __DIR__ . '/../lib/env_loader.php';

loadEnv(dirname(__DIR__) . '/.env');

$pdo = new PDO(
    sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        (string) getenv('DB_HOST'),
        (string) (getenv('DB_PORT') ?: '3306'),
        (string) (getenv('DB_NAME') ?: 'inmobiliaria')
    ),
    (string) getenv('DB_USER'),
    (string) getenv('DB_PASS'),
    [
        PDO::ATTR_ERRMODE         => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);
