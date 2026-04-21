/**
 * Función: Punto de entrada del backend de chatbotReglado. 
 * Configura el servidor Express, maneja CORS, sirve archivos estáticos del widget 
 * y proporciona el endpoint "/chat" para comunicarse con la API de Claude (Anthropic).
 */

import express from 'express';
import cors from 'cors';
import dotenv from 'dotenv';
import Anthropic from '@anthropic-ai/sdk';
import path from 'path';
import { fileURLToPath } from 'url';
import { resetearConversacionesAlInicio, registrarUsuario, obtenerUsuarioPorSession, agendarCita, verificarDisponibilidadCita, guardarArchivo, obtenerArchivoSiSessionValida, obtenerConversacion, actualizarEstadoConversacion, actualizarCosteSession, obtenerMemoriaUsuario, actualizarMemoriaUsuario } from './services/database.js';
import { enviarEmailConfirmacionCita, enviarEmailAvisoInterno, enviarEmailAvisoArchivo } from './services/emailService.js';
import { agendarEnGoogleCalendar } from './services/googleCalendar.js';
import { initTelegramService, notificarAgentes, reenviarMensajeCliente, mensajesPendientes } from './services/telegramService.js';
import multer from 'multer';
import fs from 'fs';
import rateLimit from 'express-rate-limit';

dotenv.config();
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
initTelegramService();
resetearConversacionesAlInicio();

// Configuración de Multer para subida de archivos
const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    const uploadPath = path.join(__dirname, 'uploads');
    if (!fs.existsSync(uploadPath)) {
      fs.mkdirSync(uploadPath, { recursive: true });
    }
    cb(null, uploadPath);
  },
  filename: (req, file, cb) => {
    const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1e9);
    cb(null, uniqueSuffix + '-' + file.originalname);
  }
});

const upload = multer({
  storage,
  limits: { fileSize: 100 * 1024 * 1024 }, // Límite 100MB
  fileFilter: (req, file, cb) => {
    const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    if (allowedTypes.includes(file.mimetype)) {
      cb(null, true);
    } else {
      cb(new Error('Tipo de archivo no permitido. Solo JPG, PNG y PDF.'));
    }
  }
});

const app = express();
const PORT = Number(process.env.PORT || 3000);
const MODEL = process.env.ANTHROPIC_MODEL || 'claude-haiku-4-5';
const API_KEY = process.env.ANTHROPIC_API_KEY;

if (!API_KEY) {
  console.warn('[chatbotReglado] Falta ANTHROPIC_API_KEY en el archivo .env');
}

// Costes por token en EUR (Claude Haiku-4-5, tasa USD/EUR ~0.92 aplicada).
// Configurable desde .env: COST_INPUT_PER_MTOK y COST_OUTPUT_PER_MTOK son el precio por millón de tokens en EUR.
const COST_PER_INPUT_TOKEN  = parseFloat(process.env.COST_INPUT_PER_MTOK  || '0.736') / 1000000;
const COST_PER_OUTPUT_TOKEN = parseFloat(process.env.COST_OUTPUT_PER_MTOK || '2.208') / 1000000;
const COST_WARN_EUR  = parseFloat(process.env.COST_WARN_EUR  || '0.60');
const COST_LIMIT_EUR = parseFloat(process.env.COST_LIMIT_EUR || '0.80');

const anthropic = new Anthropic({ apiKey: API_KEY });

const allowedOrigins = (process.env.ALLOWED_ORIGINS || '')
  .split(',')
  .map((item) => item.trim())
  .filter(Boolean);

app.use(
  cors({
    origin(origin, callback) {
      if (!origin || allowedOrigins.length === 0 || allowedOrigins.includes(origin)) {
        callback(null, true);
        return;
      }
      callback(new Error(`Origen no permitido por CORS: ${origin}`));
    }
  })
);

app.use(express.json({ limit: '1mb' }));
app.use('/widget', express.static(path.join(__dirname, '../widget')));

// Helper para convertir DD-MM-YYYY a YYYY-MM-DD
function convertToISO(dateStr) {
  if (!dateStr) return null;
  const parts = dateStr.split('-');
  if (parts.length !== 3) return dateStr;
  if (parts[0].length === 4) return dateStr;
  return `${parts[2]}-${parts[1]}-${parts[0]}`;
}

