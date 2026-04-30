# chatbotReglado V2

**Chatbot del ecosistema Reglado**: asistente virtual integrado como widget JS
en los frontends. Responde consultas, agenda citas en Google Calendar,
guarda leads en Notion y registra archivos enviados por usuarios.

## Qué hace

- Atiende consultas de usuarios en lenguaje natural (Anthropic Claude API).
- Agenda citas en Google Calendar (verificando disponibilidad).
- Registra leads y archivos en Notion (CRM operativo).
- Envía emails de confirmación al usuario y avisos internos al equipo.
- Memoria por usuario (perfil acumulado en SQLite local).
- Widget JS embebible que se inyecta en cualquier frontend del ecosistema.

## Stack

- **Backend**: Node.js (ES modules) + Express.
- **IA**: Anthropic SDK (`@anthropic-ai/sdk`) — modelos Claude.
- **Storage**: better-sqlite3 (BD local persistente).
- **Integraciones**: Notion API, Google Calendar API, Nodemailer (email).
- **Widget**: JS vanilla servido desde el mismo backend.

## Cómo arrancar (dev)

```bash
npm install                      # solo la primera vez
cp backend/.env.example backend/.env   # ajustar API keys (Anthropic, Notion, Google)
npm run dev                      # arranca en http://localhost:3000
```

Requisitos: **Node 18+**, claves API válidas (Anthropic, Notion, Google Service Account).

El widget se carga desde otro frontend con:

```html
<script src="http://localhost:3000/widget/chatbotReglado.js"></script>
```

## Servicio en el ecosistema

- **Es consumido por**: todos los frontends del ecosistema (cargan el widget vía `<script src="https://chatbot.regladogroup.com/widget/chatbotReglado.js">`).
- **No depende de** ApiLoging para identidad — el chatbot es público y no requiere usuario logueado, pero puede recibir contexto del usuario actual si está disponible.
- Integra servicios externos: **Anthropic Claude**, **Notion**, **Google Calendar**, SMTP.

## Dominio en producción

`https://chatbot.regladogroup.com` (subdominio de `regladogroup.com`).

## Variables de entorno

**`backend/.env`:**

```
PORT=3000
ANTHROPIC_API_KEY=sk-ant-...
NOTION_API_KEY=secret_...
NOTION_DATABASE_ID=...
GOOGLE_SERVICE_ACCOUNT_JSON=./google-credentials.json
GOOGLE_CALENDAR_ID=...
SMTP_HOST=...
SMTP_USER=...
SMTP_PASS=...
EMAIL_AVISO_INTERNO=info@regladoconsultores.com
```

`google-credentials.json` está gitignored — pedirlo al admin del proyecto.

## Estructura

```
Chatbot/
├── backend/
│   ├── server.js               # Express + endpoints del chat
│   ├── services/
│   │   ├── database.js         # SQLite (sesiones, memoria, citas)
│   │   ├── emailService.js     # Nodemailer
│   │   ├── notionService.js    # archivado de leads y archivos
│   │   ├── googleCalendar.js   # disponibilidad y reservas
│   │   └── telegramService.js  # avisos por Telegram
│   └── uploads/                # archivos enviados por usuarios (gitignored)
├── widget/
│   ├── chatbotReglado.js       # widget JS embebible
│   └── assets/                 # estilos e iconos del widget
├── docs/                       # docs internos (IMPLEMENTACION, seguridad, ...)
├── test/                       # tests
└── package.json
```

## Más documentación

- [docs/IMPLEMENTACION.md](docs/IMPLEMENTACION.md) — cómo se integran los servicios externos.
- [docs/funcionalidades.md](docs/funcionalidades.md) — comportamiento del bot.
- [docs/seguridad.md](docs/seguridad.md) — consideraciones de seguridad.
- [README raíz del repo](../README.md) — visión global del ecosistema.
