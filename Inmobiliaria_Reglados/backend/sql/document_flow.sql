CREATE TABLE IF NOT EXISTS documentos_firmados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    user_id INT NOT NULL,
    document_type ENUM('nda','loi') NOT NULL,
    original_file VARCHAR(255) NULL,
    signed_file VARCHAR(255) NOT NULL,
    signature_detected TINYINT(1) NOT NULL DEFAULT 0,
    office_status ENUM('pendiente','aceptado','rechazado') NOT NULL DEFAULT 'pendiente',
    office_reviewed_by INT NULL,
    office_reviewed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_documentos_property (property_id),
    INDEX idx_documentos_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS buyer_property_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    buyer_user_id INT NOT NULL,
    nda_uploaded TINYINT(1) NOT NULL DEFAULT 0,
    loi_uploaded TINYINT(1) NOT NULL DEFAULT 0,
    nda_approved TINYINT(1) NOT NULL DEFAULT 0,
    loi_approved TINYINT(1) NOT NULL DEFAULT 0,
    dossier_unlocked TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_buyer_property (property_id, buyer_user_id),
    INDEX idx_access_property (property_id),
    INDEX idx_access_buyer (buyer_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
