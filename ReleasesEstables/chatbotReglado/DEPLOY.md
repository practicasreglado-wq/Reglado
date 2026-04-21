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
