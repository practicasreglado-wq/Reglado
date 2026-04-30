# L7 — Fase 2: RegladoEnergy + RegladoIngenieria (Cookie HttpOnly)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Aplicar el patrón L7 a RegladoEnergy y RegladoIngenieria — cada uno con un proxy de auth en su `BACKEND/` que recibe el code del SSO handshake, lo canjea server-side con ApiLoging usando una API key, y setea cookie HttpOnly en su propio dominio. Los frontends pasan a llamar `/auth/*` same-origin (proxy local) en lugar de cross-origin a ApiLoging.

**Architecture:** El patrón es idéntico para Energy e Ingenieria. Cada `BACKEND/auth/` recibe llamadas same-origin del frontend (con cookie HttpOnly local) y reenvía a ApiLoging server-side (con `Authorization: Bearer <api_key>`). El SSO handoff entrega un `?code=` en URL que el backend del frontend canjea con `/auth/exchange-code`. El frontend ya nunca ve el JWT.

**Tech Stack:** PHP 8 raw (BACKEND/) sobre Apache + `.htaccess`; Vue 3 + Vite. Reutiliza patrones del BACKEND/ existente en cada proyecto.

**Base dir:** `c:/xampp/htdocs/Reglado/`. Todos los paths son relativos a esta raíz.

**Spec de referencia:** [docs/superpowers/specs/2026-04-30-l7-cookie-httponly-design.md](../specs/2026-04-30-l7-cookie-httponly-design.md)

**Bloqueante:** Fase 1 (`docs/superpowers/plans/2026-04-30-l7-fase1-apiloging-grupo.md`) **debe estar desplegada en producción y verificada** antes de empezar fase 2.

**Fuera de alcance:** RegladoMaps (fase 3), Inmobiliaria_Reglados (fase 4).

---

## Bloque A — RegladoEnergy

### Task A1: Configuración `BACKEND/.env`

**Files:**
- Modify: `RegladoEnergy/BACKEND/.env.example`
- Create: `RegladoEnergy/BACKEND/.env` (local, no commiteado)

- [ ] **Step A1.1: Añadir variables al `.env.example`**

Añadir al final de `RegladoEnergy/BACKEND/.env.example`:

```
# === L7: cookie HttpOnly + proxy auth a ApiLoging ===
# Origen del frontend (para validar codes y setear cookie)
SITE_ORIGIN=http://localhost:5174

# ApiLoging (mismo JWT_SECRET que ya hay)
APILOGING_BASE_URL=http://localhost:8000

# API key registrada en ApiLoging para este backend
# (en ApiLoging .env: BACKEND_API_KEYS=energy:<esta_clave>,...)
APILOGING_API_KEY=replace_with_real_value
```

- [ ] **Step A1.2: Crear `.env` local con valores reales**

```bash
cp c:/xampp/htdocs/Reglado/RegladoEnergy/BACKEND/.env.example c:/xampp/htdocs/Reglado/RegladoEnergy/BACKEND/.env
```

Editar `RegladoEnergy/BACKEND/.env` y reemplazar `replace_with_real_value` por una clave aleatoria:

```bash
echo "APILOGING_API_KEY=$(openssl rand -hex 32)"
```

(Copiar el output al `.env`.)

Importante: actualizar también `ApiLoging/.env` añadiendo la entrada `energy:<la_clave>` a `BACKEND_API_KEYS`.

- [ ] **Step A1.3: Commit (solo `.env.example`)**

```bash
git add RegladoEnergy/BACKEND/.env.example
git commit -m "config(energy/backend): vars L7 (SITE_ORIGIN, APILOGING_*)"
```

---

### Task A2: Helper de cookies en BACKEND/

**Files:**
- Create: `RegladoEnergy/BACKEND/auth_cookie.php`

- [ ] **Step A2.1: Crear el helper**

Crear `RegladoEnergy/BACKEND/auth_cookie.php`:

