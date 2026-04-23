# Single-Session Enforcement (una sesión activa por cuenta)

**Fecha**: 2026-04-23
**Proyecto**: ApiLoging + frontends del ecosistema Reglado
**Estado**: Diseño aprobado
**Fuera de alcance**: `Inmobiliaria_Reglados` (mantenimiento de otro equipo) y `RegladoBienesRaices` (no se toca en esta iteración).

## Contexto

Hoy ApiLoging emite un JWT nuevo en cada `/auth/login` sin invalidar el anterior. Como consecuencia, dos (o más) personas pueden estar usando simultáneamente la misma cuenta desde dispositivos distintos sin que el sistema lo detecte. Falla un requisito básico de control de accesos.

Ayer se introdujeron mecanismos de revocación por cuenta (`password_changed_at`, `banned_at`, `sessions_invalidated_at`) pero ninguno cubre este caso: un usuario honesto no cambia su contraseña ni se banea cada vez que otro inicia sesión.

## Objetivo

Garantizar que cada cuenta tiene como máximo **una sesión activa** en todo el ecosistema. Política escogida: **kick-old** — la sesión más reciente gana, la anterior queda inválida al siguiente request.

## Decisiones clave

| Decisión | Elección |
| --- | --- |
| Política ante login concurrente | **A (kick-old)** — la nueva sesión expulsa a la anterior. |
| Mecanismo | `sid` (session id) en JWT + columna `users.current_session_id`. |
| Resolución de rotación | Sid explícito evita los problemas de resolución de 1 s que tiene DATETIME. |
| Tokens pre-deploy | **Kick total** — al desplegar, cualquier JWT sin `sid` se rechaza; todos los usuarios deben re-loguear una vez. |

## Cambios en backend (ApiLoging)

### Migración SQL

Archivo nuevo: `database/migrate_single_session.sql`

```sql
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS current_session_id CHAR(64) NULL AFTER sessions_invalidated_at;

CREATE INDEX IF NOT EXISTS idx_users_current_session_id ON users (current_session_id);
```

Actualizar también `database/schema.sql` para reflejar la nueva columna en instalaciones limpias.

### Modelo `User`

- `User::rotateSession(int $userId): string`
  - Genera `bin2hex(random_bytes(32))` (64 chars hex).
  - `UPDATE users SET current_session_id = ? WHERE id = ?`.
  - Devuelve el sid generado.
- `User::clearSession(int $userId): void`
  - `UPDATE users SET current_session_id = NULL WHERE id = ?`.
- `User::getSecurityState(int $userId): array` se amplía:
  ```php
  return [
      'password_changed_at' => ...,
      'banned_at' => ...,
      'sessions_invalidated_at' => ...,
      'current_session_id' => $row['current_session_id'] ?? null,
  ];
  ```
- `User::banUser(int $userId, int $adminId): void` pasa a setear también `current_session_id = NULL` en el mismo UPDATE.

### `JwtService`

Firma actualizada:
```php
public static function generate(array $user, string $sid): string
```

Lanza `RuntimeException('sid required')` si `$sid` es `''`. Fail-loud para no emitir tokens sin sid. El payload añade:
```php
'sid' => $sid,
```

### Endpoints que emiten sesión (rotan sid)

| Endpoint | Cambio |
| --- | --- |
| `POST /auth/login` | Tras password + email_verified + ban check → `$sid = User::rotateSession($userId)` → `JwtService::generate($user, $sid)`. |
| `GET /auth/verify-email` | Tras `markEmailAsVerified` o `createUserFromPendingRegistration` → `rotateSession` + JWT con sid. |
| `GET /auth/confirm-email-change` | Tras `applyEmailChange` → `rotateSession` + JWT con sid. |
| `POST /auth/reset-password` | Tras `updatePasswordHash` → `rotateSession` + JWT con sid (las demás sesiones caen por `password_changed_at`). |
| `POST /auth/change-password` | Idem. |

### Endpoints que conservan sesión

`updateUsername`, `updateName`, `updatePhone` emiten JWT fresco pero conservan el sid actual:

`respondWithFreshSession(int $userId, string $message)` cambia a:

