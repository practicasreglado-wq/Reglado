-- Migración: geo login alerts.
--
-- login_locations registra cada login con su país y un status que decide
-- si dispara alerta y si cuenta como referencia para el siguiente login.
-- Estados: 'neutral' (mismo país que el anterior), 'pending' (alerta
-- enviada, aún no respondida), 'confirmed' (usuario dijo "fui yo"),
-- 'rejected' (usuario dijo "no fui yo", excluido del cálculo del último
-- legítimo).
--
-- users.require_password_reset es el flag que activamos al rechazar una
-- alerta; el login bloqueará ese flag hasta que el usuario reset su password.

CREATE TABLE IF NOT EXISTS login_locations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  ip VARCHAR(45) NOT NULL,
  country_code CHAR(2) NULL,
  country_name VARCHAR(100) NULL,
  user_agent VARCHAR(512) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'neutral',
  token_hash CHAR(64) NULL,
  token_expires_at DATETIME NULL,
  token_used_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_login_locations_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_login_locations_user_created ON login_locations (user_id, created_at);
CREATE INDEX idx_login_locations_token_hash ON login_locations (token_hash);

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS require_password_reset TINYINT(1) NOT NULL DEFAULT 0;
