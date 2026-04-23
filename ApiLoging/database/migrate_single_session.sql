-- Migración: single-session enforcement.
--
-- current_session_id: id aleatorio (32 bytes hex) que se persiste en cada
-- login y se incluye en el JWT como claim `sid`. El middleware rechaza
-- cualquier token cuyo sid no coincida con el valor actual, lo que implementa
-- la política kick-old (la sesión más reciente invalida a la anterior).

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS current_session_id CHAR(64) NULL AFTER sessions_invalidated_at;

CREATE INDEX IF NOT EXISTS idx_users_current_session_id ON users (current_session_id);
