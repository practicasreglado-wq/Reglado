# L7 — Fase 4: Inmobiliaria_Reglados (Cookie HttpOnly)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Aplicar el patrón L7 a Inmobiliaria_Reglados, replicando lo hecho en Energy/Ingenieria. **Este plan es para entregar al equipo externo** que mantiene Inmobiliaria — incluye también las tareas que debemos hacer NOSOTROS (ApiLoging registro de API key + GrupoReglado allowlist) para integrar el dominio.

**Architecture:** Idéntica al patrón de fase 2 (Energy/Ingenieria). El equipo externo crea endpoints en `Inmobiliaria_Reglados/backend/auth/` (su carpeta backend; ojo a la diferencia de mayúsculas — Inmobiliaria usa `backend` en minúsculas). Nosotros hacemos los cambios de coordinación en ApiLoging y Grupo.

**Tech Stack:** Lo que use Inmobiliaria (PHP + framework propio, según mantenga el equipo externo). El patrón es agnóstico al stack.

**Base dir:** `c:/xampp/htdocs/Reglado/`. Todos los paths son relativos a esta raíz.

**Spec de referencia:** [docs/superpowers/specs/2026-04-30-l7-cookie-httponly-design.md](../specs/2026-04-30-l7-cookie-httponly-design.md)

**Bloqueante:** Fase 1 desplegada. Equipo externo de Inmobiliaria disponible. Coordinación previa para acordar fecha de despliegue.

**Fuera de alcance:** Cambios de UX en Inmobiliaria que no sean del flujo de auth.

---

## Bloque A — Tareas para NOSOTROS (interno)

### Task A1: Generar API key y registrarla en ApiLoging

**Files:**
- Modify: `ApiLoging/.env` (en el servidor de prod, no en repo)
- Modify: `ApiLoging/services/BackendApiKey.php` (si hace falta ajustar el origen)

- [ ] **Step A1.1: Generar API key**

```bash
echo "Inmobiliaria API key: $(openssl rand -hex 32)"
```

Guardar el valor de forma segura (gestor de secretos). Compartirlo con el equipo externo por canal seguro (NO Slack/email plano — usar `pass`, 1Password, etc.).

- [ ] **Step A1.2: Registrar en ApiLoging .env**

En el servidor donde corre ApiLoging (Hostinger), añadir a `BACKEND_API_KEYS`:

```
BACKEND_API_KEYS=energy:abc...,ingenieria:def...,maps:ghi...,inmobiliaria:<la_clave_generada>
```

- [ ] **Step A1.3: Verificar el origin asociado**

En `ApiLoging/services/BackendApiKey.php`, confirmar que `ALLOWED_ORIGINS_BY_NAME` incluye:

```php
'inmobiliaria' => 'https://regladorealestate.com',
```

Si el dominio real es distinto (alias, subdominio, etc.), ajustar.

- [ ] **Step A1.4: Reiniciar ApiLoging**

Reiniciar el servicio en Hostinger para que recoja el nuevo `.env`.

- [ ] **Step A1.5: Smoke test**

```bash
curl -s -X POST https://regladogroup.com/auth/exchange-code \
  -H "Authorization: Bearer <la_clave_generada>" \
  -H "Content-Type: application/json" \
  -d '{"code":"fake","requesting_origin":"https://regladorealestate.com"}'
```

Expected: 410 con `{"error":"code invalid, expired, or already used"}` (la clave es válida, pero el code no — eso confirma el match key/origin).

Si la respuesta es 403 con `invalid api key for origin`, revisar configuración.

---

### Task A2: Añadir Inmobiliaria a la allowlist del SSO Hub

**Files:**
- Modify: `GrupoReglado/src/services/ssoHub.js`

- [ ] **Step A2.1: Verificar que Inmobiliaria está en `SSO_ALLOWED_RETURNS`**

```bash
grep -n "regladorealestate\|inmobiliaria" c:/xampp/htdocs/Reglado/GrupoReglado/src/services/ssoHub.js
```

Si NO aparece, añadirlo en `SSO_ALLOWED_RETURNS`:

