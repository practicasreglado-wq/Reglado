# L7 — Fase 1: ApiLoging + GrupoReglado (Cookie HttpOnly)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implementar la base del rediseño L7 — tabla `sso_exchange_codes`, endpoints `/auth/sso-issue-code` y `/auth/exchange-code`, cookie HttpOnly en `/auth/login` y `/auth/logout`, middleware con prioridad cookie sobre Bearer, y rediseño del SSO handshake en GrupoReglado para emitir codes en lugar de fragment-tokens.

**Architecture:** Cada login en ApiLoging setea `Set-Cookie: auth_token=<jwt>; HttpOnly; Secure; SameSite=Lax`. El middleware lee el JWT prioritariamente de cookie y cae a Bearer durante transición. Cuando GrupoReglado redirige a otro dominio del ecosistema, primero pide un `code` single-use con TTL 30s a `/auth/sso-issue-code`, y la URL de destino contiene `?code=<code>` en lugar del JWT crudo. El backend del destino canjea ese code server-to-server con `/auth/exchange-code` usando una API key, recibe el JWT y setea su propia cookie HttpOnly local.

**Tech Stack:** PHP 8 raw (ApiLoging) sobre MariaDB + `firebase/php-jwt`; Vue 3 + Vite (GrupoReglado). Compatibilidad Bearer mantenida durante toda la transición.

**Base dir:** `c:/xampp/htdocs/Reglado/`. Todos los paths son relativos a esta raíz.

**Spec de referencia:** [docs/superpowers/specs/2026-04-30-l7-cookie-httponly-design.md](../specs/2026-04-30-l7-cookie-httponly-design.md)

**Fuera de alcance:** Cualquier frontend que no sea GrupoReglado. Energy/Ingenieria/Maps/Inmobiliaria se cubren en planes de fases siguientes.

---

## Task 1: Migración SQL para `sso_exchange_codes`

**Files:**
- Create: `ApiLoging/database/migrate_l7_sso_codes.sql`
- Modify: `ApiLoging/database/schema.sql`

- [ ] **Step 1.1: Crear migración**

Crear `ApiLoging/database/migrate_l7_sso_codes.sql`:

```sql
-- Migración L7: tabla de codes single-use para handoff cross-domain.
--
-- Cada code permite a un backend frontend canjear, server-to-server, un JWT
-- válido para el usuario asociado. TTL corto (30s) y single-use enforcement
-- garantizan que un code interceptado en URL pierde valor casi inmediato.

CREATE TABLE IF NOT EXISTS sso_exchange_codes (
  code           VARCHAR(64) PRIMARY KEY,
  user_id        INT NOT NULL,
  sid            VARCHAR(64) NOT NULL,
  target_origin  VARCHAR(255) NOT NULL,
  expires_at     TIMESTAMP NOT NULL,
  used_at        TIMESTAMP NULL,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_expires_at (expires_at),
  INDEX idx_user_id (user_id),
  CONSTRAINT fk_sso_codes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

- [ ] **Step 1.2: Actualizar schema.sql**

En `ApiLoging/database/schema.sql`, añadir tras el bloque de `revoked_tokens`:

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
  INDEX idx_user_id (user_id),
  CONSTRAINT fk_sso_codes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

- [ ] **Step 1.3: Ejecutar migración localmente**

```bash
/c/xampp/mysql/bin/mysql -h 127.0.0.1 -u root regladousers < c:/xampp/htdocs/Reglado/ApiLoging/database/migrate_l7_sso_codes.sql
```

Expected: sin errores. Verificar con:

```bash
/c/xampp/mysql/bin/mysql -h 127.0.0.1 -u root regladousers -e "DESCRIBE sso_exchange_codes;"
```

Expected: 7 columnas, índices en `expires_at` y `user_id`.

- [ ] **Step 1.4: Commit**

```bash
git add ApiLoging/database/migrate_l7_sso_codes.sql ApiLoging/database/schema.sql
git commit -m "feat(apiloging): tabla sso_exchange_codes para handoff L7"
```

---

## Task 2: Modelo `SsoExchangeCode`

**Files:**
- Create: `ApiLoging/models/SsoExchangeCode.php`

- [ ] **Step 2.1: Crear el modelo**

Crear `ApiLoging/models/SsoExchangeCode.php`:

```php
<?php

require_once __DIR__ . '/../config/Database.php';

/**
 * Modelo para sso_exchange_codes — codes single-use con TTL corto (30s)
 * que permiten a un backend frontend canjear, server-to-server, un JWT
 * válido para el usuario asociado.
 */
class SsoExchangeCode
{
    /**
     * Crea un code nuevo. Devuelve el code en plano (64 chars hex).
     * El caller lo entrega al frontend para que viaje en la URL hacia el
     * destino.
     */
    public static function issue(int $userId, string $sid, string $targetOrigin, int $ttlSeconds = 30): string
    {
        $code = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + $ttlSeconds);

        $db = Database::connect();
        $stmt = $db->prepare(
            "INSERT INTO sso_exchange_codes (code, user_id, sid, target_origin, expires_at)
             VALUES (:code, :user_id, :sid, :target_origin, :expires_at)"
        );
        $stmt->execute([
            ':code' => $code,
            ':user_id' => $userId,
            ':sid' => $sid,
            ':target_origin' => $targetOrigin,
            ':expires_at' => $expiresAt,
        ]);

