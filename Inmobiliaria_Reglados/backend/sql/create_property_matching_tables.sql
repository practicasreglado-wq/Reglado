CREATE TABLE IF NOT EXISTS propiedades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria VARCHAR(50) NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    ubicacion_general VARCHAR(255) NOT NULL,
    precio DECIMAL(15,2) NOT NULL DEFAULT 0,
    metros_cuadrados INT NOT NULL DEFAULT 0,
    imagen_principal VARCHAR(255) DEFAULT NULL,
    caracteristicas_json JSON NOT NULL,
    owner_user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_propiedades_categoria (categoria),
    INDEX idx_propiedades_owner (owner_user_id)
);

ALTER TABLE propiedades
    ADD COLUMN IF NOT EXISTS categoria VARCHAR(50) NOT NULL DEFAULT 'Activos',
    ADD COLUMN IF NOT EXISTS titulo VARCHAR(255) NOT NULL DEFAULT '',
    ADD COLUMN IF NOT EXISTS ubicacion_general VARCHAR(255) NOT NULL DEFAULT '',
    ADD COLUMN IF NOT EXISTS metros_cuadrados INT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS imagen_principal VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS caracteristicas_json JSON NULL,
    ADD COLUMN IF NOT EXISTS owner_user_id INT NULL,
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

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

-- Despues de ejecutar este script, lanza:
-- php backend/sql/seed_property_matching_data.php
