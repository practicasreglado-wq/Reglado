# Single-Session Enforcement Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Garantizar una sola sesión activa por cuenta (política kick-old) mediante un claim `sid` en el JWT y una columna `users.current_session_id`, con el correspondiente interceptor 401 en los frontends afectados.

**Architecture:** Cada login (y cada flujo que emite sesión nueva) genera un `sid` aleatorio de 32 bytes hex, lo persiste en `users.current_session_id` y lo incluye en el JWT. El middleware rechaza cualquier JWT cuyo `sid` no coincida con el `current_session_id` actual. Los endpoints de actualización de perfil reutilizan el sid existente para no reiniciar la sesión. Los frontends añaden un interceptor de 401 que limpia estado local y redirige al login con el motivo.

**Tech Stack:** PHP 8 raw (ApiLoging) sobre MariaDB + `firebase/php-jwt`; Vue 3 + Vite en los frontends.

**Base dir:** `c:/xampp/htdocs/Reglado/`. Todos los paths son relativos a esta raíz.

**Spec de referencia:** [docs/superpowers/specs/2026-04-23-single-session-enforcement-design.md](../specs/2026-04-23-single-session-enforcement-design.md)

**Fuera de alcance:** `Inmobiliaria_Reglados` y `RegladoBienesRaices`.

---

## Task 1: Migración SQL y schema

**Files:**
- Create: `ApiLoging/database/migrate_single_session.sql`
- Modify: `ApiLoging/database/schema.sql`

- [ ] **Step 1.1: Crear migración**

Crear `ApiLoging/database/migrate_single_session.sql`:

```sql
-- Migración: single-session enforcement.
--
-- current_session_id: id aleatorio (32 bytes hex) que se persiste en cada
-- login y se incluye en el JWT como claim `sid`. El middleware rechaza
-- cualquier token cuyo sid no coincida con el valor actual, lo que implementa
-- la política kick-old (la sesión más reciente invalida a la anterior).

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS current_session_id CHAR(64) NULL AFTER sessions_invalidated_at;

CREATE INDEX IF NOT EXISTS idx_users_current_session_id ON users (current_session_id);
```

- [ ] **Step 1.2: Actualizar schema.sql**

En `ApiLoging/database/schema.sql`, sustituir el bloque `CREATE TABLE IF NOT EXISTS users (...)` por:

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
  current_session_id CHAR(64) NULL,
  email_verified_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_banned_by FOREIGN KEY (banned_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_users_banned_at ON users (banned_at);
CREATE INDEX idx_users_current_session_id ON users (current_session_id);
```

- [ ] **Step 1.3: Ejecutar migración**

```bash
/c/xampp/mysql/bin/mysql -h 127.0.0.1 -u root regladousers < c:/xampp/htdocs/Reglado/ApiLoging/database/migrate_single_session.sql
```

Expected: sin errores.

- [ ] **Step 1.4: Verificar columna**

```bash
/c/xampp/mysql/bin/mysql -h 127.0.0.1 -u root regladousers -e "DESCRIBE users;" | grep current_session_id
```

Expected: `current_session_id	char(64)	YES	MUL	NULL`

- [ ] **Step 1.5: Commit**

```bash
cd c:/xampp/htdocs/Reglado
git add ApiLoging/database/migrate_single_session.sql ApiLoging/database/schema.sql
git commit -m "feat(ApiLoging): añade columna users.current_session_id"
```

---

## Task 2: Modelo User — rotateSession, clearSession, getSecurityState extendido, banUser limpia sid

**Files:**
- Modify: `ApiLoging/models/User.php`

- [ ] **Step 2.1: Añadir `rotateSession` y `clearSession`**

Añadir estos dos métodos después de `invalidateSessions` (que fue añadido ayer):

```php
    /**
     * Genera un nuevo session id para el usuario y lo persiste. Devuelve el
     * sid para incluirlo en el JWT recién emitido. La sesión anterior queda
     * invalidada en el middleware por no coincidir con el sid guardado.
     */
    public static function rotateSession(int $userId): string
    {
        $sid = bin2hex(random_bytes(32));
        $db = Database::connect();
        $stmt = $db->prepare('UPDATE users SET current_session_id = ? WHERE id = ?');
        $stmt->execute([$sid, $userId]);
        return $sid;
    }

    /**
     * Elimina la sesión activa del usuario. Los JWTs existentes dejarán de
     * validar en el middleware al no coincidir su sid con NULL.
     */
    public static function clearSession(int $userId): void
    {
        $db = Database::connect();
        $stmt = $db->prepare('UPDATE users SET current_session_id = NULL WHERE id = ?');
        $stmt->execute([$userId]);
    }
```

- [ ] **Step 2.2: Extender `getSecurityState` para incluir `current_session_id`**

Sustituir el método `getSecurityState` completo por:

```php
    /**
     * Devuelve el estado de seguridad del usuario: timestamps de último cambio
     * de contraseña, ban activo, invalidación masiva de sesiones y session id
     * actual. Lo usa el middleware para decidir si un JWT sigue siendo válido.
     *
     * @return array{password_changed_at: ?int, banned_at: ?int, sessions_invalidated_at: ?int, current_session_id: ?string}
     */
    public static function getSecurityState(int $userId): array
    {
        $db = Database::connect();
        $stmt = $db->prepare('SELECT password_changed_at, banned_at, sessions_invalidated_at, current_session_id FROM users WHERE id = ? LIMIT 1');
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
            'current_session_id' => $row['current_session_id'] ?? null,
        ];
    }