        return $code;
    }

    /**
     * Marca el code como usado y devuelve la fila si era válido. Si no
     * (no existe, expirado, o ya usado), devuelve null. Operación atómica:
     * el UPDATE solo aplica si used_at IS NULL y expires_at > NOW(), evitando
     * race conditions con dos canjes simultáneos.
     */
    public static function consume(string $code): ?array
    {
        $db = Database::connect();

        $update = $db->prepare(
            "UPDATE sso_exchange_codes
             SET used_at = NOW()
             WHERE code = :code
               AND used_at IS NULL
               AND expires_at > NOW()"
        );
        $update->execute([':code' => $code]);

        if ($update->rowCount() === 0) {
            return null;
        }

        $select = $db->prepare(
            "SELECT code, user_id, sid, target_origin, expires_at, used_at
             FROM sso_exchange_codes
             WHERE code = :code"
        );
        $select->execute([':code' => $code]);
        $row = $select->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Purga codes con expires_at < NOW() - $olderThanDays días. Usado por el
     * cron de cleanup. Devuelve el número de filas borradas.
     */
    public static function purgeExpired(int $olderThanDays = 1): int
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            "DELETE FROM sso_exchange_codes
             WHERE expires_at < (NOW() - INTERVAL :days DAY)"
        );
        $stmt->execute([':days' => $olderThanDays]);
        return $stmt->rowCount();
    }
}
```

- [ ] **Step 2.2: Verificar que el require funciona**

```bash
cd c:/xampp/htdocs/Reglado/ApiLoging && php -r "require 'models/SsoExchangeCode.php'; echo class_exists('SsoExchangeCode') ? 'OK' : 'FAIL';"
```

Expected: `OK`.

- [ ] **Step 2.3: Commit**

```bash
git add ApiLoging/models/SsoExchangeCode.php
git commit -m "feat(apiloging): modelo SsoExchangeCode (issue/consume/purgeExpired)"
```

---

## Task 3: Servicio de validación de API key (`BackendApiKey`)

**Files:**
- Create: `ApiLoging/services/BackendApiKey.php`

- [ ] **Step 3.1: Crear el servicio**

Crear `ApiLoging/services/BackendApiKey.php`:

```php
<?php

/**
 * Resolución de API keys de backends (`BACKEND_API_KEYS` en .env) y validación
 * de pares (api_key, requesting_origin) para el endpoint /auth/exchange-code.
 *
 * Formato esperado en .env:
 *   BACKEND_API_KEYS=energy:abc123,ingenieria:def456,maps:ghi789
 *
 * Cada par <name>:<key> mapea al origin permitido vía un map estático en
 * código (ALLOWED_ORIGINS_BY_NAME) — ese mapa NO va en .env porque el origin
 * se valida también contra REDIRECT_ALLOWED_ORIGINS para defensa en
 * profundidad.
 */
class BackendApiKey
{
    /**
     * Mapeo nombre lógico → origen esperado en producción. En APP_ENV=local
     * se aceptan también los puertos de dev (localhost:5174, etc.) si
     * `LOCAL_BACKEND_ORIGINS` está definido.
     */
    private const ALLOWED_ORIGINS_BY_NAME = [
        'energy'        => 'https://regladoenergy.com',
        'ingenieria'    => 'https://regladoingenieria.com',
        'maps'          => 'https://regladomaps.com',
        'inmobiliaria'  => 'https://regladorealestate.com',
        'grupo'         => 'https://regladogroup.com',
    ];

    /**
     * Devuelve el origin asociado al api key dado, o null si no existe.
     * Resuelve también el nombre lógico (energy, ingenieria, ...) si se
     * necesita en logs.
     */
    public static function resolve(string $apiKey): ?array
    {
        $raw = (string) (getenv('BACKEND_API_KEYS') ?: '');
        if ($raw === '') {
            return null;
        }

        foreach (explode(',', $raw) as $entry) {
            $entry = trim($entry);
            if ($entry === '') continue;
            $parts = explode(':', $entry, 2);
            if (count($parts) !== 2) continue;
            [$name, $key] = $parts;
            $name = strtolower(trim($name));
            $key = trim($key);
            if ($key === '' || !hash_equals($key, $apiKey)) continue;

            $origin = self::ALLOWED_ORIGINS_BY_NAME[$name] ?? null;
            if ($origin === null) continue;

            return ['name' => $name, 'origin' => $origin];
        }

        return null;
    }

