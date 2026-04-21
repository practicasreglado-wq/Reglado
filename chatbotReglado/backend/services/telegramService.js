import TelegramBot from 'node-telegram-bot-api';
import dotenv from 'dotenv';
import {
  asignarAgenteConversacion,
  obtenerConversacionActivaPorAgente,
  terminarConversacionHumana,
  obtenerConversacion,
  actualizarEstadoConversacion
} from './database.js';

dotenv.config();

const token = process.env.TELEGRAM_TOKEN;
const agentes = [];
const agentNames = {};
let _i = 1;
while (process.env[`TELEGRAM_AGENT_ID_${_i}`]) {
  const id = process.env[`TELEGRAM_AGENT_ID_${_i}`];
  const nombre = process.env[`TELEGRAM_AGENT_NAME_${_i}`] || `Agente ${_i}`;
  agentes.push(id);
  agentNames[id] = nombre;
  _i++;
}

let bot = null;
const notificacionMensajes = {};
export const mensajesPendientes = {}; // Cola RAM { sessionId: [{ texto, rol, fecha }] }

// Temporizadores para cierre automático por inactividad
const inactivityTimeouts = {};
// Temporizadores para conversaciones no aceptadas por agentes (5 minutos)
const notTakenTimeouts = {};

function startInactivityTimer(sessionId, agentChatId) {
  if (inactivityTimeouts[sessionId]) clearTimeout(inactivityTimeouts[sessionId]);

  // 10 minutos = 600,000 ms
  inactivityTimeouts[sessionId] = setTimeout(async () => {
    try {
      if (!mensajesPendientes[sessionId]) mensajesPendientes[sessionId] = [];
      mensajesPendientes[sessionId].push({
        texto: 'El agente de nuestro equipo ha sido desconectado por inactividad. Vuelves a hablar con el asistente virtual.',
        rol: 'BOT',
        fecha: new Date().toISOString()
      });

      await terminarConversacionHumana(sessionId);
      if (bot) {
        bot.sendMessage(agentChatId, '❌ La conversación con el cliente se ha cerrado automáticamente tras 5 minutos de inactividad.');
      }
    } catch (e) {
      console.error('[InactivityTimerError]', e);
    }
  }, 5 * 60 * 1000); // 5 MINUTOS. TIEMPO DE INACTIVIDAD EN CONVERSACION CLIENTE-AGENTE PARA QUE VUELVA A SER ATENDIDO POR EL CHATBOT
}

export function initTelegramService() {
  if (!token) return;
  if (bot) return;

  bot = new TelegramBot(token, { polling: true });
  console.log('[TelegramService] Bot de Telegram iniciado con polling.');

  bot.on('callback_query', async (query) => {
    try {
      const chatId = query.message.chat.id;
      const data = query.data;

      if (data.startsWith('toma_conv_')) {
        const conversacionId = parseInt(data.replace('toma_conv_', ''), 10);

        // Cancelar el temporizador de "no aceptada" si alguien la toma
        if (notTakenTimeouts[conversacionId]) {
          clearTimeout(notTakenTimeouts[conversacionId]);
          delete notTakenTimeouts[conversacionId];
        }

        const agenteNombre = agentNames[chatId.toString()] || 'Agente';

        await asignarAgenteConversacion(conversacionId, agenteNombre);

        // Iniciar el temporizador de inactividad de 10 minutos al tomar la charla
        const convActiva = await obtenerConversacionActivaPorAgente(agenteNombre);
        if (convActiva) {
          startInactivityTimer(convActiva.session_id, chatId);
        }

        bot.answerCallbackQuery(query.id, { text: '¡Has tomado la conversación!' });
        bot.sendMessage(chatId, `✅ Has tomado la conversación #${conversacionId}.\nPara terminar, escribe /finalizar`);

        const sentMsgs = notificacionMensajes[conversacionId] || [];
        if (sentMsgs.length > 0) {
          for (const m of sentMsgs) {
            const isOwn = m.chatId.toString() === chatId.toString();
            const appended = isOwn ? `\n\n*(Tomada por ti)*` : `\n\n*(Tomada por ${agenteNombre})*`;

            bot.editMessageText(m.originalText + appended, {
              chat_id: m.chatId,
              message_id: m.messageId,
              parse_mode: 'Markdown'
            }).catch(() => { });
          }
          delete notificacionMensajes[conversacionId];
        } else {
          bot.editMessageText(query.message.text + `\n\n*(Tomada por ti)*`, {
            chat_id: chatId,
            message_id: query.message.message_id
          }).catch(() => { });

          for (const id of agentes) {
            if (id !== chatId.toString()) {
              bot.sendMessage(id, `ℹ️ La conversación #${conversacionId} ha sido tomada por ${agenteNombre}.`);
            }
          }
        }
      }
    } catch (err) {
      console.error('[TelegramServiceError]', err);
    }
  });

  bot.on('message', async (msg) => {
    const chatIdStr = msg.chat.id.toString();
    if (!agentes.includes(chatIdStr)) return;

    if (msg.text && msg.text.startsWith('/start')) {
      bot.sendMessage(msg.chat.id, 'Hola soy el Asistente Virtual de Grupo Reglado. Esperando clientes...');
      return;
    }

    const agenteNombre = agentNames[chatIdStr] || 'Agente';

    try {
      const convActiva = await obtenerConversacionActivaPorAgente(agenteNombre);
      if (!convActiva) {
        bot.sendMessage(msg.chat.id, msg.text === '/finalizar'
          ? 'No tienes ninguna conversación activa para finalizar.'
          : '❌ No tienes ninguna conversación activa. Espera a un cliente.');
        return;
      }

      const sessionId = convActiva.session_id;
      if (!mensajesPendientes[sessionId]) mensajesPendientes[sessionId] = [];

      if (msg.text === '/finalizar') {
        if (inactivityTimeouts[sessionId]) clearTimeout(inactivityTimeouts[sessionId]);

        mensajesPendientes[sessionId].push({
          texto: 'El agente de nuestro equipo ha finalizado la conversación. Vuelves a hablar con el asistente virtual.',
          rol: 'BOT',
          fecha: new Date().toISOString()
        });

        await terminarConversacionHumana(sessionId);
        bot.sendMessage(msg.chat.id, '❌ Has finalizado la conversación con el cliente.');
        return;
      }

      mensajesPendientes[sessionId].push({
        texto: msg.text || '[Archivo no soportado en versión actual]',
        rol: 'HUMANO',
        fecha: new Date().toISOString()
      });

      // Renovar temporizador al responder
      startInactivityTimer(sessionId, msg.chat.id);

    } catch (err) {
      console.error('[TelegramServiceError handling message]', err);
    }
  });
}

