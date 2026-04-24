-- =============================================================================
-- Feature: Buyer Intents (matchmaking comprador ↔ vendedor)
-- =============================================================================
-- Cuando un comprador busca propiedades y ninguna encaja, puede registrar un
-- "intent" con sus criterios. El sistema notifica a todos los usuarios con un
-- botón "Subir" que lleva al formulario de alta. Cuando alguien sube una
-- propiedad que cumple los criterios, se notifica al comprador con un botón
-- "Ver" que lleva a la ficha del activo.
-- =============================================================================

CREATE TABLE IF NOT EXISTS buyer_intents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    buyer_user_id INT UNSIGNED NOT NULL,

    category VARCHAR(100) DEFAULT NULL,
    city VARCHAR(150) DEFAULT NULL,
    max_price DECIMAL(14,2) DEFAULT NULL,
    min_m2 INT DEFAULT NULL,

    criteria_json JSON DEFAULT NULL,
    criteria_summary VARCHAR(500) DEFAULT NULL,

    status ENUM('active','matched','cancelled') NOT NULL DEFAULT 'active',
    matched_property_id INT UNSIGNED DEFAULT NULL,
    matched_at DATETIME DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_buyer_intents_buyer (buyer_user_id),
    INDEX idx_buyer_intents_status (status),
    INDEX idx_buyer_intents_category_city (category, city)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columna para que las notificaciones lleven un botón con destino.
ALTER TABLE notifications
    ADD COLUMN action_url VARCHAR(500) DEFAULT NULL AFTER related_request_id;
