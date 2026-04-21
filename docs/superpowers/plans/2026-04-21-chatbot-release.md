# chatbotReglado Release Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Generate a deployable release of chatbotReglado at `ReleasesEstables/chatbotReglado/`, ready to upload to Hostinger Node.js App via SFTP.

**Architecture:** Copy-based release. Clone source tree verbatim excluding dev artifacts (node_modules, docs, test, .env, google-credentials.json). Apply 3 targeted modifications: widget apiUrl default → production URL, emailService APP_BASE_URL fallback → production URL, rename `imageChatbot.png` → `imagechatbot.png` to fix Linux case-sensitivity. Generate production `.env`, `.gitignore` and `DEPLOY.md`.

**Tech Stack:** File-system operations on Windows Git Bash. No runtime, no tests, no dependencies to install.

**Source root:** `c:/xampp/htdocs/Reglado/chatbotReglado/`
**Destination root:** `c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/`

---

### Task 1: Scaffold directory tree

**Files:**
- Create: `ReleasesEstables/chatbotReglado/backend/services/`
- Create: `ReleasesEstables/chatbotReglado/backend/sql/`
- Create: `ReleasesEstables/chatbotReglado/backend/uploads/`
- Create: `ReleasesEstables/chatbotReglado/widget/assets/`
- Create: `ReleasesEstables/chatbotReglado/backend/uploads/.gitkeep`

- [ ] **Step 1: Create all directories with one command**

Run:
```bash
mkdir -p c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/services \
         c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/sql \
         c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/uploads \
         c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/widget/assets
```

- [ ] **Step 2: Create `.gitkeep` inside `uploads/` using Write tool**

Create empty file at `c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/uploads/.gitkeep` with content `` (empty).

- [ ] **Step 3: Verify tree structure**

Run:
```bash
find c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado -type d
```
Expected output (4 directories + root):
```
c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado
c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend
c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/services
c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/sql
c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/uploads
c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/widget
c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/widget/assets
```

---

### Task 2: Copy backend files verbatim

**Files to copy (4 verbatim, 1 modified in Task 3):**
- Copy: `backend/server.js`
- Copy: `backend/services/database.js`
- Copy: `backend/services/googleCalendar.js`
- Copy: `backend/services/notionService.js`
- Copy: `backend/services/telegramService.js`
- Copy: `backend/sql/chatbot_reglado.sql`

- [ ] **Step 1: Copy the 6 backend files**

Run:
```bash
cp c:/xampp/htdocs/Reglado/chatbotReglado/backend/server.js \
   c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/server.js

cp c:/xampp/htdocs/Reglado/chatbotReglado/backend/services/database.js \
   c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/services/database.js

cp c:/xampp/htdocs/Reglado/chatbotReglado/backend/services/googleCalendar.js \
   c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/services/googleCalendar.js

cp c:/xampp/htdocs/Reglado/chatbotReglado/backend/services/notionService.js \
   c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/services/notionService.js

cp c:/xampp/htdocs/Reglado/chatbotReglado/backend/services/telegramService.js \
   c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/services/telegramService.js

cp c:/xampp/htdocs/Reglado/chatbotReglado/backend/sql/chatbot_reglado.sql \
   c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/sql/chatbot_reglado.sql
```

- [ ] **Step 2: Verify byte-level equality against source**

Run:
```bash
diff -q c:/xampp/htdocs/Reglado/chatbotReglado/backend/server.js \
        c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/server.js && \
diff -q c:/xampp/htdocs/Reglado/chatbotReglado/backend/services/database.js \
        c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/services/database.js && \
diff -q c:/xampp/htdocs/Reglado/chatbotReglado/backend/services/notionService.js \
        c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/services/notionService.js && \
diff -q c:/xampp/htdocs/Reglado/chatbotReglado/backend/services/telegramService.js \
        c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/services/telegramService.js && \
diff -q c:/xampp/htdocs/Reglado/chatbotReglado/backend/services/googleCalendar.js \
        c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/services/googleCalendar.js && \
diff -q c:/xampp/htdocs/Reglado/chatbotReglado/backend/sql/chatbot_reglado.sql \
        c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/sql/chatbot_reglado.sql && \
echo "ALL 6 FILES IDENTICAL"
```
Expected: `ALL 6 FILES IDENTICAL` (no diff output for any pair).

