-- =========================
-- TABLA PROPIEDADES (LIMPIA)
-- =========================
CREATE TABLE IF NOT EXISTS propiedades (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Datos básicos (CLAVE)
    tipo_propiedad VARCHAR(150) NOT NULL,
    ciudad VARCHAR(150) NOT NULL,
    zona VARCHAR(150) NOT NULL,
    metros_cuadrados INT NOT NULL,
    precio DECIMAL(15,2) NOT NULL,

    -- Opcionales (mínimos útiles)
    direccion VARCHAR(255) DEFAULT NULL,
    categoria VARCHAR(50) NOT NULL DEFAULT 'Captada',

    -- Datos estructurados IA (solo si quieres guardar raw)
    caracteristicas_json JSON DEFAULT NULL,

    -- Documentos generados
    dossier_file VARCHAR(255) DEFAULT NULL,
    confidentiality_file VARCHAR(255) DEFAULT NULL,
    intention_file VARCHAR(255) DEFAULT NULL,

    -- Relaciones
    captador_id INT NULL,
    owner_user_id INT NOT NULL,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Índices
    INDEX idx_propiedades_owner (owner_user_id),
    INDEX idx_propiedades_captador (captador_id),
    INDEX idx_propiedades_ciudad (ciudad),
    INDEX idx_propiedades_tipo (tipo_propiedad)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================
-- TABLA CAPTADORES
-- =========================
CREATE TABLE IF NOT EXISTS captadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_captadores_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================
-- TABLA ACTIVOS RECIBIDOS
-- =========================
CREATE TABLE IF NOT EXISTS activos_recibidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    origen VARCHAR(50) NOT NULL,
    email_remitente VARCHAR(255),
    texto_recibido LONGTEXT NOT NULL,
    metadata JSON NULL,
    procesado VARCHAR(20) NOT NULL DEFAULT 'pendiente',
    captador_id INT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL DEFAULT NULL,

    INDEX idx_activos_estado (procesado),
    INDEX idx_activos_captador (captador_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================
-- TABLA FAVORITOS
-- =========================
CREATE TABLE IF NOT EXISTS propiedades_favoritas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    propiedad_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uniq_user_propiedad (user_id, propiedad_id),
    INDEX idx_favoritas_user (user_id),
    INDEX idx_favoritas_propiedad (propiedad_id),

    FOREIGN KEY (propiedad_id) REFERENCES propiedades(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================
-- HISTORIAL DE BÚSQUEDAS
-- =========================
CREATE TABLE IF NOT EXISTS search_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    categoria VARCHAR(100) NOT NULL,
    preferences JSON NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_search_history_user (user_id),
    INDEX idx_search_history_fecha (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =========================
-- DOCUMENTOS FIRMADOS
-- =========================
CREATE TABLE documentos_firmados (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    propiedad_id INT UNSIGNED NOT NULL,

    nda_file_path VARCHAR(500) DEFAULT NULL,
    loi_file_path VARCHAR(500) DEFAULT NULL,

    nda_subido_at DATETIME DEFAULT NULL,
    loi_subido_at DATETIME DEFAULT NULL,

    nda_valido TINYINT(1) NOT NULL DEFAULT 0,
    loi_valido TINYINT(1) NOT NULL DEFAULT 0,

    validado_admin TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uniq_user_propiedad (user_id, propiedad_id)
);

ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE activos_recibidos 
ADD COLUMN content_hash VARCHAR(64) NULL,
ADD INDEX idx_content_hash (content_hash);

ALTER TABLE activos_recibidos 
ADD COLUMN message_id VARCHAR(255) NULL;

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

CREATE TABLE IF NOT EXISTS user_match_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category VARCHAR(50) NOT NULL,
    answers_json JSON NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_used_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uniq_user_match (user_id),
    INDEX idx_user_match_category (category),
    INDEX idx_user_match_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
