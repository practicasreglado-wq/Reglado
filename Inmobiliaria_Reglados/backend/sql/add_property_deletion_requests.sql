-- =============================================================================
-- Feature: Solicitudes de eliminación de propiedades (flujo con revisión admin)
-- =============================================================================
-- Cuando un usuario pulsa "Eliminar" en una de sus propiedades desde
-- MyPropertiesForSale, NO se borra directamente. Se crea una solicitud
-- pendiente en esta tabla, se notifica al admin (in-app + email) y el admin
-- decide si aprobar (borra la propiedad) o rechazar (mantiene la propiedad).
-- El usuario recibe respuesta por email + notificación en ambos casos.
-- =============================================================================

CREATE TABLE IF NOT EXISTS property_deletion_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    property_id INT UNSIGNED NOT NULL,
    requester_user_id INT UNSIGNED NOT NULL,

    reason VARCHAR(1000) DEFAULT NULL,

    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    admin_notes VARCHAR(1000) DEFAULT NULL,
    resolved_by_user_id INT UNSIGNED DEFAULT NULL,
    resolved_at DATETIME DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_prop_delete_status (status),
    INDEX idx_prop_delete_property (property_id),
    INDEX idx_prop_delete_requester (requester_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
