CREATE DATABASE IF NOT EXISTS ingenieria
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE ingenieria;

CREATE TABLE IF NOT EXISTS consultas (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  nombre          VARCHAR(100)  NOT NULL,
  email           VARCHAR(150)  NOT NULL,
  telefono        VARCHAR(20)   DEFAULT NULL,
  empresa         VARCHAR(100)  DEFAULT NULL,
  mensaje         TEXT          NOT NULL,
  fecha_creacion  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  leido           TINYINT(1)    NOT NULL DEFAULT 0,
  INDEX idx_email (email),
  INDEX idx_leido (leido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