/**
 * Validaciones de Seguridad y Reglas de Negocio
 */
function isValidEmail(email) {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(String(email).toLowerCase());
}

function isValidPhone(phone) {
  const re = /^[\d\s+]{9,20}$/;
  return re.test(String(phone));
}

function isBusinessTime(dia, hora) {
  const appointmentDate = new Date(`${dia}T${hora}:00`);
  const now = new Date();

  if (appointmentDate < new Date(now.getTime() - 5 * 60000)) {
    return { valid: false, reason: 'No puedes agendar una cita en una fecha o hora pasada.' };
  }

  const day = appointmentDate.getDay();
  const hour = appointmentDate.getHours();

  if (day === 0 || day === 6) return { valid: false, reason: 'Solo atendemos de Lunes a Viernes.' };
  if (hour < 7 || hour >= 15) return { valid: false, reason: 'El horario de citas es de 07:00 a 15:00.' };

  return { valid: true };
}

// system prompt — acepta un resumen de memoria opcional para inyectar contexto de conversaciones previas
function buildSystemPrompt(domain, memoriaResumen = null) {
  const partes = [
    'Eres asistente virtual de Grupo Reglado.',
    'Responde breve, conciso y profesional en español (salvo si preguntan en otro idioma). Usa iconos en las conversaciones',
    'Capacidades: Interactuar y resolver dudas al cliente, agendar citas telefónicas y derivar a un agente de nuestro equipo. Ofrécelo sin ser repetitivo.',
    `Dominio: ${domain || 'desconocido'}. Interactuar con el cliente teniendo en cuenta el dominio donde se encuentra.`,
    'HORARIO: para citas telefónicas y chat con agentes de nuestro equipo: Lunes a Viernes, 07:00-15:00.',
    'CITAS: Usa "agendar_llamada". Pide nombre, email, móvil, día (DD-MM-YYYY) y hora (en horario laboral).',
    'AGENTES: Usa "solicitar_agente_humano" ante dudas complejas o si piden hablar con una persona de nuestro equipo. NUNCA pidas datos personales (nombre, email, teléfono) antes de conectar con el agente — llama al tool directamente.',
    'ARCHIVOS: Cuando el usuario quiera subir un archivo o diga que quiere facilitar sus datos: (1) Si ya tienes su nombre, email y teléfono (por memoria o conversación), llama INMEDIATAMENTE a "registrar_usuario" con esos datos sin volver a pedírselos. (2) Si no los tienes, pídelos y luego llama a "registrar_usuario". NUNCA respondas solo con texto cuando tengas que registrar — siempre usa el tool.',
  ];

  if (memoriaResumen) {
    partes.push(`MEMORIA DEL CLIENTE (conversaciones previas): ${memoriaResumen}`);
  }

  return partes.join(' ');
}

const CHATBOT_TOOLS = [
  {
    name: 'agendar_llamada',
    description: 'Guarda los datos del usuario y reserva una llamada en el calendario.',
    input_schema: {
      type: 'object',
      properties: {
        nombre: { type: 'string', description: 'Nombre completo.' },
        email: { type: 'string', description: 'Correo electrónico.' },
        telefono: { type: 'string', description: 'Teléfono.' },
        dia: { type: 'string', description: 'Fecha en formato DD-MM-YYYY.' },
        hora: { type: 'string', description: 'Hora en formato HH:MM (07:00 a 15:00).' },
        motivo: { type: 'string', description: 'Motivo de la llamada.' }
      },
      required: ['nombre', 'email', 'telefono', 'dia', 'hora', 'motivo']
    }
  },
  {
    name: 'solicitar_agente_humano',
    description: 'Solicita hablar directamente con un agente humano (persona real) de atención al cliente. Úsalo cuando el usuario pida hablar con una persona humana, agente o no sepas resolver una duda compleja.',
    input_schema: {
      type: 'object',
      properties: {
        motivo: { type: 'string', description: 'Breve motivo por el cual el cliente quiere hablar con un humano.' }
      },
      required: ['motivo']
    }
  },
  {
    name: 'registrar_usuario',
    description: 'Guarda los datos personales del usuario en la base de datos para asociar archivos o servicios futuros. Pide estos datos si el usuario quiere subir un archivo y no se ha identificado antes.',
    input_schema: {
      type: 'object',
      properties: {
        nombre: { type: 'string', description: 'Nombre completo.' },
        email: { type: 'string', description: 'Correo electrónico.' },
        telefono: { type: 'string', description: 'Teléfono de contacto.' }
      },
      required: ['nombre', 'email', 'telefono']
    }
  }
];

