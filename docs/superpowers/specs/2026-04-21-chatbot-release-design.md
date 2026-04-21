# chatbotReglado — Release para `chatbot.regladogroup.com`

**Fecha:** 2026-04-21
**Target:** Hostinger hPanel (Node.js App + MySQL + Let's Encrypt)
**Entorno destino:** `https://chatbot.regladogroup.com`
**Ubicación del release:** `c:\xampp\htdocs\Reglado\ReleasesEstables\chatbotReglado\` (NAS local, no versionado en Git público)

---

## 1. Contexto

El backend de chatbotReglado vive hoy en `c:\xampp\htdocs\Reglado\chatbotReglado\` corriendo contra MySQL local de XAMPP. Debe pasar a producción en un subdominio del ecosistema Reglado. El deploy es **manual vía SFTP/hPanel**; no hay CI/CD ni pipeline automatizado.

El widget se embeberá en las webs del ecosistema Reglado (no es un SaaS para terceros). Esto permite un CORS cerrado y una URL de API hardcoded como default del widget.

## 2. Alcance

Preparar una carpeta autocontenida en `ReleasesEstables/chatbotReglado/` que contenga todo lo necesario para desplegar el chatbot en Hostinger mediante copia directa, `npm install` y configuración de 2-3 valores en hPanel.

**Fuera de alcance:**
- Automatización del deploy (CI/CD, scripts de release).
- Refactor de arquitectura (cola Notion, dedupe usuarios, migración a webhooks de Telegram, etc. — documentado en sesión anterior de mejoras).
- Cambios funcionales al chatbot.

## 3. Decisiones

| Tema | Decisión | Motivo |
|---|---|---|
| Hosting | Hostinger Node.js App (Passenger) | Ya se usa para otros proyectos del ecosistema |
| CORS | Allowlist cerrada a 6 dominios + subdominio propio | Widget sólo se embebe en webs propias |
| Base de datos | MySQL de Hostinger, DB dedicada | Aislamiento del resto de proyectos |
| `.env` | Copia verbatim del de dev + 2 cambios (CORS y APP_BASE_URL) | NAS local sin Git público |
| Anthropic API key | Reutilizar la de dev | Key única válida para dev+prod |
| SMTP | Reutilizar `info@regladoconsultores.com` | Ya es la cuenta oficial |
| Telegram bot | Reutilizar el token de Alexandra | Asumimos apagar dev al probar prod (polling no soporta dos instancias) |
| Notion | Reutilizar la integración existente | Ya apunta a las DBs reales |
| Google Calendar | Reutilizar service account | Subir `google-credentials.json` manualmente vía SFTP |
| `node_modules` | **No** incluidos en release | Incompatibilidad Win→Linux; `npm install` en servidor |

### 3.1 Allowlist CORS final

```
https://regladogroup.com
https://www.regladogroup.com
https://regladoenergy.com
https://www.regladoenergy.com
https://regladoingenieria.com
https://www.regladoingenieria.com
https://chatbot.regladogroup.com
```

Excluidos explícitamente (a petición del usuario): Inmobiliaria_Reglados, RegladoMaps. Si en el futuro se necesitan, se añaden al `.env` sin redeploy.

### 3.2 Alternativas consideradas y descartadas

- **`.env.example` con placeholders** — descartado porque el NAS es privado y el usuario prefiere copy-paste directo.
- **CORS con `*.regladogroup.com` wildcard** — descartado porque los dominios del ecosistema son TLDs distintos (no subdominios), así que el wildcard no aplica.
- **Bot de Telegram nuevo para producción** — descartado. El usuario acepta la limitación de polling y apagará dev cuando pruebe prod.
- **Migrar Telegram a webhooks** — aplazado. Requiere refactor no trivial, no es blocker.
- **Incluir `node_modules` en el release** — descartado. Binarios nativos (mysql2, better-sqlite3) compilados en Windows no funcionan en el Linux de Hostinger.

## 4. Estructura de la carpeta de release

```
ReleasesEstables/chatbotReglado/
├── backend/
│   ├── server.js
│   ├── services/
│   │   ├── database.js
│   │   ├── emailService.js
│   │   ├── googleCalendar.js
│   │   ├── notionService.js
│   │   └── telegramService.js
│   ├── sql/
│   │   └── chatbot_reglado.sql
│   └── uploads/
│       └── .gitkeep
├── widget/
│   ├── chatbotReglado.js
│   └── assets/
│       └── imagechatbot.png
├── package.json
├── package-lock.json
├── .env
├── .gitignore
└── DEPLOY.md
```

**No se incluye:**
- `node_modules/` — se instala en el servidor.
- `docs/` — documentación interna, no necesaria en producción.
- `test/` — tests manuales sólo tienen sentido en dev.
- `backend/google-credentials.json` — sensible, sube manualmente vía SFTP al destino (la ruta queda referenciada en `.env`).

### 4.1 `.gitignore` del release

Aunque el NAS es privado, el `.gitignore` evita que si alguien termina poniéndolo en un repo por error, los secretos no se filtren:

```
node_modules/
backend/uploads/*
!backend/uploads/.gitkeep
backend/google-credentials.json
.env
```

## 5. Cambios al código

Sólo dos ficheros cambian respecto al repo de dev. No se modifica `backend/server.js` — el existente `const PORT = Number(process.env.PORT || 3000)` ya sirve para prod: Hostinger/Passenger respeta `PORT=3000` del `.env` y, si lo sobrescribe desde hPanel, se toma de `process.env.PORT` automáticamente.

### 5.1 `widget/chatbotReglado.js`

Línea 21, `defaultConfig.apiUrl`:

```diff
- apiUrl: 'http://localhost:3000/chat',
+ apiUrl: 'https://chatbot.regladogroup.com/chat',
```

Las webs que ya embeben el widget con `window.ChatbotRegladoConfig` pueden seguir sobrescribiendo este default; las nuevas no necesitan configurar nada.

### 5.2 `backend/services/emailService.js`

Línea 81, fallback de `APP_BASE_URL`:

```diff
- const fullUrl = `${process.env.APP_BASE_URL || 'http://localhost:3000'}${url}`;
+ const fullUrl = `${process.env.APP_BASE_URL || 'https://chatbot.regladogroup.com'}${url}`;
```

Aunque el `.env` de producción define `APP_BASE_URL`, cambiamos el default por consistencia con el nuevo target.

## 6. `.env` de producción

Copia verbatim del `.env` actual de dev con los siguientes **2 cambios únicos**:

| Variable | Dev | Prod |
|---|---|---|
| `ALLOWED_ORIGINS` | `http://localhost,http://127.0.0.1,http://localhost:5173,...` | (los 7 dominios de la sección 3.1) |
| `APP_BASE_URL` | `http://localhost:3000` | `https://chatbot.regladogroup.com` |

**No se cambian** (se copian tal cual):
`PORT, ANTHROPIC_API_KEY, ANTHROPIC_MODEL, DB_HOST, DB_USER, DB_PASS, DB_NAME, SMTP_*, GOOGLE_*, TELEGRAM_*, NOTION_*, INTERNAL_EMAIL, RATE_LIMIT_*, MEMORY_TTL_DAYS, COST_*`

El bloque `DB_*` lleva un comentario encima recordando que hay que cambiarlo cuando la DB de Hostinger esté creada.

## 7. `DEPLOY.md` — guía de despliegue

El documento incluirá 15 pasos en este orden:

1. Crear Node.js App en hPanel (entry point: `backend/server.js`, versión Node 20).
2. Crear DB MySQL en hPanel y anotar: host, usuario, password, nombre.
3. Subir el contenido de `ReleasesEstables/chatbotReglado/` vía SFTP al `application root` (incluido `.env`, `google-credentials.json` y `package.json`).
4. Conectar por SSH / terminal de hPanel → `npm install --omit=dev`.
5. Editar el `.env` en el servidor: actualizar `DB_USER`, `DB_PASS`, `DB_NAME`, `DB_HOST` con los valores del paso 2.
6. Abrir phpMyAdmin de hPanel → Importar `backend/sql/chatbot_reglado.sql`.
7. Configurar DNS: crear registro A/CNAME para `chatbot.regladogroup.com` apuntando a Hostinger.
8. Activar Let's Encrypt SSL desde hPanel para el subdominio.
9. Arrancar la app desde hPanel.
10. Health check: `curl https://chatbot.regladogroup.com/health` → `{ok:true}`.
11. **Apagar el backend de dev local** (importante por polling de Telegram).
12. Abrir una web del ecosistema que embebe el widget y verificar que responde.
13. Caso sanity: registrar un usuario de prueba → ver aparecer en Notion + email interno recibido.
14. Caso sanity: agendar una cita de prueba → ver aparecer en Notion + Google Calendar + emails.
15. Revisar logs en hPanel las primeras 24h para detectar errores inesperados.

Cada paso incluirá captura/comando exacto en el `DEPLOY.md` final.

## 8. Tareas manuales pendientes del usuario

Listado recordatorio para que el usuario tenga claro qué hacer aparte del release:

1. **Crear DB MySQL en hPanel** y darse a sí mismo las credenciales (actualizar `.env` del servidor).
2. **Subir `google-credentials.json`** por SFTP a `backend/` del release en el servidor.
3. **Configurar DNS** del subdominio `chatbot.regladogroup.com`.
4. **Embeber el widget** en las webs del ecosistema:
   ```html
   <script src="https://chatbot.regladogroup.com/widget/chatbotReglado.js" defer></script>
   ```
5. **Apagar el backend dev** cuando vaya a probar prod (limitación Telegram polling).

## 9. Criterios de aceptación

El release se considera completo cuando:

- [ ] Existe `ReleasesEstables/chatbotReglado/` con la estructura de la sección 4.
- [ ] `widget/chatbotReglado.js` apunta a `https://chatbot.regladogroup.com/chat` por defecto.
- [ ] `backend/services/emailService.js` tiene el fallback de `APP_BASE_URL` actualizado.
- [ ] `.env` contiene la copia del dev con los 2 cambios (allowlist y APP_BASE_URL).
- [ ] `.gitignore` protege `.env`, `uploads/` (excepto `.gitkeep`) y `google-credentials.json`.
- [ ] `DEPLOY.md` contiene los 15 pasos con comandos concretos.
- [ ] `package.json` y `package-lock.json` copiados tal cual.
- [ ] Nada de `node_modules/`, `docs/`, `test/`, `.env.example` en el release.

## 10. Riesgos conocidos

1. **Polling de Telegram**: si el usuario olvida apagar dev cuando prueba prod, el bot rechazará uno de los dos con `409 Conflict`. Mitigado por warning en `DEPLOY.md` (paso 11).
2. **Uploads no persistidos en backups**: la carpeta `backend/uploads/` vive en el disco del contenedor de Hostinger. Si migráis de hosting o el contenedor se recrea, se pierden. Mitigación futura: mover a S3/Hetzner Object Storage. No es blocker ahora.
3. **Sin validación de schema de Notion al arrancar**: si alguien renombra una propiedad de Notion, las inserciones fallarán silenciosamente. Mitigación: logs ya emiten `[Notion] Error creando usuario/cita` → revisar logs en el paso 15.
4. **Binarios nativos de `mysql2`**: compilados por `npm install` en el servidor Linux. Si la versión de Node de hPanel es < 16 pueden fallar. Recomendación: Node 20 (paso 1).
5. **`package-lock.json` determinista**: si se regenera en Windows y se sube, puede arrastrar binarios compatibles sólo con Windows. Mitigación: el release se hace desde la versión vigente y `npm install` se corre en servidor Linux.
