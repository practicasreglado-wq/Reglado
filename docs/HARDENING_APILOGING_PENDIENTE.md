# Hardening ApiLoging — Tareas Pendientes

**Estado:** Pendiente de ejecutar.
**Fecha del documento:** 2026-04-22
**Condición para retomar:** Disponibilidad del responsable del proyecto `Inmobiliaria_Reglados` (actualmente fuera de nuestro alcance), ya que L2 y L7 requieren coordinar los cambios en todos los frontends consumidores del SSO.

---

## 1. Contexto

El 2026-04-22 se realizó una auditoría de seguridad completa sobre `ApiLoging`. Se identificaron **24 vulnerabilidades** (3 críticas, 3 altas, 7 medias, 11 leves).

**Ya cerradas en preprod (16 de 24):**

- 🔴 C1 — Account takeover via email change (requestEmailChange exige `current_password`)
- 🔴 C2 — JWTs viejos no invalidados tras cambio de password (ahora `password_changed_at` + validación en middleware)
- 🔴 C3 — JWT_SECRET con fallback inseguro (bloqueado en todo entorno ≠ `local`)
- 🟠 A1 — Logout sin verificación (ahora verifica firma antes de revocar + rate limit)
- 🟠 A2 — `revoked_tokens` en plano (ahora guarda hash SHA-256)
- 🟠 A3 — `adminUpdateRole` sin reauth (ahora exige `current_password`)
- 🟡 M1, M2 — Enumeración de usuarios (respuestas genéricas en `register` y `requestEmailChange`)
- 🟡 M3 — `adminUpdateRole` podía quitar el último admin (ahora bloqueado con 409)
- 🟡 M5 — Fallback localhost en URLs de email (ahora exige env var en producción)
- 🟡 M7 — PDO sin `EMULATE_PREPARES=false` (corregido)
- 🟢 L1 — JWT no validaba `iss` (ahora sí)
- 🟢 L4 — Falta HSTS (añadido cuando hay HTTPS)
- 🟢 L5 — `adminSyncNotion` exponía `error.message` (ahora genérico)
- 🟢 L9 — `adminSyncNotion` sin rate limit (ahora 5/hora por admin)

Además, se implementó **defensa en profundidad contra fuerza bruta** en el login (rate limit IP+email 5/15min, rate limit global por email 20/15min, account lockout 5 fallos/30min).

**Pendientes (4 items):**

---

## 2. Tareas pendientes

### 🟢 OP-1 — Operacional: limpieza automática y monitoreo

**Estado:** ✅ Hecho. Script en [`ApiLoging/scripts/cleanup.php`](../ApiLoging/scripts/cleanup.php) (cubre `rate_limits >7d` y `revoked_tokens >30d`, idempotente) y cron activo en producción (`0 0 * * *` → `public_html/api_backend/scripts/cleanup.php`, verificado el 2026-04-30). Las "alertas básicas" (fase 2 opcional) no se han implementado y se descartan por ahora.

**Severidad:** Operacional (no es vulnerabilidad, es mantenimiento).
**Motivación:** Las tablas `rate_limits` y `revoked_tokens` crecen indefinidamente con el uso. En 3-6 meses sin limpieza, degradan el rendimiento de cada request autenticada (SELECT en middleware).

**Qué implementar:**

1. **Script de limpieza** (`ApiLoging/scripts/cleanup.php`) que borre:
   - `rate_limits` con `updated_at < NOW() - INTERVAL 7 DAY`
   - `revoked_tokens` antiguos (detectar por `revoked_at < NOW() - INTERVAL 30 DAY`; 30 días cubre de sobra el TTL del JWT de 24h)
2. **Cron diario** en el servidor de producción:
   ```
   0 3 * * * /usr/bin/php /var/www/ApiLoging/scripts/cleanup.php
   ```
3. **Alertas básicas** (opcional, fase 2): cuando `SecurityLogger` registre ≥N `login_failed` para el mismo email en <1h, enviar email al administrador.

**Proyectos afectados:** `ApiLoging` únicamente. Cero cambios en frontend.

**Coste estimado:** 1-2 horas.

**Prioridad:** ALTA entre los pendientes — es el único que previene un problema que aparecerá con certeza.

---

### 🟢 L2 — Refresh tokens

**⚠️ Nota arquitectónica añadida 2026-04-30:** los proyectos del ecosistema viven en **dominios independientes** (eTLD+1 distintos: `regladogroup.com`, `regladoenergy.com`, `regladomaps.com`, `regladorealestate.com`, etc.), no en subdominios. Por eso el plan original del doc se queda corto: el `refresh_token` en cookie HttpOnly no se puede compartir entre dominios. Las opciones reales serían: (a) cookie de refresh por dominio + endpoint `/auth/refresh` por CORS con `credentials: 'include'` y `SameSite=None; Secure` (en riesgo por la deprecación de third-party cookies); (b) refresh siempre a través del SSO Hub con fragment-token (más latencia, más complejidad). Cualquier retomada de L2 debe empezar por decidir esta arquitectura antes que el código.

