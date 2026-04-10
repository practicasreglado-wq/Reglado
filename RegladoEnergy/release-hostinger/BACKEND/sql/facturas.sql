CREATE DATABASE IF NOT EXISTS `facturas`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `facturas`;

CREATE TABLE IF NOT EXISTS `facturas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(120) NOT NULL,
  `telefono` VARCHAR(40) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `mensaje` TEXT NULL,
  `pdf_nombre_original` VARCHAR(255) NULL,
  `pdf_ruta` VARCHAR(255) NULL,
  `pdf_mime` VARCHAR(80) NULL,
  `pdf_tamano_bytes` INT UNSIGNED NULL,
  `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_creado_en` (`creado_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