```php
<?php

/**
 * Setea/limpia la cookie HttpOnly auth_token sobre el dominio actual.
 * Misma forma que ApiLoging, pero para el dominio local de Energy.
 */

if (!function_exists('energy_set_auth_cookie')) {
    function energy_set_auth_cookie(string $jwt, int $ttlSeconds = 86400): void
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

    function energy_clear_auth_cookie(): void
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

- [ ] **Step A2.2: Commit**

```bash
git add RegladoEnergy/BACKEND/auth_cookie.php
git commit -m "feat(energy/backend): helper de cookie HttpOnly"
```

---

### Task A3: Endpoint `BACKEND/auth/sso-exchange.php`

**Files:**
- Create: `RegladoEnergy/BACKEND/auth/sso-exchange.php`

- [ ] **Step A3.1: Crear endpoint**

Crear `RegladoEnergy/BACKEND/auth/sso-exchange.php`:

```php
<?php

/**
 * Recibe ?code=... emitido por GrupoReglado, lo canjea server-to-server con
 * ApiLoging y setea cookie HttpOnly auth_token sobre el dominio de Energy.
 *
 * El JWT NUNCA pasa por el navegador del usuario.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth_cookie.php';

$code = isset($_GET['code']) ? trim((string) $_GET['code']) : '';
$returnUrl = isset($_GET['return']) ? trim((string) $_GET['return']) : '/';

if ($code === '') {
    http_response_code(400);
    echo "missing code";
    exit;
}

$siteOrigin = (string) (getenv('SITE_ORIGIN') ?: 'http://localhost:5174');
$apiBase    = (string) (getenv('APILOGING_BASE_URL') ?: 'http://localhost:8000');
$apiKey     = (string) (getenv('APILOGING_API_KEY') ?: '');

if ($apiKey === '') {
    error_log('[sso-exchange] APILOGING_API_KEY no configurado');
    http_response_code(500);
    echo "service misconfigured";
    exit;
}

// Llamada server-to-server
$ch = curl_init($apiBase . '/auth/exchange-code');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ],
    CURLOPT_POSTFIELDS     => json_encode([
        'code'              => $code,
        'requesting_origin' => $siteOrigin,
    ]),
    CURLOPT_TIMEOUT        => 10,
]);
$body = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

if ($body === false) {
    error_log('[sso-exchange] curl error: ' . $curlErr);
    http_response_code(502);
    echo "upstream error";
    exit;
}

if ($httpCode !== 200) {
    error_log('[sso-exchange] ApiLoging returned ' . $httpCode . ': ' . $body);
    // Volver al return con sso_failed=1 para que el frontend muestre invitado
    $sep = strpos($returnUrl, '?') === false ? '?' : '&';
    header('Location: ' . $returnUrl . $sep . 'sso_failed=1');
    exit;
}

$data = json_decode($body, true);
if (!is_array($data) || empty($data['token'])) {
    http_response_code(502);
    echo "invalid upstream response";
    exit;
}

energy_set_auth_cookie((string) $data['token']);

// Redirigir al return URL (limpio, sin query del code)
header('Location: ' . ($returnUrl !== '' ? $returnUrl : '/'));
exit;
```

- [ ] **Step A3.2: Verificar que `bootstrap.php` carga `.env`**

```bash
grep -E "Env::load|getenv" c:/xampp/htdocs/Reglado/RegladoEnergy/BACKEND/bootstrap.php | head -3
```

Expected: alguna línea que cargue `.env` (puede ser via `phpdotenv`, función propia, etc.). Si no carga, añadir:

```php
$envPath = __DIR__ . '/.env';
if (is_file($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
        if ($k !== '') { putenv(trim($k) . '=' . trim($v)); $_ENV[trim($k)] = trim($v); }
    }
}
```

- [ ] **Step A3.3: Probar localmente**

Arrancar Energy backend (asumiendo está configurado para puerto 8001):
```bash
cd c:/xampp/htdocs/Reglado/RegladoEnergy/BACKEND && php -S localhost:8001 &
```

Generar un code válido en ApiLoging (ya cubierto en fase 1). Después:
```bash
CODE=<code generado>
curl -s -i "http://localhost:8001/auth/sso-exchange.php?code=$CODE&return=http://localhost:5174/" | head -20
```

Expected: HTTP 302 con `Location: http://localhost:5174/` y `Set-Cookie: auth_token=...; HttpOnly; SameSite=Lax`.

- [ ] **Step A3.4: Commit**

```bash
git add RegladoEnergy/BACKEND/auth/sso-exchange.php
git commit -m "feat(energy/backend): /auth/sso-exchange.php (canje code → cookie)"
```

---

### Task A4: Endpoint `BACKEND/auth/me.php`

**Files:**
- Create: `RegladoEnergy/BACKEND/auth/me.php`

- [ ] **Step A4.1: Crear endpoint**

Crear `RegladoEnergy/BACKEND/auth/me.php`:

```php
<?php

/**
 * Proxy de GET /auth/me a ApiLoging. Lee la cookie auth_token local,
 * reenvía a ApiLoging con Authorization: Bearer, devuelve la respuesta tal
 * cual.
 */

