-- Migración: moderación admin (ban + force-logout).
--
-- 1. banned_at: timestamp del ban. NULL = cuenta activa.
-- 2. banned_by: admin que aplicó el ban (FK a users.id, SET NULL si ese admin
--    desaparece; así no perdemos el registro del ban, solo la autoría).
-- 3. sessions_invalidated_at: usado por el middleware para rechazar JWTs
--    cuyo iat sea anterior a este timestamp. Se actualiza en force-logout y
--    al banear. NO se limpia al desbanear (los tokens previos al ban siguen
--    revocados por defecto).

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS banned_at DATETIME NULL AFTER is_email_verified,
  ADD COLUMN IF NOT EXISTS banned_by INT NULL AFTER banned_at,
  ADD COLUMN IF NOT EXISTS sessions_invalidated_at DATETIME NULL AFTER banned_by;

ALTER TABLE users
  ADD CONSTRAINT fk_users_banned_by FOREIGN KEY (banned_by) REFERENCES users(id) ON DELETE SET NULL;

CREATE INDEX IF NOT EXISTS idx_users_banned_at ON users (banned_at);