---

### Task 3: Copy `emailService.js` with production fallback

**Files:**
- Copy-with-modification: `backend/services/emailService.js`

Change: line 81 — fallback of `APP_BASE_URL` from `http://localhost:3000` to `https://chatbot.regladogroup.com`. Everything else byte-identical.

- [ ] **Step 1: Copy the source file to the destination**

Run:
```bash
cp c:/xampp/htdocs/Reglado/chatbotReglado/backend/services/emailService.js \
   c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/services/emailService.js
```

- [ ] **Step 2: Apply the modification using the Edit tool**

Edit `c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/services/emailService.js`:

Replace:
```javascript
        const fullUrl = `${process.env.APP_BASE_URL || 'http://localhost:3000'}${url}`;
```
With:
```javascript
        const fullUrl = `${process.env.APP_BASE_URL || 'https://chatbot.regladogroup.com'}${url}`;
```

- [ ] **Step 3: Verify the modification is the ONLY diff from source**

Run:
```bash
diff c:/xampp/htdocs/Reglado/chatbotReglado/backend/services/emailService.js \
     c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/services/emailService.js
```
Expected output (1 line changed):
```
81c81
<         const fullUrl = `${process.env.APP_BASE_URL || 'http://localhost:3000'}${url}`;
---
>         const fullUrl = `${process.env.APP_BASE_URL || 'https://chatbot.regladogroup.com'}${url}`;
```

---

### Task 4: Copy widget assets and fix filename casing

**Files:**
- Copy: `widget/assets/imageCHAT.png`
- Copy: `widget/assets/imageChatbot-Robot.png`
- Copy & rename: `widget/assets/imageChatbot.png` → `widget/assets/imagechatbot.png` *(fixes Linux case-sensitive mismatch)*
- Copy: `widget/assets/imageChatbot1.png`
- Copy: `widget/assets/imageChatbot2.png`
- Copy: `widget/assets/imageChatbot33.png`
- Copy: `widget/assets/robot.png`

- [ ] **Step 1: Copy all 7 PNGs, renaming `imageChatbot.png` → `imagechatbot.png`**

Run:
```bash
cp c:/xampp/htdocs/Reglado/chatbotReglado/widget/assets/imageCHAT.png \
   c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/widget/assets/imageCHAT.png

cp c:/xampp/htdocs/Reglado/chatbotReglado/widget/assets/imageChatbot-Robot.png \
   c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/widget/assets/imageChatbot-Robot.png

cp c:/xampp/htdocs/Reglado/chatbotReglado/widget/assets/imageChatbot.png \
   c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/widget/assets/imagechatbot.png

cp c:/xampp/htdocs/Reglado/chatbotReglado/widget/assets/imageChatbot1.png \
   c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/widget/assets/imageChatbot1.png

cp c:/xampp/htdocs/Reglado/chatbotReglado/widget/assets/imageChatbot2.png \
   c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/widget/assets/imageChatbot2.png

cp c:/xampp/htdocs/Reglado/chatbotReglado/widget/assets/imageChatbot33.png \
   c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/widget/assets/imageChatbot33.png

cp c:/xampp/htdocs/Reglado/chatbotReglado/widget/assets/robot.png \
   c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/widget/assets/robot.png
```

- [ ] **Step 2: Verify 7 PNGs exist in the destination**

Run:
```bash
ls c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/widget/assets/ | wc -l
```
Expected: `7`

Run:
```bash
ls c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/widget/assets/
```
Expected: `imageCHAT.png, imageChatbot-Robot.png, imagechatbot.png, imageChatbot1.png, imageChatbot2.png, imageChatbot33.png, robot.png` (all lowercase `imagechatbot.png`).

---

### Task 5: Copy `chatbotReglado.js` with production `apiUrl`

**Files:**
- Copy-with-modification: `widget/chatbotReglado.js`

Change: line 21 — `apiUrl` default from `http://localhost:3000/chat` to `https://chatbot.regladogroup.com/chat`. Everything else byte-identical.

- [ ] **Step 1: Copy the source file to the destination**

Run:
```bash
cp c:/xampp/htdocs/Reglado/chatbotReglado/widget/chatbotReglado.js \
   c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/widget/chatbotReglado.js
```