require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$jwt = isset($_COOKIE['auth_token']) ? trim((string) $_COOKIE['auth_token']) : '';
if ($jwt === '') {
    http_response_code(401);
    echo json_encode(['error' => 'no session']);
    exit;
}

$apiBase = (string) (getenv('APILOGING_BASE_URL') ?: 'http://localhost:8000');

$ch = curl_init($apiBase . '/auth/me');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $jwt],
    CURLOPT_TIMEOUT        => 10,
]);
$body = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($body === false) {
    http_response_code(502);
    echo json_encode(['error' => 'upstream error']);
    exit;
}

http_response_code($httpCode);
echo $body;
```

- [ ] **Step A4.2: Probar**

Tras hacer un sso-exchange exitoso (Task A3), con la cookie ya en el cliente:

```bash
curl -s -b /tmp/cookies.txt http://localhost:8001/auth/me.php
```

Expected: JSON con el usuario.

- [ ] **Step A4.3: Commit**

```bash
git add RegladoEnergy/BACKEND/auth/me.php
git commit -m "feat(energy/backend): /auth/me.php (proxy a ApiLoging con cookie local)"
```

---

### Task A5: Endpoint `BACKEND/auth/logout.php`

**Files:**
- Create: `RegladoEnergy/BACKEND/auth/logout.php`

- [ ] **Step A5.1: Crear endpoint**

Crear `RegladoEnergy/BACKEND/auth/logout.php`:

```php
<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../auth_cookie.php';

$jwt = isset($_COOKIE['auth_token']) ? trim((string) $_COOKIE['auth_token']) : '';
$apiBase = (string) (getenv('APILOGING_BASE_URL') ?: 'http://localhost:8000');

// 1. Revocar el JWT en ApiLoging server-side (best-effort)
if ($jwt !== '') {
    $ch = curl_init($apiBase . '/auth/logout');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $jwt],
        CURLOPT_TIMEOUT        => 5,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// 2. Borrar cookie local
energy_clear_auth_cookie();

// 3. Redirigir al hub para limpiar también su sesión
$siteOrigin = (string) (getenv('SITE_ORIGIN') ?: 'http://localhost:5174');
$grupoBase = (string) (getenv('GRUPO_REGLADO_BASE_URL') ?: 'http://localhost:5173');
$logoutUrl = $grupoBase . '/sso-logout?return=' . urlencode($siteOrigin . '/');
header('Location: ' . $logoutUrl);
exit;
```

- [ ] **Step A5.2: Probar**

```bash
curl -s -i -b /tmp/cookies.txt http://localhost:8001/auth/logout.php | head -10
```

Expected: HTTP 302 con `Location: http://localhost:5173/sso-logout?return=...` y `Set-Cookie: auth_token=deleted; expires=<pasada>`.

- [ ] **Step A5.3: Commit**

