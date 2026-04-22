-- Migración de hardening adicional (auditoría 2026-04-22).
--
-- 1. password_changed_at en users: invalida JWTs emitidos antes de un cambio
--    de contraseña (el middleware compara users.password_changed_at vs JWT.iat).
-- 2. revoked_tokens.token_hash: sustituye el almacenamiento del JWT en plano
--    por su hash SHA-256 (evita filtrar tokens si la BBDD se compromete).

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS password_changed_at DATETIME NULL AFTER password;

ALTER TABLE revoked_tokens
  ADD COLUMN IF NOT EXISTS token_hash CHAR(64) NULL AFTER token;

-- El logout nuevo inserta solo en token_hash (los JWTs no se guardan en
-- plano), así que la columna antigua debe permitir NULL.
ALTER TABLE revoked_tokens
  MODIFY COLUMN token TEXT NULL;

-- Backfill: rellena el hash de los tokens revocados antiguos para que el
-- middleware siga reconociéndolos como revocados tras el cambio de columna.
UPDATE revoked_tokens
SET token_hash = SHA2(token, 256)
WHERE token_hash IS NULL AND token IS NOT NULL;

-- Índice nuevo (lookup O(1) por hash) y limpieza del prefix index antiguo.
CREATE INDEX IF NOT EXISTS idx_revoked_token_hash ON revoked_tokens (token_hash);

-- El índice idx_revoked_token_prefix puede dejarse: ya no se usa pero no
-- estorba. Si quieres soltarlo manualmente:
--   DROP INDEX idx_revoked_token_prefix ON revoked_tokens;
