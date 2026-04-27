<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/cors.php';
applyCors();
handlePreflight();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../lib/env_loader.php';
require_once __DIR__ . '/../lib/audit.php';

loadEnv(__DIR__ . '/../.env');

$context = requireAuthenticatedUser($pdo);
$auth = $context['auth'] ?? [];
$userId = (int) (
    $context['local']['iduser']
    ?? $context['local']['id']
    ?? $context['auth']['id']
    ?? 0
);
$role = strtolower((string) ($auth['role'] ?? ''));
$isAdmin = $role === 'admin';

if ($userId <= 0) {
    http_response_code(401);
    exit('Debes iniciar sesión.');
}

$relative = trim((string) ($_GET['file'] ?? ''));
if ($relative === '') {
    http_response_code(400);
    exit('Archivo no indicado.');
}

$relative = str_replace('\\', '/', $relative);
$relative = ltrim($relative, '/');

$baseDir = realpath(__DIR__ . '/../uploads');
if ($baseDir === false) {
    http_response_code(500);
    exit('Directorio base no disponible.');
}

$filePath = realpath($baseDir . DIRECTORY_SEPARATOR . $relative);

// Fallback por si en la BD viene "dossiers/archivo.pdf" pero el archivo real
// está en la raíz de uploads (o viceversa).
if ($filePath === false) {
    $filePath = realpath($baseDir . DIRECTORY_SEPARATOR . basename($relative));
}

if ($filePath === false || !is_file($filePath) || !is_readable($filePath)) {
    http_response_code(404);
    exit('Archivo no encontrado.');
}

// Path traversal guard: asegurarse de que el archivo resuelto sigue dentro
// de uploads/.
if (!str_starts_with($filePath, $baseDir . DIRECTORY_SEPARATOR)) {
    http_response_code(403);
    exit('Acceso denegado.');
}

// Ownership: el archivo solicitado debe ser el dossier de una propiedad a la
// que el usuario tenga acceso desbloqueado en buyer_property_access. Los
// admins pueden saltarse esta comprobación.
$propertyId = null;

$documentKind = 'dossier';

if ($isAdmin) {
    $dossierStmt = $pdo->prepare('
        SELECT id
        FROM propiedades
        WHERE dossier_file = :file_relative OR dossier_file = :file_base
        LIMIT 1
    ');
    $dossierStmt->execute([
        'file_relative' => $relative,
        'file_base'     => basename($relative),
    ]);
    $dossierRow = $dossierStmt->fetch(PDO::FETCH_ASSOC);

    if ($dossierRow) {
        $propertyId = (int) $dossierRow['id'];
        $documentKind = 'dossier';
    } else {
        $signedStmt = $pdo->prepare("
            SELECT propiedad_id,
                   CASE
                       WHEN nda_file_path = :f1a OR nda_file_path = :f1b THEN 'nda'
                       WHEN loi_file_path = :f2a OR loi_file_path = :f2b THEN 'loi'
                       ELSE NULL
                   END AS kind
            FROM documentos_firmados
            WHERE nda_file_path = :f3a OR nda_file_path = :f3b
               OR loi_file_path = :f4a OR loi_file_path = :f4b
            LIMIT 1
        ");
        $signedStmt->execute([
            'f1a' => $relative, 'f1b' => basename($relative),
            'f2a' => $relative, 'f2b' => basename($relative),
            'f3a' => $relative, 'f3b' => basename($relative),
            'f4a' => $relative, 'f4b' => basename($relative),
        ]);
        $signedRow = $signedStmt->fetch(PDO::FETCH_ASSOC);

        if ($signedRow) {
            $propertyId = (int) $signedRow['propiedad_id'];
            $documentKind = (string) ($signedRow['kind'] ?? 'signed');
        }
    }
} else {
    $accessStmt = $pdo->prepare('
        SELECT a.property_id
        FROM buyer_property_access a
        INNER JOIN propiedades p ON p.id = a.property_id
        WHERE a.buyer_user_id = :user_id
          AND a.dossier_unlocked = 1
          AND (p.dossier_file = :file_relative OR p.dossier_file = :file_base)
        LIMIT 1
    ');
    $accessStmt->execute([
        'user_id'       => $userId,
        'file_relative' => $relative,
        'file_base'     => basename($relative),
    ]);
    $accessRow = $accessStmt->fetch(PDO::FETCH_ASSOC);

    if (!$accessRow) {
        http_response_code(403);
        exit('No tienes acceso a este documento.');
    }

    $propertyId = (int) $accessRow['property_id'];
}

auditLog($pdo, 'document.' . $documentKind . '.download', array_merge(
    auditContextFromAuth($auth, $userId),
    [
        'resource_type' => 'document',
        'resource_id'   => $documentKind . ':' . ($propertyId ?? 'unknown'),
        'metadata'      => [
            'file'        => basename($filePath),
            'property_id' => $propertyId,
            'via_admin'   => $isAdmin,
            'kind'        => $documentKind,
        ],
    ]
));

$filename = basename($filePath);

if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Description: File Transfer');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: public');
header('Expires: 0');
header('Content-Length: ' . (string) filesize($filePath));

readfile($filePath);
exit;
