// archivo donde se configura el envio de correos de confirmacion de citas y avisos internos.

import nodemailer from 'nodemailer';

function createTransporter() {
  // Configuraremos estas variables de entorno en .env posteriormente.
  return nodemailer.createTransport({
    host: process.env.SMTP_HOST || 'smtp.gmail.com',
    port: process.env.SMTP_PORT || 587,
    secure: process.env.SMTP_SECURE === 'true', // true para 465, false para otros
    auth: {
      user: process.env.SMTP_USER,
      pass: process.env.SMTP_PASS,
    },
  });
}

/**
 * Envía un correo de confirmación al CLIENTE tras agendar cita.
 */
async function enviarEmailConfirmacionCita(emailDestino, nombre, dia, hora, motivo) {
  if (!process.env.SMTP_USER || !process.env.SMTP_PASS) {
    console.warn('[EmailService] SMTP no configurado. Omitiendo email al cliente.');
    return;
  }

  const transporter = createTransporter();

  const textContent = `Hola ${nombre},\n\nHemos registrado tu solicitud de contacto con éxito.\n\nDetalles de la cita:\n- Día: ${dia}\n- Hora: ${hora}\n- Motivo: ${motivo}\n\nNuestro equipo se pondrá en contacto contigo pronto.\nGracias por confiar en nosotros.`;

  const htmlContent = `
    <div style="font-family: Arial, sans-serif; color: #333;">
      <h2 style="color: #0056b3;">Hola, ${nombre}</h2>
      <p>Hemos registrado tu solicitud de contacto con éxito.</p>
      <p>Nuestro equipo se pondrá en contacto contigo en la siguiente fecha:</p>
      <ul style="background: #f4f4f4; padding: 20px; list-style: none; border-radius: 8px;">
        <li><strong>Día:</strong> ${dia}</li>
        <li><strong>Hora aproximada:</strong> ${hora}</li>
        <li><strong>Motivo:</strong> ${motivo}</li>
      </ul>
      <p>Gracias por confiar en nosotros.</p>
    </div>
  `;

  try {
    const info = await transporter.sendMail({
      from: `"Equipo Reglado" <${process.env.SMTP_USER}>`,
      to: emailDestino,
      subject: 'Confirmación de su cita - Reglado',
      text: textContent,
      html: htmlContent,
      messageId: `<cita-${Date.now()}-${Math.random().toString(36).substr(2,9)}@regladoconsultores.com>`,
      headers: { 'X-Entity-Ref-ID': `cita-${Date.now()}` }
    });
    console.log('[EmailService] Email enviado al cliente: %s', info.messageId);
    return true;
  } catch (error) {
    console.error('[EmailService] Error al enviar email al cliente:', error);
    return false;
  }
}

/**
 * Envía un aviso interno a la EMPRESA con los datos del nuevo lead.
 */
