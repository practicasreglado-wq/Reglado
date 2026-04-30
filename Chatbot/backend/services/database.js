// archivo donde se crean las tablas y se configura la base de datos.

import mysql from 'mysql2/promise';
import dotenv from 'dotenv';
import { crearUsuarioNotion, crearCitaNotion } from './notionService.js';

dotenv.config();

const dbConfig = {
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASS || '',
  database: process.env.DB_NAME || 'chatbot_reglado'
};

let pool;

async function getPool() {
  if (!pool) {
    pool = mysql.createPool(dbConfig);
  }
  return pool;
}

/**
 * Inicializa las tablas necesarias en MySQL.
 * Crea la base de datos si no existe (si el usuario root tiene permisos).
 */
async function initDB() {
  try {
    // Primero conectamos sin base de datos para asegurarnos de que existe
    const connection = await mysql.createConnection({
      host: dbConfig.host,
      user: dbConfig.user,
      password: dbConfig.password
    });

    await connection.query(`CREATE DATABASE IF NOT EXISTS \`${dbConfig.database}\``);
    await connection.end();

    const p = await getPool();

    // Tabla de usuarios (antes leads)
    await p.query(`
      CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(255) NOT NULL,
        email VARCHAR(255),
        telefono VARCHAR(50),
        fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `);

    // Tabla de citas interconectada con leads
    await p.query(`
      CREATE TABLE IF NOT EXISTS citas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lead_id INT,
        nombre VARCHAR(255),
        fecha DATE NOT NULL,
        hora TIME NOT NULL,
        motivo TEXT,
        estado VARCHAR(50) DEFAULT 'pendiente',
        FOREIGN KEY(lead_id) REFERENCES usuarios(id) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `);

    // Asegurar que la columna 'nombre' existe en 'citas' si la tabla ya fue creada antes
    try {
      await p.query(`ALTER TABLE citas ADD COLUMN IF NOT EXISTS nombre VARCHAR(255) AFTER lead_id`);
    } catch (e) {
      // Ignorar si el motor no soporta IF NOT EXISTS en ALTER o si ya existe
    }

    // Tabla de archivos asociados a usuarios
    await p.query(`
      CREATE TABLE IF NOT EXISTS archivos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT,
        nombre VARCHAR(255),
        nombre_original VARCHAR(255),
        ruta_servidor VARCHAR(255),
        tipo_mimo VARCHAR(100),
        fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `);

    // Migraciones seguras de columnas
    try {
      await p.query(`ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS session_id VARCHAR(255) AFTER telefono`);
    } catch (e) { /* ignorar */ }
    try {
      await p.query(`ALTER TABLE archivos ADD COLUMN IF NOT EXISTS nombre VARCHAR(255) AFTER usuario_id`);
    } catch (e) { /* ignorar */ }
    try {
      await p.query(`ALTER TABLE archivos ADD COLUMN IF NOT EXISTS session_id VARCHAR(255) AFTER nombre`);
    } catch (e) { /* ignorar */ }

    // Tabla de conversaciones para agente humano
    await p.query(`
      CREATE TABLE IF NOT EXISTS conversaciones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_id VARCHAR(255) NOT NULL,
        estado ENUM('IA', 'WAITING_HUMAN', 'HUMAN') DEFAULT 'IA',
        agente_telegram_id VARCHAR(50) DEFAULT NULL,
        last_active DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY(session_id)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `);

    // Añadir columnas de seguimiento de coste si no existen (migracion segura)
    try {
      await p.query(`ALTER TABLE conversaciones ADD COLUMN IF NOT EXISTS tokens_input  INT            DEFAULT 0        AFTER agente_telegram_id`);
      await p.query(`ALTER TABLE conversaciones ADD COLUMN IF NOT EXISTS tokens_output INT            DEFAULT 0        AFTER tokens_input`);
      await p.query(`ALTER TABLE conversaciones ADD COLUMN IF NOT EXISTS cost_eur      DECIMAL(10,6)  DEFAULT 0.000000 AFTER tokens_output`);
    } catch (e) {
      // Ignorar si el motor no soporta ADD COLUMN IF NOT EXISTS
    }

    console.log('[Database] MySQL inicializado correctamente.');

    // Tabla de memoria larga por sesión (resumen de conversaciones cliente-IA)
    await p.query(`
      CREATE TABLE IF NOT EXISTS memoria_usuario (
        id                 INT AUTO_INCREMENT PRIMARY KEY,
        session_id         VARCHAR(255) NOT NULL UNIQUE,
        resumen            TEXT,
        tokens_input       INT           DEFAULT 0,
        tokens_output      INT           DEFAULT 0,
        cost_eur           DECIMAL(10,6) DEFAULT 0.000000,
        fecha_conversacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `);

    // Añadir columnas de coste a memoria_usuario si la tabla ya existía (migración segura)
    try {
      await p.query(`ALTER TABLE memoria_usuario ADD COLUMN IF NOT EXISTS tokens_input  INT           DEFAULT 0        AFTER resumen`);
      await p.query(`ALTER TABLE memoria_usuario ADD COLUMN IF NOT EXISTS tokens_output INT           DEFAULT 0        AFTER tokens_input`);
      await p.query(`ALTER TABLE memoria_usuario ADD COLUMN IF NOT EXISTS cost_eur      DECIMAL(10,6) DEFAULT 0.000000 AFTER tokens_output`);
    } catch (e) {
      // Ignorar si el motor no soporta ADD COLUMN IF NOT EXISTS
    }

    // Sincronizar citas pasadas al arrancar y cada hora
    await finalizarCitasPasadas();
    setInterval(finalizarCitasPasadas, 60 * 60 * 1000);

    // Limpiar memoria antigua al arrancar y cada 24h
    await limpiarMemoriaAntigua();
    setInterval(limpiarMemoriaAntigua, 24 * 60 * 60 * 1000);
  } catch (error) {
    console.error('[Database] Error inicializando MySQL:', error.message);
    console.warn('[Database] Asegúrate de que MySQL/XAMPP esté corriendo y los datos en .env sean correctos.');
  }
}