```php
private static function respondWithFreshSession(int $userId, string $message): void
{
    $user = User::findById($userId);
    if (!$user) {
        Response::json(['error' => 'user not found'], 404);
    }

    $currentSid = $user['current_session_id'] ?? null;
    if ($currentSid === null) {
        // Si el admin forzó logout mientras el usuario guardaba el perfil,
        // abortamos aquí — no reemitimos sesión sin mandato explícito.
        Response::json(['error' => 'session expired'], 401);
    }

    $token = JwtService::generate($user, $currentSid);

    Response::json([
        'message' => $message,
        'token' => $token,
        'user' => self::mapUser($user),
    ]);
}
```

### Middleware

`AuthMiddleware::handle` añade al final del bloque de validación, tras el check de `sessions_invalidated_at`:

```php
$tokenSid = isset($decoded['sid']) ? (string) $decoded['sid'] : '';
if ($state['current_session_id'] === null || $tokenSid === '' || !hash_equals((string) $state['current_session_id'], $tokenSid)) {
    SecurityLogger::log('token_session_mismatch', $userId);
    Response::json(['error' => 'session expired'], 401);
}
```

`hash_equals` evita timing attacks al comparar strings de sesión (aunque el sid no es un secreto criptográfico directo, la comparación constante es barata y evita señalar parcialmente cuándo falla).

### Logout explícito

`POST /auth/logout`:
```php
User::clearSession($userId);
// Mantenemos también el INSERT en revoked_tokens: defensa redundante, coste despreciable.
```

### Acciones admin (extensión de la feature de ayer)

- `AuthController::adminForceLogout`: tras `User::invalidateSessions($userId)`, añadir `User::clearSession($userId)`.
- `AuthController::adminSetBan` con `banned=true`: `User::banUser` ya queda reescrito para setear `current_session_id = NULL` en el mismo UPDATE; no hace falta llamada extra.

## Cambios en frontends

Afectados: `GrupoReglado`, `RegladoEnergy`, `RegladoIngenieria`, `RegladoMaps`. El parche es **idéntico** en todos (cada uno tiene su copia de `src/services/auth.js`).

### Interceptor 401 en `auth.js`