/**
 * Actualiza el resumen de memoria del cliente de forma asíncrona (fire-and-forget).
 * Se llama solo cuando Claude usa una tool, para no añadir latencia a la respuesta.
 */
async function dispararActualizacionMemoria(sessionId, messages, memoriaAnterior) {
  try {
    const ultimos = messages.slice(-6).map(m => `${m.role}: ${m.content}`).join('\n');
    const prompt = [
      'Actualiza el resumen del cliente en máximo 200 palabras.',
      'Incluye si los hay: nombre, email, teléfono, intención, última acción, incidencias.',
      `Resumen anterior: ${memoriaAnterior || '(sin resumen previo)'}`,
      `Últimos mensajes:\n${ultimos}`,
      'Devuelve solo el resumen actualizado, sin explicaciones.'
    ].join('\n');

    const res = await anthropic.messages.create({
      model: MODEL,
      max_tokens: 300,
      messages: [{ role: 'user', content: prompt }]
    });

    const nuevoResumen = res.content?.[0]?.text?.trim();
    if (nuevoResumen) {
      const memInputTokens  = res.usage?.input_tokens  || 0;
      const memOutputTokens = res.usage?.output_tokens || 0;
      const memCoste = (memInputTokens * COST_PER_INPUT_TOKEN) + (memOutputTokens * COST_PER_OUTPUT_TOKEN);
      await actualizarMemoriaUsuario(sessionId, nuevoResumen, memInputTokens, memOutputTokens, memCoste);
      console.log(`[Memoria] Resumen actualizado para sesión ${sessionId} (+${memInputTokens}in +${memOutputTokens}out +€${memCoste.toFixed(6)})`);
    }
  } catch (err) {
    console.error('[Memoria] Error al actualizar resumen:', err.message);
  }
}

// Rate limiting: protege /chat y /api/upload de abuso y bots
// Para el chat, limitamos a 20 mensajes por minuto por IP (ajustable desde .env). Para uploads, 5 por minuto.
const chatLimiter = rateLimit({
  windowMs: 60 * 1000,
  max: parseInt(process.env.RATE_LIMIT_CHAT_RPM || '20'),
  standardHeaders: true,
  legacyHeaders: false,
  message: { error: 'Demasiadas peticiones. Por favor, espera un momento.' }
});

const uploadLimiter = rateLimit({
  windowMs: 60 * 1000,
  max: 5,
  standardHeaders: true,
  legacyHeaders: false,
  message: { error: 'Demasiadas subidas. Por favor, espera un momento.' }
});

app.get('/health', (_req, res) => {
  res.json({ ok: true, service: 'chatbotReglado', model: MODEL });
});

// Endpoint temporal de diagnóstico. Retirar cuando todo funcione.
app.get('/diag', async (_req, res) => {
  const result = {
    env: {
      db_host: process.env.DB_HOST,
      db_user: process.env.DB_USER,
      db_name: process.env.DB_NAME,
      anthropic_key_set: Boolean(process.env.ANTHROPIC_API_KEY),
      notion_key_set: Boolean(process.env.NOTION_API_KEY),
      allowed_origins: process.env.ALLOWED_ORIGINS
    }
  };
  try {
    const mysql = (await import('mysql2/promise')).default;
    const conn = await mysql.createConnection({
      host: process.env.DB_HOST,
      user: process.env.DB_USER,
      password: process.env.DB_PASS,
      database: process.env.DB_NAME
    });
    const [tables] = await conn.query('SHOW TABLES');
    result.mysql = { ok: true, tables: tables.map(r => Object.values(r)[0]) };
    try {
      await conn.query("INSERT INTO conversaciones (session_id, estado) VALUES ('__diag_test__', 'IA') ON DUPLICATE KEY UPDATE last_active = NOW()");
      const [rows] = await conn.query("SELECT * FROM conversaciones WHERE session_id = '__diag_test__'");
      result.mysql.insert_test = rows.length > 0 ? 'OK' : 'FAIL';
      await conn.query("DELETE FROM conversaciones WHERE session_id = '__diag_test__'");
    } catch (e) {
      result.mysql.insert_test = `FAIL: ${e.message}`;
    }
    await conn.end();
  } catch (e) {
    result.mysql = { ok: false, error: e.message, code: e.code };
  }
  res.json(result);
});

