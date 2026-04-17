-- Optimización de índices para consultas de notificaciones.
-- Script no destructivo: solo crea índices si no existen.

SET @schema_name := DATABASE();

SET @idx_user_exists := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = @schema_name
    AND table_name = 'notifications'
    AND index_name = 'idx_notifications_user'
);

SET @sql_idx_user := IF(
  @idx_user_exists = 0,
  'ALTER TABLE notifications ADD INDEX idx_notifications_user (user_id)',
  'SELECT ''idx_notifications_user ya existe'''
);

PREPARE stmt FROM @sql_idx_user;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_user_created_exists := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = @schema_name
    AND table_name = 'notifications'
    AND index_name = 'idx_notifications_user_created_at'
);

SET @sql_idx_user_created := IF(
  @idx_user_created_exists = 0,
  'ALTER TABLE notifications ADD INDEX idx_notifications_user_created_at (user_id, created_at)',
  'SELECT ''idx_notifications_user_created_at ya existe'''
);

PREPARE stmt FROM @sql_idx_user_created;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