// Inicializar al cargar el módulo
initDB();

/**
 * Verifica si un hueco está disponible (no hay citas en ese día/hora que se solapen).
 * Las citas duran 30 minutos.
 */
async function verificarDisponibilidadCita(dia, hora) {
  const p = await getPool();
  console.log(`[Database] Verificando disponibilidad para ${dia} a las ${hora}`);

  // Buscamos si hay alguna cita en ese día que se solape (menos de 30 min de diferencia)
  // Usamos TIME_TO_SEC y TIMEDIFF para una comparación más precisa en MySQL
  const query = `
    SELECT id, hora FROM citas 
    WHERE fecha = ? 
    AND ABS(TIME_TO_SEC(TIMEDIFF(hora, ?))) < 1800 
    AND estado != "cancelada"
  `;

  const [rows] = await p.execute(query, [dia, hora]);

  if (rows.length > 0) {
    console.log(`[Database] Conflicto detectado con cita ID: ${rows[0].id} a las ${rows[0].hora}`);
  }

  return rows.length === 0;
}

/**
 * Guarda un usuario y su cita en la misma transacción para mantener coherencia.
 */
async function agendarCita(nombre, email, telefono, dia, hora, motivo, sessionId = null) {
  const p = await getPool();
  const connection = await p.getConnection();

  try {
    await connection.beginTransaction();

    // 1. Upsert usuario por sessionId — cada sesión es su propio registro
    let leadId;
    if (sessionId) {
      const [existing] = await connection.execute(
        'SELECT id FROM usuarios WHERE session_id = ? LIMIT 1',
        [sessionId]
      );
      if (existing.length > 0) leadId = existing[0].id;
    }
    let usuarioCreado = false;
    if (!leadId) {
      const [userResult] = await connection.execute(
        'INSERT INTO usuarios (nombre, email, telefono, session_id) VALUES (?, ?, ?, ?)',
        [nombre, email, telefono, sessionId || null]
      );
      leadId = userResult.insertId;
      usuarioCreado = true;
    }

    // 2. Insertar Cita (incluyendo el nombre para mayor claridad como pidió el usuario)
    await connection.execute(
      'INSERT INTO citas (lead_id, nombre, fecha, hora, motivo) VALUES (?, ?, ?, ?, ?)',
      [leadId, nombre, dia, hora, motivo]
    );

    await connection.commit();

    // Mirror a Notion (fire-and-forget: no bloquea ni tumba el flujo si falla)
    if (usuarioCreado) {
      crearUsuarioNotion({ nombre, email, telefono });
    }
    crearCitaNotion({ nombre, fecha: dia, hora, motivo, estado: 'pendiente' });

    return { leadId, nombre, dia, hora };
  } catch (error) {
    await connection.rollback();
    console.error('[Database] Error en agendarCita:', error);
    throw error;
  } finally {
    connection.release();
  }
}

/**
 * Obtiene todas las citas pendientes
 */
async function obtenerCitasPendientes() {
  const p = await getPool();
  const [rows] = await p.execute(`
    SELECT c.fecha, c.hora, c.motivo, u.nombre, u.telefono, u.email
    FROM citas c
    JOIN usuarios u ON c.lead_id = u.id
    WHERE c.estado = 'pendiente'
    ORDER BY c.fecha ASC, c.hora ASC
  `);
  return rows;
}

/**
 * Guarda los metadatos de un archivo subido.
 */