```

- [ ] **Step 2.3: Actualizar `banUser` para limpiar también `current_session_id`**

Sustituir el método `banUser` por:

```php
    public static function banUser(int $userId, int $adminId): void
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'UPDATE users SET banned_at = NOW(), banned_by = ?, sessions_invalidated_at = NOW(), current_session_id = NULL WHERE id = ?'
        );
        $stmt->execute([$adminId, $userId]);
    }
```

- [ ] **Step 2.4: Verificación sintáctica**

```bash
php -l c:/xampp/htdocs/Reglado/ApiLoging/models/User.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 2.5: Commit**

```bash
cd c:/xampp/htdocs/Reglado
git add ApiLoging/models/User.php
git commit -m "feat(ApiLoging): User::rotateSession + clearSession + sid en security state"
```

---

## Task 3: JwtService — `sid` obligatorio en generate

**Files:**
- Modify: `ApiLoging/services/JwtService.php`

- [ ] **Step 3.1: Actualizar `generate` para exigir `sid`**

Sustituir el método `generate` completo por:

```php
    /**
     * Genera un token JWT para un usuario.
     * Incluye datos de perfil básicos en el payload para evitar consultas
     * constantes a la BD, y un session id (`sid`) que el middleware compara
     * contra users.current_session_id para garantizar una única sesión activa.
     *
     * @param array $user Datos del usuario (id, email, role, etc.)
     * @param string $sid Session id (64 hex chars). Obligatorio.
     * @return string Token JWT codificado
     */
    public static function generate(array $user, string $sid): string
    {
        if ($sid === '') {
            throw new RuntimeException('sid required');
        }

        $now = time();
        $ttl = (int) (getenv('JWT_TTL_SECONDS') ?: 86400);
        $secret = getenv('JWT_SECRET') ?: 'change-this-secret';
        $issuer = getenv('JWT_ISSUER') ?: 'reglado-auth';

        $payload = [
            'iss' => $issuer,
            'iat' => $now,
            'exp' => $now + $ttl,
            'sub' => (int) $user['id'],
            'sid' => $sid,
            'email' => $user['email'],
            'username' => $user['username'] ?? null,
            'first_name' => $user['first_name'] ?? null,
            'last_name' => $user['last_name'] ?? null,
            'phone' => $user['phone'] ?? null,
            'name' => $user['name'],
            'role' => $user['role'],
        ];

        return JWT::encode($payload, $secret, 'HS256');
    }
```

- [ ] **Step 3.2: Verificación sintáctica**

```bash
php -l c:/xampp/htdocs/Reglado/ApiLoging/services/JwtService.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 3.3: Commit**

```bash
cd c:/xampp/htdocs/Reglado
git add ApiLoging/services/JwtService.php
git commit -m "feat(ApiLoging): JwtService::generate exige sid obligatorio"
```

---

## Task 4: AuthController — endpoints que emiten sesión nueva (login, verify-email, reset-password, change-password, confirm-email-change)

**Files:**
- Modify: `ApiLoging/controllers/AuthController.php`

Tras este task, todos los sitios que llaman `JwtService::generate` pasan un sid. Los que emiten sesión NUEVA (este task) llaman a `User::rotateSession`; los que conservan sesión (Task 5) leen el sid actual.

- [ ] **Step 4.1: Rotar sid en `login`**

Localizar en `AuthController::login` la línea:
```php
        $token = JwtService::generate($user);
        SecurityLogger::log('login_success', (int) $user['id']);
```

Sustituir por:
```php
        $sid = User::rotateSession((int) $user['id']);
        $token = JwtService::generate($user, $sid);
        SecurityLogger::log('login_success', (int) $user['id']);
```

- [ ] **Step 4.2: Rotar sid en `verifyEmail`**

Localizar en `AuthController::verifyEmail`:
```php
            $jwt = JwtService::generate($freshUser);
            $redirectBase = getenv('EMAIL_VERIFY_REDIRECT_URL') ?: '';
```

Sustituir por:
```php
            $sid = User::rotateSession((int) $freshUser['id']);
            $jwt = JwtService::generate($freshUser, $sid);
            $redirectBase = getenv('EMAIL_VERIFY_REDIRECT_URL') ?: '';
```

- [ ] **Step 4.3: Rotar sid en `confirmEmailChange`**

Localizar en `AuthController::confirmEmailChange`:
```php
            $jwt = JwtService::generate($freshUser);
            $redirectBase = getenv('EMAIL_CHANGE_REDIRECT_URL') ?: '';
```

Sustituir por:
```php
            $sid = User::rotateSession((int) $freshUser['id']);
            $jwt = JwtService::generate($freshUser, $sid);
            $redirectBase = getenv('EMAIL_CHANGE_REDIRECT_URL') ?: '';
