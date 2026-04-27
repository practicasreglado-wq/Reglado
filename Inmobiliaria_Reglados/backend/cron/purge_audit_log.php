<?php
declare(strict_types=1);

/**
 * Cron: purga entradas viejas de la tabla `audit_log`.
 *
 * Sin esta limpieza la tabla crecería indefinidamente (cada acción crítica
 * escribe una fila vía lib/audit.php). En sistemas con tráfico, eso lleva
 * tarde o temprano a problemas de tamaño de disco y de rendimiento.
 *
 * Configuración: variable AUDIT_LOG_RETENTION_DAYS del .env (default 365).
 *
 * Algoritmo:
 *  - Borra en chunks de 5.000 filas para no bloquear la tabla con un DELETE
 *    masivo (importante en MySQL/InnoDB con mucho tráfico concurrente).
 *  - Hasta 1.000 iteraciones por ejecución → tope de 5M filas/run.
 *
 * Programar en Hostinger (cron):
 *   0 3 * * * /usr/bin/php .../backend/cron/purge_audit_log.php
 *
 * Logs: backend/logs/purge_audit_log.log.
 */

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/lib/env_loader.php';

loadEnv(dirname(__DIR__) . '/.env');

/**
 * Helper de log específico del job. Escribe en backend/logs/purge_audit_log.log.
 */
function logPurgeAuditJob(string $message, array $context = []): void
{
    $logsDir = dirname(__DIR__) . '/logs';

    if (!is_dir($logsDir)) {
        if (@mkdir($logsDir, 0750, true)) {
            @chmod($logsDir, 0750);
        }
    }

    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;

    if (!empty($context)) {
        $json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json !== false) {
            $line .= ' | ' . $json;
        }
    }

    file_put_contents($logsDir . '/purge_audit_log.log', $line . PHP_EOL, FILE_APPEND);
}

$retentionDays = (int) (getenv('AUDIT_LOG_RETENTION_DAYS') ?: 365);

if ($retentionDays <= 0) {
    logPurgeAuditJob('Aborted: AUDIT_LOG_RETENTION_DAYS inválido', [
        'value' => getenv('AUDIT_LOG_RETENTION_DAYS'),
    ]);
    exit(1);
}

// Borra en tandas para no bloquear la tabla con un DELETE masivo.
$chunkSize = 5000;
$maxIterations = 1000;
$totalDeleted = 0;
$iterations = 0;

// Prepared statement con LIMIT bindeado. PDO con emulate_prepares=false exige
// que el LIMIT se pase como PDO::PARAM_INT; con emulate_prepares=true acepta
// cualquier scalar. Usamos bindValue explícito por compatibilidad.
$stmt = $pdo->prepare(
    'DELETE FROM audit_log
     WHERE timestamp < DATE_SUB(NOW(), INTERVAL :retention_days DAY)
     LIMIT :chunk_size'
);
$stmt->bindValue(':retention_days', $retentionDays, PDO::PARAM_INT);
$stmt->bindValue(':chunk_size', $chunkSize, PDO::PARAM_INT);

try {
    do {
        $stmt->execute();
        $deleted = $stmt->rowCount();
        $totalDeleted += $deleted;
        $iterations++;
    } while ($deleted === $chunkSize && $iterations < $maxIterations);

    logPurgeAuditJob('Purga completada', [
        'retention_days' => $retentionDays,
        'rows_deleted' => $totalDeleted,
        'iterations' => $iterations,
    ]);
} catch (Throwable $e) {
    logPurgeAuditJob('Error durante la purga', [
        'error' => $e->getMessage(),
        'rows_deleted_before_error' => $totalDeleted,
    ]);
    exit(1);
}
