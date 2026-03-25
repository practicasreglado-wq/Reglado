ALTER TABLE documentos_firmados
    ADD COLUMN document_valid TINYINT(1) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS signed_document_review_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    buyer_user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    approved_at DATETIME NULL,
    approved_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_review_token (token_hash),
    INDEX idx_review_property (property_id),
    INDEX idx_review_buyer (buyer_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
