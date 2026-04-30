// aqui se configura el envio de citas a google calendar

import { google } from 'googleapis';

// NOTA: Para que esto funcione realmente, el usuario deberá descargar un archivo JSON
// de credenciales de Google Service Account y guardarlo en la carpeta raiz o backend
// y poner su ruta en el .env como GOOGLE_APPLICATION_CREDENTIALS.
// Además de configurar el GOOGLE_CALENDAR_ID. (HECHO).

function getAuth() {
  if (!process.env.GOOGLE_APPLICATION_CREDENTIALS) {
    return null;
  }

  return new google.auth.GoogleAuth({
    scopes: ['https://www.googleapis.com/auth/calendar.events'],
  });
}

/**
 * Crea un evento en Google Calendar dentro del horario de 7:00 a 15:00.
 * La fecha (dia) debe ser YYYY-MM-DD y la hora HH:MM.
 */
async function agendarEnGoogleCalendar(nombre, telefono, email, dia, hora, motivo) {
  const auth = getAuth();
  if (!auth) {
    console.warn('[GoogleCalendar] No hay credenciales configuradas. Omitiendo creación de evento.');
    return null;
  }

  const calendarId = process.env.GOOGLE_CALENDAR_ID;
  if (!calendarId) {
    console.warn('[GoogleCalendar] No hay GOOGLE_CALENDAR_ID configurado.');
    return null;
  }

  const calendar = google.calendar({ version: 'v3', auth });

  // Construir las fechas ISO
  const startDateTime = new Date(`${dia}T${hora}:00`);

  // Asumimos que la cita dura 30 minutos por defecto
  const endDateTime = new Date(startDateTime.getTime() + 30 * 60000);

  const event = {
    summary: `Reunión telefónica - ${nombre}`,
    description: `Lead contactado a través del chatbot.\nTeléfono: ${telefono}\nEmail: ${email}\nMotivo: ${motivo}`,
    start: {
      dateTime: startDateTime.toISOString(),
      timeZone: 'Europe/Madrid', // Ajustar zona horaria si es necesario
    },
    end: {
      dateTime: endDateTime.toISOString(),
      timeZone: 'Europe/Madrid',
    },
  };

  try {
    const response = await calendar.events.insert({
      calendarId: calendarId,
      resource: event,
    });
    console.log('[GoogleCalendar] Evento creado:', response.data.htmlLink);
    return response.data.htmlLink;
  } catch (error) {
    console.error('[GoogleCalendar] Error al crear el evento:', error);
    return null;
  }
}

export {
  agendarEnGoogleCalendar
};