- [ ] **Step 2: Apply the modification using Edit tool**

Edit `c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/widget/chatbotReglado.js`:

Replace:
```javascript
    apiUrl: 'http://localhost:3000/chat',
```
With:
```javascript
    apiUrl: 'https://chatbot.regladogroup.com/chat',
```

- [ ] **Step 3: Verify the modification is the ONLY diff from source**

Run:
```bash
diff c:/xampp/htdocs/Reglado/chatbotReglado/widget/chatbotReglado.js \
     c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/widget/chatbotReglado.js
```
Expected:
```
21c21
<     apiUrl: 'http://localhost:3000/chat',
---
>     apiUrl: 'https://chatbot.regladogroup.com/chat',
```

---

### Task 6: Copy `package.json` and `package-lock.json`

**Files:**
- Copy: `package.json`
- Copy: `package-lock.json`

- [ ] **Step 1: Copy both manifests verbatim**

Run:
```bash
cp c:/xampp/htdocs/Reglado/chatbotReglado/package.json \
   c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/package.json

cp c:/xampp/htdocs/Reglado/chatbotReglado/package-lock.json \
   c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/package-lock.json
```

- [ ] **Step 2: Verify byte-level equality**

Run:
```bash
diff -q c:/xampp/htdocs/Reglado/chatbotReglado/package.json \
        c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/package.json && \
diff -q c:/xampp/htdocs/Reglado/chatbotReglado/package-lock.json \
        c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/package-lock.json && \
echo "MANIFESTS IDENTICAL"
```
Expected: `MANIFESTS IDENTICAL`.

---

### Task 7: Generate production `.env`

**Files:**
- Create: `ReleasesEstables/chatbotReglado/.env`

Body: copy of dev `.env` with exactly 2 value changes (`ALLOWED_ORIGINS`, `APP_BASE_URL`) and 1 comment change (DB block header).

- [ ] **Step 1: Create `.env` with production values using the Write tool**

Write to `c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/.env` with this content:

```
# Función: Archivo de configuración que contiene las variables de entorno necesarias para el funcionamiento del backend y su conexión con el modelo de IA.
PORT=3000
ANTHROPIC_API_KEY=<REDACTED — ver .env de dev>    # copiar literal del .env del workspace dev
ANTHROPIC_MODEL=claude-haiku-4-5
ALLOWED_ORIGINS=https://regladogroup.com,https://www.regladogroup.com,https://regladoenergy.com,https://www.regladoenergy.com,https://regladoingenieria.com,https://www.regladoingenieria.com,https://chatbot.regladogroup.com

# Configuración Base de Datos (MySQL en Hostinger)
# ⚠️ Rellenar estos 4 valores desde hPanel → Bases de Datos MySQL tras crear la DB dedicada del chatbot.
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=chatbot_reglado

# Configuración SMTP (Email corporativo Hostinger)
SMTP_HOST=smtp.hostinger.com
SMTP_PORT=465
SMTP_SECURE=true
SMTP_USER=info@regladoconsultores.com
SMTP_PASS=<REDACTED — ver .env de dev>

# Configuración Google Calendar
GOOGLE_APPLICATION_CREDENTIALS=backend/google-credentials.json
GOOGLE_CALENDAR_ID=regladoconsultores@gmail.com

# Configuración Telegram
# Para añadir agentes ejemplo: TELEGRAM_AGENT_ID_2, TELEGRAM_AGENT_NAME_2, etc...
TELEGRAM_TOKEN=<REDACTED — ver .env de dev>
TELEGRAM_AGENT_ID_1=5621051906
TELEGRAM_AGENT_NAME_1=Alexandra

# Configuración Notion (mirror de usuarios y citas)
NOTION_API_KEY=<REDACTED — ver .env de dev>
NOTION_DB_USUARIOS=3495fb53f79580c8927ecd844dc2620b
NOTION_DB_CITAS=3495fb53f795805cadeef68f8f480b84

# URL base del servidor (producción)
APP_BASE_URL=https://chatbot.regladogroup.com

# Email interno para avisos (archivo subido, cita agendada)
INTERNAL_EMAIL=regladoconsultores@gmail.com

# Rate limiting (Ajustable). Seguridad antibots y abuso:
# Limita el número de solicitudes por minuto (20) y limite se uploads por hora (3).
RATE_LIMIT_CHAT_RPM=20
RATE_LIMIT_UPLOAD_RPH=3

# Memoria larga cliente-IA: días antes de borrar resúmenes inactivos
MEMORY_TTL_DAYS=15

# Límite de coste por sesión de chatbot
# Precios por millón de tokens en EUR (Claude Haiku-4-5 × tasa USD/EUR ~0.92)
COST_INPUT_PER_MTOK=0.736
COST_OUTPUT_PER_MTOK=2.208

# Aviso suave al usuario cuando se acerca al límite
COST_WARN_EUR=0.60
# COST_WARN_EUR=0.005 (pruebas)

# Límite máximo: se fuerza handoff a agente (o aviso de cita si es fuera de horario)
COST_LIMIT_EUR=0.80
# COST_LIMIT_EUR=0.010 (pruebas)
```

