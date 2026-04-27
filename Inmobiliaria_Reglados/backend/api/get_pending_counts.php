<?php
declare(strict_types=1);

/**
 * Devuelve los contadores de "elementos pendientes" para el panel admin
 * (badges del menú lateral): solicitudes Premium, eliminaciones de
 * propiedad, documentos por revisar, citas por gestionar, etc.
 *
 * Endpoint ligero pensado para polling frecuente — solo COUNT(*) por tabla.
 * Solo accesible para role=admin.
 */

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once __DIR__ . '/../config/cors.php';
require_once dirname(__DIR__) . '/lib/error_reporting.php';

applyCors();
handlePreflight();

$context = requireAuthenticatedUser($pdo);
$auth = $context['auth'] ?? [];
$role = strtolower((string) ($auth['role'] ?? ''));

if ($role !== 'admin') {
    respondJson(403, [
        'success' => false,
        'message' => 'Acceso restringido. Solo administradores.',
    ]);
}

try {
    $roles = (int) $pdo
        ->query("SELECT COUNT(*) FROM role_promotion_requests WHERE status = 'pending'")
        ->fetchColumn();

    $documents = (int) $pdo
        ->query("SELECT COUNT(*) FROM signed_document_review_tokens WHERE approved_at IS NULL AND expires_at > NOW()")
        ->fetchColumn();

    $purchases = (int) $pdo
        ->query("SELECT COUNT(*) FROM purchase_requests WHERE status = 'pending'")
        ->fetchColumn();

    $appointments = 0;
    try {
        $appointments = (int) $pdo
            ->query("SELECT COUNT(*) FROM purchase_appointments WHERE status = 'scheduled'")
            ->fetchColumn();
    } catch (Throwable $_) {
        // Tabla puede no existir aún en entornos sin la migración aplicada
    }

    $propertyDeletions = 0;
    try {
        $propertyDeletions = (int) $pdo
            ->query("SELECT COUNT(*) FROM property_deletion_requests WHERE status = 'pending'")
            ->fetchColumn();
    } catch (Throwable $_) {
        // Tabla puede no existir aún en entornos sin la migración aplicada
    }

    respondJson(200, [
        'success' => true,
        'total'   => $roles + $documents + $purchases + $appointments + $propertyDeletions,
        'counts'  => [
            'roles'              => $roles,
            'documents'          => $documents,
            'purchases'          => $purchases,
            'appointments'       => $appointments,
            'property_deletions' => $propertyDeletions,
        ],
    ]);
} catch (Throwable $e) {
    $errorId = logAndReferenceError('get_pending_counts', $e);
    respondJson(500, [
        'success' => false,
        'message' => 'Error al consultar los contadores. Referencia: ' . $errorId,
    ]);
}