```bash
git add RegladoEnergy/BACKEND/auth/logout.php
git commit -m "feat(energy/backend): /auth/logout.php (revoca + limpia cookie + redirect)"
```

---

### Task A6: Endpoint genérico `BACKEND/auth/proxy.php`

**Files:**
- Create: `RegladoEnergy/BACKEND/auth/proxy.php`

- [ ] **Step A6.1: Crear endpoint**

Crear `RegladoEnergy/BACKEND/auth/proxy.php`:

```php
<?php

/**
 * Proxy genérico a ApiLoging para cualquier endpoint /auth/* que el frontend
 * necesite (update-name, change-password, request-email-change, etc.).
 *
 * Uso: el frontend llama /auth/proxy.php?path=/auth/update-name con method
 * POST y body JSON. Este script reenvía con la cookie del usuario como
 * Authorization Bearer y devuelve la respuesta.
 *
 * Nota: alternativamente se puede crear un endpoint dedicado por cada acción
 * en lugar de un proxy genérico, si se prefiere control fino sobre qué
 * llamadas se permiten desde el frontend. Esta versión genérica es la
 * mínima viable.
 */

require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$path = isset($_GET['path']) ? trim((string) $_GET['path']) : '';
if ($path === '' || !str_starts_with($path, '/auth/')) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid path']);
    exit;
}

// Whitelist de endpoints permitidos. Ampliar según necesidad del frontend.
$allowed = [
    '/auth/update-name',
    '/auth/update-username',
    '/auth/update-phone',
    '/auth/change-password',
    '/auth/request-email-change',
    '/auth/confirm-login-location',
];
if (!in_array($path, $allowed, true)) {
    http_response_code(403);
    echo json_encode(['error' => 'endpoint not allowed via proxy']);
    exit;
}

$jwt = isset($_COOKIE['auth_token']) ? trim((string) $_COOKIE['auth_token']) : '';
if ($jwt === '') {
    http_response_code(401);
    echo json_encode(['error' => 'no session']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$body = file_get_contents('php://input') ?: '';

$apiBase = (string) (getenv('APILOGING_BASE_URL') ?: 'http://localhost:8000');

$ch = curl_init($apiBase . $path);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST  => $method,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $jwt,
        'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS     => $body,
    CURLOPT_TIMEOUT        => 10,
]);
$resp = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($resp === false) {
    http_response_code(502);
    echo json_encode(['error' => 'upstream error']);
    exit;
}

http_response_code($httpCode);
echo $resp;
```

- [ ] **Step A6.2: Commit**

```bash
git add RegladoEnergy/BACKEND/auth/proxy.php
git commit -m "feat(energy/backend): /auth/proxy.php (whitelist de endpoints autenticados)"
```

---

### Task A7: Adaptar `BACKEND/auth.php` para leer cookie

**Files:**
- Modify: `RegladoEnergy/BACKEND/auth.php`

- [ ] **Step A7.1: Modificar lectura de JWT**

Localizar en `auth.php` la función que extrae el JWT del header. Sustituir su cuerpo por:

```php
function extract_jwt(): ?string
{
    // 1. Cookie HttpOnly (preferida desde L7)
    if (isset($_COOKIE['auth_token']) && $_COOKIE['auth_token'] !== '') {
        return trim((string) $_COOKIE['auth_token']);
    }
    // 2. Header Authorization Bearer (fallback)
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (is_string($auth) && stripos($auth, 'Bearer ') === 0) {
        return trim(substr($auth, 7));
    }
    return null;
}
```

(Adaptar nombre de función al existente — puede llamarse `getJwtFromRequest()`, `extractToken()`, etc.)

- [ ] **Step A7.2: Probar admin endpoint con cookie**

Tras login en Energy (sso-exchange), llamar admin:
```bash
curl -s -b /tmp/cookies.txt http://localhost:8001/admin_list.php
```

Expected: JSON con la lista (si el usuario es admin) o 403 (si no lo es). NO 401.

- [ ] **Step A7.3: Commit**

