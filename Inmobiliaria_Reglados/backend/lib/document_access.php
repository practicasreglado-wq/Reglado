<?php
declare(strict_types=1);

/**
 * Acceso del comprador (buyer) a la documentación de una propiedad.
 *
 * Modela el flujo de gating: para que un comprador pueda ver el dossier
 * completo / contactar al vendedor / agendar firma, primero tiene que:
 *  1) Descargar NDA y LOI (registrado en `buyer_property_document_download_progress`).
 *  2) Subir esos documentos firmados (gestionado en api/upload_signed_documents.php).
 *  3) Que el admin valide la firma (gestionado en api/approve_signed_documents.php).
 *
 * Este archivo trabaja con dos tablas:
 *  - `buyer_property_access`: una fila por (propiedad, comprador) cuando ya
 *    tiene acceso. Sus columnas (dossier_unlocked, contact_unlocked, etc.)
 *    son flags que el admin va activando según el progreso.
 *  - `buyer_property_document_download_progress`: tracking de qué legal
 *    docs (NDA/LOI) ha descargado para no dejarle subir firmados sin haber
 *    descargado primero las plantillas.
 */

/**
 * Lee la fila de buyer_property_access (o null si no existe). Esta fila se
 * crea automáticamente con `ensureBuyerPropertyAccess()` cuando el comprador
 * ya ha descargado ambos documentos legales.
 */
