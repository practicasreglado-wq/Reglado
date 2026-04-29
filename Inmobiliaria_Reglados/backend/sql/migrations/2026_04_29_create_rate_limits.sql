-- Migración: crear tabla rate_limits propia en la BD de inmobiliaria.
--
-- Hasta ahora inmobiliaria reutilizaba `regladousers.rate_limits` (la misma
-- que usa ApiLogin), lo que requería permisos cross-DB. Cada servicio pasa
-- a tener su propia tabla porque ApiLogin e inmobiliaria no comparten ningún
-- scope; cada uno usa los suyos.
--
-- Idempotente: usa CREATE TABLE IF NOT EXISTS, se puede ejecutar varias veces.
-- No migra datos: los rate limits son contadores efímeros (ventanas <24h),
-- empezar de cero post-deploy es aceptable.
--
-- Ejecutar contra la BD de inmobiliaria (en producción: u238278696_inmobiliaria).

CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key_hash` char(64) NOT NULL,
  `scope_name` varchar(100) NOT NULL,
  `attempts` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_key_hash` (`key_hash`),
  KEY `idx_scope_updated` (`scope_name`,`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