- [ ] **Step 2: Verify the 2 mandated value changes and nothing else**

Run:
```bash
diff c:/xampp/htdocs/Reglado/chatbotReglado/.env \
     c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/.env
```
Expected changes (visually inspect the diff):
- Line ~5: `ALLOWED_ORIGINS` changed to the 7 production origins.
- Lines ~7-9: DB comment block rewritten to reference Hostinger / hPanel instead of XAMPP.
- Line ~37-38: `APP_BASE_URL` comment rewritten and value changed to `https://chatbot.regladogroup.com`.

No other key=value pair should differ. No changes to `ANTHROPIC_*`, `DB_*` values, `SMTP_*`, `GOOGLE_*`, `TELEGRAM_*`, `NOTION_*`, `INTERNAL_EMAIL`, `RATE_LIMIT_*`, `MEMORY_TTL_DAYS`, `COST_*`.

---

### Task 8: Generate `.gitignore`

**Files:**
- Create: `ReleasesEstables/chatbotReglado/.gitignore`

- [ ] **Step 1: Create `.gitignore` using the Write tool**

Write to `c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/.gitignore`:

```
node_modules/
backend/uploads/*
!backend/uploads/.gitkeep
backend/google-credentials.json
.env
```

- [ ] **Step 2: Verify the file exists and has 5 rules**

Run:
```bash
wc -l c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/.gitignore
```
Expected: `5 <path>` (5 lines).

---

### Task 9: Generate `DEPLOY.md`

**Files:**
- Create: `ReleasesEstables/chatbotReglado/DEPLOY.md`

- [ ] **Step 1: Create `DEPLOY.md` using the Write tool**

Write to `c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/DEPLOY.md`:

````markdown
# Deploy Guide — chatbotReglado → chatbot.regladogroup.com

Guía paso a paso para desplegar este release en Hostinger hPanel (Node.js App).
Target URL pública: `https://chatbot.regladogroup.com`.

---

## Paso 1 — Crear Node.js App en hPanel

1. hPanel → **Avanzado → Node.js**.
2. **Crear aplicación**:
   - Versión Node: **20.x** (o la más reciente LTS disponible).
   - Application mode: **Production**.
   - Application root: `chatbot.regladogroup.com` (carpeta que creará Hostinger).
   - Application URL: `chatbot.regladogroup.com`.
   - Application startup file: `backend/server.js`.
3. **Save / Create**.

## Paso 2 — Crear base de datos MySQL

1. hPanel → **Bases de datos → MySQL**.
2. **Crear nueva base de datos**:
   - Nombre: `u<PREFIJO>_chatbot` (p.ej. `u123456789_chatbot`).
   - Usuario: `u<PREFIJO>_chatbot`.
   - Contraseña: generar una fuerte y guardarla.
3. Anotar: `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`.

## Paso 3 — Subir el código por SFTP

Subir **todo el contenido** de este release (la carpeta `ReleasesEstables/chatbotReglado/` completa, incluido `.env`, `.gitignore`, `package.json`, `package-lock.json`, `backend/`, `widget/`, `DEPLOY.md`) al `application root` creado en el paso 1.

Usa FileZilla o el File Manager de hPanel.

**NO subir:** `node_modules/` (no existe en el release; se instala en el paso 5).

