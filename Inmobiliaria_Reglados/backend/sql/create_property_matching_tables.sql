CREATE TABLE IF NOT EXISTS propiedades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria VARCHAR(50) NOT NULL DEFAULT 'Captada',
    titulo VARCHAR(255) NOT NULL DEFAULT '',
    ubicacion_general VARCHAR(255) NOT NULL DEFAULT '',
    tipo_input VARCHAR(60) NOT NULL DEFAULT 'text',
    tipo_propiedad VARCHAR(150) DEFAULT NULL,
    subtipo VARCHAR(150) DEFAULT NULL,
    ciudad VARCHAR(150) DEFAULT NULL,
    zona VARCHAR(150) DEFAULT NULL,
    direccion VARCHAR(255) DEFAULT NULL,
    metros_cuadrados INT NOT NULL DEFAULT 0,
    habitaciones INT NOT NULL DEFAULT 0,
    estado_activo VARCHAR(150) DEFAULT NULL,
    precio DECIMAL(15,2) NOT NULL DEFAULT 0,
    precio_m2 DECIMAL(15,2) DEFAULT NULL,
    ingresos_actuales DECIMAL(15,2) DEFAULT NULL,
    ingresos_estimados DECIMAL(15,2) DEFAULT NULL,
    gastos_estimados DECIMAL(15,2) DEFAULT NULL,
    EBITDA DECIMAL(15,2) DEFAULT NULL,
    cash_flow DECIMAL(15,2) DEFAULT NULL,
    rentabilidad_bruta VARCHAR(50) DEFAULT NULL,
    rentabilidad_neta VARCHAR(50) DEFAULT NULL,
    cap_rate VARCHAR(50) DEFAULT NULL,
    roi VARCHAR(50) DEFAULT NULL,
    payback VARCHAR(50) DEFAULT NULL,
    ocupacion VARCHAR(60) DEFAULT NULL,
    ADR VARCHAR(60) DEFAULT NULL,
    RevPAR VARCHAR(60) DEFAULT NULL,
    analisis TEXT DEFAULT NULL,
    analisis_json LONGTEXT DEFAULT NULL,
    imagen_principal VARCHAR(255) DEFAULT NULL,
    caracteristicas_json JSON NOT NULL,
    dossier_file VARCHAR(255) DEFAULT NULL,
    confidentiality_file VARCHAR(255) DEFAULT NULL,
    intention_file VARCHAR(255) DEFAULT NULL,
    captador_id INT NULL,
    owner_user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_propiedades_categoria (categoria),
    INDEX idx_propiedades_owner (owner_user_id),
    INDEX idx_propiedades_captador (captador_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE propiedades
    ADD COLUMN IF NOT EXISTS tipo_input VARCHAR(60) NOT NULL DEFAULT 'text',
    ADD COLUMN IF NOT EXISTS tipo_propiedad VARCHAR(150) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS subtipo VARCHAR(150) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS ciudad VARCHAR(150) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS zona VARCHAR(150) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS direccion VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS metros_cuadrados INT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS habitaciones INT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS estado_activo VARCHAR(150) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS precio_m2 DECIMAL(15,2) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS ingresos_actuales DECIMAL(15,2) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS ingresos_estimados DECIMAL(15,2) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS gastos_estimados DECIMAL(15,2) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS EBITDA DECIMAL(15,2) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS cash_flow DECIMAL(15,2) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS rentabilidad_bruta VARCHAR(50) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS rentabilidad_neta VARCHAR(50) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS cap_rate VARCHAR(50) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS roi VARCHAR(50) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS payback VARCHAR(50) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS ocupacion VARCHAR(60) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS ADR VARCHAR(60) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS RevPAR VARCHAR(60) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS analisis TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS analisis_json LONGTEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS dossier_file VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS confidentiality_file VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS intention_file VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS captador_id INT NULL,
    ADD INDEX IF NOT EXISTS idx_propiedades_captador (captador_id);

CREATE TABLE IF NOT EXISTS captadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_captadores_email (email),
    INDEX idx_captadores_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activos_recibidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    origen VARCHAR(100) NOT NULL,
    email_remitente VARCHAR(255),
    texto_recibido LONGTEXT NOT NULL,
    metadata JSON NULL,
    procesado VARCHAR(20) NOT NULL DEFAULT 'pendiente',
    captador_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_activos_status (procesado),
    INDEX idx_activos_captador (captador_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS propiedades_favoritas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    propiedad_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_propiedad (user_id, propiedad_id),
    INDEX idx_favoritas_user (user_id),
    INDEX idx_favoritas_propiedad (propiedad_id),
    CONSTRAINT fk_propiedades_favoritas_propiedad
        FOREIGN KEY (propiedad_id) REFERENCES propiedades(id)
        ON DELETE CASCADE
);

ALTER TABLE propiedades_favoritas
    ADD COLUMN IF NOT EXISTS user_id INT NOT NULL,
    ADD COLUMN IF NOT EXISTS propiedad_id INT NOT NULL,
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

CREATE TABLE IF NOT EXISTS search_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    categoria VARCHAR(100) NOT NULL,
    preferences JSON NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_search_history_user_created (user_id, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS documentos_firmados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    propiedad_id INT NOT NULL,
    tipo_documento ENUM('nda', 'loi') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    firmado_valido BOOLEAN NOT NULL DEFAULT 0,
    ip VARCHAR(45),
    user_agent TEXT,
    validation_token CHAR(64),
    validation_token_expires_at DATETIME,
    validado_admin TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_documentos_token (validation_token),
    KEY idx_documentos_user_property (user_id, propiedad_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

