<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/cors.php';
applyCors();
handlePreflight();
require_once dirname(__DIR__) . '/../config/db.php';
require_once dirname(__DIR__) . '/../config/auth.php';
require_once dirname(__DIR__) . '/../lib/env_loader.php';
require_once dirname(__DIR__) . '/../lib/signature_access.php';


loadEnv(dirname(__DIR__) . '/../.env');

$context = requireAuthenticatedUser($pdo);
$userId = (int) ($context['local']['iduser'] ?? 0);
$propertyId = (int) ($_POST['property_id'] ?? 0);

if ($userId <= 0 || $propertyId <= 0) {
    respondJson(422, ['success' => false, 'message' => 'Faltan datos.']);
}

$summary = fetchSignatureSummary($pdo, $userId, $propertyId);
$allowed = $summary['status'] === 'validado';

respondJson(200, [
    'success' => true,
    'allowed' => $allowed,
    'status' => $summary['status'],
    'message' => $summary['message'],
    'signed_documents' => $summary['total_signed'],
    'nda_count' => $summary['nda_count'],
    'loi_count' => $summary['loi_count'],
    'admin_validated' => $summary['admin_validated'],
]);