async function guardarArchivo(usuarioId, nombre, sessionId, nombreOriginal, rutaServidor, tipoMimo) {
  const p = await getPool();
  const [result] = await p.execute(
    'INSERT INTO archivos (usuario_id, nombre, session_id, nombre_original, ruta_servidor, tipo_mimo) VALUES (?, ?, ?, ?, ?, ?)',
    [usuarioId || null, nombre || null, sessionId || null, nombreOriginal, rutaServidor, tipoMimo]
  );
  return result.insertId;
}

async function obtenerArchivoSiSessionValida(rutaServidor, sessionId) {
  const p = await getPool();
  const [rows] = await p.execute(
    'SELECT ruta_servidor FROM archivos WHERE ruta_servidor = ? AND session_id = ? LIMIT 1',
    [rutaServidor, sessionId]
  );
  return rows.length > 0 ? rows[0] : null;
}

// CONVERSACIONES CON AGENTE

/**
 * Obtener o crear conversación por session_id o por id
 */
async function obtenerConversacion(sessionId, id = null) {
  const p = await getPool();
  if (id) {
    const [rows] = await p.execute('SELECT * FROM conversaciones WHERE id = ?', [id]);
    return rows.length > 0 ? rows[0] : null;
  }
  
  const [rows] = await p.execute('SELECT * FROM conversaciones WHERE session_id = ?', [sessionId]);
  if (rows.length > 0) return rows[0];

  const [result] = await p.execute("INSERT INTO conversaciones (session_id, estado) VALUES (?, 'IA')", [sessionId]);
  return { id: result.insertId, session_id: sessionId, estado: 'IA', agente_telegram_id: null };
}

/**
 * Actualizar el estado de la conversación (para solicitar humano)
 */
async function actualizarEstadoConversacion(sessionId, estado) {
  const p = await getPool();
  await p.execute('UPDATE conversaciones SET estado = ? WHERE session_id = ?', [estado, sessionId]);
}

/**
 * Terminar la conversación humana y volver a la IA
 */
async function terminarConversacionHumana(sessionId) {
  const p = await getPool();
  await p.execute('UPDATE conversaciones SET estado = "IA" WHERE session_id = ?', [sessionId]);
}

/**
 * Asignar un agente de Telegram a una conversación de la que se ha hecho claim
 */
async function asignarAgenteConversacion(conversacionId, agenteNombre) {
  const p = await getPool();
  await p.execute(
    'UPDATE conversaciones SET estado = "HUMAN", agente_telegram_id = ? WHERE id = ?',
    [agenteNombre, conversacionId]
  );
}


/**
 * Función que busca qué conversación tiene asignada un agente de Telegram que acaba de hablar por Telegram 
 * (asumimos que el agente está hablando con "la última conversación no resuelta que tomó" si permitimos varias, 
 * o lo más sencillo: buscamos la última conversacion activa de este agente)
 */
async function obtenerConversacionActivaPorAgente(telegramId) {
  const p = await getPool();
  const [rows] = await p.execute(
    'SELECT * FROM conversaciones WHERE agente_telegram_id = ? AND estado = "HUMAN" ORDER BY last_active DESC LIMIT 1',
    [telegramId]
  );
  return rows.length > 0 ? rows[0] : null;
}


/**
 * Registra un usuario en la base de datos (nombre, email, telefono).
 * Upsert por sessionId: reutiliza el registro de la sesión si ya existe, o crea uno nuevo.
 */
async function registrarUsuario(nombre, email, telefono, sessionId = null) {
  const p = await getPool();
  if (sessionId) {
    const [existing] = await p.execute(
      'SELECT id, nombre, email, telefono FROM usuarios WHERE session_id = ? LIMIT 1',
      [sessionId]
    );
    if (existing.length > 0) {
      return { id: existing[0].id, nombre: existing[0].nombre, email: existing[0].email, telefono: existing[0].telefono };
    }
  }
  const [result] = await p.execute(
    'INSERT INTO usuarios (nombre, email, telefono, session_id) VALUES (?, ?, ?, ?)',
    [nombre, email, telefono, sessionId || null]
  );

  // Mirror a Notion (fire-and-forget: no bloquea ni tumba el flujo si falla)
  crearUsuarioNotion({ nombre, email, telefono });

  return { id: result.insertId, nombre, email, telefono };
}

async function obtenerUsuarioPorSession(sessionId) {
  const p = await getPool();
  const [rows] = await p.execute(
    'SELECT id, nombre, email, telefono FROM usuarios WHERE session_id = ? LIMIT 1',
    [sessionId]
  );
  return rows.length > 0 ? rows[0] : null;
}


/**
 * Limpia el estado de todas las conversaciones al arrancar el servidor.
 * Esto evita que las sesiones se queden bloqueadas si el servidor se reinicia inesperadamente.
 */
