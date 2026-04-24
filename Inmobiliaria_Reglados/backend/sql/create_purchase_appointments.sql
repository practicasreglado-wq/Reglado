-- Citas agendadas por compradores cuando pulsan "Me interesa comprar".
-- Las gestiona el admin desde el panel (completar/cancelar).

CREATE TABLE IF NOT EXISTS purchase_appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    property_id INT NOT NULL,
    appointment_date DATETIME NOT NULL,
    notary_name VARCHAR(255) NULL,
    notary_address VARCHAR(500) NULL,
    notary_city VARCHAR(150) NULL,
    notary_phone VARCHAR(50) NULL,
    notes TEXT NULL,
    status ENUM('scheduled', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled',
    admin_notes TEXT NULL,
    completed_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_property (property_id),
    INDEX idx_status_date (status, appointment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
