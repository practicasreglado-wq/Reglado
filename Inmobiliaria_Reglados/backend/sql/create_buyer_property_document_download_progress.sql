-- =========================================
-- TABLA AUXILIAR: buyer_property_document_download_progress
-- Objetivo: registrar el progreso parcial de descargas (NDA/LOI)
-- y permitir crear buyer_property_access SOLO cuando existan ambas descargas.
-- =========================================

CREATE TABLE IF NOT EXISTS buyer_property_document_download_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    buyer_user_id INT NOT NULL,

    nda_downloaded TINYINT(1) NOT NULL DEFAULT 0,
    loi_downloaded TINYINT(1) NOT NULL DEFAULT 0,
    nda_downloaded_at DATETIME NULL,
    loi_downloaded_at DATETIME NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uniq_buyer_property_download_progress (property_id, buyer_user_id),
    INDEX idx_download_progress_property (property_id),
    INDEX idx_download_progress_buyer (buyer_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