```javascript
const SSO_ALLOWED_RETURNS = [
  // ... entradas existentes ...
  "https://regladorealestate.com",
  "https://www.regladorealestate.com",
];
```

- [ ] **Step A2.2: Añadir tema visual (opcional)**

En `SSO_THEMES`, opcionalmente añadir:

```javascript
"https://regladorealestate.com": INMOBILIARIA_THEME(),
```

Y la función `INMOBILIARIA_THEME()` con la paleta corporativa de Inmobiliaria.

- [ ] **Step A2.3: Build y release de Grupo**

```bash
cd c:/xampp/htdocs/Reglado/GrupoReglado && npm run build
```

Crear nueva release de Grupo siguiendo el patrón habitual y desplegarla.

- [ ] **Step A2.4: Commit**

```bash
git add GrupoReglado/src/services/ssoHub.js
git commit -m "config(grupo): allowlist SSO incluye regladorealestate.com (L7 fase 4)"
```

---

## Bloque B — Tareas para el equipo externo de Inmobiliaria

### Documentación a entregar

Pasar al equipo externo:
1. Esta sección B del plan.
2. La spec [`docs/superpowers/specs/2026-04-30-l7-cookie-httponly-design.md`](../specs/2026-04-30-l7-cookie-httponly-design.md).
3. La API key generada en Task A1 (canal seguro).
4. Como referencia de implementación: el plan de fase 2 (`2026-04-30-l7-fase2-energy-ingenieria.md`) — el patrón es idéntico.

### Tasks que el equipo externo debe ejecutar

#### B1. Configurar `.env`

En `Inmobiliaria_Reglados/backend/.env` (o donde tenga el equipo sus vars):

```
APP_ENV=production
SITE_ORIGIN=https://regladorealestate.com
APILOGING_BASE_URL=https://regladogroup.com
APILOGING_API_KEY=<clave_proporcionada_por_equipo_reglado>
GRUPO_REGLADO_BASE_URL=https://regladogroup.com
```

Asegurarse de que `.env` está en `.gitignore`. Compartir solo `.env.example` sin valores reales.

#### B2. Helper de cookies

Crear `Inmobiliaria_Reglados/backend/auth_cookie.php` (o equivalente en su framework):

```php
<?php

if (!function_exists('inmo_set_auth_cookie')) {
    function inmo_set_auth_cookie(string $jwt, int $ttlSeconds = 86400): void
    {
        $appEnv = strtolower((string) (getenv('APP_ENV') ?: 'local'));
        setcookie('auth_token', $jwt, [
            'expires'  => time() + $ttlSeconds,
            'path'     => '/',
            'secure'   => $appEnv !== 'local',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    function inmo_clear_auth_cookie(): void
    {
        $appEnv = strtolower((string) (getenv('APP_ENV') ?: 'local'));
        setcookie('auth_token', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => $appEnv !== 'local',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
```

#### B3. Endpoint `auth/sso-exchange.php`

Replicar el patrón de Energy (`RegladoEnergy/BACKEND/auth/sso-exchange.php`), sustituyendo `energy_set_auth_cookie` por `inmo_set_auth_cookie`. Path final: `Inmobiliaria_Reglados/backend/auth/sso-exchange.php`.

#### B4. Endpoint `auth/me.php`

Igual que Energy (`me.php`). Path: `Inmobiliaria_Reglados/backend/auth/me.php`.

#### B5. Endpoint `auth/logout.php`

Igual que Energy (`logout.php`), sustituyendo cookie helper. Path: `Inmobiliaria_Reglados/backend/auth/logout.php`.

#### B6. Endpoint `auth/proxy.php` (opcional)

Si el frontend de Inmobiliaria necesita llamar endpoints autenticados de ApiLoging (update-name, change-password, etc.), incluir el proxy genérico con whitelist. Ver `RegladoEnergy/BACKEND/auth/proxy.php` como referencia.

#### B7. Adaptar el validador JWT del backend existente

Donde el backend de Inmobiliaria valide JWTs hoy (típicamente lee `Authorization: Bearer`), añadir la lectura prioritaria de la cookie `auth_token`:

