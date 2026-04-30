# 🤖 Chatbot Reglado

Asistente virtual inteligente (ChatBot) para múltiples sitios web. Cuenta con un widget JavaScript premium independiente y un backend robusto basado en **Node.js + Anthropic (Claude)**.

## 🚀 Características Principales

-   **Atención 24/7**: Respuestas naturales y fluidas en español.
-   **Agenda Inteligente**: Sistema de reserva de citas telefónicas con detección de colisiones (bloques de 30 min).
-   **Reglas de Negocio**: Control estricto de horario (L-V, 07:00 - 15:00) y validaciones de seguridad.
-   **Sincronización**: Integración directa con **Google Calendar**.
-   **Notificaciones**: Confirmación por email para el cliente y aviso interno para la empresa.
-   **Subida de Archivos**: Soporte para adjuntos (JPG, PNG, PDF) durante el chat.
-   **Soporte Híbrido**: Derivación a agentes humanos vía **Telegram** con cola de mensajes en RAM.

## 🛠️ Stack Tecnológico

-   **Frontend**: JavaScript Vanilla (Inter, Google Fonts), CSS3 Dinámico.
-   **Backend**: Node.js, Express.js.
-   **IA**: Anthropic SDK (Claude Haiku 4.5).
-   **Base de Datos**: MySQL (XAMPP compatible).
-   **Email**: Nodemailer (SMTP).
-   **Archivos**: Multer.
-   **Mensajería**: Telegram Bot API. (node-telegram-bot-api)

## 📁 Estructura del Proyecto

-   `widget/chatbotReglado.js`: Script principal inyectable.
-   `backend/server.js`: Servidor API y lógica de negocio.
-   `backend/services/`: Módulos de base de datos, email y calendario.
-   `backend/uploads/`: Carpeta de almacenamiento de archivos subidos.
-   `test/index.html`: Página de demostración.

## ⚙️ Configuración (.env)

Necesitarás configurar las siguientes variables en tu archivo `.env`:
- `ANTHROPIC_API_KEY`: Tu clave de Anthropic.
- `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`: Credenciales de MySQL.
- `SMTP_HOST`, `SMTP_USER`, `SMTP_PASS`: Configuración de correo.
- `GOOGLE_APPLICATION_CREDENTIALS`: Ruta al JSON de tu Service Account de Google.
- `GOOGLE_CALENDAR_ID`: ID del calendario donde se agendarán las citas.
- `TELEGRAM_TOKEN`: Token de tu bot de Telegram.
- `TELEGRAM_AGENT_ID_1`, `TELEGRAM_AGENT_ID_2`: ID numérico de los agentes autorizados.

---
*Para ver un resumen detallado de las funciones para el usuario final, consulta [funcionalidades.md](./funcionalidades.md).*

# Utilizacion en local:

1. arranca XAMPP
2. introduce el proyecto en xampp/htdocs
3. raíz proyecto: npm install / npm run dev
4. abrir navegador el index.html
	http://localhost/Reglado-main/chatbotReglado/test/index.html