**Severidad:** Leve (defensa en profundidad).
**Motivación:** Reducir de 24h a 15min la ventana útil de un JWT comprometido. Es la mejora de seguridad más relevante del bloque.

**Qué implementar:**

1. **Backend (`ApiLoging`):**
   - Nueva tabla `refresh_tokens` (user_id, token_hash, expires_at, rotated_from, used_at, created_at).
   - Endpoint `POST /auth/refresh` que acepta un refresh_token válido, lo marca como rotado e invalida sus descendientes en caso de reuso (rotation detection).
   - `AuthController::login` devuelve `{access_token, refresh_token}` en lugar de solo `token`.
   - Reducir `JWT_TTL_SECONDS` de 86400 (24h) a 900 (15min) **solo cuando todos los frontends estén migrados**.

2. **Frontends (`GrupoReglado`, `RegladoEnergy`, `RegladoMaps`, `RegladoIngenieria`, `Inmobiliaria_Reglados`):**
   - Interceptor en `fetch` que, al recibir 401 "invalid token", llama a `/auth/refresh`, actualiza el token y reintenta la petición original.
   - Mutex/lock para evitar race conditions cuando hay peticiones paralelas (varias peticiones 401 simultáneas deben refrescar una sola vez).
   - Almacenar `refresh_token` donde se almacena hoy el `access_token` (por ahora localStorage; si se implementa L7, cookie HttpOnly).

**Proyectos afectados:** `ApiLoging` + los **5 frontends** del SSO. Cuando se retome, coordinar con el responsable de `Inmobiliaria_Reglados`.

**Estrategia recomendada — por fases, no rompedor:**

- **Fase A (solo backend):** Añadir `/auth/refresh` y devolver `refresh_token` opcional, pero **mantener access TTL de 24h**. Los frontends ignoran el refresh y siguen como siempre. **Cero rotura.**
- **Fase B (frontends uno a uno):** Cada frontend adopta el refresh a su ritmo. Se puede probar en preprod antes de cada release.
- **Fase C:** Cuando el último frontend lo tenga, reducir `JWT_TTL_SECONDS` a 900 y ganar el valor real.

**Coste estimado:** 2-3 días (backend + el primer frontend); +0.5 día por cada frontend adicional.

---

### 🟢 L7 — Cookie HttpOnly para el JWT

**⚠️ El plan descrito en este doc queda OBSOLETO (revisión 2026-04-30):** asumía dominios bajo un apex común (`Domain=.regladogroup.com`). La realidad es que el ecosistema vive en **dominios independientes** (`regladogroup.com`, `regladoenergy.com`, `regladomaps.com`, `regladorealestate.com`, etc.) — la cookie compartida cross-domain es físicamente imposible. Si se quiere recuperar L7, hay que rediseñar: cookie HttpOnly **por dominio individual** + el SSO Hub seguiría siendo el único transportador del JWT entre dominios (vía fragment-token, como ya hace). El "esperar a producción real con subdominios" del doc original NO se va a cumplir; ese requisito está cancelado. **Antes de retomar L7, escribir un nuevo plan que parta de la arquitectura real.**

**Severidad:** Leve (defensa en profundidad contra XSS).
**Motivación:** Un XSS en cualquier frontend no podría robar el JWT porque JavaScript no accedería a la cookie.

**Qué implementar:**

1. **Backend (`ApiLoging`):**
   - `login`, `verify-email`, `confirm-email-change`, `refresh` setean `Set-Cookie: auth_token=...; HttpOnly; Secure; SameSite=Lax; Domain=.regladogroup.com` en lugar de (o además de) devolver el JWT en el body.
   - `AuthMiddleware::extractBearerToken` también lee de cookie.
   - CORS: añadir `Access-Control-Allow-Credentials: true` y revisar que `Access-Control-Allow-Origin` sea explícito (no `*`).

2. **Frontends:** todos los consumidores del SSO.
   - Eliminar `localStorage.getItem/setItem` del token.
   - Eliminar el header `Authorization: Bearer`.
   - Todos los `fetch` llevan `credentials: 'include'`.
   - Para saber si hay sesión, llamar a `/auth/me` en lugar de leer la cookie desde JS (la cookie es HttpOnly).

**Proyectos afectados:** `ApiLoging` + los **5 frontends**.

**⚠️ Requisito de infraestructura:** La cookie HttpOnly compartida requiere que todos los frontends estén bajo el mismo dominio principal (ej. `regladogroup.com`, `energy.regladogroup.com`, `mapas.regladogroup.com`, `ingenieria.regladogroup.com`, `inmobiliaria.regladogroup.com`). En local con puertos distintos de `localhost` esto es diferente — habría que mantener un modo legacy compatible, lo que complica el código.

**Recomendación:** esperar a producción real con subdominios antes de atacar este item.

**Coste estimado:** 3-5 días + ajuste CORS + testing completo del SSO en staging.

---

### 🔴 L6 — Tokens de verificación vía POST en lugar de GET

