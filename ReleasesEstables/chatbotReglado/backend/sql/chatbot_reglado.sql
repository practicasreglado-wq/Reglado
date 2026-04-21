-- =====================================================================
-- chatbotReglado — Schema MySQL
-- Genera la base de datos `chatbot_reglado` con todas sus tablas.
-- Importable desde phpMyAdmin: Import -> seleccionar este archivo -> Go.
--
-- Fuente de verdad: backend/services/database.js (incluye migraciones
-- ALTER que este dump ya consolida en los CREATE).
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Base de datos
-- ---------------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `chatbot_reglado`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE `chatbot_reglado`;

-- ---------------------------------------------------------------------
-- Tabla: usuarios
-- Clientes identificados por sesión que han interactuado con el chatbot.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id`             INT(11)      NOT NULL AUTO_INCREMENT,
  `nombre`         VARCHAR(255) NOT NULL,
  `email`          VARCHAR(255) DEFAULT NULL,
  `telefono`       VARCHAR(50)  DEFAULT NULL,
  `session_id`     VARCHAR(255) DEFAULT NULL,
  `fecha_registro` DATETIME     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- Tabla: citas
-- Citas telefónicas agendadas. FK a usuarios (se borran en cascada).
-- estado: pendiente | confirmada | cancelada | finalizada
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `citas`;
CREATE TABLE `citas` (
  `id`      INT(11)      NOT NULL AUTO_INCREMENT,
  `lead_id` INT(11)      DEFAULT NULL,
  `nombre`  VARCHAR(255) DEFAULT NULL,
  `fecha`   DATE         NOT NULL,
  `hora`    TIME         NOT NULL,
  `motivo`  TEXT         DEFAULT NULL,
  `estado`  VARCHAR(50)  DEFAULT 'pendiente',
  PRIMARY KEY (`id`),
  KEY `lead_id` (`lead_id`),
  CONSTRAINT `citas_ibfk_1` FOREIGN KEY (`lead_id`)
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- Tabla: archivos
-- Metadatos de archivos subidos por el cliente durante la conversación.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `archivos`;
CREATE TABLE `archivos` (
  `id`              INT(11)      NOT NULL AUTO_INCREMENT,
  `usuario_id`      INT(11)      DEFAULT NULL,
  `nombre`          VARCHAR(255) DEFAULT NULL,
  `session_id`      VARCHAR(255) DEFAULT NULL,
  `nombre_original` VARCHAR(255) DEFAULT NULL,
  `ruta_servidor`   VARCHAR(255) DEFAULT NULL,
  `tipo_mimo`       VARCHAR(100) DEFAULT NULL,
  `fecha_subida`    DATETIME     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `archivos_ibfk_1` FOREIGN KEY (`usuario_id`)
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- Tabla: conversaciones
-- Estado de cada conversación (IA / en cola humana / con humano) y
-- acumulado de tokens+coste por sesión.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `conversaciones`;
CREATE TABLE `conversaciones` (
  `id`                 INT(11)                              NOT NULL AUTO_INCREMENT,
  `session_id`         VARCHAR(255)                         NOT NULL,
  `estado`             ENUM('IA','WAITING_HUMAN','HUMAN')   DEFAULT 'IA',
  `agente_telegram_id` VARCHAR(50)                          DEFAULT NULL,
  `tokens_input`       INT(11)                              DEFAULT 0,
  `tokens_output`      INT(11)                              DEFAULT 0,
  `cost_eur`           DECIMAL(10,6)                        DEFAULT 0.000000,
  `last_active`        DATETIME                             DEFAULT CURRENT_TIMESTAMP
                                                            ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- Tabla: memoria_usuario
-- Resumen de memoria larga cliente–IA por sesión. Se purga por TTL
-- (var de entorno MEMORY_TTL_DAYS, por defecto 15 días).
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `memoria_usuario`;
CREATE TABLE `memoria_usuario` (
  `id`                 INT(11)       NOT NULL AUTO_INCREMENT,
  `session_id`         VARCHAR(255)  NOT NULL,
  `resumen`            TEXT          DEFAULT NULL,
  `tokens_input`       INT(11)       DEFAULT 0,
  `tokens_output`      INT(11)       DEFAULT 0,
  `cost_eur`           DECIMAL(10,6) DEFAULT 0.000000,
  `fecha_conversacion` DATETIME      DEFAULT CURRENT_TIMESTAMP
                                     ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;