## Paso 4 — Subir `google-credentials.json`

Por seguridad este archivo no viaja en el release. Súbelo manualmente vía SFTP a:
`<application_root>/backend/google-credentials.json`

(Cópialo del backend dev en tu máquina: `c:/xampp/htdocs/Reglado/chatbotReglado/backend/google-credentials.json`.)

## Paso 5 — Instalar dependencias

En hPanel → Node.js App de chatbotReglado:
1. Botón **Run NPM Install** (o desde terminal SSH: `npm install --omit=dev`).
2. Esperar a que termine. Debe crearse `node_modules/` en el servidor.

Si `mysql2` o `better-sqlite3` fallan al compilar binarios nativos, abrir ticket de soporte Hostinger pidiendo compilar con la versión de Node seleccionada.

## Paso 6 — Rellenar variables de entorno

El release trae un `.env` ya casi completo. Sólo hay que rellenar los 4 valores de DB del paso 2.

Opción A (editor web en hPanel):
1. File Manager → `<application_root>/.env`.
2. Editar `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`.
3. Guardar.

Opción B (terminal SSH):
```
nano .env
```
Editar los 4 `DB_*`.

## Paso 7 — Importar el schema SQL

1. hPanel → **phpMyAdmin** → seleccionar la DB del paso 2.
2. Pestaña **Importar** → elegir `backend/sql/chatbot_reglado.sql` (desde el servidor vía SFTP o subirlo localmente).
3. **Continuar** — debe crear las 5 tablas: `usuarios`, `citas`, `archivos`, `conversaciones`, `memoria_usuario`.

## Paso 8 — Configurar DNS del subdominio

1. hPanel → **Dominios → Subdominios**.
2. **Crear subdominio** `chatbot` bajo `regladogroup.com`.
3. Apuntar a la Application root del paso 1 (hPanel suele hacerlo automáticamente al marcar la app como subdominio).
4. Esperar propagación DNS (1-15 min normalmente).

## Paso 9 — Activar SSL (Let's Encrypt)

1. hPanel → **SSL** → seleccionar `chatbot.regladogroup.com`.
2. **Instalar Let's Encrypt** (gratis).
3. Esperar validación (~1 min).
4. Activar **Force HTTPS**.

## Paso 10 — Arrancar la aplicación

En hPanel → Node.js App → **Restart / Start**.

Comprobar estado: debe indicar "Running" con tiempo de uptime.

## Paso 11 — Health check

```
curl https://chatbot.regladogroup.com/health
```
Esperado:
```
{"ok":true,"service":"chatbotReglado","model":"claude-haiku-4-5"}
```

Si falla:
- Revisar logs en hPanel → Node.js App → **Logs**.
- Errores típicos: `ECONNREFUSED` (MySQL mal configurado), `ENOENT google-credentials.json` (no se subió), `listen EADDRINUSE` (Passenger ya usa el puerto → dejar `PORT` sin valor o cambiar en hPanel).

## Paso 12 — **Apagar el backend dev local**

⚠️ Importante. El bot de Telegram no soporta dos procesos en modo polling con el mismo token. Si dev y prod corren a la vez, uno de los dos rechazará peticiones con error 409.

```
taskkill //F //IM node.exe
```
(o más quirúrgico: matar sólo el `node backend/server.js` de chatbotReglado dev.)

## Paso 13 — Smoke test del widget

Abrir una de las webs del ecosistema que ya embeba el widget (ej. `https://regladogroup.com`).

Si alguna web aún no tiene el widget, añadir en su `<head>`:
```html
<script src="https://chatbot.regladogroup.com/widget/chatbotReglado.js" defer></script>
```

Probar:
1. Abrir el chat → saludo bot aparece.
2. Enviar un mensaje → respuesta IA llega.

## Paso 14 — Sanity de integraciones

1. En el chat pedir: *"quiero agendar una cita"*.
2. Responder con datos de prueba en horario laboral.
3. Verificar:
   - Email de confirmación recibido en la cuenta del cliente de prueba.
   - Email interno recibido en `regladoconsultores@gmail.com`.
   - Cita aparecida en Google Calendar de `regladoconsultores@gmail.com`.
   - Usuario creado en Notion DB "USUARIOS" y cita en Notion DB "CITAS TELEFÓNICAS".
   - Registros en phpMyAdmin (`usuarios` y `citas`).