**⚠️ Alcance real revisado 2026-04-30:** el doc original dice que "solo toca GrupoReglado", pero al verificar el código se descubre que los 4 frontends internos (Grupo, Energy, Maps, Ingenieria) tienen su propia ruta `/verificacion-exitosa` con `EmailVerifiedView`. El flujo actual es: email → backend → backend redirige al frontend de origen (vía `return_origin`). Si el email pasa a apuntar a Grupo, **hay que decidir cómo el usuario acaba viendo el feedback de "verificado" en el frontend desde el que se registró**. Tres caminos viables: (1) feedback en Grupo + SSO al origen (UX visible cambia de dominio), (2) Grupo verifica en silencio y redirige con flag `?email_verified=1` al origen (toca Grupo + 4 frontends internos), (3) Grupo verifica y reusa la `EmailVerifiedView` del origen vía fragment-token (toca Grupo + las 4 vistas existentes). Cualquier opción **toca más proyectos que los que dice el doc original**. Aplazar hasta tener acceso a Inmobiliaria para hacerlo de una pasada en los 5 frontends.

**Severidad:** Leve (muy baja).
**Motivación:** Los tokens de verificación de email y cambio de email quedan en logs del servidor, historial del navegador y cabecera `Referer` al llevarlos en query string. Un atacante con acceso a esos logs en el momento preciso (antes de que expiren o sean usados) podría consumirlos.

**Qué implementar:**

1. **Backend (`ApiLoging`):**
   - Cambiar `GET /auth/verify-email?token=...` a `POST /auth/verify-email` con el token en body.
   - Cambiar `GET /auth/confirm-email-change?token=...` a `POST /auth/confirm-email-change` con el token en body.

2. **Frontend (`GrupoReglado` únicamente):**
   - Nueva página `/verificar-email` que lee `?token=X` de la URL, hace `POST /auth/verify-email` con el body, y muestra resultado + redirección.
   - Nueva página `/confirmar-email-nuevo` igual para el cambio de email.
   - Cambiar `EMAIL_VERIFY_URL_BASE` y `EMAIL_CHANGE_VERIFY_URL_BASE` en `.env` para apuntar a las páginas nuevas del frontend en vez de al endpoint del backend.

**Proyectos afectados:** `ApiLoging` + **solo `GrupoReglado`**. Los demás consumidores (Energy/Maps/Ingenieria/Inmobiliaria) no están implicados.

**Coste estimado:** 1-2 días.

**Recomendación:** prioridad más baja del bloque. El vector de ataque es muy específico (acceso a logs del servidor en una ventana corta) y los tokens son de un solo uso con expiración. Considerar descartar si no hay cambios operacionales.

---

## 3. Matriz de impacto por proyecto

| Tarea | ApiLoging | GrupoReglado | Energy | Maps | Ingenieria | Inmobiliaria |
|---|---|---|---|---|---|---|
| OP-1 — Cron + alertas | ✅ Hecho | — | — | — | — | — |
| L2 — Refresh tokens | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ (externo) |
| L7 — Cookie HttpOnly | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ (externo) |
| L6 — GET → POST | ✅ | ✅ | — | — | — | — |

`Inmobiliaria_Reglados` aparece como **(externo)** porque lo mantiene otra persona. Cuando retomemos estos pendientes habrá que coordinar con su responsable para que aplique el cambio del lado de su frontend.

---

## 4. Orden recomendado cuando se retome

1. **OP-1** — Primero. Cero impacto multi-proyecto, beneficio inmediato y duradero.
2. **L2** — Segundo. Siguiendo la estrategia de fases A→B→C para no romper nada.
3. **L7** — Cuando producción esté estable con dominio común de subdominios.
4. **L6** — Opcional. Descartar si no aporta frente al coste.

---

## 5. Checklist pre-retomada

Cuando el responsable de `Inmobiliaria_Reglados` esté disponible y se decida retomar, verificar:

- [ ] Estado actual del ApiLoging en producción (si ya se ha deployado la versión actual hardenizada).
- [ ] La tabla `rate_limits` y `revoked_tokens` — si han crecido significativamente, **ejecutar OP-1 antes** que cualquier otra cosa.
- [ ] `JWT_SECRET` en producción es sólido (32+ chars, no el placeholder).
- [ ] Todos los frontends están en una versión conocida y se pueden desplegar coordinadamente.
- [ ] Hay ventana de mantenimiento acordada para Fase C de L2 (bajar JWT_TTL_SECONDS) — aunque si se hace bien el refresh, el usuario no lo debería notar.

---

## 6. Referencias internas

- Auditoría original: realizada el 2026-04-22 sobre todos los archivos de `ApiLoging/`.
- Migración SQL aplicada: [`ApiLoging/database/migrate_security_v2.sql`](../ApiLoging/database/migrate_security_v2.sql).
- Servicios clave modificados:
  - [`ApiLoging/services/RateLimiter.php`](../ApiLoging/services/RateLimiter.php)
  - [`ApiLoging/middleware/AuthMiddleware.php`](../ApiLoging/middleware/AuthMiddleware.php)
  - [`ApiLoging/controllers/AuthController.php`](../ApiLoging/controllers/AuthController.php)
  - [`ApiLoging/utils/Security.php`](../ApiLoging/utils/Security.php)
  - [`ApiLoging/services/JwtService.php`](../ApiLoging/services/JwtService.php)