```php
function extract_jwt(): ?string
{
    if (isset($_COOKIE['auth_token']) && $_COOKIE['auth_token'] !== '') {
        return trim((string) $_COOKIE['auth_token']);
    }
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (is_string($auth) && stripos($auth, 'Bearer ') === 0) {
        return trim(substr($auth, 7));
    }
    return null;
}
```

#### B8. Frontend `services/auth.js`

- Cambiar `API_BASE` a `'/auth'` (proxy local).
- Endpoints: `/me.php`, `/logout.php`, `/proxy.php?path=/auth/<endpoint>`.
- Quitar `Authorization: Bearer`, añadir `credentials: 'include'`.
- Eliminar `state.token` y helpers de cookie.

(Si Inmobiliaria usa axios o cliente HTTP propio, equivalente.)

#### B9. Frontend `services/ssoClient.js` (si lo tiene)

- Eliminar `consumeTokenFromFragment` (el JWT ya no llega por fragmento).
- Mantener `redirectToHandshake`, `wasHandshakeAttempted`, etc.

Si Inmobiliaria todavía no tiene integrado el SSO Hub, esta es la oportunidad de implementarlo siguiendo el patrón documentado en `docs/PENDIENTE_INMOBILIARIA_APILOGING.md`.

#### B10. Vite dev proxy (si usan Vite)

```javascript
server: {
  proxy: {
    '/auth': { target: 'http://localhost:<puerto_backend>', changeOrigin: true },
  },
},
```

#### B11. Despliegue coordinado

- Equipo externo prepara release con todos los cambios anteriores.
- Avisa al equipo Reglado.
- Reglado verifica que la API key de Inmobiliaria está en producción de ApiLoging (Task A1) y que el dominio está en allowlist (Task A2).
- Despliegue simultáneo (o ventana de mantenimiento corta).

---

## Bloque C — Verificación E2E (entre los dos equipos)

- [ ] **Step C1: Login en Grupo, ir a Inmobiliaria**

Verificar handshake → code → exchange → cookie HttpOnly de `regladorealestate.com`.

- [ ] **Step C2: Logout en Inmobiliaria → cierra Grupo también**

Verificar la cadena de redirects.

- [ ] **Step C3: DevTools en `regladorealestate.com`**

- ✓ Cookie `auth_token` con `HttpOnly` + `Secure` + `SameSite=Lax`.
- ✓ `document.cookie` no incluye `auth_token`.
- ✓ `/auth/me` (proxy local) responde 200 sin header Authorization.

- [ ] **Step C4: Single-session enforcement intacto**

Login en Grupo con usuario X, después login en Inmobiliaria con el mismo X. La sesión vieja queda invalidada (kick-old). Volver a Grupo: `/auth/me` 401, frontend limpia sesión.

---

## Notas

- **Desincronización**: Si el equipo externo de Inmobiliaria tarda mucho en implementar, ApiLoging mantiene el fallback `Authorization: Bearer` indefinidamente. Inmobiliaria sigue funcionando con el modo legacy hasta que se migre.
- **API key compromise**: si la API key de Inmobiliaria se compromete (filtrada, log, etc.), generar una nueva, registrarla en ApiLoging `BACKEND_API_KEYS`, comunicar al equipo externo, y eliminar la antigua. La rotación es independiente del JWT_SECRET.
- **Coordinación de cambios futuros**: cualquier cambio en `/auth/exchange-code` o `/auth/sso-issue-code` debe comunicarse con suficiente antelación al equipo externo para evitar romper su backend.

## Final del rollout L7

Tras completar fase 4, **deprecar el fallback Bearer** en una release posterior:

1. Verificar en logs de ApiLoging que ya nadie usa `Authorization: Bearer` desde frontends del ecosistema (debería ser cero tras todas las fases).
2. Modificar `AuthMiddleware::extractToken()` para devolver `null` si no hay cookie (ignorar el header Authorization).
3. Mantener compatibilidad para llamadas server-to-server entre backends (donde sí se sigue usando Bearer con API keys, pero esos endpoints son distintos y usan el modelo de `BackendApiKey`).

Con esto, L7 queda 100% cerrada. El JWT solo vive en cookies HttpOnly del navegador y nunca pasa por JavaScript del cliente.