Modificar la función `request()`:

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
    // Sesión invalidada server-side (login en otro dispositivo, password
    // change, ban, force-logout, etc.). Limpiamos estado local y redirigimos
    // al login con el motivo para que LoginView pueda mostrar el aviso.
    clearSession();
    if (typeof window !== "undefined" && !window.location.pathname.startsWith("/login")) {
      const reason = encodeURIComponent(payload.error || "session expired");
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

### `LoginView.vue`

Leer `?reason=...` de la URL en `onMounted` y mostrar un aviso informativo sobre el formulario:

```js
const reason = new URLSearchParams(window.location.search).get('reason');
if (reason) {
  infoMessage.value = auth.translateMessage(decodeURIComponent(reason));
}
```

Render condicional:
```vue
<p v-if="infoMessage" class="feedback info">{{ infoMessage }}</p>
```

Los estilos `.feedback.info` reutilizan la paleta neutra del proyecto (azul suave). No bloquea el formulario.

## No cambia

- **`revoked_tokens`** sigue funcionando como hoy; redundante pero barato mantenerlo.
- **Flujo de `initialize()`** en `auth.js`: ya limpia sesión al fallar `/auth/me`, no necesita cambios.
- **`chatbotReglado`**: usa su propia tabla `usuarios`, no ApiLoging. No afectado.
- **`Inmobiliaria_Reglados`**: fuera de alcance (mantenimiento de otro equipo).
- **`RegladoBienesRaices`**: fuera de alcance en esta iteración. Si usa ApiLoging, seguirá emitiendo JWTs sin `sid` y recibirá 401 hasta que se aplique el parche del interceptor y el spec completo en una iteración futura.

## Seguridad y límites

- **Race de dos logins simultáneos del mismo usuario**: el último `UPDATE users SET current_session_id = ?` gana (last-write-wins). El otro cliente tendrá un JWT cuyo `sid` no coincide con el `current_session_id` al siguiente request → 401. No hay ventana donde ambos sigan activos.
- **Kick accidental entre proyectos del ecosistema**: no ocurre. Los frontends comparten token vía localStorage/cookie en el mismo navegador → un único sid, una única sesión.
- **Coste del middleware**: mismo `getSecurityState` de ayer devuelve un campo más. Query idéntica; comparación de string adicional despreciable.
- **JWT con sid robado**: equivalente a JWT robado hoy; ninguna mejora ni empeora. Mitigaciones ya existentes (HTTPS, cookies Secure, revoked_tokens) siguen aplicando.
- **Usuario con dos dispositivos legítimos**: experiencia degradada — al loguearse en uno, el otro recibe `session expired` en su próxima acción. Es el comportamiento deseado por diseño.

## Cómo testarlo manualmente

1. **Login normal**:
   - Login → respuesta incluye token. Decodificar payload → `sid` presente con 64 hex chars.
   - BD: `SELECT current_session_id FROM users WHERE id = X` → mismo valor que el claim.

2. **Kick-old entre dispositivos**:
   - Terminal A: login → `token_A`. Verificar `/auth/me` con `token_A` → 200.
   - Terminal B: login (mismo usuario) → `token_B`. `token_A.sid != token_B.sid`.
   - Terminal A: `/auth/me` con `token_A` → 401 `session expired`.
   - Terminal B: `/auth/me` con `token_B` → 200.

3. **Profile update no rota sesión**:
   - Login → `token_A` con `sid_A`.
   - `POST /auth/update-phone` con `token_A` → respuesta contiene `token_B`. Decodificar → `sid_B == sid_A`.
   - `/auth/me` con `token_A` → 200 (aún vale, sid no ha cambiado).
   - `/auth/me` con `token_B` → 200.

4. **Logout explícito**:
   - Login → `token_A`. `POST /auth/logout` con `token_A` → 200.
   - BD: `current_session_id = NULL`.
   - `/auth/me` con `token_A` → 401 `session expired` (sid no coincide con NULL).

5. **Admin force-logout invalida sid**:
   - Usuario logueado con `token_A`.
   - Admin hace `force-logout` del usuario.
   - BD: `current_session_id = NULL`.
   - `/auth/me` con `token_A` → 401 `session expired`.

6. **Ban invalida sid**:
   - Usuario logueado con `token_A`.
   - Admin hace `set-ban { banned: true }`.
   - BD: `banned_at != NULL` y `current_session_id = NULL`.
   - `/auth/me` con `token_A` → 401 `account banned` (el check de ban va ANTES que el de sid).

7. **Change password emite nueva sesión para el caller + kickea otras**:
   - Login en A y B. Misma cuenta → B mata a A (kick-old). Estado: solo B con `sid_B`.
   - Volver a loguear en A → `sid_A`. Ahora solo A vale.
   - Desde A, `POST /auth/change-password` → respuesta con `token_A'` y nuevo `sid_A'`.
   - `/auth/me` con `token_A` → 401 (old sid).
   - `/auth/me` con `token_A'` → 200.

8. **Frontend interceptor**:
   - Login en Chrome y Firefox de la misma cuenta (último gana).
   - En Chrome, clicar cualquier acción que llame API.
   - Red: 401 `session expired` → `auth.js` limpia token y redirige a `/login?reason=session%20expired`.
   - LoginView muestra aviso traducido "Tu sesión ha caducado...".

## Archivos tocados

**Backend** (ApiLoging):
- `ApiLoging/database/migrate_single_session.sql` (nuevo)
- `ApiLoging/database/schema.sql`
- `ApiLoging/models/User.php`
- `ApiLoging/services/JwtService.php`
- `ApiLoging/controllers/AuthController.php`
- `ApiLoging/middleware/AuthMiddleware.php`

**Frontends** (misma edit en cada `src/services/auth.js` y `src/views/LoginView.vue`):
- `GrupoReglado/src/services/auth.js`
- `GrupoReglado/src/pages/LoginView.vue` (o `src/views/LoginView.vue`, a confirmar cuando se edite)
- `RegladoEnergy/src/services/auth.js` + LoginView
- `RegladoIngenieria/src/services/auth.js` + LoginView
- `RegladoMaps/src/services/auth.js` + LoginView

Cada frontend se revisa para confirmar que tiene el mismo `auth.js` base (y no una variante local) antes de aplicar el parche.
