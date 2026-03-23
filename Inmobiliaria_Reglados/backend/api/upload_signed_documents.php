<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/../config/db.php';
require_once dirname(__DIR__) . '/../config/auth.php';
require_once dirname(__DIR__) . '/../lib/env_loader.php';
require_once dirname(__DIR__) . '/../utils/verify_pdf_signature.php';
require_once dirname(__DIR__) . '/../send_mail.php';
require_once __DIR__ . '/../config/cors.php';
applyCors();
handlePreflight();

loadEnv(dirname(__DIR__) . '/../.env');

$context = requireAuthenticatedUser($pdo);
$auth = $context['auth'] ?? [];
$userId = (int) ($context['local']['iduser'] ?? 0);
$propertyId = (int) ($_POST['property_id'] ?? 0);

if ($userId <= 0 || $propertyId <= 0) {
    respondJson(422, ['success' => false, 'message' => 'Datos incompletos.']);
}

$propertyStmt = $pdo->prepare('SELECT id, titulo, ciudad, zona, precio, dossier_file FROM propiedades WHERE id = :id LIMIT 1');
$propertyStmt->execute(['id' => $propertyId]);
$property = $propertyStmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    respondJson(404, ['success' => false, 'message' => 'Propiedad no encontrada.']);
}

$userNameParts = array_filter([
    $auth['nombre'] ?? $auth['first_name'] ?? '',
    $auth['apellidos'] ?? $auth['last_name'] ?? '',
    $auth['name'] ?? '',
]);
$userName = trim(implode(' ', $userNameParts));
$userEmail = $auth['email'] ?? '';
$userPhone = $auth['phone'] ?? '';

$required = [
    'nda' => 'Acuerdo de confidencialidad (NDA)',
    'loi' => 'Carta de intención (LOI)',
];

$files = [];
foreach ($required as $key => $label) {
    if (!isset($_FILES[$key]) || $_FILES[$key]['error'] !== UPLOAD_ERR_OK) {
        respondJson(422, ['success' => false, 'message' => "$label no fue enviado correctamente."]);
    }

    $files[$key] = $_FILES[$key];
}

$signedDir = dirname(__DIR__) . '/../uploads/signed';
if (!is_dir($signedDir)) {
    mkdir($signedDir, 0777, true);
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$savedPaths = [];
$attachments = [];

$token = hash('sha256', random_bytes(32) . $userId . $propertyId . microtime(true));
$expiresAt = (new DateTimeImmutable('+72 hours'))->format('Y-m-d H:i:s');

$insert = $pdo->prepare('
    INSERT INTO documentos_firmados
        (user_id, propiedad_id, tipo_documento, file_path, firmado_valido, ip, user_agent, validation_token, validation_token_expires_at, validado_admin)
    VALUES
        (:user_id, :propiedad_id, :tipo, :file_path, :firmado, :ip, :user_agent, :token, :expires, :validated)
');

try {
    $pdo->beginTransaction();

    foreach ($files as $type => $meta) {
        $tmpPath = $meta['tmp_name'];
        $extension = strtolower(pathinfo($meta['name'], PATHINFO_EXTENSION));

        if ($extension !== 'pdf') {
            throw new RuntimeException('Solo se aceptan documentos PDF firmados.');
        }

        if (!isPdfSigned($tmpPath)) {
            throw new RuntimeException(sprintf('El documento %s no contiene una firma digital válida.', $required[$type]));
        }

        $uniqueName = sprintf('%s_%d_%s.pdf', $type, $propertyId, bin2hex(random_bytes(6)));
        $destination = $signedDir . DIRECTORY_SEPARATOR . $uniqueName;

        if (!move_uploaded_file($tmpPath, $destination)) {
            copy($tmpPath, $destination);
        }

        $relativePath = 'signed/' . $uniqueName;
        $savedPaths[] = $destination;
        $attachments[] = [
            'path' => $destination,
            'name' => sprintf('%s_%s.pdf', strtoupper($type), preg_replace('/[^A-Za-z0-9._-]/', '_', $property['titulo'] ?? 'documento')),
        ];

        $insert->execute([
            'user_id' => $userId,
            'propiedad_id' => $propertyId,
            'tipo' => $type,
            'file_path' => $relativePath,
            'firmado' => 1,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'token' => $token,
            'expires' => $expiresAt,
            'validated' => 0,
        ]);
    }

    $activationLink = buildValidationLink($token);

    $companyEmail = 'realstate@regladoconsultores.com';
    $subject = sprintf(
        'Documentos firmados propiedad #%d - %s',
        $propertyId,
        $property['titulo'] ?? 'Sin título'
    );

    $body = "<h2>Documentos firmados recibidos</h2>";
    $body .= "<p>Usuario: " . htmlspecialchars($userName ?: 'Sin nombre') . "</p>";
    $body .= "<p>Email: " . htmlspecialchars($userEmail ?: 'Sin email') . "</p>";
    $body .= "<p>Teléfono: " . htmlspecialchars($userPhone ?: 'Sin teléfono') . "</p>";
    $body .= "<hr>";
    $body .= "<p><strong>Propiedad:</strong></p><ul>";
    $body .= "<li>ID: {$propertyId}</li>";
    $body .= "<li>Ciudad: " . htmlspecialchars($property['ciudad'] ?? 'No disponible') . "</li>";
    $body .= "<li>Zona: " . htmlspecialchars($property['zona'] ?? 'No disponible') . "</li>";
    $body .= "<li>Precio: " . number_format((float) ($property['precio'] ?? 0), 2, ',', '.') . " €</li>";
    $body .= "</ul>";
    $body .= "<p>Validar documentos firmados: <a href=\"" . htmlspecialchars($activationLink) . "\">" . htmlspecialchars($activationLink) . "</a></p>";
    $body .= "<p>Token válido hasta: " . $expiresAt . "</p>";

    sendNotificationEmail($companyEmail, $subject, $body, $userEmail ?: null, $attachments);

    $pdo->commit();

    respondJson(200, [
        'success' => true,
        'message' => 'Documentos firmados correctamente. Espera que el equipo valide tu firma.',
        'status' => 'firmado',
        'dossier_url' => buildUploadsUrl($property['dossier_file'] ?? ''),
    ]);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    foreach ($savedPaths as $path) {
        if (is_file($path)) {
            @unlink($path);
        }
    }

    respondJson(500, [
        'success' => false,
        'message' => 'No se pudieron subir los documentos: ' . $exception->getMessage(),
    ]);
}

function buildUploadsUrl(string $fileName): ?string
{
    $clean = trim((string) $fileName);
    if ($clean === '') {
        return null;
    }

    $origin =
        $_SERVER['HTTP_ORIGIN']
        ?? ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
            . '://'
            . ($_SERVER['HTTP_HOST'] ?? 'localhost'));

    $basePath = dirname(dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $basePath = str_replace('\\', '/', $basePath);
    $basePath = rtrim($basePath, '/');

    return rtrim($origin, '/') . $basePath . '/uploads/' . rawurlencode($clean);
}

function buildValidationLink(string $token): string
{
    $origin =
        $_SERVER['HTTP_ORIGIN']
        ?? ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
            . '://'
            . ($_SERVER['HTTP_HOST'] ?? 'localhost'));

    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $scriptDir = str_replace('\\', '/', $scriptDir);
    $scriptDir = rtrim($scriptDir, '/');
    $activationPath = $scriptDir . '/activar.php';

    return rtrim($origin, '/') . $activationPath . '?token=' . rawurlencode($token);
}