// Endpoint para subir archivos
app.post('/api/upload', uploadLimiter, upload.single('archivo'), async (req, res) => {
  try {
    if (!req.file) {
      return res.status(400).json({ error: 'No se ha subido ningún archivo.' });
    }

    console.log('[Upload] Archivo recibido:', req.file.originalname);

    const { nombre, email, telefono, usuarioId, sessionId: uploadSessionId } = req.body;

    const archivoId = await guardarArchivo(
      usuarioId,
      nombre,
      uploadSessionId,
      req.file.originalname,
      req.file.filename,
      req.file.mimetype
    );

    res.json({
      success: true,
      archivoId,
      nombre: req.file.originalname,
      url: `/api/files/${req.file.filename}?sessionId=${uploadSessionId || ''}`,
      mensajeConfirmacion: `Tu archivo "${req.file.originalname}" ha sido subido a nuestra base de datos asignado a tu nombre (${nombre || 'Usuario'}) y demás datos personales.`
    });

    enviarEmailAvisoArchivo(process.env.INTERNAL_EMAIL, nombre, email, telefono, req.file.originalname, req.file.path);
  } catch (error) {
    console.error('[Upload Error]', error);
    res.status(500).json({ error: 'Error procesando la subida del archivo.' });
  }
});

// Archivos privados — solo accesibles con el sessionId que los subió
app.get('/api/files/:filename', async (req, res) => {
  const { filename } = req.params;
  const { sessionId } = req.query;
  if (!sessionId) return res.status(401).json({ error: 'Acceso no autorizado.' });
  const archivo = await obtenerArchivoSiSessionValida(filename, sessionId);
  if (!archivo) return res.status(403).json({ error: 'No tienes permiso para acceder a este archivo.' });
  res.sendFile(path.join(__dirname, 'uploads', filename));
});