export async function notificarAgentes(conversacionId, mensajeUsuario) {
  if (!bot && token) initTelegramService();
  if (!bot) return;

  const texto = `🚨 *Nuevo cliente necesita agente*\n\nConversación #${conversacionId}\n\nMotivo: "${mensajeUsuario}"`;
  const opciones = {
    parse_mode: 'Markdown',
    reply_markup: {
      inline_keyboard: [[{ text: '🙋‍♂️ Tomar conversación', callback_data: `toma_conv_${conversacionId}` }]]
    }
  };

  notificacionMensajes[conversacionId] = [];
  for (const agenteId of agentes) {
    try {
      const agenteNombre = agentNames[agenteId] || 'Agente';
      const convActiva = await obtenerConversacionActivaPorAgente(agenteNombre);

      if (convActiva) {
        // Agente ocupado: recibe aviso sin botón
        const textoOcupado = texto + '\n\n_(Estás ocupado atendiendo otra conversación)_';
        bot.sendMessage(agenteId, textoOcupado, { parse_mode: 'Markdown' })
          .catch(err => console.error(`[Error enviando a ${agenteId}]:`, err.message));
      } else {
        // Agente libre: recibe aviso con botón y se registra para edición posterior
        const sentMsg = await bot.sendMessage(agenteId, texto, opciones);
        notificacionMensajes[conversacionId].push({
          chatId: sentMsg.chat.id,
          messageId: sentMsg.message_id,
          originalText: texto
        });
      }
    } catch (err) {
      console.error(`[Error enviando a ${agenteId}]:`, err.message);
    }
  }

  // Iniciar temporizador de 5 minutos para retorno automático a IA si ningún agente acepta
  if (notTakenTimeouts[conversacionId]) clearTimeout(notTakenTimeouts[conversacionId]);
  notTakenTimeouts[conversacionId] = setTimeout(async () => {
    try {
      const conv = await obtenerConversacion(null, conversacionId);
      if (!conv || conv.estado !== 'WAITING_HUMAN') return;

      const sessionId = conv.session_id;
      if (!mensajesPendientes[sessionId]) mensajesPendientes[sessionId] = [];

      mensajesPendientes[sessionId].push({
        texto: 'Lo sentimos, en este momento nuestros agentes están ocupados. Por favor, agendemos una cita telefónica para atenderte mejor.',
        rol: 'BOT',
        fecha: new Date().toISOString()
      });

      await actualizarEstadoConversacion(sessionId, 'IA');

      // Notificar a los agentes que la petición ha expirado
      const sentMsgs = notificacionMensajes[conversacionId] || [];
      for (const m of sentMsgs) {
        bot.editMessageText(m.originalText + `\n\n*(Expirada: No aceptada a tiempo)*`, {
          chat_id: m.chatId,
          message_id: m.messageId,
          parse_mode: 'Markdown'
        }).catch(() => { });
      }
      delete notificacionMensajes[conversacionId];
      delete notTakenTimeouts[conversacionId];

    } catch (err) {
      console.error('[TimeoutNotTakenError]', err);
    }
  }, 5 * 60 * 1000); // 5 MINUTOS. TIEMPO QUE TIENE EL AGENTE PARA ACEPTAR LA CONVERSACION
}

export async function reenviarMensajeCliente(agenteNombre, mensajeUsuario, sessionId) {
  if (!bot) return;
  if (agenteNombre) {
    const chatId = Object.keys(agentNames).find(key => agentNames[key] === agenteNombre);
    if (chatId) {
      bot.sendMessage(chatId, `👤 *Cliente*: ${mensajeUsuario}`, { parse_mode: 'Markdown' });
      // Renovar temporizador al recibir mensaje del cliente
      if (sessionId) startInactivityTimer(sessionId, chatId);
    }
  } else {
    for (const id of agentes) bot.sendMessage(id, `👤 *Cliente (Esperando)*: ${mensajeUsuario}`, { parse_mode: 'Markdown' });
  }
}