    /**
     * Comprueba que (api_key, requesting_origin) es una pareja válida:
     *   - el api_key existe en .env
     *   - el origin asociado al api_key coincide con requesting_origin (o uno
     *     de sus equivalentes locales si APP_ENV=local)
     */
    public static function isValidPair(string $apiKey, string $requestingOrigin): bool
    {
        $resolved = self::resolve($apiKey);
        if ($resolved === null) {
            return false;
        }

        if ($resolved['origin'] === $requestingOrigin) {
            return true;
        }

        // Permitir overrides locales en dev (ej. http://localhost:5174 actuando
        // como Energy). Comma-separated en .env: LOCAL_BACKEND_ORIGINS=energy:http://localhost:5174,ingenieria:http://localhost:5177
        $appEnv = strtolower((string) (getenv('APP_ENV') ?: 'local'));
        if ($appEnv !== 'local') {
            return false;
        }

        $localRaw = (string) (getenv('LOCAL_BACKEND_ORIGINS') ?: '');
        if ($localRaw === '') return false;

        foreach (explode(',', $localRaw) as $entry) {
            $entry = trim($entry);
            $parts = explode(':', $entry, 2);
            if (count($parts) !== 2) continue;
            [$name, $origin] = $parts;
            if (strtolower(trim($name)) === $resolved['name'] && trim($origin) === $requestingOrigin) {
                return true;
            }
        }

        return false;
    }
}
```

- [ ] **Step 3.2: Verificar carga**

```bash
cd c:/xampp/htdocs/Reglado/ApiLoging && php -r "require 'services/BackendApiKey.php'; echo class_exists('BackendApiKey') ? 'OK' : 'FAIL';"
```

Expected: `OK`.

- [ ] **Step 3.3: Commit**

```bash
git add ApiLoging/services/BackendApiKey.php
git commit -m "feat(apiloging): servicio BackendApiKey para validar pares (key, origin)"
```

---

## Task 4: Endpoint `POST /auth/sso-issue-code`

**Files:**
- Modify: `ApiLoging/index.php`
- Modify: `ApiLoging/controllers/AuthController.php`

- [ ] **Step 4.1: Añadir ruta en `index.php`**

En `ApiLoging/index.php`, después de la última ruta autenticada (busca el bloque que enruta `/auth/me`, debería estar cerca de las líneas 60-90), añadir:

```php
if ($uri === '/auth/sso-issue-code' && $method === 'POST') {
    require_once __DIR__ . '/middleware/AuthMiddleware.php';
    AuthMiddleware::requireAuth();
    require_once __DIR__ . '/controllers/AuthController.php';
    AuthController::ssoIssueCode();
    exit;
}
```

- [ ] **Step 4.2: Implementar controlador**

En `ApiLoging/controllers/AuthController.php`, al final de la clase (antes del `}` de cierre), añadir:

```php
    /**
     * Emite un code single-use de TTL 30s que permite a un backend frontend
     * canjear, server-to-server, un JWT válido para el usuario actual.
     * Solo se acepta si target_origin está en REDIRECT_ALLOWED_ORIGINS.
     */
    public static function ssoIssueCode(): void
    {
        $data = self::readJsonBody();
        $targetOrigin = isset($data['target_origin']) ? trim((string) $data['target_origin']) : '';

        if ($targetOrigin === '') {
            Response::json(['error' => 'target_origin is required'], 422);
        }

        if (!Security::isAllowedRedirectOrigin($targetOrigin)) {
            Response::json(['error' => 'target_origin not allowed'], 403);
        }

        $authData = AuthMiddleware::context(); // ['user_id' => ..., 'sid' => ...]
        $userId = (int) $authData['user_id'];
        $sid = (string) $authData['sid'];

        require_once __DIR__ . '/../models/SsoExchangeCode.php';
        $code = SsoExchangeCode::issue($userId, $sid, $targetOrigin, 30);

        Response::json(['code' => $code], 200);
    }
```

- [ ] **Step 4.3: Verificar que `AuthMiddleware::context()` y `Security::isAllowedRedirectOrigin()` existen**

```bash
grep -n "public static function context\|public static function isAllowedRedirectOrigin" c:/xampp/htdocs/Reglado/ApiLoging/middleware/AuthMiddleware.php c:/xampp/htdocs/Reglado/ApiLoging/utils/Security.php
```

Expected: ambas funciones aparecen.

Si `context()` no existe en AuthMiddleware, **completar Task 6 primero** (allí se añade junto al refactor del middleware) y luego volver a Task 4. Alternativa: implementar Task 4 leyendo los claims directamente con `JwtService::decode()` desde el cookie/header — pero la versión limpia es esperar a Task 6.

Si `isAllowedRedirectOrigin()` no existe en Security.php, crearla:

```php
    /**
     * True si el origin (esquema+host+puerto) está en REDIRECT_ALLOWED_ORIGINS.
     */
    public static function isAllowedRedirectOrigin(string $origin): bool
    {
        $list = (string) (getenv('REDIRECT_ALLOWED_ORIGINS') ?: '');
        if ($list === '') return false;

        $allowed = array_map('trim', explode(',', $list));
        $allowed = array_filter($allowed, fn($o) => $o !== '');
        return in_array($origin, $allowed, true);
    }
