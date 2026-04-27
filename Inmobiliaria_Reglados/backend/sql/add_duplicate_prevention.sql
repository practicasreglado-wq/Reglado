-- =============================================================================
-- Blindaje de duplicados a nivel de base de datos
-- =============================================================================
-- Este script añade UNIQUE KEYs que cierran la race condition de los chequeos
-- app-level (SELECT + INSERT) y establecen dedup semántica por dirección.
--
-- EJECUCION:
--   1) PRIMERO: ejecutar las queries de auditoría comentadas al final para
--      detectar duplicados existentes. Si hay filas duplicadas, los ALTERs
--      fallarán.
--   2) Si hay duplicados, decidir cuál conservar y borrar el resto, o bien
--      aplicar el script incremental de limpieza que quieras.
--   3) Después aplicar los ALTER TABLE de este fichero.
-- =============================================================================

-- activos_recibidos: blindar content_hash y message_id contra inserciones
-- concurrentes. NULL en MySQL UNIQUE se considera distinto, así que las filas
-- existentes sin hash no bloquean el ALTER.
ALTER TABLE activos_recibidos
    ADD UNIQUE KEY uniq_activos_content_hash (content_hash),
    ADD UNIQUE KEY uniq_activos_message_id (message_id);

-- propiedades: columna address_hash + UNIQUE para deduplicar por dirección.
-- La columna se rellenará solo cuando haya datos suficientes (direccion o
-- ubicación + ciudad). Las filas antiguas quedan con NULL y no bloquean
-- nuevas altas (NULL != NULL en UNIQUE de MySQL).
ALTER TABLE propiedades
    ADD COLUMN address_hash VARCHAR(64) DEFAULT NULL AFTER longitud,
    ADD UNIQUE KEY uniq_propiedades_address_hash (address_hash);

-- =============================================================================
-- Queries de auditoría previa (ejecutar ANTES de los ALTER si hay miedo a
-- que fallen por duplicados previos):
-- =============================================================================
-- SELECT content_hash, COUNT(*) c
--   FROM activos_recibidos
--   WHERE content_hash IS NOT NULL
--   GROUP BY content_hash HAVING c > 1;
--
-- SELECT message_id, COUNT(*) c
--   FROM activos_recibidos
--   WHERE message_id IS NOT NULL
--   GROUP BY message_id HAVING c > 1;
-- =============================================================================