```bash
git add RegladoEnergy/BACKEND/auth.php
git commit -m "feat(energy/backend): auth.php lee JWT de cookie (fallback Bearer)"
```

---

### Task A8: Frontend Energy — `services/auth.js`

**Files:**
- Modify: `RegladoEnergy/src/services/auth.js`

- [ ] **Step A8.1: Cambiar `API_BASE` a relativo**

Cambiar la primera línea de configuración:

```javascript
// const API_BASE = import.meta.env.VITE_AUTH_API_URL || 'http://localhost:8000';
const API_BASE = '/auth';   // proxy local del BACKEND
```

- [ ] **Step A8.2: Cambiar paths de llamadas**

Las llamadas que antes iban a `${API_BASE}/auth/me` ahora van a `${API_BASE}/me.php` (porque el proxy local expone archivos `.php`):

Buscar todas las llamadas a `request("/auth/...")` en `auth.js` y cambiar:

| Antes | Después |
|---|---|
| `request('/auth/me')` | `request('/me.php')` |
| `request('/auth/logout')` | `request('/logout.php')` (o redirect directo si logout.php hace 302) |
| `request('/auth/update-name')` | `request('/proxy.php?path=/auth/update-name')` |
| `request('/auth/change-password')` | `request('/proxy.php?path=/auth/change-password')` |
| etc. | `proxy.php?path=...` para cualquier endpoint autenticado de ApiLoging |

(Adaptar según los métodos existentes en `auth.js` de Energy.)

- [ ] **Step A8.3: Quitar `Authorization: Bearer`, añadir `credentials: 'include'`**

Idéntico a Task 10 de fase 1 — adaptar la función `request()`.

- [ ] **Step A8.4: Eliminar `state.token` y helpers de cookie**

Idéntico a Task 10 de fase 1 — adaptar para Energy (puede haber pequeñas diferencias en el código existente).

- [ ] **Step A8.5: Probar build**

```bash
cd c:/xampp/htdocs/Reglado/RegladoEnergy && npm run build
```

Expected: build limpio.

- [ ] **Step A8.6: Commit**

```bash
git add RegladoEnergy/src/services/auth.js
git commit -m "feat(energy): services/auth.js usa proxy local + cookie HttpOnly"
```

---

### Task A9: Vite dev proxy

**Files:**
- Modify: `RegladoEnergy/vite.config.js`

- [ ] **Step A9.1: Añadir proxy `/auth`**

Sustituir el `defineConfig` por:

```javascript
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  plugins: [vue()],
  server: {
    proxy: {
      '/auth': {
        target: 'http://localhost:8001',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/auth/, '/auth'),
      },
    },
  },
});
```

- [ ] **Step A9.2: Probar**

Reiniciar `npm run dev`. Hacer login (vía SSO) y verificar:

DevTools → Network → `/auth/me.php`:
- Request URL: `http://localhost:5174/auth/me.php` (mismo origen)
- Real fulfilling server: el dev server de Vite proxy-a a `http://localhost:8001`.

DevTools → Application → Cookies → `localhost:5174`:
- ✓ `auth_token` con `HttpOnly`.

- [ ] **Step A9.3: Commit**

```bash
git add RegladoEnergy/vite.config.js
git commit -m "config(energy): Vite dev proxy /auth → BACKEND :8001"
```

---

### Task A10: Frontend Energy — `services/ssoClient.js`

**Files:**
- Modify: `RegladoEnergy/src/services/ssoClient.js`

- [ ] **Step A10.1: Eliminar `consumeTokenFromFragment`**

Esta función ya no se usa — el JWT no llega por fragmento. Eliminarla del archivo y de cualquier import en `App.vue`.

- [ ] **Step A10.2: Mantener `redirectToHandshake`, `wasSsoHandshakeFailed`, etc.**

El resto de utilidades (cooldown, `wasHandshakeAttempted`, `markHandshakeAttempted`, `redirectToLogout`) siguen siendo útiles. No se tocan.

- [ ] **Step A10.3: Verificar `App.vue`**

