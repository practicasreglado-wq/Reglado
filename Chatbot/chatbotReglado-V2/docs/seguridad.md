# 🔒 Revisión de Seguridad — chatbotReglado

Pendientes detectados en revisión del 2026-04-17. Ordenados por prioridad.

---

## 🔴 Crítico

### ~~1. Sin rate limiting en `/chat`~~ ✅ Resuelto 2026-04-17
- **Implementado:** `express-rate-limit` — 20 req/min por IP en `/chat` (configurable con `RATE_LIMIT_CHAT_RPM` en `.env`), 5 req/min en `/api/upload`.
- **Archivo:** `backend/server.js` (líneas 230-248)

### ~~2. Archivos subidos son públicos~~ ✅ Resuelto 2026-04-20
- **Implementado:** Eliminado el static middleware. Nuevo endpoint `GET /api/files/:filename?sessionId=xxx` que valida en BD que el `sessionId` coincide con el que subió el archivo. Sin sessionId → 401. SessionId incorrecto → 403.
- **Archivos:** `backend/server.js`, `backend/services/database.js`

---

## 🟠 Alto

### 3. `sessionId` generado en el cliente sin verificación
- **Problema:** El `sessionId` se genera en el navegador y se envía al servidor sin ninguna validación. Cualquiera puede inventarse un sessionId y acceder a la sesión o memoria de otro usuario.
- **Solución:** Validar formato mínimo del sessionId en el servidor. Considerar firmarlo con HMAC o usar tokens cortos de vida limitada.
- **Archivo:** `backend/server.js`, `widget/chatbotReglado.js`

### 4. Inyección HTML en emails internos
- **Problema:** Los datos del usuario (`nombre`, `motivo`, `email`) se insertan directamente en HTML sin escapar en los correos internos. Un atacante podría inyectar HTML malicioso.
- **Solución:** Escapar todas las variables de usuario antes de insertarlas en HTML (reemplazar `<`, `>`, `&`, `"`, `'`).
- **Archivo:** `backend/services/emailService.js`

### 5. CORS abierto por defecto
- **Problema:** Si `ALLOWED_ORIGINS` está vacío en `.env`, se acepta cualquier origen. Cualquier web puede embeber y consumir el chatbot.
- **Solución:** Definir siempre `ALLOWED_ORIGINS` en `.env` con los dominios permitidos. Cambiar el comportamiento por defecto a denegar si no hay orígenes configurados.
- **Archivo:** `backend/server.js` (línea 78)

---

## 🟡 Medio

### ~~6. Email interno hardcodeado~~ ✅ Resuelto 2026-04-20
- **Implementado:** Movido a `.env` como `INTERNAL_EMAIL`. Las 2 llamadas en `server.js` usan `process.env.INTERNAL_EMAIL`.
- **Archivos:** `backend/server.js`, `.env`

### ~~7. Links de archivos apuntan a `localhost` en emails~~ ✅ Resuelto 2026-04-20
- **Implementado:** URL construida con `process.env.APP_BASE_URL` (por defecto `http://localhost:3000`). En producción cambiar a `https://tudominio.com` en el `.env`.
- **Archivos:** `backend/services/emailService.js` (línea 79), `.env`

### ~~8. `__dirname` usado antes de declararse en Multer~~ ✅ Resuelto 2026-04-20
- **Implementado:** `__filename` y `__dirname` movidos justo después de `dotenv.config()` (línea 22), antes de que Multer los use.
- **Archivo:** `backend/server.js`

---

## 🔵 Informativo

### ~~9. `google-credentials.json` dentro del proyecto~~ ✅ Resuelto 2026-04-20
- **Implementado:** Añadidos `backend/google-credentials.json` y `backend/uploads/` al `.gitignore`.
- **Archivo:** `.gitignore`

### 10. API key de Anthropic — regenerar
- **Problema:** La API key quedó expuesta en una sesión de trabajo. Aunque no haya sido publicada, se recomienda regenerarla por precaución.
- **Solución:** Regenerar en [console.anthropic.com](https://console.anthropic.com) y actualizar el `.env`.
- **Archivo:** `.env`

---

## Orden de implementación sugerido

1. Rate limiting → protege el dinero
2. Archivos privados → protege privacidad de clientes
3. Inyección HTML en emails → fácil y rápido
4. Email hardcodeado + URL base → limpieza de configuración
5. `__dirname` bug → fix quirúrgico
6. CORS → configurar en `.env`
7. sessionId → mejora progresiva