```

- [ ] **Step 4.4: Probar manualmente**

Arrancar ApiLoging si no está corriendo:
```bash
cd c:/xampp/htdocs/Reglado/ApiLoging && php -S localhost:8000 &
```

Hacer login y obtener token:
```bash
TOKEN=$(curl -s -X POST http://localhost:8000/auth/login -H "Content-Type: application/json" -d '{"email":"<email_admin>","password":"<password>"}' | python -c "import sys,json;print(json.load(sys.stdin)['token'])")
echo "Token: $TOKEN"
```

Llamar al nuevo endpoint:
```bash
curl -s -X POST http://localhost:8000/auth/sso-issue-code \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"target_origin":"http://localhost:5174"}'
```

Expected: respuesta `{"code":"..."}` con un hex de 64 chars.

Probar rechazo:
```bash
curl -s -X POST http://localhost:8000/auth/sso-issue-code \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"target_origin":"http://malicious.example"}'
```

Expected: `{"error":"target_origin not allowed"}` con HTTP 403.

- [ ] **Step 4.5: Commit**

```bash
git add ApiLoging/index.php ApiLoging/controllers/AuthController.php ApiLoging/utils/Security.php
git commit -m "feat(apiloging): endpoint POST /auth/sso-issue-code"
```

---

## Task 5: Endpoint `POST /auth/exchange-code` (server-to-server)

**Files:**
- Modify: `ApiLoging/index.php`
- Modify: `ApiLoging/controllers/AuthController.php`

- [ ] **Step 5.1: Añadir ruta en `index.php`**

```php
if ($uri === '/auth/exchange-code' && $method === 'POST') {
    require_once __DIR__ . '/controllers/AuthController.php';
    AuthController::ssoExchangeCode();
    exit;
}
```

NO se llama a `AuthMiddleware::requireAuth()` aquí — la autenticación es por API key, no por JWT de usuario.

- [ ] **Step 5.2: Implementar controlador**

En `AuthController.php`, añadir tras `ssoIssueCode`:

```php
    /**
     * Endpoint server-to-server. Recibe un code emitido por
     * /auth/sso-issue-code y devuelve un JWT fresco si el code es válido y
     * el requesting_origin coincide con el target_origin original.
     *
     * Auth por API key (Bearer del backend frontend, NO del usuario).
     */
    public static function ssoExchangeCode(): void
    {
        // 1. Validar API key
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        if (!is_string($authHeader) || stripos($authHeader, 'Bearer ') !== 0) {
            Response::json(['error' => 'api key required'], 401);
        }
        $apiKey = trim(substr($authHeader, 7));
        if ($apiKey === '') {
            Response::json(['error' => 'api key required'], 401);
        }

        $data = self::readJsonBody();
        $code = isset($data['code']) ? trim((string) $data['code']) : '';
        $requestingOrigin = isset($data['requesting_origin']) ? trim((string) $data['requesting_origin']) : '';

        if ($code === '' || $requestingOrigin === '') {
            Response::json(['error' => 'code and requesting_origin are required'], 422);
        }

        require_once __DIR__ . '/../services/BackendApiKey.php';
        if (!BackendApiKey::isValidPair($apiKey, $requestingOrigin)) {
            error_log('[AuthController::ssoExchangeCode] api_key/origin mismatch from ' . $requestingOrigin);
            Response::json(['error' => 'invalid api key for origin'], 403);
        }

        // 2. Consumir code (atómico, single-use)
        require_once __DIR__ . '/../models/SsoExchangeCode.php';
        $row = SsoExchangeCode::consume($code);
        if ($row === null) {
            Response::json(['error' => 'code invalid, expired, or already used'], 410);
        }

        // 3. Validar que target_origin del code coincide con requesting_origin
        if ($row['target_origin'] !== $requestingOrigin) {
            error_log('[AuthController::ssoExchangeCode] target_origin mismatch: code=' . $row['target_origin'] . ' req=' . $requestingOrigin);
            Response::json(['error' => 'origin mismatch'], 403);
        }

        // 4. Cargar usuario y emitir JWT con el sid del code (mantiene la sesión activa)
        $user = User::findById((int) $row['user_id']);
        if (!$user) {
            Response::json(['error' => 'user not found'], 410);
        }

        // El sid debe seguir vigente en users.current_session_id (single-session enforcement)
        $currentSid = User::getCurrentSessionId((int) $user['id']);
        if ($currentSid !== $row['sid']) {
            // La sesión que originó el code ya fue invalidada (logout o kick-old)
            Response::json(['error' => 'session no longer active'], 410);
        }

        $jwt = JwtService::generate($user, (string) $row['sid']);

        Response::json([
            'token' => $jwt,
            'user' => User::publicFields($user),
        ], 200);
    }
```

- [ ] **Step 5.3: Probar manualmente con curl**

Asumiendo `BACKEND_API_KEYS=energy:testkey-energy-123` y `LOCAL_BACKEND_ORIGINS=energy:http://localhost:5174` en `.env`:

```bash
# Reiniciar ApiLoging para que recoja el nuevo .env
pkill -f "php -S localhost:8000" || true
cd c:/xampp/htdocs/Reglado/ApiLoging && php -S localhost:8000 &
sleep 1

# Login y obtener un code (paso 4 ya hecho)
TOKEN=$(curl -s -X POST http://localhost:8000/auth/login -H "Content-Type: application/json" -d '{"email":"<email>","password":"<pwd>"}' | python -c "import sys,json;print(json.load(sys.stdin)['token'])")
CODE=$(curl -s -X POST http://localhost:8000/auth/sso-issue-code -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" -d '{"target_origin":"http://localhost:5174"}' | python -c "import sys,json;print(json.load(sys.stdin)['code'])")
echo "Code: $CODE"

# Canjear el code
curl -s -X POST http://localhost:8000/auth/exchange-code \
  -H "Authorization: Bearer testkey-energy-123" \
  -H "Content-Type: application/json" \
  -d "{\"code\":\"$CODE\",\"requesting_origin\":\"http://localhost:5174\"}"
```

Expected: respuesta `{"token":"...","user":{...}}`.

Verificar single-use — segunda llamada con el mismo code:
```bash
curl -s -X POST http://localhost:8000/auth/exchange-code \
  -H "Authorization: Bearer testkey-energy-123" \
  -H "Content-Type: application/json" \
  -d "{\"code\":\"$CODE\",\"requesting_origin\":\"http://localhost:5174\"}"
```

Expected: `{"error":"code invalid, expired, or already used"}` con HTTP 410.

Verificar API key inválida:
```bash
curl -s -X POST http://localhost:8000/auth/exchange-code \
  -H "Authorization: Bearer wrongkey" \
  -H "Content-Type: application/json" \
  -d '{"code":"x","requesting_origin":"http://localhost:5174"}'
```

Expected: `{"error":"invalid api key for origin"}` con HTTP 403.

- [ ] **Step 5.4: Commit**

```bash
git add ApiLoging/index.php ApiLoging/controllers/AuthController.php
git commit -m "feat(apiloging): endpoint POST /auth/exchange-code (server-to-server)"
```

---

## Task 6: AuthMiddleware con prioridad cookie sobre Bearer

**Files:**
- Modify: `ApiLoging/middleware/AuthMiddleware.php`

- [ ] **Step 6.1: Sustituir `extractBearerToken`**

En `AuthMiddleware.php`, localizar `public static function extractBearerToken(): ?string` y sustituirlo por:

```php
    /**
     * Extrae el JWT priorizando cookie sobre header Authorization.
     *
     * Orden:
     *   1. Cookie `auth_token` (modo nuevo, L7).
     *   2. Header `Authorization: Bearer ...` (modo legacy / fallback durante
     *      transición y para llamadas server-to-server desde otros backends).
     *
     * Mantenemos el nombre legacy `extractBearerToken()` como alias por
     * compatibilidad con código existente.
     */
    public static function extractToken(): ?string
    {
        // 1. Cookie HttpOnly (preferida)
        if (isset($_COOKIE['auth_token']) && is_string($_COOKIE['auth_token'])) {
            $cookieToken = trim($_COOKIE['auth_token']);
            if ($cookieToken !== '') {
                return $cookieToken;
            }
        }

        // 2. Authorization: Bearer (fallback)
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        if (is_string($authHeader) && stripos($authHeader, 'Bearer ') === 0) {
            $bearer = trim(substr($authHeader, 7));
            if ($bearer !== '') {
                return $bearer;
            }
        }

        return null;
    }

    /**
     * Alias legacy. Mantener hasta que se elimine el modo Bearer en una
     * release posterior (ver "Cleanup deprecation Bearer" en spec).
     */
    public static function extractBearerToken(): ?string
    {
        return self::extractToken();
    }
```

- [ ] **Step 6.2: Añadir helper `context()` si no existe**

Buscar en `AuthMiddleware.php` si ya hay un método público que devuelva los claims actuales (`user_id`, `sid`). Si no existe, añadirlo:

```php
    private static array $currentClaims = [];

    /**
     * Devuelve los claims del JWT validado en este request. Solo válido
     * tras `requireAuth()`.
     */
    public static function context(): array
    {
        return self::$currentClaims;
    }
```

Y dentro de `requireAuth()` (al final, cuando ya se validó el token con éxito), añadir:

```php
        self::$currentClaims = [
            'user_id' => (int) ($claims['sub'] ?? 0),
            'sid'     => (string) ($claims['sid'] ?? ''),
            'role'    => (string) ($claims['role'] ?? 'user'),
        ];
```

(Adaptar según los nombres reales de los claims del JWT del proyecto — consultar `services/JwtService.php`.)

- [ ] **Step 6.3: Probar que el modo Bearer sigue funcionando**

```bash
TOKEN=$(curl -s -X POST http://localhost:8000/auth/login -H "Content-Type: application/json" -d '{"email":"<email>","password":"<pwd>"}' | python -c "import sys,json;print(json.load(sys.stdin)['token'])")
curl -s http://localhost:8000/auth/me -H "Authorization: Bearer $TOKEN"
```

Expected: respuesta `{"user":{...}}` HTTP 200.

- [ ] **Step 6.4: Commit**

```bash
git add ApiLoging/middleware/AuthMiddleware.php
git commit -m "feat(apiloging): AuthMiddleware lee JWT de cookie con fallback Bearer"
```

---

## Task 7: Cookie HttpOnly en `/auth/login`

**Files:**
- Modify: `ApiLoging/controllers/AuthController.php`
- Create: `ApiLoging/utils/AuthCookie.php`

- [ ] **Step 7.1: Crear helper `AuthCookie`**

Crear `ApiLoging/utils/AuthCookie.php`:

```php
<?php

/**
 * Centraliza el seteo y limpieza de la cookie HttpOnly `auth_token`.
 *
 * - HttpOnly: no accesible desde JavaScript (mitiga exfiltración por XSS).
 * - Secure: solo HTTPS en cualquier entorno != local.
 * - SameSite=Lax: cookie viaja en navegación top-level (incluido el redirect
 *   tras canjear el code), pero NO en cross-site fetch/XHR — eso se hace
 *   siempre vía proxy local del frontend.
 * - Path=/: la cookie aplica a todo el dominio.
 */
class AuthCookie
{
    public const NAME = 'auth_token';

    /**
     * Setea la cookie con el JWT. TTL toma el valor de JWT_TTL_SECONDS o
     * 86400 (24h) por defecto.
     */
    public static function set(string $jwt): void
    {
        $ttl = (int) (getenv('JWT_TTL_SECONDS') ?: 86400);
        $appEnv = strtolower((string) (getenv('APP_ENV') ?: 'local'));

        $options = [
            'expires'  => time() + $ttl,
            'path'     => '/',
            'secure'   => $appEnv !== 'local',
            'httponly' => true,
            'samesite' => 'Lax',
        ];

        // En PHP < 7.3 no admite array de options, pero asumimos PHP 8+.
        setcookie(self::NAME, $jwt, $options);
    }

    /**
     * Limpia la cookie en el navegador del cliente.
     */
    public static function clear(): void
    {
        $appEnv = strtolower((string) (getenv('APP_ENV') ?: 'local'));
        $options = [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => $appEnv !== 'local',
            'httponly' => true,
            'samesite' => 'Lax',
        ];
        setcookie(self::NAME, '', $options);
    }
}
```

- [ ] **Step 7.2: Modificar `AuthController::login`**

En `AuthController.php`, localizar `public static function login()`. Tras la línea donde se genera el JWT (algo como `$jwt = JwtService::generate(...)`) y **antes** de la última `Response::json([...])`, añadir:

```php
        require_once __DIR__ . '/../utils/AuthCookie.php';
        AuthCookie::set($jwt);
```

La response sigue devolviendo `{token, user}` en el body (compatibilidad con frontends sin migrar).

- [ ] **Step 7.3: Modificar todos los endpoints que emiten JWT nuevo**

Buscar todas las llamadas a `JwtService::generate(` en `AuthController.php`:

```bash
grep -n "JwtService::generate" c:/xampp/htdocs/Reglado/ApiLoging/controllers/AuthController.php
```

Para cada coincidencia que esté en un endpoint accesible directamente desde browser (login, verify-email, confirm-email-change, reset-password), añadir `AuthCookie::set($jwt);` justo después.

NO añadir en `ssoExchangeCode` — ahí el JWT viaja en JSON al backend frontend y es ese backend quien setea su propia cookie local.

- [ ] **Step 7.4: Probar manualmente**

```bash
curl -s -i -X POST http://localhost:8000/auth/login -H "Content-Type: application/json" -d '{"email":"<email>","password":"<pwd>"}' | grep -i "set-cookie"
```

Expected: línea `Set-Cookie: auth_token=...; expires=...; Max-Age=86400; path=/; HttpOnly; SameSite=Lax`. En APP_ENV=local NO debe aparecer `Secure`.

Verificar acceso con cookie:
```bash
curl -s -c /tmp/cookies.txt -X POST http://localhost:8000/auth/login -H "Content-Type: application/json" -d '{"email":"<email>","password":"<pwd>"}' >/dev/null
curl -s -b /tmp/cookies.txt http://localhost:8000/auth/me
```

Expected: respuesta de `/auth/me` con el usuario, sin necesidad de header `Authorization`.

- [ ] **Step 7.5: Commit**

```bash
git add ApiLoging/utils/AuthCookie.php ApiLoging/controllers/AuthController.php
git commit -m "feat(apiloging): cookie HttpOnly auth_token en endpoints que emiten JWT"
```

---

## Task 8: Limpieza de cookie en `/auth/logout`

**Files:**
- Modify: `ApiLoging/controllers/AuthController.php`

- [ ] **Step 8.1: Modificar `AuthController::logout`**

Localizar `public static function logout()`. Tras la lógica de revocación del token (si existe), añadir:

```php
        require_once __DIR__ . '/../utils/AuthCookie.php';
        AuthCookie::clear();
```

- [ ] **Step 8.2: Probar manualmente**

```bash
curl -s -c /tmp/cookies.txt -X POST http://localhost:8000/auth/login -H "Content-Type: application/json" -d '{"email":"<email>","password":"<pwd>"}' >/dev/null
cat /tmp/cookies.txt | grep auth_token

curl -s -i -b /tmp/cookies.txt -c /tmp/cookies.txt -X POST http://localhost:8000/auth/logout | grep -i "set-cookie"
```

Expected: línea `Set-Cookie: auth_token=deleted; expires=<fecha pasada>; Max-Age=-3600; path=/; HttpOnly; SameSite=Lax`.

Verificar /auth/me tras logout:
```bash
curl -s -i -b /tmp/cookies.txt http://localhost:8000/auth/me | head -3
```

Expected: HTTP 401.

- [ ] **Step 8.3: Commit**

```bash
git add ApiLoging/controllers/AuthController.php
git commit -m "feat(apiloging): /auth/logout limpia cookie auth_token"
```

---

## Task 9: Extender cron `cleanup.php` con purga de codes

**Files:**
- Modify: `ApiLoging/scripts/cleanup.php`

- [ ] **Step 9.1: Añadir purga de sso_exchange_codes**

En `ApiLoging/scripts/cleanup.php`, tras el bloque que purga `revoked_tokens`, añadir:

```php
// 3) sso_exchange_codes — codes ya expirados o usados, antiguos
try {
    $stmt = $db->prepare(
        "DELETE FROM sso_exchange_codes WHERE expires_at < (NOW() - INTERVAL 1 DAY)"
    );
    $stmt->execute();
    $deleted = $stmt->rowCount();
    $totalDeleted += $deleted;
    echo "sso_exchange_codes: {$deleted} filas eliminadas\n";
} catch (Throwable $e) {
    fwrite(STDERR, "sso_exchange_codes FAIL: " . $e->getMessage() . "\n");
}
```

- [ ] **Step 9.2: Probar el script**

```bash
cd c:/xampp/htdocs/Reglado/ApiLoging && php scripts/cleanup.php
```

Expected: 3 líneas (`rate_limits`, `revoked_tokens`, `sso_exchange_codes`) y `CLEANUP_END total=...`.

- [ ] **Step 9.3: Commit**

```bash
git add ApiLoging/scripts/cleanup.php
git commit -m "feat(apiloging): cron cleanup.php purga sso_exchange_codes obsoletos"
```

---

## Task 10: Frontend Grupo — `services/auth.js`

**Files:**
- Modify: `GrupoReglado/src/services/auth.js`

- [ ] **Step 10.1: Quitar `Authorization: Bearer`, añadir `credentials: 'include'`**

Localizar la función `request()` en `GrupoReglado/src/services/auth.js`. Modificar la llamada `fetch` para que **siempre** incluya `credentials: 'include'` y NO incluya `Authorization`:

```javascript
async function request(path, options = {}) {
  const response = await fetch(`${API_BASE}${path}`, {
    ...options,
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      ...(options.headers || {}),
    },
  });
  // ... resto igual
}
```

Borrar cualquier línea que añada `Authorization: 'Bearer ' + state.token`.

- [ ] **Step 10.2: Eliminar `state.token` y helpers de cookie**

En `auth.js`:

- Eliminar la propiedad `token` del `reactive({...})` inicial.
- Eliminar las funciones `setCookie`, `clearCookie`, `getCookie`, `setToken`, `COOKIE_TOKEN_KEY`.
- En `setSession(token, user)`, ignorar el parámetro `token` (mantener firma por compatibilidad de llamadas, pero no hacer nada con él):

```javascript
function setSession(token, user = null) {
  // El token ya no se almacena en cliente (cookie HttpOnly del backend lo gestiona).
  // Mantenemos la firma por compatibilidad. Solo persistimos user.
  state.user = user;
}

function clearSession() {
  state.user = null;
}
```

- [ ] **Step 10.3: Simplificar `initialize`**

```javascript
async function initialize() {
  state.loading = true;
  try {
    const payload = await request('/auth/me', { method: 'GET' });
    state.user = payload.user || null;
  } catch {
    state.user = null;
  } finally {
    state.loading = false;
  }
}
```

(Sin lectura de cookie en cliente. La cookie HttpOnly viaja sola con `credentials: 'include'`.)

- [ ] **Step 10.4: Probar build**

```bash
cd c:/xampp/htdocs/Reglado/GrupoReglado && npm run build
```

Expected: `✓ built in N.NNs` sin errores.

- [ ] **Step 10.5: Probar manualmente con dev**

Arrancar Grupo: `npm run dev` y en navegador hacer login. Verificar en DevTools → Console:

```javascript
document.cookie
```

Expected: NO incluye `auth_token` (porque es HttpOnly).

DevTools → Application → Cookies → `localhost:5173`:

Expected: `auth_token` aparece con flag `HttpOnly` ✓.

`/auth/me` en Network tab: Request Headers NO debe incluir `Authorization`. Cookie SÍ se envía (visible en Request Cookies).

- [ ] **Step 10.6: Commit**

```bash
git add GrupoReglado/src/services/auth.js
git commit -m "feat(grupo): services/auth.js usa cookie HttpOnly + credentials include"
```

---

## Task 11: Frontend Grupo — `services/ssoClient.js` con code flow

**Files:**
- Modify: `GrupoReglado/src/services/ssoClient.js`

- [ ] **Step 11.1: Sustituir `redirectToStore` por code flow**

Localizar `export function redirectToStore(token, returnTo = null)`. Sustituir el cuerpo:

```javascript
/**
 * Solicita un code single-use a ApiLoging y redirige al backend del destino
 * para que canjee el code por una cookie HttpOnly local. El JWT NUNCA pasa
 * por el navegador del usuario.
 */
export async function redirectToStore(_unusedToken, returnTo = null) {
  const finalReturn = returnTo || window.location.origin + '/';
  const targetUrl = new URL(finalReturn);
  const targetOrigin = targetUrl.origin;

  // Pedir code a ApiLoging (cookie HttpOnly viaja con credentials: include)
  const apiBase = import.meta.env.VITE_AUTH_API_URL || 'http://localhost:8000';
  const response = await fetch(`${apiBase}/auth/sso-issue-code`, {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ target_origin: targetOrigin }),
  });

  if (!response.ok) {
    // Sin code → redirigir al destino con sso_failed=1 para que muestre login
    const failed = new URL('/?sso_failed=1', targetOrigin).toString();
    window.location.replace(failed);
    return;
  }

  const { code } = await response.json();

  // Redirigir al backend del destino para canjear el code
  const exchangeUrl = new URL('/auth/sso-exchange', targetOrigin);
  exchangeUrl.searchParams.set('code', code);
  exchangeUrl.searchParams.set('return', finalReturn);
  window.location.replace(exchangeUrl.toString());
}
```

NOTA: el endpoint `sso-exchange` (sin extensión `.php`) lo gestionará el `.htaccess` del destino mediante rewrite. En backends que no usen rewrite, sería `/auth/sso-exchange.php`.

- [ ] **Step 11.2: Eliminar `consumeTokenFromFragment` (ya no aplica)**

`consumeTokenFromFragment` solo se usa en frontends que reciben token; en Grupo NO se usa para recibir, solo se exporta. Verificar:

```bash
grep -rn "consumeTokenFromFragment" c:/xampp/htdocs/Reglado/GrupoReglado/src
```

Si no hay uso interno, se puede eliminar la función. Si hay uso (p. ej. en `App.vue` por si Grupo recibe un token de otro origen), dejarla pero documentar como deprecated.

- [ ] **Step 11.3: Probar build**

```bash
cd c:/xampp/htdocs/Reglado/GrupoReglado && npm run build
```

Expected: build limpio.

- [ ] **Step 11.4: Commit**

```bash
git add GrupoReglado/src/services/ssoClient.js
git commit -m "feat(grupo): redirectToStore con code flow (no más fragment-token)"
```

---

## Task 12: Frontend Grupo — `SsoHandshakeView.vue`

**Files:**
- Modify: `GrupoReglado/src/pages/SsoHandshakeView.vue`

- [ ] **Step 12.1: Refactor a code flow**

Sustituir todo el contenido del `<script setup>` por:

```javascript
import { onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import { auth } from "../services/auth";
import SsoLayout from "../components/SsoLayout.vue";
import {
  isAllowedReturnUrl,
  buildReturnUrlWithParams,
} from "../services/ssoHub";

const route = useRoute();
const state = ref("processing");

onMounted(async () => {
  const returnUrl = typeof route.query.return === "string" ? route.query.return : "";

  if (!isAllowedReturnUrl(returnUrl)) {
    state.value = "invalid";
    return;
  }

  // Verifica si Grupo tiene sesión activa (cookie HttpOnly de regladogroup.com)
  await auth.initialize();

  if (!auth.state.user) {
    // Sin sesión: avisar al destino con sso_failed=1 para que muestre invitado
    const failed = buildReturnUrlWithParams(returnUrl, { sso_failed: 1 });
    window.location.replace(failed);
    return;
  }

  // Con sesión: pedir un code y redirigir al backend del destino
  const targetOrigin = new URL(returnUrl).origin;
  const apiBase = import.meta.env.VITE_AUTH_API_URL || "http://localhost:8000";
  const codeResp = await fetch(`${apiBase}/auth/sso-issue-code`, {
    method: "POST",
    credentials: "include",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ target_origin: targetOrigin }),
  });

  if (!codeResp.ok) {
    const failed = buildReturnUrlWithParams(returnUrl, { sso_failed: 1 });
    window.location.replace(failed);
    return;
  }

  const { code } = await codeResp.json();
  const exchangeUrl = new URL("/auth/sso-exchange", targetOrigin);
  exchangeUrl.searchParams.set("code", code);
  exchangeUrl.searchParams.set("return", returnUrl);
  window.location.replace(exchangeUrl.toString());
});
```

- [ ] **Step 12.2: Eliminar import `buildReturnUrlWithTokenFragment`**

Ya no se usa. Si el archivo `services/ssoHub.js` no tiene otro consumer, eliminar también la función exportada.

- [ ] **Step 12.3: Probar build**

```bash
cd c:/xampp/htdocs/Reglado/GrupoReglado && npm run build
```

Expected: build limpio.

- [ ] **Step 12.4: Commit**

```bash
git add GrupoReglado/src/pages/SsoHandshakeView.vue GrupoReglado/src/services/ssoHub.js
git commit -m "feat(grupo): SsoHandshakeView emite codes en vez de fragment-token"
```

---

## Task 13: Simplificar `SsoStoreView.vue`

**Files:**
- Modify: `GrupoReglado/src/pages/SsoStoreView.vue`

- [ ] **Step 13.1: Comprobar uso actual**

```bash
grep -rn "sso-store\|SsoStoreView" c:/xampp/htdocs/Reglado/GrupoReglado/src
```

`SsoStoreView` originalmente se usaba para que un origen externo enviara un token al hub para que lo guardara. Con code flow, **el hub ya no recibe tokens** — solo emite codes.

- [ ] **Step 13.2: Convertir SsoStoreView en página de error/transición**

Como la ruta `/sso-store` puede seguir siendo invocada desde frontends sin migrar (modo Bearer), mantener la vista pero simplificada: si recibe un `?token=` (legacy), redirige al return con `sso_failed=1` y un log de aviso.

```vue
<template>
  <SsoLayout :error="'El protocolo de SSO ha cambiado. Vuelve al origen y reintenta.'" />
</template>

<script setup>
import { onMounted } from "vue";
import { useRoute } from "vue-router";
import SsoLayout from "../components/SsoLayout.vue";
import { isAllowedReturnUrl, buildReturnUrlWithParams } from "../services/ssoHub";

const route = useRoute();

onMounted(() => {
  const returnUrl = typeof route.query.return === "string" ? route.query.return : "";
  // Si el origen sigue mandando token, lo ignoramos y volvemos con sso_failed=1
  console.warn("[SsoStoreView] llamada legacy detectada — el origen no está migrado al code flow");
  if (isAllowedReturnUrl(returnUrl)) {
    const failed = buildReturnUrlWithParams(returnUrl, { sso_failed: 1 });
    setTimeout(() => window.location.replace(failed), 1500);
  }
});
</script>
```

- [ ] **Step 13.3: Commit**

```bash
git add GrupoReglado/src/pages/SsoStoreView.vue
git commit -m "feat(grupo): SsoStoreView retira lógica legacy (code flow no la usa)"
```

---

## Task 14: Verificación E2E completa

**Files:** ninguno (solo verificación manual).

- [ ] **Step 14.1: Levantar el ecosistema**

Usar la skill `levantar-reglado` o manualmente:
```bash
cd c:/xampp/htdocs/Reglado/ApiLoging && php -S localhost:8000 &
cd c:/xampp/htdocs/Reglado/GrupoReglado && npm run dev &
```

- [ ] **Step 14.2: Login en Grupo**

Navegar a `http://localhost:5173/login`, hacer login con un usuario válido.

DevTools → Application → Cookies → `localhost:5173`:
- ✓ `auth_token` presente
- ✓ Flag `HttpOnly` activo
- ✓ `Path=/`
- ✓ `SameSite=Lax`

DevTools → Console:
```javascript
document.cookie
```
Expected: NO incluye `auth_token`.

- [ ] **Step 14.3: Llamada a `/auth/me`**

Tras login, en Network tab buscar `auth/me`:
- Request Headers: NO debe contener `Authorization`.
- Request Cookies: SÍ contiene `auth_token`.
- Response: 200 con `{user: {...}}`.

- [ ] **Step 14.4: Code flow manual**

En Console:
```javascript
fetch('/auth/sso-issue-code', {
  method: 'POST',
  credentials: 'include',
  headers: {'Content-Type':'application/json'},
  body: JSON.stringify({target_origin: 'http://localhost:5174'})
}).then(r => r.json()).then(console.log);
```

Expected: `{code: "..."}` con 64 chars hex.

- [ ] **Step 14.5: Logout**

Click en logout (o navegar a un endpoint que lo dispare). Verificar:
- Cookie `auth_token` desaparece de Application → Cookies.
- `/auth/me` siguiente devuelve 401.

- [ ] **Step 14.6: Verificar fallback Bearer sigue funcionando**

```bash
TOKEN=$(curl -s -X POST http://localhost:8000/auth/login -H "Content-Type: application/json" -d '{"email":"<email>","password":"<pwd>"}' | python -c "import sys,json;print(json.load(sys.stdin)['token'])")
curl -s http://localhost:8000/auth/me -H "Authorization: Bearer $TOKEN"
```

Expected: 200 con user. (Esto cubre que un frontend sin migrar todavía funciona.)

- [ ] **Step 14.7: Final commit (si quedan cambios sueltos)**

```bash
cd c:/xampp/htdocs/Reglado && git status
```

Si limpio, fase 1 cerrada.

---

## Notas para los implementadores

- **Concurrencia**: el `consume()` del code es atómico vía `UPDATE ... WHERE used_at IS NULL AND expires_at > NOW()`. Dos canjes simultáneos del mismo code: solo uno gana, el otro recibe `null` y devuelve 410.
- **Compatibilidad Bearer**: ningún frontend NO migrado debería romperse. Las llamadas siguen yendo con `Authorization: Bearer`. El middleware lo acepta como fallback.
- **Local dev sin HTTPS**: la cookie sale sin flag `Secure` cuando `APP_ENV=local`. En producción Hostinger ya hay HTTPS y `Secure` se aplica automáticamente.
- **CORS y `credentials: include`**: ApiLoging ya tiene `Access-Control-Allow-Credentials: true` en `Cors.php` (verificar). Si no, añadirlo en una task previa o como fix in-flight.

## Plan posterior

Cuando esta fase 1 esté desplegada y validada en producción, ejecutar `docs/superpowers/plans/2026-04-30-l7-fase2-energy-ingenieria.md`.