En `RegladoEnergy/src/App.vue`, dentro de `bootstrapAuth()`:
- Eliminar el bloque `if (fragmentToken) { auth.setSession(fragmentToken, null); ... }` — ya no llega fragmento.
- El resto (handshake, sso_failed, etc.) sigue igual.

- [ ] **Step A10.4: Probar build y dev**

```bash
cd c:/xampp/htdocs/Reglado/RegladoEnergy && npm run build && npm run dev
```

Probar flujo completo: login en Grupo → navegar a Energy → verificar cookie HttpOnly de `localhost:5174`.

- [ ] **Step A10.5: Commit**

```bash
git add RegladoEnergy/src/services/ssoClient.js RegladoEnergy/src/App.vue
git commit -m "feat(energy): bootstrapAuth retira lógica de fragment-token"
```

---

### Task A11: E2E manual de Energy

**Files:** ninguno.

- [ ] **Step A11.1: Levantar todo**

ApiLoging :8000, Grupo :5173, Energy :5174 (frontend) + Energy/BACKEND :8001.

- [ ] **Step A11.2: Login en Grupo, ir a Energy**

Login en `http://localhost:5173`. Después navegar manualmente a `http://localhost:5174` (o vía link si lo hay).

Esperado:
1. Energy hace handshake con Grupo (`/sso-handshake?return=...`).
2. Grupo emite code y redirige a `localhost:5174/auth/sso-exchange.php?code=...`.
3. Backend de Energy canjea, setea cookie y redirige a `localhost:5174/`.

- [ ] **Step A11.3: Verificar cookie**

DevTools en `localhost:5174`:
- Application → Cookies → `auth_token` con `HttpOnly` ✓.
- Console → `document.cookie` NO incluye `auth_token`.

- [ ] **Step A11.4: Verificar `/auth/me`**

Network → `auth/me.php` debe responder 200 con user. Sin header Authorization.

- [ ] **Step A11.5: Logout**

Click logout. Esperado:
- Backend revoca server-side, borra cookie local, redirige a `localhost:5173/sso-logout`.
- Tras la cadena de redirects, llega de vuelta a `localhost:5174` sin sesión.

---

## Bloque B — RegladoIngenieria

El patrón es idéntico a Energy con sustituciones de paths/nombres. Las tasks B1-B11 son una réplica de A1-A11 sobre `RegladoIngenieria/`. Para evitar duplicar 200+ líneas, las describo como **operaciones concretas de copia + sustitución**: el implementer debería seguirlas en orden y con cuidado de las sustituciones.

**Mapeo de sustituciones para todas las tasks de este bloque:**

| Concepto | Valor en Energy | Valor en Ingenieria |
|---|---|---|
| Carpeta proyecto | `RegladoEnergy` | `RegladoIngenieria` |
| Puerto frontend dev | 5174 | 5177 |
| Puerto BACKEND dev | 8001 | 8003 |
| `SITE_ORIGIN` (local) | `http://localhost:5174` | `http://localhost:5177` |
| `SITE_ORIGIN` (prod) | `https://regladoenergy.com` | dominio asignado (cuando esté) |
| Prefijo helper cookie | `energy_` | `ingenieria_` |
| Nombre lógico API key | `energy` | `ingenieria` |

### Task B1: Configuración `.env`

**Files:**
- Modify: `RegladoIngenieria/BACKEND/.env.example`
- Create: `RegladoIngenieria/BACKEND/.env`

- [ ] **Step B1.1: Añadir vars al `.env.example`**

Mismas vars que Task A1.1 con `SITE_ORIGIN=http://localhost:5177`.

- [ ] **Step B1.2: Crear `.env` local con clave nueva**

```bash
cp c:/xampp/htdocs/Reglado/RegladoIngenieria/BACKEND/.env.example c:/xampp/htdocs/Reglado/RegladoIngenieria/BACKEND/.env
echo "APILOGING_API_KEY=$(openssl rand -hex 32)"
```

