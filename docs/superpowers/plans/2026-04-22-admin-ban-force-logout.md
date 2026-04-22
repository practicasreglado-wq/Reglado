# Admin Ban & Force-Logout Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Añadir al panel admin de GrupoReglado un menú de 3 puntos por usuario con acciones de cerrar sesión y banear/desbanear, respaldado por columnas nuevas en `users` y dos endpoints protegidos en ApiLoging.

**Architecture:** Invalidación de sesiones por usuario vía `users.sessions_invalidated_at` (el middleware ya usa un mecanismo análogo con `password_changed_at`), ban persistente vía `users.banned_at` que bloquea login, middleware y re-registro (UNIQUE natural). Frontend reusa el patrón `Teleport` + `custom-detached-dropdown` que ya existe para la celda de rol.

**Tech Stack:** PHP 8 raw (ApiLoging) sobre MariaDB, Vue 3 + Vite (GrupoReglado). Sin framework de tests — validación manual vía curl y navegador siguiendo el patrón del repo.

**Base dir:** `c:/xampp/htdocs/Reglado/`. Todos los paths son relativos a esta raíz salvo que se indique lo contrario.

**Spec de referencia:** [docs/superpowers/specs/2026-04-22-admin-ban-force-logout-design.md](../specs/2026-04-22-admin-ban-force-logout-design.md)

---

## Task 1: Migración SQL y schema

**Files:**
- Create: `ApiLoging/database/migrate_user_bans.sql`
- Modify: `ApiLoging/database/schema.sql`

- [ ] **Step 1.1: Crear el archivo de migración**

Crear `ApiLoging/database/migrate_user_bans.sql` con este contenido exacto:

```sql
-- Migración: moderación admin (ban + force-logout).
--
-- 1. banned_at: timestamp del ban. NULL = cuenta activa.
-- 2. banned_by: admin que aplicó el ban (FK a users.id, SET NULL si ese admin
--    desaparece; así no perdemos el registro del ban, solo la autoría).
-- 3. sessions_invalidated_at: usado por el middleware para rechazar JWTs
--    cuyo iat sea anterior a este timestamp. Se actualiza en force-logout y
--    al banear. NO se limpia al desbanear (los tokens previos al ban siguen
--    revocados por defecto).

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS banned_at DATETIME NULL AFTER is_email_verified,
  ADD COLUMN IF NOT EXISTS banned_by INT NULL AFTER banned_at,
  ADD COLUMN IF NOT EXISTS sessions_invalidated_at DATETIME NULL AFTER banned_by;

ALTER TABLE users
  ADD CONSTRAINT fk_users_banned_by FOREIGN KEY (banned_by) REFERENCES users(id) ON DELETE SET NULL;

CREATE INDEX IF NOT EXISTS idx_users_banned_at ON users (banned_at);
```

- [ ] **Step 1.2: Actualizar `schema.sql` para instalaciones limpias**

Reemplazar el bloque `CREATE TABLE IF NOT EXISTS users (...)` en `ApiLoging/database/schema.sql` (líneas 1-15) por:

```sql
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  password_changed_at DATETIME NULL,
  name VARCHAR(255) NOT NULL,
  first_name VARCHAR(100),
  last_name VARCHAR(100),
  phone VARCHAR(30),
  role VARCHAR(50) NOT NULL DEFAULT 'user',
  is_email_verified TINYINT(1) NOT NULL DEFAULT 0,
  banned_at DATETIME NULL,
  banned_by INT NULL,
  sessions_invalidated_at DATETIME NULL,
  email_verified_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_banned_by FOREIGN KEY (banned_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_users_banned_at ON users (banned_at);
```

- [ ] **Step 1.3: Ejecutar la migración en la BBDD local**

Obtener el nombre de la BBDD desde `.env`:

```bash
grep '^DB_' ApiLoging/.env
```

Ejecutar la migración contra esa BBDD (ajustar `-u`, `-p`, y el nombre de la BBDD):

```bash
mysql -u <DB_USER> -p<DB_PASS> <DB_NAME> < ApiLoging/database/migrate_user_bans.sql
```

Expected: sin errores. Si la BBDD ya tuviera alguna de las columnas, `IF NOT EXISTS` evita el fallo.

- [ ] **Step 1.4: Verificar estructura**

```bash
mysql -u <DB_USER> -p<DB_PASS> <DB_NAME> -e "DESCRIBE users;" | grep -E "banned_at|banned_by|sessions_invalidated_at"
```

Expected: las tres columnas aparecen con tipos `datetime`, `int`, `datetime` y todas `YES` nullables.

- [ ] **Step 1.5: Commit**

```bash
cd c:/xampp/htdocs/Reglado
git add ApiLoging/database/migrate_user_bans.sql ApiLoging/database/schema.sql
git commit -m "feat(ApiLoging): añade columnas de ban y sessions_invalidated_at"
```

---

## Task 2: Modelo `User` — métodos de ban y security state

**Files:**
- Modify: `ApiLoging/models/User.php`

- [ ] **Step 2.1: Ampliar `listAll()` para incluir campos de ban**

