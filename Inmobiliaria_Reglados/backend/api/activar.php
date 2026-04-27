<?php
declare(strict_types=1);

/**
 * Endpoint de "activación" de docs firmados vía token corto (legacy).
 *
 * Variante anterior del flujo de aprobación: el comprador (no el revisor)
 * recibe un token y al pulsar el enlace marca sus propios documentos como
 * validados. Mantenido por compatibilidad con tokens emitidos antes de
 * migrar al flujo del revisor (approve_signed_documents.php).
 *
 * Para flujos nuevos, usar approve_document_review_admin.php /
 * reject_document_review_admin.php — tienen mejores garantías de seguridad.
 */

require_once __DIR__ . '/../config/cors.php';
applyCors();

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/lib/env_loader.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/send_mail.php';
require_once __DIR__ . '/../lib/error_reporting.php';

applyAuthCors();
handlePreflight();

loadEnv(__DIR__ . '/.env');

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
if ($token === '') {
    respondJson(422, ['success' => false, 'message' => 'Token de validación no proporcionado.']);
}

$stmt = $pdo->prepare('
    SELECT
        user_id,
        propiedad_id,
        validation_token_expires_at,
        MIN(validado_admin) AS admin_status
    FROM documentos_firmados
    WHERE validation_token = :token
      AND firmado_valido = 1
    LIMIT 1
');
$stmt->execute(['token' => $token]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record) {
    respondJson(404, ['success' => false, 'message' => 'Token inválido o ya utilizado.']);
}

if ((int) ($record['admin_status'] ?? 0) === 1) {
    respondJson(409, ['success' => false, 'message' => 'Los documentos ya fueron validados anteriormente.']);
}

if (!empty($record['validation_token_expires_at'])) {
    $expires = new DateTimeImmutable($record['validation_token_expires_at']);
    if ($expires < new DateTimeImmutable()) {
        respondJson(410, ['success' => false, 'message' => 'El token ha expirado.']);
    }
}

$userId = (int) $record['user_id'];
$propertyId = (int) $record['propiedad_id'];

$userStmt = $pdo->prepare('SELECT nombre, apellidos, email FROM users WHERE id = :id LIMIT 1');
$userStmt->execute(['id' => $userId]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

$propertyStmt = $pdo->prepare('SELECT titulo FROM propiedades WHERE id = :id LIMIT 1');
$propertyStmt->execute(['id' => $propertyId]);
$property = $propertyStmt->fetch(PDO::FETCH_ASSOC);

$userNameParts = array_filter([
    $user['nombre'] ?? '',
    $user['apellidos'] ?? '',
]);
$userName = trim(implode(' ', $userNameParts));
$userEmail = $user['email'] ?? '';

try {
    $pdo->beginTransaction();

    $update = $pdo->prepare('
        UPDATE documentos_firmados
        SET validado_admin = 1,
            validation_token = NULL,
            validation_token_expires_at = NULL
        WHERE validation_token = :token
    ');
    $update->execute(['token' => $token]);

    $subject = 'Firmas validadas correctamente';
    $body = "<p>Hola " . htmlspecialchars($userName ?: 'usuario') . ",</p>";
    $body .= "<p>Hemos confirmado la validez de tus documentos NDA y LOI. Ya puedes descargar el dossier de la propiedad.</p>";
    if (!empty($property['titulo'])) {
        $body .= "<p><strong>Propiedad:</strong> " . htmlspecialchars($property['titulo']) . "</p>";
    }

    if ($userEmail !== '') {
        sendNotificationEmail($userEmail, $subject, $body);
    }

    $pdo->commit();

    respondJson(200, [
        'success' => true,
        'message' => 'Documentos validados correctamente.',
        'status' => 'validado',
    ]);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $errorId = logAndReferenceError('activar', $exception);
    respondJson(500, [
        'success' => false,
        'message' => 'No se pudo validar el token. Referencia: ' . $errorId,
    ]);
}
