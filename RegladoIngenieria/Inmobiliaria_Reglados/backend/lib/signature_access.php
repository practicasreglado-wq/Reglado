<?php
declare(strict_types=1);

/**
 * Fetches a summary of signed documents for a user/property pair.
 *
 * @return array{
 *     status: string,
 *     message: string,
 *     total_signed: int,
 *     nda_count: int,
 *     loi_count: int,
 *     admin_validated: int
 * }
 */
function fetchSignatureSummary(PDO $pdo, int $userId, int $propertyId): array
{
    $stmt = $pdo->prepare('
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN tipo_documento = \'nda\' THEN 1 ELSE 0 END) AS nda_count,
            SUM(CASE WHEN tipo_documento = \'loi\' THEN 1 ELSE 0 END) AS loi_count,
            MIN(validado_admin) AS admin_validated
        FROM documentos_firmados
        WHERE user_id = :user_id
          AND propiedad_id = :propiedad_id
          AND firmado_valido = 1
    ');

    $stmt->execute([
        'user_id' => $userId,
        'propiedad_id' => $propertyId,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $ndaCount = (int) ($row['nda_count'] ?? 0);
    $loiCount = (int) ($row['loi_count'] ?? 0);
    $total = (int) ($row['total'] ?? 0);
    $adminValidated = $row['admin_validated'] !== null ? (int) $row['admin_validated'] : 0;

    $status = 'pendiente';
    if ($ndaCount > 0 && $loiCount > 0) {
        $status = $adminValidated === 1 ? 'validado' : 'firmado';
    }

    $message = 'Documentos pendientes de firma.';
    if ($status === 'firmado') {
        $message = 'Documentos recibidos. Esperando validación administrativa.';
    } elseif ($status === 'validado') {
        $message = 'Documentos validados. Ya puedes descargar el dossier.';
    }

    return [
        'status' => $status,
        'message' => $message,
        'total_signed' => $total,
        'nda_count' => $ndaCount,
        'loi_count' => $loiCount,
        'admin_validated' => $adminValidated,
    ];
}
