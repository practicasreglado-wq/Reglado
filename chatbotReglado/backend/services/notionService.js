// Servicio de sincronización con Notion (mirror de usuarios y citas).
// MySQL es la fuente de verdad; Notion es un espejo.
// Si Notion falla, se loguea pero NO se tumba el flujo del chatbot.

import { Client } from '@notionhq/client';
import dotenv from 'dotenv';

dotenv.config();

const apiKey = process.env.NOTION_API_KEY;
const dbUsuarios = process.env.NOTION_DB_USUARIOS;
const dbCitas = process.env.NOTION_DB_CITAS;

const enabled = Boolean(apiKey && dbUsuarios && dbCitas);
const notion = enabled ? new Client({ auth: apiKey }) : null;

if (!enabled) {
  console.warn('[Notion] Desactivado: falta NOTION_API_KEY / NOTION_DB_USUARIOS / NOTION_DB_CITAS en .env');
}

// MySQL usa estados en femenino ("cita"), Notion usa masculino.
const ESTADO_MAP = {
  pendiente: 'pendiente',
  confirmada: 'confirmado',
  cancelada: 'cancelado',
  finalizada: 'finalizado'
};

/**
 * Crea una página en la DB USUARIOS de Notion.
 * No bloquea el flujo si falla.
 */
async function crearUsuarioNotion({ nombre, email, telefono }) {
  if (!enabled) return null;
  try {
    const response = await notion.pages.create({
      parent: { database_id: dbUsuarios },
      properties: {
        Nombre: { title: [{ text: { content: nombre || 'Sin nombre' } }] },
        Email: email ? { email } : { email: null },
        'Teléfono': telefono ? { phone_number: telefono } : { phone_number: null }
      }
    });
    console.log(`[Notion] Usuario creado: ${nombre} (${response.id})`);
    return response.id;
  } catch (error) {
    console.error('[Notion] Error creando usuario:', error.body || error.message);
    return null;
  }
}

/**
 * Crea una página en la DB CITAS TELEFÓNICAS de Notion.
 * Combina fecha (YYYY-MM-DD) + hora (HH:MM:SS) en un datetime ISO.
 */
async function crearCitaNotion({ nombre, fecha, hora, motivo, estado = 'pendiente' }) {
  if (!enabled) return null;
  try {
    const fechaIso = construirFechaIso(fecha, hora);
    const estadoNotion = ESTADO_MAP[estado] || 'pendiente';

    const response = await notion.pages.create({
      parent: { database_id: dbCitas },
      properties: {
        Nombre: { title: [{ text: { content: `Cita - ${nombre || 'Sin nombre'}` } }] },
        Cliente: { rich_text: [{ text: { content: nombre || '' } }] },
        Fecha: fechaIso ? { date: { start: fechaIso } } : { date: null },
        Motivo: { rich_text: [{ text: { content: motivo || '' } }] },
        Estado: { select: { name: estadoNotion } }
      }
    });
    console.log(`[Notion] Cita creada: ${nombre} ${fecha} ${hora} (${response.id})`);
    return response.id;
  } catch (error) {
    console.error('[Notion] Error creando cita:', error.body || error.message);
    return null;
  }
}

/**
 * Combina una fecha (Date o string YYYY-MM-DD) y una hora (string HH:MM:SS o HH:MM)
 * en un datetime ISO válido para Notion. Devuelve null si la fecha no es parseable.
 */
function construirFechaIso(fecha, hora) {
  if (!fecha) return null;
  const fechaStr = fecha instanceof Date
    ? fecha.toISOString().slice(0, 10)
    : String(fecha).slice(0, 10);
  const horaStr = hora ? String(hora).slice(0, 8).padEnd(8, '0').replace(/^(\d{2}:\d{2})$/, '$1:00') : '00:00:00';
  const normalizada = /^\d{2}:\d{2}:\d{2}$/.test(horaStr) ? horaStr : `${horaStr}:00`;
  const iso = `${fechaStr}T${normalizada}`;
  return isNaN(new Date(iso).getTime()) ? null : iso;
}

export {
  crearUsuarioNotion,
  crearCitaNotion
};
