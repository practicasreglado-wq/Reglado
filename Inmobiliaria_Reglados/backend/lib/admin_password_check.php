<?php
declare(strict_types=1);

require_once __DIR__ . '/error_reporting.php';

/**
 * Verifica la contraseña del admin contra regladousers.users y aplica rate
 * limit por scope (5 intentos fallidos en 15 minutos).
 *
 * Si la contraseña es válida → limpia el contador y devuelve el control.
 * Si falta, es incorrecta, o se supera el rate limit → responde con HTTP
 * apropiado y aborta la ejecución (respondJson + exit).
 *
 * El llamador debe haber comprobado ya que el usuario es admin y pasar el
 * ID de admin y el scope único de la acción (ej. 'admin_appointment_action',
 * 'admin_role_approve', etc.) — el scope permite tener contadores de rate
 * limit separados por acción.
 */
function requireAdminPasswordConfirmation(int $adminId, string $adminPassword, string $scope): void
{
    if ($adminPassword === '') {
        respondJson(422, [
            'success' => false,
            'message' => 'Debes confirmar tu contraseña para realizar esta acción.',
        ]);
    }

    try {
        $pdoAuth = new PDO(
            sprintf(
                'mysql:host=%s;port=%s;dbname=regladousers;charset=utf8mb4',
                (string) getenv('DB_HOST'),
                (string) getenv('DB_PORT')
            ),
            (string) getenv('DB_USER'),
            (string) getenv('DB_PASS'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );

        $rateKeyHash = hash('sha256', $scope . '|' . $adminId);
        $rateWindowSeconds = 900;
        $rateMaxFailures = 5;

        $rlRead = $pdoAuth->prepare('SELECT id, attempts, updated_at FROM rate_limits WHERE key_hash = ? AND scope_name = ? LIMIT 1');
        $rlRead->execute([$rateKeyHash, $scope]);
        $rlRow = $rlRead->fetch();

        $nowTs = time();
        $withinWindow = $rlRow && (strtotime((string) $rlRow['updated_at']) ?: 0) >= $nowTs - $rateWindowSeconds;

        if ($withinWindow && (int) $rlRow['attempts'] >= $rateMaxFailures) {
            respondJson(429, [
                'success' => false,
                'message' => 'Demasiados intentos fallidos. Espera unos minutos antes de volver a probar.',
            ]);
        }

        $adminStmt = $pdoAuth->prepare('SELECT password FROM users WHERE id = :id LIMIT 1');
        $adminStmt->execute(['id' => $adminId]);
        $adminRow = $adminStmt->fetch();

        if (!$adminRow || !password_verify($adminPassword, (string) $adminRow['password'])) {
            if (!$rlRow) {
                $pdoAuth->prepare('INSERT INTO rate_limits(key_hash, scope_name, attempts, updated_at, created_at) VALUES(?, ?, 1, NOW(), NOW())')
                        ->execute([$rateKeyHash, $scope]);
            } elseif (!$withinWindow) {
                $pdoAuth->prepare('UPDATE rate_limits SET attempts = 1, updated_at = NOW() WHERE id = ?')
                        ->execute([(int) $rlRow['id']]);
            } else {
                $pdoAuth->prepare('UPDATE rate_limits SET attempts = attempts + 1, updated_at = NOW() WHERE id = ?')
                        ->execute([(int) $rlRow['id']]);
            }
            respondJson(401, [
                'success' => false,
                'message' => 'Contraseña incorrecta.',
            ]);
        }

        // Contraseña correcta: limpiar contador
        $pdoAuth->prepare('DELETE FROM rate_limits WHERE key_hash = ? AND scope_name = ?')
                ->execute([$rateKeyHash, $scope]);
    } catch (Throwable $e) {
        $errorId = logAndReferenceError('admin_password_check.' . $scope, $e);
        respondJson(500, [
            'success' => false,
            'message' => 'No se pudo verificar la contraseña. Referencia: ' . $errorId,
        ]);
    }
}