En [ApiLoging/models/User.php:411-421](ApiLoging/models/User.php#L411-L421), sustituir:

```php
    public static function listAll(): array
    {
        $db = Database::connect();
        $stmt = $db->query(
            'SELECT id, username, email, name, first_name, last_name, phone, role, is_email_verified, email_verified_at, created_at
             FROM users
             ORDER BY created_at DESC, id DESC'
        );

        return $stmt->fetchAll() ?: [];
    }
```

Por:

```php
    public static function listAll(): array
    {
        $db = Database::connect();
        $stmt = $db->query(
            'SELECT id, username, email, name, first_name, last_name, phone, role, is_email_verified, email_verified_at, banned_at, banned_by, created_at
             FROM users
             ORDER BY created_at DESC, id DESC'
        );

        return $stmt->fetchAll() ?: [];
    }
```

- [ ] **Step 2.2: Reemplazar `getPasswordChangedAt` por `getSecurityState`**

En [ApiLoging/models/User.php:238-255](ApiLoging/models/User.php#L238-L255), sustituir el método `getPasswordChangedAt` completo por:

```php
    /**
     * Devuelve el estado de seguridad del usuario: timestamps de último cambio
     * de contraseña, ban activo e invalidación masiva de sesiones. Lo usa el
     * middleware para decidir si un JWT dado sigue siendo válido.
     *
     * @return array{password_changed_at: ?int, banned_at: ?int, sessions_invalidated_at: ?int}
     */
    public static function getSecurityState(int $userId): array
    {
        $db = Database::connect();
        $stmt = $db->prepare('SELECT password_changed_at, banned_at, sessions_invalidated_at FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        $toTs = static function ($value): ?int {
            if (empty($value)) return null;
            $ts = strtotime((string) $value);
            return $ts !== false ? $ts : null;
        };

        return [
            'password_changed_at' => $toTs($row['password_changed_at'] ?? null),
            'banned_at' => $toTs($row['banned_at'] ?? null),
            'sessions_invalidated_at' => $toTs($row['sessions_invalidated_at'] ?? null),
        ];
    }
```

- [ ] **Step 2.3: Añadir `banUser`, `unbanUser`, `invalidateSessions`**

Añadir estos tres métodos justo después de `updateRole` (línea ~212):

```php
    public static function banUser(int $userId, int $adminId): void
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'UPDATE users SET banned_at = NOW(), banned_by = ?, sessions_invalidated_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$adminId, $userId]);
    }

    public static function unbanUser(int $userId): void
    {
        // Nota: sessions_invalidated_at NO se limpia a propósito. Los JWTs que
        // el usuario tuviera antes del ban deben seguir siendo inválidos.
        $db = Database::connect();
        $stmt = $db->prepare('UPDATE users SET banned_at = NULL, banned_by = NULL WHERE id = ?');
        $stmt->execute([$userId]);
    }

    public static function invalidateSessions(int $userId): void
    {
        $db = Database::connect();
        $stmt = $db->prepare('UPDATE users SET sessions_invalidated_at = NOW() WHERE id = ?');
        $stmt->execute([$userId]);
    }
```

- [ ] **Step 2.4: Verificación sintáctica**

```bash
php -l ApiLoging/models/User.php
```

Expected: `No syntax errors detected in ApiLoging/models/User.php`

- [ ] **Step 2.5: Commit**

```bash
cd c:/xampp/htdocs/Reglado
git add ApiLoging/models/User.php
git commit -m "feat(ApiLoging): métodos User::banUser/unbanUser/invalidateSessions/getSecurityState"
```

---

## Task 3: Middleware — checks de ban y sesiones invalidadas

**Files:**
- Modify: `ApiLoging/middleware/AuthMiddleware.php`

- [ ] **Step 3.1: Sustituir el bloque de validación post-firma**

En [ApiLoging/middleware/AuthMiddleware.php:38-50](ApiLoging/middleware/AuthMiddleware.php#L38-L50), localizar el bloque que empieza con `// Invalidación masiva tras cambio de contraseña:` y termina con el cierre del `if ($userId > 0 && $iat > 0) { ... }`. Sustituirlo por:

```php
            // Validación centralizada del estado de seguridad del usuario:
            // 1. Cambio de contraseña -> invalida JWTs anteriores.
            // 2. Ban activo -> rechaza cualquier JWT (independiente del iat).
            // 3. Sessions invalidated -> rechaza JWTs emitidos antes del
            //    timestamp (usado por force-logout y también por el ban).
            $userId = isset($decoded['sub']) ? (int) $decoded['sub'] : 0;
            $iat = isset($decoded['iat']) ? (int) $decoded['iat'] : 0;
            if ($userId > 0 && $iat > 0) {
                $state = User::getSecurityState($userId);

                if ($state['password_changed_at'] !== null && $state['password_changed_at'] > $iat) {
                    SecurityLogger::log('token_invalidated_by_password_change', $userId);
                    Response::json(['error' => 'session expired'], 401);
                }

                if ($state['banned_at'] !== null) {
                    SecurityLogger::log('token_banned_account', $userId);
                    Response::json(['error' => 'account banned'], 401);
                }

                if ($state['sessions_invalidated_at'] !== null && $state['sessions_invalidated_at'] > $iat) {
                    SecurityLogger::log('token_session_invalidated', $userId);
                    Response::json(['error' => 'session expired'], 401);
                }
            }
```

- [ ] **Step 3.2: Verificación sintáctica**

```bash
php -l ApiLoging/middleware/AuthMiddleware.php
```

Expected: `No syntax errors detected in ApiLoging/middleware/AuthMiddleware.php`

- [ ] **Step 3.3: Commit**

```bash
cd c:/xampp/htdocs/Reglado
git add ApiLoging/middleware/AuthMiddleware.php
git commit -m "feat(ApiLoging): middleware rechaza JWTs de cuentas baneadas o con sesiones invalidadas"
```

---

## Task 4: Login — bloqueo de cuentas baneadas

**Files:**
- Modify: `ApiLoging/controllers/AuthController.php`

- [ ] **Step 4.1: Añadir guarda de ban tras `password_verify`**

En [ApiLoging/controllers/AuthController.php:131-140](ApiLoging/controllers/AuthController.php#L131-L140), localizar el bloque:

```php
        if (!$user || !password_verify($password, $user['password'])) {
            RateLimiter::recordFailure('login_lockout', $normalizedEmail, 1800);
            SecurityLogger::log('login_failed', $user ? (int) $user['id'] : null, ['email' => $email]);
            Response::json(['error' => 'invalid credentials'], 401);
        }

        if ((int) ($user['is_email_verified'] ?? 0) !== 1) {
            SecurityLogger::log('login_blocked_unverified', (int) $user['id'], ['email' => $email]);
            Response::json(['error' => 'email not verified'], 403);
        }
```

Añadir inmediatamente después del check de `is_email_verified`:

```php
        if (!empty($user['banned_at'])) {
            SecurityLogger::log('login_blocked_banned', (int) $user['id'], ['email' => $email]);
            Response::json(['error' => 'account banned'], 403);
        }
```

- [ ] **Step 4.2: Verificación sintáctica**

```bash
php -l ApiLoging/controllers/AuthController.php
```

Expected: `No syntax errors detected in ApiLoging/controllers/AuthController.php`

- [ ] **Step 4.3: Commit**

```bash
cd c:/xampp/htdocs/Reglado
git add ApiLoging/controllers/AuthController.php
git commit -m "feat(ApiLoging): login rechaza cuentas baneadas con 403 account banned"
```

---

## Task 5: Endpoints admin — `adminForceLogout`, `adminSetBan`, extensión de `adminUsers`

**Files:**
- Modify: `ApiLoging/controllers/AuthController.php`
- Modify: `ApiLoging/index.php`

- [ ] **Step 5.1: Extender `adminUsers()` para devolver `banned_at` y `banned_by`**

En [ApiLoging/controllers/AuthController.php:584-608](ApiLoging/controllers/AuthController.php#L584-L608), dentro del `array_map` del método `adminUsers()`, añadir dos claves en el array devuelto justo después de `email_verified_at`:

```php
                    'email_verified_at' => $user['email_verified_at'] ?? null,
                    'banned_at' => $user['banned_at'] ?? null,
                    'banned_by' => isset($user['banned_by']) ? (int) $user['banned_by'] : null,
                    'created_at' => $user['created_at'] ?? null,
```

- [ ] **Step 5.2: Añadir helper privado para re-auth + validación target**

Añadir este método privado al final de la clase (justo antes del cierre `}` del controller):

```php
    /**
     * Verifica password del admin + valida user_id objetivo + devuelve
     * adminId, target user y el payload parseado (para que los callers puedan
     * leer campos adicionales sin releer `php://input`).
     *
     * @return array{adminId: int, targetUser: array, data: array}
     */
    private static function requireAdminForUserMutation(): array
    {
        $session = self::requireAdmin();
        $adminId = (int) ($session['sub'] ?? 0);
        RateLimiter::enforce('admin_mutate', (string) $adminId, 30, 60);

        $data = self::getJsonInput();
        $userId = (int) ($data['user_id'] ?? 0);
        $currentPassword = (string) ($data['current_password'] ?? '');

        if ($userId <= 0) {
            Response::json(['error' => 'user_id is required'], 422);
        }

        if ($currentPassword === '') {
            Response::json(['error' => 'current_password is required'], 422);
        }

        if ($userId === $adminId) {
            Response::json(['error' => 'cannot target self'], 422);
        }

        $adminUser = User::findById($adminId);
        if (!$adminUser || !password_verify($currentPassword, (string) $adminUser['password'])) {
            SecurityLogger::log('admin_mutation_bad_password', $adminId, ['target' => $userId]);
            Response::json(['error' => 'current password is incorrect'], 401);
        }

        $targetUser = User::findById($userId);
        if (!$targetUser) {
            Response::json(['error' => 'user not found'], 404);
        }

        return ['adminId' => $adminId, 'targetUser' => $targetUser, 'data' => $data];
    }
```

- [ ] **Step 5.3: Añadir `adminForceLogout()`**

Añadir este método público justo después de `adminSyncNotion()` (línea ~710), antes de `logout()`:

```php
    /**
     * Cierra todas las sesiones activas del usuario objetivo invalidando sus
     * JWTs anteriores al timestamp actual (el middleware los rechazará).
     * Requiere re-auth con la contraseña del admin.
     */
    public static function adminForceLogout(): void
    {
        ['adminId' => $adminId, 'targetUser' => $targetUser] = self::requireAdminForUserMutation();
        $userId = (int) $targetUser['id'];

        try {
            User::invalidateSessions($userId);
            SecurityLogger::log('admin_forced_logout', $userId, ['by_admin' => $adminId]);
            Response::json(['message' => 'sessions invalidated']);
        } catch (Throwable $e) {
            Response::json(['error' => 'could not force logout'], 500);
        }
    }
```

- [ ] **Step 5.4: Añadir `adminSetBan()`**

Añadir este método justo después de `adminForceLogout()`:

```php
    /**
     * Aplica o revoca un ban sobre un usuario. Requiere re-auth con la
     * contraseña del admin. Al banear también invalida sesiones vivas.
     */
    public static function adminSetBan(): void
    {
        ['adminId' => $adminId, 'targetUser' => $targetUser, 'data' => $data] = self::requireAdminForUserMutation();
        $userId = (int) $targetUser['id'];

        if (!array_key_exists('banned', $data)) {
            Response::json(['error' => 'banned flag is required'], 422);
        }
        $banned = (bool) $data['banned'];

        try {
            if ($banned) {
                User::banUser($userId, $adminId);
                SecurityLogger::log('admin_banned_user', $userId, ['by_admin' => $adminId]);
                Response::json(['message' => 'user banned']);
            } else {
                User::unbanUser($userId);
                SecurityLogger::log('admin_unbanned_user', $userId, ['by_admin' => $adminId]);
                Response::json(['message' => 'user unbanned']);
            }
        } catch (Throwable $e) {
            Response::json(['error' => 'could not update ban state'], 500);
        }
    }
```

- [ ] **Step 5.5: Registrar las rutas en `index.php`**

En [ApiLoging/index.php:97-103](ApiLoging/index.php#L97-L103), después del bloque de `adminSyncNotion` añadir:

```php
if ($uri === '/auth/admin/force-logout' && $method === 'POST') {
    AuthController::adminForceLogout();
}

if ($uri === '/auth/admin/set-ban' && $method === 'POST') {
    AuthController::adminSetBan();
}
```

- [ ] **Step 5.6: Verificación sintáctica**

```bash
php -l ApiLoging/controllers/AuthController.php
php -l ApiLoging/index.php
```

Expected ambos: `No syntax errors detected`

- [ ] **Step 5.7: Verificar con curl (endpoint vivo)**

Arrancar el servidor PHP embedded si no está ya corriendo:

```bash
cd c:/xampp/htdocs/Reglado/ApiLoging && php -S localhost:8000
```

En otro terminal, hacer login como admin y guardar el JWT. Sustituye `<ADMIN_EMAIL>` y `<ADMIN_PASS>`:

```bash
TOKEN=$(curl -s -X POST http://localhost:8000/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"<ADMIN_EMAIL>","password":"<ADMIN_PASS>"}' | sed -E 's/.*"token":"([^"]+)".*/\1/')
echo "Token: $TOKEN"
```

Expected: una cadena JWT larga. Si devuelve vacío → revisa credenciales.

Listar usuarios para obtener un `user_id` no-admin:

```bash
curl -s -H "Authorization: Bearer $TOKEN" http://localhost:8000/auth/admin/users | python -m json.tool | head -40
```

Expected: objeto con campo `users[]`, cada usuario con `banned_at` y `banned_by`.

Probar `set-ban` sobre self (debe fallar 422):

```bash
curl -i -X POST http://localhost:8000/auth/admin/set-ban \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{"user_id":<ADMIN_ID>,"banned":true,"current_password":"<ADMIN_PASS>"}'
```

Expected: `HTTP/1.1 422` + `{"error":"cannot target self"}`.

Banear un usuario no-admin (sustituye `<USER_ID>` por un id real no-admin):

```bash
curl -i -X POST http://localhost:8000/auth/admin/set-ban \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{"user_id":<USER_ID>,"banned":true,"current_password":"<ADMIN_PASS>"}'
```

Expected: `HTTP/1.1 200` + `{"message":"user banned"}`.

Verificar en BD:

```bash
mysql -u <DB_USER> -p<DB_PASS> <DB_NAME> -e "SELECT id, email, banned_at, banned_by, sessions_invalidated_at FROM users WHERE id = <USER_ID>;"
```

Expected: `banned_at` y `sessions_invalidated_at` con timestamp actual; `banned_by` con el id del admin.

Desbanear:

```bash
curl -i -X POST http://localhost:8000/auth/admin/set-ban \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{"user_id":<USER_ID>,"banned":false,"current_password":"<ADMIN_PASS>"}'
```

Expected: `HTTP/1.1 200` + `{"message":"user unbanned"}`. En BD: `banned_at = NULL`, `banned_by = NULL`, `sessions_invalidated_at` SIGUE con el timestamp anterior (intencional).

Force-logout:

```bash
curl -i -X POST http://localhost:8000/auth/admin/force-logout \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{"user_id":<USER_ID>,"current_password":"<ADMIN_PASS>"}'
```

Expected: `HTTP/1.1 200` + `{"message":"sessions invalidated"}`. En BD: `sessions_invalidated_at` actualizado a `NOW()`.

- [ ] **Step 5.8: Commit**

```bash
cd c:/xampp/htdocs/Reglado
git add ApiLoging/controllers/AuthController.php ApiLoging/index.php
git commit -m "feat(ApiLoging): endpoints admin/force-logout y admin/set-ban"
```

---

## Task 6: Servicio auth del frontend

**Files:**
- Modify: `GrupoReglado/src/services/auth.js`

- [ ] **Step 6.1: Añadir traducciones al `AUTH_MESSAGE_MAP`**

En [GrupoReglado/src/services/auth.js:23-45](GrupoReglado/src/services/auth.js#L23-L45), añadir estas claves dentro del objeto `AUTH_MESSAGE_MAP` (cualquier posición antes del cierre `}`):

```js
  "account banned": "Esta cuenta está suspendida. Contacta con el administrador.",
  "session expired": "Tu sesión ha caducado. Vuelve a iniciar sesión.",
  "cannot target self": "No puedes aplicar esta acción sobre tu propia cuenta.",
  "sessions invalidated": "Sesiones del usuario cerradas.",
  "user banned": "Usuario baneado.",
  "user unbanned": "Usuario desbaneado.",
  "could not force logout": "No se pudo cerrar la sesión del usuario.",
  "could not update ban state": "No se pudo actualizar el estado de baneo.",
  "banned flag is required": "Falta indicar la acción (banear o desbanear).",
  "user_id is required": "Falta el identificador del usuario.",
```

- [ ] **Step 6.2: Añadir métodos `adminForceLogout` y `adminSetBan`**

En [GrupoReglado/src/services/auth.js](GrupoReglado/src/services/auth.js), tras la función `adminSyncNotion` (línea ~265) y antes de `logout`, añadir:

```js
async function adminForceLogout(userId, currentPassword) {
  return request("/auth/admin/force-logout", {
    method: "POST",
    headers: authHeaders(),
    body: JSON.stringify({ user_id: userId, current_password: currentPassword }),
  });
}

async function adminSetBan(userId, banned, currentPassword) {
  return request("/auth/admin/set-ban", {
    method: "POST",
    headers: authHeaders(),
    body: JSON.stringify({ user_id: userId, banned, current_password: currentPassword }),
  });
}
```

- [ ] **Step 6.3: Exponer los nuevos métodos en `auth`**

En [GrupoReglado/src/services/auth.js:280-313](GrupoReglado/src/services/auth.js#L280-L313) el objeto exportado `export const auth = { ... }`, añadir las dos funciones nuevas junto a las otras `admin*`. Localizar:

```js
  adminUsers,
  adminUpdateRole,
  adminSyncNotion,
  adminSyncNotion,
  logout,
```

Sustituir por (nota: **también se elimina la duplicación existente de `adminSyncNotion`** — es un bug preexistente trivialmente subsanable al tocar esta sección):

```js
  adminUsers,
  adminUpdateRole,
  adminSyncNotion,
  adminForceLogout,
  adminSetBan,
  logout,
```

- [ ] **Step 6.4: Verificación sintáctica**

```bash
cd c:/xampp/htdocs/Reglado/GrupoReglado && node --check src/services/auth.js
```

Expected: sin output (node --check no imprime si OK). Si falla: revisar llaves/comas.

- [ ] **Step 6.5: Commit**

```bash
cd c:/xampp/htdocs/Reglado
git add GrupoReglado/src/services/auth.js
git commit -m "feat(GrupoReglado): auth.adminForceLogout y auth.adminSetBan"
```

---

## Task 7: UI del admin — columnas Estado y Acciones con menú 3 puntos

**Files:**
- Modify: `GrupoReglado/src/pages/AdminView.vue`

- [ ] **Step 7.1: Añadir state reactivo y funciones en `<script setup>`**

En [GrupoReglado/src/pages/AdminView.vue:93-95](GrupoReglado/src/pages/AdminView.vue#L93-L95), localizar:

```js
const openDropdownId = ref(null);
const activeRole = ref("");
const dropdownStyle = ref({});
```

Añadir justo debajo:

```js
const openActionsMenuId = ref(null);
const actionsMenuStyle = ref({});
```

Añadir estas funciones dentro del `<script setup>` (cualquier posición tras `selectRole`):

```js
function isSelfUser(user) {
  return auth.state.user && auth.state.user.id === user.id;
}

async function toggleActionsMenu(user, event) {
  if (openActionsMenuId.value === user.id) {
    closeActionsMenu();
    return;
  }
  closeDropdowns();
  openActionsMenuId.value = user.id;

  await nextTick();
  const rect = event.currentTarget.getBoundingClientRect();
  actionsMenuStyle.value = {
    position: 'absolute',
    top: `${rect.bottom + window.scrollY + 5}px`,
    left: `${rect.right + window.scrollX - 160}px`,
    minWidth: '160px',
    zIndex: 9999,
  };
}

function closeActionsMenu() {
  openActionsMenuId.value = null;
}

async function handleForceLogout(user) {
  closeActionsMenu();
  if (!confirm(`¿Cerrar la sesión activa de ${user.username || user.email}?`)) return;

  const currentPassword = prompt("Confirma tu contraseña para cerrar la sesión del usuario:");
  if (!currentPassword) return;

  error.value = "";
  try {
    const res = await auth.adminForceLogout(user.id, currentPassword);
    alert(res.message || "Sesiones del usuario cerradas.");
    await loadUsers();
  } catch (err) {
    error.value = err instanceof Error ? err.message : "No se pudo cerrar la sesión.";
  }
}

async function handleToggleBan(user) {
  closeActionsMenu();
  const banning = !user.banned_at;
  const verb = banning ? "Banear" : "Desbanear";
  if (!confirm(`¿${verb} a ${user.username || user.email}?`)) return;

  const currentPassword = prompt(`Confirma tu contraseña para ${verb.toLowerCase()}:`);
  if (!currentPassword) return;

  error.value = "";
  try {
    const res = await auth.adminSetBan(user.id, banning, currentPassword);
    alert(res.message || (banning ? "Usuario baneado." : "Usuario desbaneado."));
    await loadUsers();
  } catch (err) {
    error.value = err instanceof Error ? err.message : "No se pudo actualizar el baneo.";
  }
}
```

- [ ] **Step 7.2: Extender los listeners existentes para cerrar también el menú de acciones**

En [GrupoReglado/src/pages/AdminView.vue:134-144](GrupoReglado/src/pages/AdminView.vue#L134-L144), sustituir:

```js
function closeDropdowns() {
  openDropdownId.value = null;
}

const clickOutsideListener = (e) => {
  if (!e.target.closest('.dropdown-trigger') && !e.target.closest('.custom-detached-dropdown')) {
    closeDropdowns();
  }
};

const scrollListener = () => closeDropdowns();
```

Por:

```js
function closeDropdowns() {
  openDropdownId.value = null;
}

const clickOutsideListener = (e) => {
  if (!e.target.closest('.dropdown-trigger') && !e.target.closest('.custom-detached-dropdown')) {
    closeDropdowns();
  }
  if (!e.target.closest('.actions-trigger') && !e.target.closest('.actions-menu')) {
    closeActionsMenu();
  }
};

const scrollListener = () => {
  closeDropdowns();
  closeActionsMenu();
};
```

- [ ] **Step 7.3: Añadir columnas Estado y Acciones en la tabla**

En [GrupoReglado/src/pages/AdminView.vue:29-40](GrupoReglado/src/pages/AdminView.vue#L29-L40), sustituir el `<thead>`:

```vue
        <thead>
          <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Nombre</th>
            <th>Email</th>
            <th>Teléfono</th>
            <th>Rol</th>
            <th>Verificado</th>
            <th>Alta</th>
          </tr>
        </thead>
```

Por:

```vue
        <thead>
          <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Nombre</th>
            <th>Email</th>
            <th>Teléfono</th>
            <th>Rol</th>
            <th>Verificado</th>
            <th>Estado</th>
            <th>Alta</th>
            <th class="col-actions">Acciones</th>
          </tr>
        </thead>
```

- [ ] **Step 7.4: Añadir las celdas Estado y Acciones en cada fila**

En [GrupoReglado/src/pages/AdminView.vue:42-66](GrupoReglado/src/pages/AdminView.vue#L42-L66), sustituir el bloque `<tr v-for="user in filteredUsers">...</tr>` y la fila vacía por:

```vue
          <tr v-for="user in filteredUsers" :key="user.id" :class="{ 'is-banned': !!user.banned_at }">
            <td>{{ user.id }}</td>
            <td>{{ user.username || "-" }}</td>
            <td>{{ formatName(user) }}</td>
            <td>{{ user.email || "-" }}</td>
            <td>{{ user.phone || "-" }}</td>
            <td class="role-cell">
              <div class="custom-dropdown" v-if="user.role !== 'admin'">
                <button class="dropdown-trigger" :class="{ 'is-active': openDropdownId === user.id }" @click.stop="toggleDropdown(user, $event)" type="button">
                  <span class="role-text">{{ getRoleName(user.role) }}</span>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                  </svg>
                </button>
              </div>
              <span class="role-badge admin" v-else>Administrador</span>
            </td>
            <td>{{ user.is_email_verified ? "Sí" : "No" }}</td>
            <td>
              <span class="status-pill" :class="user.banned_at ? 'is-banned-pill' : 'is-active-pill'">
                {{ user.banned_at ? 'Baneado' : 'Activo' }}
              </span>
            </td>
            <td>{{ formatDate(user.created_at) }}</td>
            <td class="col-actions">
              <button
                v-if="!isSelfUser(user)"
                class="actions-trigger"
                :class="{ 'is-active': openActionsMenuId === user.id }"
                type="button"
                @click.stop="toggleActionsMenu(user, $event)"
                aria-label="Acciones"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="5" r="1.5"></circle>
                  <circle cx="12" cy="12" r="1.5"></circle>
                  <circle cx="12" cy="19" r="1.5"></circle>
                </svg>
              </button>
            </td>
          </tr>
          <tr v-if="!loading && filteredUsers.length === 0">
            <td colspan="10" class="empty-state">
              {{ searchQuery ? "No se encontraron usuarios para tu búsqueda." : "No hay usuarios para mostrar." }}
            </td>
          </tr>
```

- [ ] **Step 7.5: Añadir el menú detached de acciones en el `<Teleport>`**

En [GrupoReglado/src/pages/AdminView.vue:72-79](GrupoReglado/src/pages/AdminView.vue#L72-L79), sustituir el bloque `<Teleport to="body">...</Teleport>` completo por:

```vue
  <Teleport to="body">
    <div class="custom-detached-dropdown" v-if="openDropdownId" :style="dropdownStyle">
      <ul>
        <li @click="selectRole('user')" :class="{ active: activeRole === 'user' }">User</li>
        <li @click="selectRole('real')" :class="{ active: activeRole === 'real' }">Real</li>
      </ul>
    </div>

    <div class="actions-menu" v-if="openActionsMenuId" :style="actionsMenuStyle">
      <ul>
        <template v-if="!(users.find(u => u.id === openActionsMenuId) || {}).banned_at">
          <li class="action-item" @click="handleForceLogout(users.find(u => u.id === openActionsMenuId))">
            Cerrar sesión
          </li>
          <li class="action-item action-danger" @click="handleToggleBan(users.find(u => u.id === openActionsMenuId))">
            Banear
          </li>
        </template>
        <template v-else>
          <li class="action-item action-success" @click="handleToggleBan(users.find(u => u.id === openActionsMenuId))">
            Desbanear
          </li>
        </template>
      </ul>
    </div>
  </Teleport>
```

- [ ] **Step 7.6: Añadir estilos**

Al final del bloque `<style>` no-scoped (tras la regla `@keyframes fadeInDown`, antes de `</style>`), añadir:

```css
.actions-menu {
  background: var(--surface);
  border-radius: 12px;
  box-shadow: var(--shadow-strong);
  border: 1px solid var(--line);
  padding: 0.4rem;
  animation: fadeInDown 0.15s ease-out;
}
.actions-menu ul {
  list-style: none;
  margin: 0;
  padding: 0;
}
.actions-menu .action-item {
  padding: 0.55rem 0.9rem;
  font-size: 0.86rem;
  font-weight: 500;
  border-radius: 8px;
  cursor: pointer;
  color: var(--text);
  transition: all 0.15s ease;
}
.actions-menu .action-item:hover {
  background: var(--surface-soft);
}
.actions-menu .action-item.action-danger {
  color: #c0392b;
}
.actions-menu .action-item.action-danger:hover {
  background: rgba(192, 57, 43, 0.08);
}
.actions-menu .action-item.action-success {
  color: #1f7a3a;
}
.actions-menu .action-item.action-success:hover {
  background: rgba(31, 122, 58, 0.08);
}
```

Al final del bloque `<style scoped>` (antes del `}` final de la media query o tras ella), añadir:

```css
.col-actions {
  width: 64px;
  text-align: center;
}

.actions-trigger {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 50%;
  border: 1px solid transparent;
  background: transparent;
  color: var(--text);
  cursor: pointer;
  transition: all 0.2s ease;
}
.actions-trigger svg {
  width: 18px;
  height: 18px;
  stroke: var(--text);
}
.actions-trigger:hover,
.actions-trigger.is-active {
  background: var(--surface-soft);
  border-color: var(--line-strong);
}

.status-pill {
  display: inline-block;
  padding: 0.3rem 0.75rem;
  border-radius: 50px;
  font-weight: 600;
  font-size: 0.78rem;
  user-select: none;
}
.status-pill.is-active-pill {
  background-color: var(--surface-soft);
  color: var(--muted);
}
.status-pill.is-banned-pill {
  background-color: rgba(192, 57, 43, 0.12);
  color: #c0392b;
}

.users-table tr.is-banned td {
  opacity: 0.55;
}
.users-table tr.is-banned:hover td {
  opacity: 0.75;
}
.users-table tr.is-banned td.col-actions,
.users-table tr.is-banned:hover td.col-actions {
  opacity: 1;
}
```

- [ ] **Step 7.7: Ajustar `min-width` de la tabla para acomodar columnas nuevas**

En el bloque `.users-table` del `<style scoped>` (línea ~358), sustituir:

```css
.users-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 920px;
}
```

Por:

```css
.users-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 1080px;
}
```

- [ ] **Step 7.8: Verificación en navegador**

```bash
cd c:/xampp/htdocs/Reglado/GrupoReglado && npm run dev
```

Abrir `http://localhost:5173/admin` (o la ruta donde esté el AdminView — ver `src/router/`), loguearse como admin y comprobar visualmente:

Expected:
1. La tabla carga y muestra columna **Estado** con pill "Activo" y columna **Acciones** con el botón ⋯.
2. En la fila del propio admin (self), **no** aparece el botón de acciones.
3. Clic en ⋯ abre menú con "Cerrar sesión" y "Banear".
4. "Banear" pide confirmación y contraseña, tras aceptar recarga → la fila se vuelve tenue + pill rojo "Baneado" + el menú ahora solo muestra "Desbanear".
5. "Desbanear" revierte a estado anterior (excepto el ban histórico).
6. "Cerrar sesión" dispara alert "Sesiones del usuario cerradas." tras confirmación.

- [ ] **Step 7.9: Commit**

```bash
cd c:/xampp/htdocs/Reglado
git add GrupoReglado/src/pages/AdminView.vue
git commit -m "feat(GrupoReglado): menú 3 puntos en admin con banear y cerrar sesión"
```

---

## Task 8: Validación end-to-end manual

Esta tarea no modifica código — ejecuta los 8 casos de test del spec para confirmar que todo el flujo funciona.

Prerrequisitos: backend corriendo (`php -S localhost:8000` desde `ApiLoging/`) y frontend (`npm run dev` desde `GrupoReglado/`). Dos usuarios en BD: el admin y al menos un usuario normal verificado.

- [ ] **Step 8.1: Test 1 — Banear desde UI**

En la UI admin → menú ⋯ de un usuario no-admin → "Banear" → confirmar + password.

Verificar en BD:

```bash
mysql -u <DB_USER> -p<DB_PASS> <DB_NAME> -e "SELECT id, email, banned_at, banned_by, sessions_invalidated_at FROM users WHERE id = <USER_ID>;"
```

Expected: las tres columnas con timestamp/id válido.

- [ ] **Step 8.2: Test 2 — Sesión del usuario baneado es rechazada**

En otra pestaña (modo incógnito), loguear al usuario objetivo ANTES del ban (obviamente: primero restaurarlo del paso anterior, o bien loguearlo antes del Test 1 y mantener la pestaña abierta). Tras el ban, cualquier request autenticada debería devolver 401 `account banned`.

Curl-friendly reproduction: obtener token del usuario antes del ban:

```bash
USER_TOKEN=$(curl -s -X POST http://localhost:8000/auth/login -H 'Content-Type: application/json' -d '{"email":"<USER_EMAIL>","password":"<USER_PASS>"}' | sed -E 's/.*"token":"([^"]+)".*/\1/')
```

(Ahora banea desde el admin) y después:

```bash
curl -i -H "Authorization: Bearer $USER_TOKEN" http://localhost:8000/auth/me
```

Expected: `HTTP/1.1 401` + `{"error":"account banned"}`.

- [ ] **Step 8.3: Test 3 — Login bloqueado si baneado**

```bash
curl -i -X POST http://localhost:8000/auth/login -H 'Content-Type: application/json' -d '{"email":"<USER_EMAIL>","password":"<USER_PASS>"}'
```

Expected: `HTTP/1.1 403` + `{"error":"account banned"}`.

- [ ] **Step 8.4: Test 4 — Re-registro con mismo email está bloqueado**

```bash
curl -i -X POST http://localhost:8000/auth/register \
  -H 'Content-Type: application/json' \
  -d '{"username":"<OTRO_USERNAME>","first_name":"X","last_name":"Y","email":"<USER_EMAIL>","phone":"600111222","password":"Aaaaaa11","password_confirmation":"Aaaaaa11"}'
```

Expected: `HTTP/1.1 202` + respuesta genérica `"if the data is valid, a verification email has been sent"`. **No** se crea ningún registro nuevo (verificar con un `SELECT COUNT(*) FROM users WHERE email = '<USER_EMAIL>'`, sigue siendo 1).

- [ ] **Step 8.5: Test 5 — Desbanear y login vuelve a funcionar**

Desde UI admin → menú ⋯ del usuario baneado → "Desbanear" → confirmar + password.

```bash
curl -i -X POST http://localhost:8000/auth/login -H 'Content-Type: application/json' -d '{"email":"<USER_EMAIL>","password":"<USER_PASS>"}'
```

Expected: `HTTP/1.1 200` + token. Y en BD: `banned_at = NULL`, `banned_by = NULL`, `sessions_invalidated_at` **sigue** con el timestamp previo (el antiguo USER_TOKEN del Test 2 sigue inválido).

- [ ] **Step 8.6: Test 6 — Token pre-ban sigue inválido tras desbaneo**

```bash
curl -i -H "Authorization: Bearer $USER_TOKEN" http://localhost:8000/auth/me
```

Expected: `HTTP/1.1 401` + `{"error":"session expired"}` (el `iat` de `$USER_TOKEN` es anterior a `sessions_invalidated_at`).

- [ ] **Step 8.7: Test 7 — Force-logout**

Loguear al usuario de nuevo y capturar el token fresh:

```bash
FRESH_TOKEN=$(curl -s -X POST http://localhost:8000/auth/login -H 'Content-Type: application/json' -d '{"email":"<USER_EMAIL>","password":"<USER_PASS>"}' | sed -E 's/.*"token":"([^"]+)".*/\1/')
curl -s -H "Authorization: Bearer $FRESH_TOKEN" http://localhost:8000/auth/me
```

Expected: datos del usuario.

Desde UI admin → menú ⋯ → "Cerrar sesión" → confirmar + password.

```bash
curl -i -H "Authorization: Bearer $FRESH_TOKEN" http://localhost:8000/auth/me
```

Expected: `HTTP/1.1 401` + `{"error":"session expired"}`.

- [ ] **Step 8.8: Test 8 — Auto-ban bloqueado en UI y API**

En la UI, verificar que la fila del propio admin **no** muestra el botón ⋯.

Intento por API directamente:

```bash
ADMIN_ID=$(curl -s -H "Authorization: Bearer $TOKEN" http://localhost:8000/auth/me | sed -E 's/.*"id":([0-9]+).*/\1/')
curl -i -X POST http://localhost:8000/auth/admin/set-ban \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d "{\"user_id\":$ADMIN_ID,\"banned\":true,\"current_password\":\"<ADMIN_PASS>\"}"
```

Expected: `HTTP/1.1 422` + `{"error":"cannot target self"}`.

- [ ] **Step 8.9: No hay commit**

Todo este task es validación. Si algún test falla, regresar al task correspondiente y arreglar. No hay cambios de código aquí.

---

## Verificación final del plan

Tras completar los 8 tasks:

- [ ] `git log --oneline` muestra 7 commits (Task 1, 2, 3, 4, 5, 6, 7; Task 8 no commitea).
- [ ] Los 8 tests del Task 8 pasan.
- [ ] `php -l` sin errores en los 3 archivos PHP modificados.
- [ ] La UI admin se ve correctamente (sin errores en consola del navegador).

Si todo OK → la feature está lista. Mover al flujo de PR/merge a `main` según la política del proyecto.