Pegar la clave generada en `.env`. Registrar `ingenieria:<clave>` en `ApiLoging/.env` → `BACKEND_API_KEYS`.

- [ ] **Step B1.3: Commit**

```bash
git add RegladoIngenieria/BACKEND/.env.example
git commit -m "config(ingenieria/backend): vars L7"
```

### Task B2: Helper de cookie

**Files:** Create `RegladoIngenieria/BACKEND/auth_cookie.php`.

- [ ] **Step B2.1: Crear archivo**

Copiar el contenido de `RegladoEnergy/BACKEND/auth_cookie.php` y sustituir `energy_set_auth_cookie` → `ingenieria_set_auth_cookie` y `energy_clear_auth_cookie` → `ingenieria_clear_auth_cookie` en todas las apariciones.

- [ ] **Step B2.2: Commit**

```bash
git add RegladoIngenieria/BACKEND/auth_cookie.php
git commit -m "feat(ingenieria/backend): helper de cookie HttpOnly"
```

### Task B3: Endpoint `auth/sso-exchange.php`

- [ ] **Step B3.1: Crear archivo**

Copiar `RegladoEnergy/BACKEND/auth/sso-exchange.php` a `RegladoIngenieria/BACKEND/auth/sso-exchange.php`. Sustituciones:
- `energy_set_auth_cookie` → `ingenieria_set_auth_cookie`.
- Los `getenv('SITE_ORIGIN') ?: 'http://localhost:5174'` → `'http://localhost:5177'` como default.
- En la cabecera, ajustar el comentario para mencionar Ingeniería.

- [ ] **Step B3.2: Probar**

Mismo procedimiento que A3.3 con puerto 8003 y origin localhost:5177.

- [ ] **Step B3.3: Commit**

```bash
git add RegladoIngenieria/BACKEND/auth/sso-exchange.php
git commit -m "feat(ingenieria/backend): /auth/sso-exchange.php"
```

### Task B4: Endpoint `auth/me.php`

- [ ] **Step B4.1: Copiar y adaptar**

Copiar de Energy. No requiere sustituciones (el contenido es idéntico — solo lee cookie y reenvía).

- [ ] **Step B4.2: Commit**

```bash
git add RegladoIngenieria/BACKEND/auth/me.php
git commit -m "feat(ingenieria/backend): /auth/me.php"
```

### Task B5: Endpoint `auth/logout.php`

- [ ] **Step B5.1: Copiar y adaptar**

Copiar de Energy. Sustituciones:
- `energy_clear_auth_cookie` → `ingenieria_clear_auth_cookie`.
- Default `SITE_ORIGIN` → `http://localhost:5177`.

- [ ] **Step B5.2: Commit**

```bash
git add RegladoIngenieria/BACKEND/auth/logout.php
git commit -m "feat(ingenieria/backend): /auth/logout.php"
```

### Task B6: Endpoint `auth/proxy.php`

- [ ] **Step B6.1: Copiar tal cual**

Idéntico al de Energy — sin sustituciones (el endpoint es agnóstico al proyecto).

- [ ] **Step B6.2: Commit**

```bash
git add RegladoIngenieria/BACKEND/auth/proxy.php
git commit -m "feat(ingenieria/backend): /auth/proxy.php"
```

### Task B7: Adaptar validador JWT existente

**Files:** Modify `RegladoIngenieria/BACKEND/auth.php`.

- [ ] **Step B7.1: Mismo patrón que Task A7**

Localizar la función que extrae JWT del header. Sustituir su cuerpo para priorizar cookie sobre Bearer (mismo código que en Energy A7.1). Esta función puede llamarse distinto en Ingenieria (`getJwtFromRequest`, `extractToken`, etc.) — adaptar.

- [ ] **Step B7.2: Probar**

Mismo procedimiento que A7.2 con puerto 8003.

- [ ] **Step B7.3: Commit**

```bash
git add RegladoIngenieria/BACKEND/auth.php
git commit -m "feat(ingenieria/backend): auth.php lee JWT de cookie"
```