```

- [ ] **Step 4.4: Rotar sid en `resetPassword` y `changePassword`**

Ambos endpoints llaman a `respondWithFreshSession` al final. Como `changePassword` y `resetPassword` SÍ deben rotar sid (el otro dispositivo cae vía `password_changed_at`, pero para ser explícitos rotamos también sid), necesitamos un helper distinto.

Añadir al controller un nuevo helper privado justo antes de `respondWithFreshSession`:

```php
    /**
     * Idéntico a respondWithFreshSession pero rota el session id. Úsalo desde
     * flujos que crean una sesión nueva (cambio de contraseña, verificación
     * de email). respondWithFreshSession conserva el sid existente, para
     * updates de perfil que no reinician la sesión.
     */
    private static function respondWithRotatedSession(int $userId, string $message): void
    {
        $user = User::findById($userId);
        if (!$user) {
            Response::json(['error' => 'user not found'], 404);
        }

        $sid = User::rotateSession($userId);
        $token = JwtService::generate($user, $sid);

        Response::json([
            'message' => $message,
            'token' => $token,
            'user' => self::mapUser($user),
        ]);
    }
```

Cambiar en `resetPassword` (final del try tras `markPasswordResetAsUsed`):
```php
            User::markPasswordResetAsUsed((int) $user['reset_id']);
            SecurityLogger::log('password_reset_completed', (int) $user['id']);
            Response::json(['message' => 'password updated']);
```

Por:
```php
            User::markPasswordResetAsUsed((int) $user['reset_id']);
            SecurityLogger::log('password_reset_completed', (int) $user['id']);
            self::respondWithRotatedSession((int) $user['id'], 'password updated');
```

Cambiar en `changePassword` (final del try):
```php
            User::updatePasswordHash($userId, password_hash($newPassword, PASSWORD_BCRYPT));
            SecurityLogger::log('password_changed', $userId);
            self::respondWithFreshSession($userId, 'password updated');
```

Por:
```php
            User::updatePasswordHash($userId, password_hash($newPassword, PASSWORD_BCRYPT));
            SecurityLogger::log('password_changed', $userId);
            self::respondWithRotatedSession($userId, 'password updated');
```

Nota: `resetPassword` originalmente devolvía sólo `{"message": "password updated"}` sin token. Ahora también devuelve token (mismo patrón que el resto). El frontend del reset (`ResetPasswordView`) puede ignorar el token si no lo necesita, pero es mejor tenerlo — permite que el usuario entre directamente.

- [ ] **Step 4.5: Verificación sintáctica**

```bash
php -l c:/xampp/htdocs/Reglado/ApiLoging/controllers/AuthController.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 4.6: Commit**

```bash
cd c:/xampp/htdocs/Reglado
git add ApiLoging/controllers/AuthController.php
git commit -m "feat(ApiLoging): endpoints de login/verificación/password rotan session id"
```

---

## Task 5: AuthController — `respondWithFreshSession` conserva sid existente

Los endpoints `updateUsername`, `updateName`, `updatePhone` llaman a `respondWithFreshSession` y deben conservar el sid actual (la sesión no se reinicia al editar el perfil).

**Files:**
- Modify: `ApiLoging/controllers/AuthController.php`

- [ ] **Step 5.1: Sustituir `respondWithFreshSession`**

Sustituir el método completo por:

```php
    private static function respondWithFreshSession(int $userId, string $message): void
    {
        $user = User::findById($userId);
        if (!$user) {
            Response::json(['error' => 'user not found'], 404);
        }

        $currentSid = $user['current_session_id'] ?? null;
        if (empty($currentSid)) {
            // Caso borde: el admin forzó logout (o baneo) mientras el usuario
            // estaba editando su perfil. No reemitimos sesión sin mandato;
            // que vuelva a loguear.
            Response::json(['error' => 'session expired'], 401);
        }

        $token = JwtService::generate($user, (string) $currentSid);

        Response::json([
            'message' => $message,
            'token' => $token,
            'user' => self::mapUser($user),
        ]);
    }
```

- [ ] **Step 5.2: Verificación sintáctica**

```bash
php -l c:/xampp/htdocs/Reglado/ApiLoging/controllers/AuthController.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 5.3: Commit**

```bash
cd c:/xampp/htdocs/Reglado
git add ApiLoging/controllers/AuthController.php
git commit -m "feat(ApiLoging): respondWithFreshSession conserva sid en updates de perfil"
```

---

## Task 6: AuthController — logout propio y admin force-logout limpian sid

**Files:**
- Modify: `ApiLoging/controllers/AuthController.php`

- [ ] **Step 6.1: `POST /auth/logout` limpia `current_session_id`**

En `AuthController::logout`, localizar el bloque final del try:
```php
            $tokenHash = hash('sha256', $token);
            $db = Database::connect();
            $stmt = $db->prepare('INSERT INTO revoked_tokens(token_hash) VALUES(?)');
            $stmt->execute([$tokenHash]);
            SecurityLogger::log('logout', isset($decoded['sub']) ? (int) $decoded['sub'] : null);
            Response::json(['message' => 'logged out']);