async function resetearConversacionesAlInicio() {
  try {
    const p = await getPool();
    const [result] = await p.execute('UPDATE conversaciones SET estado = "IA", agente_telegram_id = NULL WHERE estado != "IA"');
    if (result.affectedRows > 0) {
      console.log(`[Database] Se han reseteado ${result.affectedRows} conversaciones bloqueadas.`);
    }
  } catch (error) {
    console.error('[Database] Error al resetear conversaciones:', error.message);
  }
}


/**
 * Acumula tokens y coste de la sesion tras cada llamada a la IA.
 * Devuelve el coste total acumulado en EUR para esa sesion.
 * El calculo del incremento se realiza en server.js con los precios configurados.
 */
async function actualizarCosteSession(sessionId, inputTokens, outputTokens, costeIncremento) {
  const p = await getPool();
  await p.execute(
    `UPDATE conversaciones
     SET tokens_input  = tokens_input  + ?,
         tokens_output = tokens_output + ?,
         cost_eur      = cost_eur      + ?
     WHERE session_id = ?`,
    [inputTokens, outputTokens, costeIncremento, sessionId]
  );
  const [rows] = await p.execute(
    'SELECT cost_eur FROM conversaciones WHERE session_id = ?',
    [sessionId]
  );
  return rows.length > 0 ? parseFloat(rows[0].cost_eur) : costeIncremento;
}


// MEMORIA LARGA CLIENTE-IA

/**
 * Devuelve el resumen de memoria de una sesión, o null si no existe.
 */
async function obtenerMemoriaUsuario(sessionId) {
  const p = await getPool();
  const [rows] = await p.execute(
    'SELECT resumen FROM memoria_usuario WHERE session_id = ?',
    [sessionId]
  );
  return rows.length > 0 ? rows[0].resumen : null;
}

/**
 * Crea o actualiza el resumen de memoria de una sesión (upsert).
 * Acumula tokens_input, tokens_output y cost_eur de cada llamada de resumen.
 */
async function actualizarMemoriaUsuario(sessionId, resumen, inputTokens = 0, outputTokens = 0, costeIncremento = 0) {
  const p = await getPool();
  await p.execute(
    `INSERT INTO memoria_usuario (session_id, resumen, tokens_input, tokens_output, cost_eur)
     VALUES (?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
       resumen        = VALUES(resumen),
       tokens_input   = tokens_input  + VALUES(tokens_input),
       tokens_output  = tokens_output + VALUES(tokens_output),
       cost_eur       = cost_eur      + VALUES(cost_eur),
       fecha_conversacion = CURRENT_TIMESTAMP`,
    [sessionId, resumen, inputTokens, outputTokens, costeIncremento]
  );
}

/**
 * Borra los resúmenes de memoria cuya fecha_conversacion supere el TTL configurado en MEMORY_TTL_DAYS.
 */
async function limpiarMemoriaAntigua() {
  try {
    const p = await getPool();
    const ttl = parseInt(process.env.MEMORY_TTL_DAYS || '15', 10);
    const [result] = await p.execute(
      'DELETE FROM memoria_usuario WHERE fecha_conversacion < DATE_SUB(NOW(), INTERVAL ? DAY)',
      [ttl]
    );
    if (result.affectedRows > 0) {
      console.log(`[Database] ${result.affectedRows} resumen(es) de memoria eliminados por antigüedad (>${ttl} días).`);
    }
  } catch (error) {
    console.error('[Database] Error al limpiar memoria antigua:', error.message);
  }
}

/**
 * Marca como 'finalizada' todas las citas pendientes cuya fecha y hora ya han pasado.
 */
async function finalizarCitasPasadas() {
  try {
    const p = await getPool();
    const [result] = await p.execute(`
      UPDATE citas SET estado = 'finalizada'
      WHERE estado = 'pendiente'
      AND CONCAT(fecha, ' ', hora) < NOW()
    `);
    if (result.affectedRows > 0) {
      console.log(`[Database] ${result.affectedRows} cita(s) marcadas como finalizadas.`);
    }
  } catch (error) {
    console.error('[Database] Error al finalizar citas pasadas:', error.message);
  }
}

export {
  resetearConversacionesAlInicio,
  registrarUsuario,
  obtenerUsuarioPorSession,
  agendarCita,
  obtenerCitasPendientes,
  verificarDisponibilidadCita,
  guardarArchivo,
  obtenerArchivoSiSessionValida,
  obtenerConversacion,
  actualizarEstadoConversacion,
  asignarAgenteConversacion,
  obtenerConversacionActivaPorAgente,
  terminarConversacionHumana,
  actualizarCosteSession,
  finalizarCitasPasadas,
  obtenerMemoriaUsuario,
  actualizarMemoriaUsuario,
  limpiarMemoriaAntigua
};