function fetchBuyerPropertyAccess(PDO $pdo, int $propertyId, int $buyerUserId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT * FROM buyer_property_access
         WHERE property_id = :property_id AND buyer_user_id = :buyer_user_id
         LIMIT 1'
    );
    $stmt->execute([
        'property_id' => $propertyId,
        'buyer_user_id' => $buyerUserId,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * Lee el progreso de descarga de docs legales del comprador para una
 * propiedad. Devuelve null si todavía no ha descargado nada.
 */
function fetchBuyerPropertyDocumentDownloadProgress(PDO $pdo, int $propertyId, int $buyerUserId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT * FROM buyer_property_document_download_progress
         WHERE property_id = :property_id AND buyer_user_id = :buyer_user_id
         LIMIT 1'
    );
    $stmt->execute([
        'property_id' => $propertyId,
        'buyer_user_id' => $buyerUserId,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * Comprueba si el comprador ya descargó NDA y LOI. Devuelve false si
 * $progress es null (nunca descargó nada) o si falta alguno.
 */
function buyerHasDownloadedBothLegalDocuments(?array $progress): bool
{
    if ($progress === null) {
        return false;
    }

    return (int) ($progress['nda_downloaded'] ?? 0) === 1
        && (int) ($progress['loi_downloaded'] ?? 0) === 1;
}

/**
 * Registra la descarga de un documento legal (NDA/LOI) en una tabla auxiliar.
 *
 * @return array<string,mixed>
 */
function markBuyerPropertyLegalDocumentDownloaded(PDO $pdo, int $propertyId, int $buyerUserId, string $documentType): array
{
    $doc = strtolower(trim($documentType));
    if (!in_array($doc, ['nda', 'loi'], true)) {
        return fetchBuyerPropertyDocumentDownloadProgress($pdo, $propertyId, $buyerUserId) ?? [];
    }

    $downloadedColumn = $doc === 'nda' ? 'nda_downloaded' : 'loi_downloaded';
    $downloadedAtColumn = $doc === 'nda' ? 'nda_downloaded_at' : 'loi_downloaded_at';

    $sql = '
        INSERT INTO buyer_property_document_download_progress (
            property_id,
            buyer_user_id,
            ' . $downloadedColumn . ',
            ' . $downloadedAtColumn . '
        ) VALUES (
            :property_id,
            :buyer_user_id,
            1,
            NOW()
        )
        ON DUPLICATE KEY UPDATE
            ' . $downloadedColumn . ' = 1,
            ' . $downloadedAtColumn . ' = COALESCE(' . $downloadedAtColumn . ', NOW()),
            updated_at = CURRENT_TIMESTAMP
    ';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'property_id' => $propertyId,
        'buyer_user_id' => $buyerUserId,
    ]);

    return fetchBuyerPropertyDocumentDownloadProgress($pdo, $propertyId, $buyerUserId) ?? [];
}

/**
 * Marca NDA+LOI como descargados (útil cuando el flujo implica que el usuario ya dispone de ambos documentos).
 *
 * @return array<string,mixed>
 */
function markBuyerPropertyAllLegalDocumentsDownloaded(PDO $pdo, int $propertyId, int $buyerUserId): array
{
    $stmt = $pdo->prepare('
        INSERT INTO buyer_property_document_download_progress (
            property_id,
            buyer_user_id,
            nda_downloaded,
            loi_downloaded,
            nda_downloaded_at,
            loi_downloaded_at
        ) VALUES (
            :property_id,
            :buyer_user_id,
            1,
            1,
            NOW(),
            NOW()
        )
        ON DUPLICATE KEY UPDATE
            nda_downloaded = 1,
            loi_downloaded = 1,
            nda_downloaded_at = COALESCE(nda_downloaded_at, NOW()),
            loi_downloaded_at = COALESCE(loi_downloaded_at, NOW()),
            updated_at = CURRENT_TIMESTAMP
    ');
    $stmt->execute([
        'property_id' => $propertyId,
        'buyer_user_id' => $buyerUserId,
    ]);

    return fetchBuyerPropertyDocumentDownloadProgress($pdo, $propertyId, $buyerUserId) ?? [];
}

/**
 * Crea la fila de buyer_property_access si no existía Y el comprador ya
 * descargó NDA + LOI. Si no cumple ese gate, devuelve [] (sin acceso). Es
 * idempotente: llamarlo varias veces no duplica filas (UPSERT por clave
 * compuesta).
 */
function ensureBuyerPropertyAccess(PDO $pdo, int $propertyId, int $buyerUserId): array
{
    $existing = fetchBuyerPropertyAccess($pdo, $propertyId, $buyerUserId);
    if ($existing !== null) {
        return $existing;
    }

    $progress = fetchBuyerPropertyDocumentDownloadProgress($pdo, $propertyId, $buyerUserId);
    if (!buyerHasDownloadedBothLegalDocuments($progress)) {
        return [];
    }

    $insert = $pdo->prepare('
        INSERT INTO buyer_property_access (property_id, buyer_user_id)
        VALUES (:property_id, :buyer_user_id)
        ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP
    ');
    $insert->execute([
        'property_id' => $propertyId,
        'buyer_user_id' => $buyerUserId,
    ]);

    return fetchBuyerPropertyAccess($pdo, $propertyId, $buyerUserId) ?? [];
}

/**
 * Actualiza columnas concretas de buyer_property_access (típicamente flags
 * como dossier_unlocked, contact_unlocked) y devuelve la fila refrescada.
 *
 * ⚠️ $updates va directo a SQL — solo pasar nombres de columna validados a
 * mano, NO valores que vengan del request.
 *
 * @param array<string,int> $updates
 */
function updateBuyerPropertyAccess(PDO $pdo, int $propertyId, int $buyerUserId, array $updates): array
{
    if ($updates === []) {
        return fetchBuyerPropertyAccess($pdo, $propertyId, $buyerUserId) ?? [];
    }

    $columns = [];
    $params = [];
    foreach ($updates as $column => $value) {
        $columns[] = "`$column` = :$column";
        $params[$column] = $value;
    }

    $params['property_id'] = $propertyId;
    $params['buyer_user_id'] = $buyerUserId;

    $sql = 'UPDATE buyer_property_access SET ' . implode(', ', $columns) . ', updated_at = CURRENT_TIMESTAMP
            WHERE property_id = :property_id AND buyer_user_id = :buyer_user_id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return fetchBuyerPropertyAccess($pdo, $propertyId, $buyerUserId) ?? [];
}