### Task B8: Frontend `services/auth.js`

**Files:** Modify `RegladoIngenieria/src/services/auth.js`.

- [ ] **Step B8.1: Mismo patrón que Task A8**

Cambios:
- `API_BASE = '/auth'` (relativo).
- Llamadas: `/me.php`, `/logout.php`, `/proxy.php?path=/auth/...`.
- `credentials: 'include'`, sin `Authorization: Bearer`.
- Eliminar `state.token` y helpers de cookie.

- [ ] **Step B8.2: Build**

```bash
cd c:/xampp/htdocs/Reglado/RegladoIngenieria && npm run build
```

Expected: build limpio.

- [ ] **Step B8.3: Commit**

```bash
git add RegladoIngenieria/src/services/auth.js
git commit -m "feat(ingenieria): services/auth.js usa proxy local + cookie HttpOnly"
```

### Task B9: Vite dev proxy

**Files:** Modify `RegladoIngenieria/vite.config.js`.

- [ ] **Step B9.1: Sustituir contenido del archivo**

Sustituir todo el archivo por:

```javascript
import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) },
  },
  server: {
    proxy: {
      '/auth': { target: 'http://localhost:8003', changeOrigin: true },
    },
  },
});
```

(Mantiene el alias `@` existente y añade el proxy.)

- [ ] **Step B9.2: Commit**

```bash
git add RegladoIngenieria/vite.config.js
git commit -m "config(ingenieria): Vite dev proxy /auth → BACKEND :8003"
```

### Task B10: Frontend `services/ssoClient.js` y `App.vue`

- [ ] **Step B10.1: Mismo patrón que Task A10**

- Eliminar `consumeTokenFromFragment` del `ssoClient.js`.
- En `App.vue` → `bootstrapAuth()`, eliminar la rama de fragment-token.

- [ ] **Step B10.2: Commit**

```bash
git add RegladoIngenieria/src/services/ssoClient.js RegladoIngenieria/src/App.vue
git commit -m "feat(ingenieria): bootstrapAuth retira fragment-token"
```

### Task B11: E2E manual de Ingeniería

- [ ] **Step B11.1: Levantar ApiLoging :8000, Grupo :5173, Ingenieria :5177 + BACKEND :8003**

- [ ] **Step B11.2: Login en Grupo, navegar a Ingenieria**

Mismo checklist que Task A11 sustituyendo puertos y dominio.

- [ ] **Step B11.3: Verificación final**

DevTools en `localhost:5177`:
- ✓ Cookie `auth_token` con `HttpOnly`.
- ✓ `document.cookie` no incluye `auth_token`.
- ✓ `/auth/me.php` (proxy local) responde 200 sin Authorization.
- ✓ Logout vuelve a Grupo y limpia ambas cookies.

- [ ] **Step B11.4: Push y release de Ingeniería + Energy**

Tras completar B11, subir cambios al repo y crear releases nuevas para Hostinger (siguiendo el patrón de releases del proyecto).

---

## Notas comunes

- **Orden de despliegue**: ApiLoging fase 1 ya en prod ANTES de desplegar Energy/Ingenieria fase 2. Si ApiLoging no tiene aún `BACKEND_API_KEYS` ni los endpoints `/auth/sso-issue-code` y `/auth/exchange-code`, los backends de Energy/Ingenieria fallarán en prod.

- **Generación de API keys**: cada API key debe ser único y suficientemente largo. Recomendado: 64 chars hex generados con `openssl rand -hex 32`. Almacenar SOLO en `.env` del backend correspondiente (gitignored).

- **Compatibilidad**: hasta que Maps fase 3 esté desplegada, Energy/Ingeniería pueden seguir conviviendo con un Maps que llame a ApiLoging directo con Bearer. Es por eso que ApiLoging mantiene el fallback Bearer durante toda la transición.

## Plan posterior

Cuando esta fase 2 esté desplegada y validada en producción, ejecutar `docs/superpowers/plans/2026-04-30-l7-fase3-maps.md`.