async function enviarEmailAvisoInterno(emailDestino, nombre, emailLead, telefono, dia, hora, motivo) {
  if (!process.env.SMTP_USER || !process.env.SMTP_PASS) {
    console.warn('[EmailService] SMTP no configurado. Omitiendo aviso interno.');
    return;
  }

  const transporter = createTransporter();

  // Detectar si hay enlaces de archivos subidos en el motivo para destacarlos
  let fileLinksHtml = '';
  if (motivo.includes('/api/files/')) {
    const urls = motivo.match(/\/api\/files\/[^\s)]+/g) || [];
    if (urls.length > 0) {
      fileLinksHtml = '<p><strong>Archivos Adjuntos:</strong></p><ul>';
      urls.forEach(url => {
        const fullUrl = `${process.env.APP_BASE_URL || 'http://localhost:3000'}${url}`;
        fileLinksHtml += `<li><a href="${fullUrl}">${url.split('/').pop()}</a></li>`;
      });
      fileLinksHtml += '</ul>';
    }
  }

  const textContent = `NUEVA CITA REGISTRADA\n\nHola Reglado,\n\nSe ha agendado una nueva cita a través del chatbot:\n\n- Cliente: ${nombre}\n- Email: ${emailLead}\n- Teléfono: ${telefono}\n- Fecha: ${dia} a las ${hora}\n- Motivo: ${motivo}`;

  const htmlContent = `
    <div style="font-family: Arial, sans-serif; color: #333;">
      <h2 style="color: #d9534f;">¡Hola Reglado!</h2>
      <p>Se ha registrado una <strong>nueva cita</strong> a través del chatbot.</p>
      <div style="background: #f9f9f9; padding: 20px; border-left: 4px solid #d9534f;">
        <p><strong>Datos del Lead:</strong></p>
        <ul>
          <li><strong>Nombre:</strong> ${nombre}</li>
          <li><strong>Email:</strong> ${emailLead}</li>
          <li><strong>Teléfono:</strong> ${telefono}</li>
          <li><strong>Cita:</strong> ${dia} a las ${hora}</li>
          <li><strong>Motivo:</strong> ${motivo}</li>
        </ul>
        ${fileLinksHtml}
      </div>
      <p>Por favor, revisa el panel de control o el calendario para más detalles.</p>
    </div>
  `;

  try {
    const info = await transporter.sendMail({
      from: `"Sistema Chatbot" <${process.env.SMTP_USER}>`,
      to: emailDestino,
      subject: `[NUEVA CITA] - ${nombre}`,
      text: textContent,
      html: htmlContent,
      messageId: `<nuevacita-${Date.now()}-${Math.random().toString(36).substr(2,9)}@regladoconsultores.com>`,
      headers: { 'X-Entity-Ref-ID': `nuevacita-${Date.now()}` }
    });
    console.log('[EmailService] Aviso interno enviado: %s', info.messageId);
    return true;
  } catch (error) {
    console.error('[EmailService] Error al enviar aviso interno:', error);
    return false;
  }
}

/**
 * Envía un aviso interno a la EMPRESA cuando un cliente sube un archivo.
 * Incluye el archivo como adjunto.
 */
async function enviarEmailAvisoArchivo(emailDestino, nombre, emailLead, telefono, nombreOriginal, rutaArchivo) {
  if (!process.env.SMTP_USER || !process.env.SMTP_PASS) {
    console.warn('[EmailService] SMTP no configurado. Omitiendo aviso de archivo.');
    return;
  }

  const transporter = createTransporter();

  const textContent = `NUEVA SUBIDA DE ARCHIVO\n\nHola Reglado,\n\nUn cliente ha subido un archivo a través del chatbot:\n\n- Cliente: ${nombre}\n- Email: ${emailLead}\n- Teléfono: ${telefono}\n- Archivo: ${nombreOriginal}`;

  const htmlContent = `
    <div style="font-family: Arial, sans-serif; color: #333;">
      <h2 style="color: #0275d8;">¡Hola Reglado!</h2>
      <p>Se ha recibido un <strong>archivo adjunto</strong> redactado a través del chatbot.</p>
      <div style="background: #f0f7ff; padding: 20px; border-left: 4px solid #0275d8;">
        <p><strong>Datos del Cliente:</strong></p>
        <ul>
          <li><strong>Nombre:</strong> ${nombre}</li>
          <li><strong>Email:</strong> ${emailLead}</li>
          <li><strong>Teléfono:</strong> ${telefono}</li>
          <li><strong>Archivo subido:</strong> ${nombreOriginal}</li>
        </ul>
      </div>
      <p>El archivo se adjunta en este correo para tu revisión.</p>
    </div>
  `;

  try {
    const info = await transporter.sendMail({
      from: `"Sistema Chatbot" <${process.env.SMTP_USER}>`,
      to: emailDestino,
      subject: `[ARCHIVO SUBIDO] - ${nombre}`,
      text: textContent,
      html: htmlContent,
      messageId: `<archivo-${Date.now()}-${Math.random().toString(36).substr(2,9)}@regladoconsultores.com>`,
      headers: { 'X-Entity-Ref-ID': `archivo-${Date.now()}` },
      attachments: [
        {
          filename: nombreOriginal,
          path: rutaArchivo
        }
      ]
    });
    console.log('[EmailService] Aviso de archivo enviado con adjunto: %s', info.messageId);
    return true;
  } catch (error) {
    console.error('[EmailService] Error al enviar aviso de archivo con adjunto:', error);
    return false;
  }
}

export {
  enviarEmailConfirmacionCita,
  enviarEmailAvisoInterno,
  enviarEmailAvisoArchivo
};