## Paso 15 — Vigilar logs 24 h

- hPanel → Node.js App → Logs → pegar pestaña.
- Buscar: `[Notion] Error`, `[Email] Error`, `ECONNREFUSED`, `UnhandledPromiseRejection`.
- Si todo limpio a las 24h, el release está estable.

---

## Rollback rápido

Si algo falla y hay que volver al dev local:
1. hPanel → **Stop** la Node.js App.
2. Cambiar DNS del subdominio para que no resuelva (opcional).
3. Reiniciar el backend dev local tal cual estaba.

## Credenciales reutilizadas

Este release usa las mismas credenciales que dev para:
- Anthropic API key
- SMTP (info@regladoconsultores.com)
- Telegram bot (Alexandra)
- Notion integration
- Google Calendar service account

Si necesitas rotarlas en el futuro, cámbialas en el `.env` del servidor y reinicia la app.
````

- [ ] **Step 2: Verify file created and non-empty**

Run:
```bash
wc -l c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/DEPLOY.md
```
Expected: >= 100 lines.

---

### Task 10: Final acceptance check

- [ ] **Step 1: Verify complete tree structure**

Run:
```bash
find c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado -type f -not -path '*/node_modules/*' | sort
```
Expected output (21 files):
```
c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/.env
c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/.gitignore
c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/DEPLOY.md
c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/server.js
c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/services/database.js
c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/services/emailService.js
c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/services/googleCalendar.js
c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/services/notionService.js
c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/services/telegramService.js
c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/sql/chatbot_reglado.sql
c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/uploads/.gitkeep
c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/package-lock.json
c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/package.json
c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/widget/chatbotReglado.js
c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/widget/assets/imageCHAT.png
c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/widget/assets/imageChatbot-Robot.png
c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/widget/assets/imageChatbot1.png
c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/widget/assets/imageChatbot2.png
c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/widget/assets/imageChatbot33.png
c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/widget/assets/imagechatbot.png
c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/widget/assets/robot.png
```
(21 files total: 3 root configs `.env` + `.gitignore` + `DEPLOY.md`, 2 manifests `package.json` + `package-lock.json`, 7 backend source files, 1 `.gitkeep`, 1 widget JS, 7 widget PNGs.)

- [ ] **Step 2: Verify no forbidden artifacts**

Run:
```bash
[ ! -d c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/node_modules ] && \
[ ! -d c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/docs ] && \
[ ! -d c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/test ] && \
[ ! -f c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/google-credentials.json ] && \
echo "NO FORBIDDEN ARTIFACTS"
```
Expected: `NO FORBIDDEN ARTIFACTS`.

- [ ] **Step 3: Verify production URL references in modified files**

Run:
```bash
grep -c 'chatbot.regladogroup.com' \
  c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/.env \
  c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/widget/chatbotReglado.js \
  c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/services/emailService.js
```
Expected: each file should have at least 1 match:
```
.env: >= 2 (ALLOWED_ORIGINS + APP_BASE_URL)
widget/chatbotReglado.js: 1
emailService.js: 1
```

- [ ] **Step 4: Verify no `localhost` leaks in files that shouldn't have it**

Run:
```bash
grep -n 'localhost' c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/widget/chatbotReglado.js \
                    c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/backend/services/emailService.js
```
Expected: no matches (or at most comments/unrelated usages — inspect if any appear).

Note: the `.env` intentionally keeps `DB_HOST=localhost` because that's what Hostinger expects for its MySQL. That's fine.

- [ ] **Step 5: Print release summary**

Run:
```bash
echo "=== RELEASE SUMMARY ==="
du -sh c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/
echo "Files:"
find c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado -type f -not -path '*/node_modules/*' | wc -l
```

Expected: release ~3-4 MB total (mostly PNGs of `widget/assets/`), 21 files.

---

## Post-completion

Once all 10 tasks pass, report to the user:
- Release path: `c:/xampp/htdocs/Reglado/ReleasesEstables/chatbotReglado/`
- Total size
- File count
- Link to `DEPLOY.md` as next step for them

The user will then follow `DEPLOY.md` manually on the Hostinger side.