```

Sustituir por:
```php
            $tokenHash = hash('sha256', $token);
            $db = Database::connect();
            $stmt = $db->prepare('INSERT INTO revoked_tokens(token_hash) VALUES(?)');
            $stmt->execute([$tokenHash]);
            $userId = isset($decoded['sub']) ? (int) $decoded['sub'] : 0;
            if ($userId > 0) {
                User::clearSession($userId);
            }
            SecurityLogger::log('logout', $userId > 0 ? $userId : null);
            Response::json(['message' => 'logged out']);
```

- [ ] **Step 6.2: `adminForceLogout` también limpia `current_session_id`**

Localizar `adminForceLogout`:
```php
        try {
            User::invalidateSessions($userId);
            SecurityLogger::log('admin_forced_logout', $userId, ['by_admin' => $adminId]);
            Response::json(['message' => 'sessions invalidated']);
        } catch (Throwable $e) {
```

Sustituir por:
```php
        try {
            User::invalidateSessions($userId);
            User::clearSession($userId);
            SecurityLogger::log('admin_forced_logout', $userId, ['by_admin' => $adminId]);
            Response::json(['message' => 'sessions invalidated']);
        } catch (Throwable $e) {
```

Nota: `adminSetBan` con `banned=true` llama a `User::banUser`, que ya fue actualizado en Task 2 para limpiar `current_session_id` en el mismo UPDATE. No requiere cambios aquí.

- [ ] **Step 6.3: Verificación sintáctica**

```bash
php -l c:/xampp/htdocs/Reglado/ApiLoging/controllers/AuthController.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 6.4: Commit**

```bash
cd c:/xampp/htdocs/Reglado
git add ApiLoging/controllers/AuthController.php
git commit -m "feat(ApiLoging): logout y admin force-logout limpian current_session_id"
```

---

## Task 7: Middleware — check de `sid`

**Files:**
- Modify: `ApiLoging/middleware/AuthMiddleware.php`

- [ ] **Step 7.1: Añadir check de sid al final del bloque de validación**

En `AuthMiddleware::handle`, localizar el bloque que termina con:
```php
                if ($state['sessions_invalidated_at'] !== null && $state['sessions_invalidated_at'] > $iat) {
                    SecurityLogger::log('token_session_invalidated', $userId);
                    Response::json(['error' => 'session expired'], 401);
                }
            }
```

Sustituir por:
```php
                if ($state['sessions_invalidated_at'] !== null && $state['sessions_invalidated_at'] > $iat) {
                    SecurityLogger::log('token_session_invalidated', $userId);
                    Response::json(['error' => 'session expired'], 401);
                }

                // Single-session enforcement: el sid del token debe coincidir
                // con users.current_session_id. Si no coincide (o el usuario
                // no tiene sesión activa) rechazamos sin considerar el iat.
                $tokenSid = isset($decoded['sid']) ? (string) $decoded['sid'] : '';
                if ($state['current_session_id'] === null || $tokenSid === '' || !hash_equals((string) $state['current_session_id'], $tokenSid)) {
                    SecurityLogger::log('token_session_mismatch', $userId);
                    Response::json(['error' => 'session expired'], 401);
                }
            }
```

- [ ] **Step 7.2: Verificación sintáctica**

```bash
php -l c:/xampp/htdocs/Reglado/ApiLoging/middleware/AuthMiddleware.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 7.3: Commit**

```bash
cd c:/xampp/htdocs/Reglado
git add ApiLoging/middleware/AuthMiddleware.php
git commit -m "feat(ApiLoging): middleware valida sid contra users.current_session_id"
```

---

## Task 8: Validación backend end-to-end (curl)

Este task usa la infraestructura de pruebas de ayer: el servidor PHP embedded, credenciales de admin reales del entorno dev, un usuario de prueba temporal creado por SQL. No modifica código.

**Prerrequisitos**: conocer credenciales admin dev. En la ejecución pedir al usuario si no están disponibles.

- [ ] **Step 8.1: Arrancar servidor PHP y limpiar rate limits**

```bash
cd c:/xampp/htdocs/Reglado/ApiLoging && php -S localhost:8765 -t . > /tmp/phpserver.log 2>&1 &
echo "PID: $!"
sleep 1
/c/xampp/mysql/bin/mysql -h 127.0.0.1 -u root regladousers -e "DELETE FROM rate_limits WHERE scope_name LIKE 'login%';"
```

- [ ] **Step 8.2: Crear usuario de prueba**

```bash
php -r '
$dsn = "mysql:host=127.0.0.1;dbname=regladousers;charset=utf8mb4";
$db = new PDO($dsn, "root", "", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$hash = password_hash("Test1234", PASSWORD_BCRYPT);
$db->prepare("INSERT INTO users (username, email, password, name, first_name, last_name, phone, role, is_email_verified, email_verified_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())")->execute(["test_sid_e2e", "test_sid_e2e@reglado.test", $hash, "Test Sid", "Test", "Sid", "600111222", "user"]);
echo "user_id: " . $db->lastInsertId() . "\n";
'
```

Guardar el id devuelto como `USER_ID` para los siguientes steps.

- [ ] **Step 8.3: Test — login emite sid**

```bash
BASE="http://localhost:8765"
LOGIN=$(curl -s -X POST $BASE/auth/login -H 'Content-Type: application/json' -d '{"email":"test_sid_e2e@reglado.test","password":"Test1234"}')
echo "Login response: $LOGIN"
TOKEN=$(echo "$LOGIN" | python -c "import sys,json; print(json.load(sys.stdin).get('token',''))")
# Decodificar claim sid del JWT (base64url del segundo segmento, arreglando padding)
SID=$(echo "$TOKEN" | cut -d. -f2 | python -c "import sys,json,base64; s=sys.stdin.read().strip(); s+='='*(-len(s)%4); print(json.loads(base64.urlsafe_b64decode(s)).get('sid',''))")
echo "JWT sid: $SID (len=${#SID})"
DB_SID=$(/c/xampp/mysql/bin/mysql -h 127.0.0.1 -u root regladousers -N -e "SELECT current_session_id FROM users WHERE email = 'test_sid_e2e@reglado.test';" | tail -1)
echo "DB sid:  $DB_SID"
[ "$SID" = "$DB_SID" ] && [ ${#SID} = 64 ] && echo "TEST 8.3 PASS ✓" || echo "TEST 8.3 FAIL ✗"
```

Expected: el sid tiene 64 chars hex y coincide con el almacenado en BD.

- [ ] **Step 8.4: Test — kick-old entre dos logins**

```bash
# Login A
TOKEN_A=$(curl -s -X POST $BASE/auth/login -H 'Content-Type: application/json' -d '{"email":"test_sid_e2e@reglado.test","password":"Test1234"}' | python -c "import sys,json; print(json.load(sys.stdin).get('token',''))")
# Verificar que A funciona
CODE=$(curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $TOKEN_A" $BASE/auth/me)
echo "A /auth/me pre-login-B: $CODE (debe 200)"

sleep 1
# Login B (misma cuenta, diferente sesión)
TOKEN_B=$(curl -s -X POST $BASE/auth/login -H 'Content-Type: application/json' -d '{"email":"test_sid_e2e@reglado.test","password":"Test1234"}' | python -c "import sys,json; print(json.load(sys.stdin).get('token',''))")
# A debe haber sido expulsado
CODE_A=$(curl -s -o /tmp/resp -w "%{http_code}" -H "Authorization: Bearer $TOKEN_A" $BASE/auth/me)
BODY_A=$(cat /tmp/resp)
echo "A /auth/me post-login-B: $CODE_A | $BODY_A"

# B debe funcionar
CODE_B=$(curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $TOKEN_B" $BASE/auth/me)
echo "B /auth/me: $CODE_B (debe 200)"

[ "$CODE_A" = "401" ] && [ "$BODY_A" = '{"error":"session expired"}' ] && [ "$CODE_B" = "200" ] && echo "TEST 8.4 PASS ✓" || echo "TEST 8.4 FAIL ✗"
```

- [ ] **Step 8.5: Test — profile update NO rota sid**

```bash
# Con TOKEN_B aún válido, actualizar phone
RESP=$(curl -s -X POST $BASE/auth/update-phone -H "Authorization: Bearer $TOKEN_B" -H 'Content-Type: application/json' -d '{"phone":"600999888"}')
echo "Update-phone response: $RESP"
TOKEN_B2=$(echo "$RESP" | python -c "import sys,json; print(json.load(sys.stdin).get('token',''))")
SID_B2=$(echo "$TOKEN_B2" | cut -d. -f2 | python -c "import sys,base64,json; s=sys.stdin.read().strip(); s+='='*(-len(s)%4); print(json.loads(base64.urlsafe_b64decode(s)).get('sid',''))")
SID_B=$(echo "$TOKEN_B" | cut -d. -f2 | python -c "import sys,base64,json; s=sys.stdin.read().strip(); s+='='*(-len(s)%4); print(json.loads(base64.urlsafe_b64decode(s)).get('sid',''))")
echo "sid antes: $SID_B"
echo "sid después: $SID_B2"

# Ambos tokens deben seguir valiendo (mismo sid)
CODE1=$(curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $TOKEN_B" $BASE/auth/me)
CODE2=$(curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $TOKEN_B2" $BASE/auth/me)
echo "B original: $CODE1 | B refrescado: $CODE2"

[ "$SID_B" = "$SID_B2" ] && [ "$CODE1" = "200" ] && [ "$CODE2" = "200" ] && echo "TEST 8.5 PASS ✓" || echo "TEST 8.5 FAIL ✗"
```

- [ ] **Step 8.6: Test — logout limpia current_session_id**

```bash
curl -s -X POST $BASE/auth/logout -H "Authorization: Bearer $TOKEN_B2" > /dev/null
DB_SID=$(/c/xampp/mysql/bin/mysql -h 127.0.0.1 -u root regladousers -N -e "SELECT IFNULL(current_session_id, 'NULL') FROM users WHERE email = 'test_sid_e2e@reglado.test';" | tail -1)
echo "DB sid tras logout: $DB_SID"
CODE=$(curl -s -o /tmp/resp -w "%{http_code}" -H "Authorization: Bearer $TOKEN_B2" $BASE/auth/me)
BODY=$(cat /tmp/resp)
echo "Request con token: $CODE | $BODY"
[ "$DB_SID" = "NULL" ] && [ "$CODE" = "401" ] && [ "$BODY" = '{"error":"session expired"}' ] && echo "TEST 8.6 PASS ✓" || echo "TEST 8.6 FAIL ✗"
```

- [ ] **Step 8.7: Test — change-password rota sid**

```bash
# Login nuevo
TOKEN_C=$(curl -s -X POST $BASE/auth/login -H 'Content-Type: application/json' -d '{"email":"test_sid_e2e@reglado.test","password":"Test1234"}' | python -c "import sys,json; print(json.load(sys.stdin).get('token',''))")
SID_C=$(echo "$TOKEN_C" | cut -d. -f2 | python -c "import sys,base64,json; s=sys.stdin.read().strip(); s+='='*(-len(s)%4); print(json.loads(base64.urlsafe_b64decode(s)).get('sid',''))")

RESP=$(curl -s -X POST $BASE/auth/change-password -H "Authorization: Bearer $TOKEN_C" -H 'Content-Type: application/json' -d '{"current_password":"Test1234","new_password":"NewPass99","new_password_confirmation":"NewPass99"}')
echo "change-password response: $RESP"
TOKEN_C2=$(echo "$RESP" | python -c "import sys,json; print(json.load(sys.stdin).get('token',''))")
SID_C2=$(echo "$TOKEN_C2" | cut -d. -f2 | python -c "import sys,base64,json; s=sys.stdin.read().strip(); s+='='*(-len(s)%4); print(json.loads(base64.urlsafe_b64decode(s)).get('sid',''))")
echo "sid antes change-password: $SID_C"
echo "sid después change-password: $SID_C2"

# Token antiguo cae
CODE1=$(curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $TOKEN_C" $BASE/auth/me)
# Token nuevo funciona
CODE2=$(curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $TOKEN_C2" $BASE/auth/me)
echo "Token antiguo: $CODE1 (debe 401) | nuevo: $CODE2 (debe 200)"
[ "$SID_C" != "$SID_C2" ] && [ "$CODE1" = "401" ] && [ "$CODE2" = "200" ] && echo "TEST 8.7 PASS ✓" || echo "TEST 8.7 FAIL ✗"
```

- [ ] **Step 8.8: Test — admin ban limpia sid**

Sustituye `<ADMIN_EMAIL>` y `<ADMIN_PASS>` por credenciales reales.

```bash
ADMIN_TOKEN=$(curl -s -X POST $BASE/auth/login -H 'Content-Type: application/json' -d '{"email":"<ADMIN_EMAIL>","password":"<ADMIN_PASS>"}' | python -c "import sys,json; print(json.load(sys.stdin).get('token',''))")
USER_ID=$(/c/xampp/mysql/bin/mysql -h 127.0.0.1 -u root regladousers -N -e "SELECT id FROM users WHERE email = 'test_sid_e2e@reglado.test';" | tail -1)

# Usuario loguea de nuevo (con password nueva)
TOKEN_D=$(curl -s -X POST $BASE/auth/login -H 'Content-Type: application/json' -d '{"email":"test_sid_e2e@reglado.test","password":"NewPass99"}' | python -c "import sys,json; print(json.load(sys.stdin).get('token',''))")

# Admin banea
curl -s -X POST $BASE/auth/admin/set-ban -H "Authorization: Bearer $ADMIN_TOKEN" -H 'Content-Type: application/json' -d "{\"user_id\":$USER_ID,\"banned\":true,\"current_password\":\"<ADMIN_PASS>\"}" > /dev/null

# sid debe ser NULL
DB_SID=$(/c/xampp/mysql/bin/mysql -h 127.0.0.1 -u root regladousers -N -e "SELECT IFNULL(current_session_id, 'NULL') FROM users WHERE id = $USER_ID;" | tail -1)
echo "DB sid tras ban: $DB_SID"
CODE=$(curl -s -o /tmp/resp -w "%{http_code}" -H "Authorization: Bearer $TOKEN_D" $BASE/auth/me)
BODY=$(cat /tmp/resp)
echo "Request usuario baneado: $CODE | $BODY"
# Debe responder "account banned" (ban check va antes que sid check)
[ "$DB_SID" = "NULL" ] && [ "$CODE" = "401" ] && [ "$BODY" = '{"error":"account banned"}' ] && echo "TEST 8.8 PASS ✓" || echo "TEST 8.8 FAIL ✗"

# Desban y cleanup
curl -s -X POST $BASE/auth/admin/set-ban -H "Authorization: Bearer $ADMIN_TOKEN" -H 'Content-Type: application/json' -d "{\"user_id\":$USER_ID,\"banned\":false,\"current_password\":\"<ADMIN_PASS>\"}" > /dev/null
```

- [ ] **Step 8.9: Cleanup — borrar usuario de prueba y parar servidor**

```bash
/c/xampp/mysql/bin/mysql -h 127.0.0.1 -u root regladousers -e "DELETE FROM users WHERE email = 'test_sid_e2e@reglado.test';"
# Parar el servidor PHP: sustituir <PID> por el del paso 8.1
kill <PID> 2>/dev/null
```

- [ ] **Step 8.10: No hay commit**

Task 8 es verificación end-to-end, no produce cambios en código. Si cualquier test falla, volver al task correspondiente.

---

## Task 9: Frontend GrupoReglado — interceptor 401 + aviso en LoginView

**Files:**
- Modify: `GrupoReglado/src/services/auth.js`
- Modify: `GrupoReglado/src/pages/LoginView.vue`

- [ ] **Step 9.1: Añadir interceptor 401 en `request()`**

En `GrupoReglado/src/services/auth.js`, localizar la función `request()` (línea ~51). Tras el bloque `try { payload = await response.json(); } catch { payload = {}; }` y ANTES del `if (!response.ok)`, insertar:

```js
  if (response.status === 401 && state.token) {
    // Sesión invalidada server-side (login en otro dispositivo, password
    // change, ban, admin force-logout). Limpiamos estado local y redirigimos
    // al login con el motivo para que LoginView pueda mostrar el aviso.
    const reason = encodeURIComponent(payload.error || "session expired");
    clearSession();
    if (typeof window !== "undefined" && !window.location.pathname.startsWith("/login")) {
      window.location.replace(`/login?reason=${reason}`);
    }
  }
```

El resultado final de `request` queda así:

```js
async function request(path, options = {}) {
  const response = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers: {
      "Content-Type": "application/json",
      ...(options.headers || {}),
    },
  });

  let payload = {};
  try {
    payload = await response.json();
  } catch {
    payload = {};
  }

  if (response.status === 401 && state.token) {
    const reason = encodeURIComponent(payload.error || "session expired");
    clearSession();
    if (typeof window !== "undefined" && !window.location.pathname.startsWith("/login")) {
      window.location.replace(`/login?reason=${reason}`);
    }
  }

  if (!response.ok) {
    const message = translateAuthMessage(payload.error || payload.message || "request failed");
    throw new Error(message);
  }

  if (typeof payload.message === "string") {
    payload.message = translateAuthMessage(payload.message);
  }

  return payload;
}
```

- [ ] **Step 9.2: Mostrar aviso en LoginView**

Localizar `GrupoReglado/src/pages/LoginView.vue`. Leer el archivo para identificar la sección de state/script y la sección de template.

En `<script setup>`, añadir una ref `info` y poblarla desde la query string:

```js
import { ref, onMounted } from "vue";

const info = ref("");

onMounted(() => {
  const params = new URLSearchParams(window.location.search);
  const reason = params.get("reason");
  if (reason) {
    info.value = auth.translateMessage(decodeURIComponent(reason));
  }
});
```

En el `<template>`, añadir un bloque de aviso encima del formulario (justo debajo de `<h1>`, `<h2>` o título del login — adaptar al markup real):

```vue
<p v-if="info" class="feedback info">{{ info }}</p>
```

Si no existe un estilo `.feedback.info` en el componente o en `styles.css`, añadir al bloque `<style scoped>`:

```css
.feedback.info {
  background: rgba(123, 150, 185, 0.12);
  color: #27436b;
  border: 1px solid rgba(123, 150, 185, 0.35);
  padding: 0.6rem 0.9rem;
  border-radius: 8px;
  font-size: 0.88rem;
  margin-bottom: 0.75rem;
}
```

- [ ] **Step 9.3: Verificación sintáctica + build**

```bash
cd c:/xampp/htdocs/Reglado/GrupoReglado
node --check src/services/auth.js
npx vite build
```

Expected: build completo sin errores.

- [ ] **Step 9.4: Commit**

```bash
cd c:/xampp/htdocs/Reglado
git add GrupoReglado/src/services/auth.js GrupoReglado/src/pages/LoginView.vue
git commit -m "feat(GrupoReglado): interceptor 401 en auth.js + aviso reason en LoginView"
```

---

## Task 10: Frontend RegladoEnergy, RegladoMaps, RegladoIngenieria — interceptor 401 + LoginView

La estructura de `auth.js` varía ligeramente entre estos proyectos:
- `RegladoEnergy` y `RegladoMaps` tienen `translateAuthMessage` local.
- `RegladoIngenieria` no tiene helper de traducción (request maneja errores de forma más simple).

El interceptor 401 es idéntico (no depende de la traducción). El aviso en LoginView se adapta.

- [ ] **Step 10.1: Interceptor 401 en RegladoEnergy**

En `RegladoEnergy/src/services/auth.js` localizar `request()` (línea 36). Tras el `try/catch` del `payload` y ANTES de `if (!response.ok)`, insertar:

```js
  if (response.status === 401 && state.token) {
    const reason = encodeURIComponent(payload.error || "session expired");
    clearSession();
    if (typeof window !== "undefined" && !window.location.pathname.startsWith("/login")) {
      window.location.replace(`/login?reason=${reason}`);
    }
  }
```

- [ ] **Step 10.2: Interceptor 401 en RegladoMaps**

Mismo bloque en `RegladoMaps/src/services/auth.js` (estructura idéntica a Energy).

- [ ] **Step 10.3: Interceptor 401 en RegladoIngenieria**

En `RegladoIngenieria/src/services/auth.js` localizar `request()` (línea 18). La versión compacta; sustituir la función completa por:

```js
async function request(path, options = {}) {
  const response = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers: { "Content-Type": "application/json", ...(options.headers || {}) },
  });
  let payload = {};
  try { payload = await response.json(); } catch { payload = {}; }

  if (response.status === 401 && state.token) {
    const reason = encodeURIComponent(payload.error || "session expired");
    clearSession();
    if (typeof window !== "undefined" && !window.location.pathname.startsWith("/login")) {
      window.location.replace(`/login?reason=${reason}`);
    }
  }

  if (!response.ok) throw new Error(payload.error || payload.message || "La solicitud no se pudo completar.");
  return payload;
}
```

- [ ] **Step 10.4: Aviso `reason` en LoginView de cada proyecto**

Para cada proyecto, localizar su LoginView (suele estar en `src/views/LoginView.vue`). Abrir el archivo. En `<script setup>` (o `<script>` si usa opciones API):

1. Importar `ref, onMounted` si aún no están.
2. Crear `const info = ref("");`.
3. Añadir en `onMounted`:

```js
const params = new URLSearchParams(window.location.search);
const reason = params.get("reason");
if (reason) {
  const decoded = decodeURIComponent(reason);
  const dictionary = {
    "session expired": "Tu sesión ha caducado. Vuelve a iniciar sesión.",
    "account banned": "Esta cuenta está suspendida. Contacta con el administrador.",
  };
  info.value = dictionary[decoded] || "Tu sesión se ha cerrado. Inicia sesión de nuevo.";
}
```

4. En el `<template>` insertar sobre el formulario:

```vue
<p v-if="info" class="feedback info">{{ info }}</p>
```

5. Si el proyecto ya tiene una clase `.feedback.info` reutilizable (en `styles.css` o similar), úsala. Si no, añadir al `<style scoped>`:

```css
.feedback.info {
  background: rgba(123, 150, 185, 0.12);
  color: #27436b;
  border: 1px solid rgba(123, 150, 185, 0.35);
  padding: 0.6rem 0.9rem;
  border-radius: 8px;
  font-size: 0.88rem;
  margin-bottom: 0.75rem;
}
```

Nota: usamos un diccionario inline (no `auth.translateMessage`) porque no todos los `auth.js` exportan ese helper (en particular `RegladoIngenieria` no lo tiene). Así el parche es portable.

- [ ] **Step 10.5: Verificación sintáctica**

```bash
for p in RegladoEnergy RegladoMaps RegladoIngenieria; do
  echo "=== $p ===";
  node --check c:/xampp/htdocs/Reglado/$p/src/services/auth.js && echo "$p auth.js OK";
done
```

Expected: cada proyecto reporta OK.

- [ ] **Step 10.6: Build de los tres proyectos**

```bash
for p in RegladoEnergy RegladoMaps RegladoIngenieria; do
  echo "=== build $p ===";
  (cd c:/xampp/htdocs/Reglado/$p && npx vite build 2>&1 | tail -5);
done
```

Expected: los tres terminan con `built in Xs` sin errores.

- [ ] **Step 10.7: Commit**

```bash
cd c:/xampp/htdocs/Reglado
git add RegladoEnergy/src/services/auth.js RegladoEnergy/src/views/LoginView.vue RegladoMaps/src/services/auth.js RegladoMaps/src/views/LoginView.vue RegladoIngenieria/src/services/auth.js RegladoIngenieria/src/views/LoginView.vue
git commit -m "feat(ecosistema): interceptor 401 y aviso reason en Energy/Maps/Ingenieria"
```

---

## Task 11: Validación frontend en navegador (GrupoReglado)

Este task es verificación humana. No modifica código.

- [ ] **Step 11.1: Arrancar backend + frontend**

```bash
# Terminal 1: backend
cd c:/xampp/htdocs/Reglado/ApiLoging && php -S localhost:8765 -t .

# Terminal 2: frontend
cd c:/xampp/htdocs/Reglado/GrupoReglado && npm run dev
```

- [ ] **Step 11.2: Test kick-old manual**

1. Abrir Chrome → `http://localhost:5173/login` → loguear como un usuario real.
2. Abrir Firefox (u otra sesión/modo incógnito) → misma URL → loguear como el mismo usuario.
3. Volver a Chrome → clicar cualquier enlace o acción del portal que dispare una request autenticada (p. ej. abrir SettingsView).
4. Verificar:
   - El usuario es redirigido a `/login?reason=session%20expired`.
   - Aparece el aviso "Tu sesión ha caducado. Vuelve a iniciar sesión." sobre el formulario.
   - El localStorage/cookie del token ha sido limpiado (inspeccionar en DevTools → Application).

Expected: comportamiento descrito.

- [ ] **Step 11.3: No hay commit**

Validación humana. Si algo falla → volver a Task 9.

---

## Verificación final

Tras Tasks 1–11 completos:

- [ ] `git log --oneline` muestra 9 commits nuevos (Tasks 1, 2, 3, 4, 5, 6, 7, 9, 10; Tasks 8 y 11 no commitean).
- [ ] Los 7 tests del Task 8 pasan.
- [ ] El test manual del Task 11 pasa.
- [ ] `php -l` sin errores en los 4 archivos PHP modificados.
- [ ] `vite build` sin errores en los 4 frontends tocados.

Si todo OK → feature lista. Mover al flujo de PR/merge a `main` según la política del proyecto.