app.post('/chat', chatLimiter, async (req, res) => {
  try {
    const { messages, domain, sessionId } = req.body || {};

    if (!messages || !Array.isArray(messages) || messages.length === 0) {
      return res.status(400).json({ error: 'Mensajes obligatorios.' });
    }

    if (!sessionId) {
      return res.status(400).json({ error: 'sessionId es obligatorio para mantener la conversación.' });
    }

    const conversacion = await obtenerConversacion(sessionId);
    const ultimoMensajeUsuario = messages[messages.length - 1].content;

    // Si ya estamos en modo humano, no invocamos a la IA, el agente responderá por Telegram
    if (conversacion.estado === 'HUMAN' || conversacion.estado === 'WAITING_HUMAN') {
      await reenviarMensajeCliente(conversacion.agente_telegram_id, ultimoMensajeUsuario, conversacion.session_id);
      return res.json({ reply: null, estado: conversacion.estado });
    }

    // Si el límite de coste ya fue alcanzado en un turno anterior (p.ej. fuera de horario),
    // bloqueamos nuevas llamadas a la IA sin consumir más tokens.
    const costePrevio = parseFloat(conversacion.cost_eur || 0);
    if (costePrevio >= COST_LIMIT_EUR) {
      return res.json({
        reply: '⚠️ Esta conversación ha alcanzado su límite de uso. Gracias por tu comprensión.',
        userContext: null,
        estado: conversacion.estado
      });
    }

    // Cargar memoria larga del cliente para enriquecer el contexto de Claude
    const memoriaResumen = await obtenerMemoriaUsuario(sessionId);

    const anthropicMessages = messages.map(msg => ({
      role: msg.role === 'user' ? 'user' : 'assistant',
      content: msg.content
    }));

    const response = await anthropic.messages.create({
      model: MODEL,
      max_tokens: 700,
      system: buildSystemPrompt(domain, memoriaResumen),
      tools: CHATBOT_TOOLS,
      messages: anthropicMessages
    });

    // Capturar uso de tokens y actualizar coste acumulado en DB
    const inputTokens  = response.usage?.input_tokens  || 0;
    const outputTokens = response.usage?.output_tokens || 0;
    const costeIncremento = (inputTokens * COST_PER_INPUT_TOKEN) + (outputTokens * COST_PER_OUTPUT_TOKEN);
    const costeTotalEur = await actualizarCosteSession(sessionId, inputTokens, outputTokens, costeIncremento);
    console.log(`[Tokens] ${sessionId}: +${inputTokens}in +${outputTokens}out (+€${costeIncremento.toFixed(6)}) → acumulado €${costeTotalEur.toFixed(6)}`);

    // Auto-detectar usuario registrado por sessionId para habilitar el clip sin pedir datos
    const usuarioExistente = await obtenerUsuarioPorSession(sessionId);

    let reply = 'Lo siento, no he podido procesar tu solicitud.';
    let userContext = usuarioExistente || null;
    let nuevoEstado = conversacion.estado;

    for (const block of response.content) {
      if (block.type === 'text') {
        reply = block.text;
      } else if (block.type === 'tool_use' && block.name === 'agendar_llamada') {
        const { nombre, email, telefono, dia: diaOriginal, hora, motivo } = block.input;
        const diaISO = convertToISO(diaOriginal);

        try {
          if (nombre.length > 100 || email.length > 100 || motivo.length > 500) {
            reply = "La información es demasiado larga. Por favor, sé más breve.";
            break;
          }
          if (!isValidEmail(email)) {
            reply = `El correo "${email}" no es válido.`;
            break;
          }
          if (!isValidPhone(telefono)) {
            reply = `El teléfono "${telefono}" no es válido.`;
            break;
          }
          const businessCheck = isBusinessTime(diaISO, hora);
          if (!businessCheck.valid) {
            reply = businessCheck.reason;
            break;
          }
          const disponible = await verificarDisponibilidadCita(diaISO, hora);
          if (!disponible) {
            reply = `El día ${diaOriginal} a las ${hora} ya está ocupado. Intenta 30 min más tarde.`;
            break;
          }

          const agenda = await agendarCita(nombre, email, telefono, diaISO, hora, motivo, sessionId);
          await enviarEmailConfirmacionCita(email, nombre, diaOriginal, hora, motivo);
          await enviarEmailAvisoInterno(process.env.INTERNAL_EMAIL, nombre, email, telefono, diaOriginal, hora, motivo);
          await agendarEnGoogleCalendar(nombre, telefono, email, diaISO, hora, motivo);

          reply = `¡Perfecto, ${nombre}! Cita agendada para el ${diaOriginal} a las ${hora}. Te hemos enviado un email a ${email}.`;
          userContext = { id: agenda.leadId, nombre, email, telefono };
        } catch (err) {
          console.error('[Error Services]', err);
          reply = 'Error técnico al agendar. Inténtalo más tarde.';
        }
      } else if (block.type === 'tool_use' && block.name === 'solicitar_agente_humano') {
        const { motivo } = block.input;
        const now = new Date();
        const yyyy = now.getFullYear();
        const mm = String(now.getMonth() + 1).padStart(2, '0');
        const dd = String(now.getDate()).padStart(2, '0');
        const HH = String(now.getHours()).padStart(2, '0');
        const MM = String(now.getMinutes()).padStart(2, '0');

        const businessCheck = isBusinessTime(`${yyyy}-${mm}-${dd}`, `${HH}:${MM}`);

        if (!businessCheck.valid) {
          reply = `Actualmente nuestros agentes no están disponibles. ${businessCheck.reason} ¿Te gustaría agendar una llamada para que nos pongamos en contacto contigo?`;
        } else {
          nuevoEstado = 'WAITING_HUMAN';
          await actualizarEstadoConversacion(sessionId, nuevoEstado);
          await notificarAgentes(conversacion.id, ultimoMensajeUsuario);
          reply = 'He avisado a un agente; por favor, espera un momento y te atenderá un humano enseguida.';
        }
        break;
      } else if (block.type === 'tool_use' && block.name === 'registrar_usuario') {
        const { nombre, email, telefono } = block.input;
        try {
          if (!isValidEmail(email)) {
            reply = `El correo "${email}" no parece válido.`;
            break;
          }
          if (!isValidPhone(telefono)) {
            reply = `El teléfono "${telefono}" no parece válido.`;
            break;
          }
          const newUser = await registrarUsuario(nombre, email, telefono, sessionId);
          reply = `¡Muchas gracias, ${nombre}! He guardado tus datos correctamente. Ya puedes subir archivos usando el botón del clip 📎 o agendar una cita para ponernos en contacto contigo.`;
          userContext = { id: newUser.id, nombre, email, telefono };
        } catch (err) {
          console.error('[Error Registrar]', err);
          reply = 'Lo siento, hubo un error al guardar tus datos.';
        }
        break;
      }
    }

    // --- Umbrales de coste --- \\

    // Solo actúa si la IA todavía controla la conversación (evitar doble handoff)
    if (nuevoEstado === 'IA') {
      if (costeTotalEur >= COST_LIMIT_EUR) {
        // Límite alcanzado: forzar handoff si hay horario laboral, avisar si no
        const now = new Date();
        const yyyy = now.getFullYear();
        const mm   = String(now.getMonth() + 1).padStart(2, '0');
        const dd   = String(now.getDate()).padStart(2, '0');
        const HH   = String(now.getHours()).padStart(2, '0');
        const MM   = String(now.getMinutes()).padStart(2, '0');
        const businessCheck = isBusinessTime(`${yyyy}-${mm}-${dd}`, `${HH}:${MM}`);

        if (businessCheck.valid) {
          nuevoEstado = 'WAITING_HUMAN';
          await actualizarEstadoConversacion(sessionId, nuevoEstado);
          await notificarAgentes(conversacion.id, ultimoMensajeUsuario);
          reply += '\n\n👋 *Esta conversación ha alcanzado su límite de uso.* Te estoy poniendo en contacto con un agente de nuestro equipo para continuar ayudándote. ¡Un momento!';
        } else {
          reply += '\n\n⚠️ *Esta conversación ha alcanzado su límite de uso.* Gracias por tu comprensión.';
        }
      } else if (costeTotalEur >= COST_WARN_EUR) {
        // Aviso suave: la conversación es extensa, se ofrece agente sin forzar
        reply += '\n\n💬 *Esta conversación está siendo bastante extensa.* Si lo prefieres, puedo conectarte con uno de nuestros agentes (Horario de L-V de 07:00 a 15:00) o agendar una cita para que te llame un agente.';
      }
    }
    // --- Fin umbrales de coste ---

    res.json({ reply, userContext, estado: nuevoEstado });

    // Actualizar memoria del cliente de forma asíncrona (fire-and-forget):
    // - Siempre que Claude use una tool (cita, registro, agente)
    // - O cada 5 mensajes en conversación normal
    const huboToolUse = response.content.some(b => b.type === 'tool_use');
    if (huboToolUse || messages.length % 5 === 0) {
      dispararActualizacionMemoria(sessionId, messages, memoriaResumen);
    }

  } catch (error) {
    console.error('[Chat Error]', error);
    res.status(500).json({ error: 'Error en el chat.' });
  }
});

// Endpoint para polling de mensajes de agente desde el widget
app.get('/api/poll_messages', async (req, res) => {
  try {
    const { sessionId } = req.query;
    if (!sessionId) return res.status(400).json({ error: 'Falta sessionId' });

    const conversacion = await obtenerConversacion(sessionId);
    if (!conversacion) return res.json({ mensajes: [], estado: 'IA' });

    // Extraer mensajes de la cola RAM y vaciarla automáticamente
    const pending = mensajesPendientes[sessionId] || [];
    mensajesPendientes[sessionId] = [];

    res.json({
      estado: conversacion.estado,
      mensajes: pending
    });

  } catch (error) {
    console.error('[Poll Error]', error);
    res.status(500).json({ error: 'Error en polling de mensajes.' });
  }
});

app.listen(PORT, () => {
  console.log(`[chatbotReglado] Servidor en puerto ${PORT}`);
});
