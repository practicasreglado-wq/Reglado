<?php
declare(strict_types=1);

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

function ensureBuyerPropertyAccess(PDO $pdo, int $propertyId, int $buyerUserId): array
{
    $existing = fetchBuyerPropertyAccess($pdo, $propertyId, $buyerUserId);
    if ($existing !== null) {
        return $existing;
    }

    $insert = $pdo->prepare('
        INSERT INTO buyer_property_access (property_id, buyer_user_id)
        VALUES (:property_id, :buyer_user_id)
    ');
    $insert->execute([
        'property_id' => $propertyId,
        'buyer_user_id' => $buyerUserId,
    ]);

    return fetchBuyerPropertyAccess($pdo, $propertyId, $buyerUserId) ?? [];
}

/**
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
