# Admin: Banear y Cerrar Sesión de Usuarios

**Fecha**: 2026-04-22
**Proyecto**: GrupoReglado + ApiLoging
**Estado**: Diseño aprobado

## Contexto

El panel de administración de GrupoReglado ([`src/pages/AdminView.vue`](../../../GrupoReglado/src/pages/AdminView.vue)) permite listar usuarios y cambiar su rol. Faltan dos acciones de moderación:

1. **Cerrar sesión** del usuario (forzar logout de todas sus sesiones activas).
2. **Banear** la cuenta: bloquear login y bloquear re-registro con el mismo email/username. Si la cuenta ya está baneada, la acción es **desbanear**.

## Objetivo

Añadir un menú de 3 puntos (⋯) por fila en la tabla de usuarios del admin con dos acciones contextuales. El backend debe:

- Invalidar sesiones vivas al cerrar sesión o al banear.
- Rechazar login de cuentas baneadas.
- Rechazar JWTs existentes de cuentas baneadas / con sesión invalidada.
- Mantener la UNIQUE sobre email/username para que no se pueda re-registrar con la misma identidad.

## Decisiones clave

| Decisión | Elección |
| --- | --- |
| Reautenticación admin | A — contraseña del admin en cada acción (mismo patrón que `adminUpdateRole`). |
| Protección anti-error | A — solo proteger "self": el admin no puede banearse ni cerrar su propia sesión. |
| Datos guardados | B — `banned_at`, `banned_by`, `sessions_invalidated_at` (auditoría básica, sin motivo textual). |
| Feedback visual | A — pill rojo "Baneado" + fila con opacidad reducida. |

## Cambios en backend (ApiLoging)

### Migración SQL

Archivo nuevo: `database/migrate_user_bans.sql`

```sql
ALTER TABLE users
  ADD COLUMN banned_at DATETIME NULL AFTER is_email_verified,
  ADD COLUMN banned_by INT NULL AFTER banned_at,
  ADD COLUMN sessions_invalidated_at DATETIME NULL AFTER banned_by,
  ADD CONSTRAINT fk_users_banned_by FOREIGN KEY (banned_by) REFERENCES users(id) ON DELETE SET NULL;

CREATE INDEX idx_users_banned_at ON users (banned_at);
```

El `schema.sql` también se actualiza para reflejar las nuevas columnas y FK en instalaciones limpias.

### Modelo `User` ([`models/User.php`](../../../ApiLoging/models/User.php))

Nuevos métodos:

- `User::banUser(int $userId, int $adminId): void`
  - `UPDATE users SET banned_at = NOW(), banned_by = ?, sessions_invalidated_at = NOW() WHERE id = ?`
- `User::unbanUser(int $userId): void`
  - `UPDATE users SET banned_at = NULL, banned_by = NULL WHERE id = ?`
  - **No** limpia `sessions_invalidated_at` (los tokens emitidos antes del ban siguen inválidos).
- `User::invalidateSessions(int $userId): void`
  - `UPDATE users SET sessions_invalidated_at = NOW() WHERE id = ?`
- `User::getSecurityState(int $userId): array`
  - Devuelve `['password_changed_at', 'banned_at', 'sessions_invalidated_at']` en una sola query para el middleware. Reemplaza a `getPasswordChangedAt`.

`User::listAll()` amplía el `SELECT` para incluir `banned_at` y `banned_by`.

### Controlador ([`controllers/AuthController.php`](../../../ApiLoging/controllers/AuthController.php))

**Endpoints nuevos** (ambos requieren admin + password re-auth):

```
POST /auth/admin/force-logout
  body: { user_id, current_password }
  → 200 { message: 'sessions invalidated' }

POST /auth/admin/set-ban
  body: { user_id, banned: bool, current_password }
  → 200 { message: 'user banned' | 'user unbanned' }
```

Validaciones comunes a ambos:

1. `requireAdmin()` (ya existente).
2. Re-verificación de la contraseña del admin (patrón de `adminUpdateRole`).
3. `user_id > 0` y distinto del `adminId` (protección self → 422 `cannot target self`).
4. El usuario objetivo debe existir (404 si no).
5. Rate limit `admin_mutate` (30/min por admin), compartido entre ambos endpoints.
6. Log en `security_events`:
   - `admin_forced_logout` con `target_user_id`.
   - `admin_banned_user` / `admin_unbanned_user` con `target_user_id`.

**Login** — tras `password_verify`, antes de emitir JWT, comprobar ban:

```php
if (!empty($user['banned_at'])) {
    SecurityLogger::log('login_blocked_banned', (int) $user['id']);
    Response::json(['error' => 'account banned'], 403);
}
```

### Middleware ([`middleware/AuthMiddleware.php`](../../../ApiLoging/middleware/AuthMiddleware.php))

Reemplazar la llamada a `getPasswordChangedAt` por `User::getSecurityState($userId)` y añadir dos checks después del actual de `password_changed_at`:

```php
// Ban activo: invalida cualquier JWT independientemente de cuándo se emitió.
if (!empty($state['banned_at'])) {
    SecurityLogger::log('token_banned_account', $userId);
    Response::json(['error' => 'account banned'], 401);
}

// Sesiones invalidadas por admin (force-logout o ban): rechaza tokens
// emitidos antes del timestamp de invalidación.
if (!empty($state['sessions_invalidated_at'])) {
    $ts = strtotime((string) $state['sessions_invalidated_at']);
    if ($ts !== false && $ts > $iat) {
        SecurityLogger::log('token_session_invalidated', $userId);
        Response::json(['error' => 'session expired'], 401);
    }
}
```

### Router ([`index.php`](../../../ApiLoging/index.php))

Dos rutas nuevas:

```php
if ($uri === '/auth/admin/force-logout' && $method === 'POST') {
    AuthController::adminForceLogout();
}
if ($uri === '/auth/admin/set-ban' && $method === 'POST') {
    AuthController::adminSetBan();
}
```

### `adminUsers()`

Incluir `banned_at` y `banned_by` en la respuesta (además de los campos actuales).

## Cambios en frontend (GrupoReglado)

### `src/services/auth.js`

Nuevos métodos y sus claves en `AUTH_MESSAGE_MAP`:

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

Nuevas traducciones:

```js
"account banned": "Esta cuenta está suspendida. Contacta con el administrador.",
"session expired": "Tu sesión ha caducado. Vuelve a iniciar sesión.",
"cannot target self": "No puedes aplicar esta acción sobre tu propia cuenta.",
"sessions invalidated": "Sesiones del usuario cerradas.",
"user banned": "Usuario baneado.",
"user unbanned": "Usuario desbaneado.",
```

### `src/pages/AdminView.vue`

**Tabla** — dos columnas nuevas:

| Posición | Columna | Contenido |
| --- | --- | --- |
| Entre "Verificado" y "Alta" | **Estado** | Pill `Baneado` (rojo) si `banned_at`, `Activo` (neutro) si no. |
| Última | **Acciones** | Botón circular con icono ⋯. Oculto si el usuario es el admin actual. |

**Menú detached** (reusa el patrón `Teleport` + `custom-detached-dropdown` ya existente) — contenido contextual:

- `banned_at === null` → `Cerrar sesión` · `Banear` (ítem en rojo).
- `banned_at !== null` → `Desbanear` (ítem en verde).

**Flujo de acción**:

1. Clic en ⋯ → abre el menú detached posicionado junto al botón.
2. Clic en una opción → `confirm()` con mensaje ("¿Banear a `<usuario>`?" / "¿Cerrar la sesión de `<usuario>`?" / "¿Desbanear a `<usuario>`?").
3. Si confirma → `prompt()` pidiendo contraseña del admin (mismo flujo que `updateUserRole`).
4. Llamar a `auth.adminSetBan(...)` / `auth.adminForceLogout(...)`.
5. En éxito: `await loadUsers()` para refrescar estado.
6. En error: `error.value = message`, no se mutan datos locales.

**Estilos** — fila baneada:

```css
.users-table tr.is-banned td { opacity: 0.55; }
.users-table tr.is-banned:hover td { opacity: 0.75; }
```

Pill `Baneado`: reutilizar paleta del proyecto (rojo desaturado sobre fondo tenue) para no romper glassmorphism.

## No cambia

- **Registro** ([`AuthController::register`](../../../ApiLoging/controllers/AuthController.php)): sigue devolviendo la respuesta genérica "if the data is valid, a verification email has been sent". El UNIQUE de `email`/`username` bloquea re-registro mientras exista el registro (baneado) y la respuesta genérica evita enumeración.
- **Verificación de email**: un usuario baneado que intente verificar un email antiguo no puede, porque ni el login ni el middleware le dejarán pasar — pero la UX no necesita cambios aquí.
- **`revoked_tokens`**: sigue siendo el mecanismo para el logout explícito del propio usuario (`POST /auth/logout`). El nuevo mecanismo de invalidación por admin se hace vía `sessions_invalidated_at` para no requerir conocer los JWTs emitidos.

## Seguridad y límites

- **JWT robado del admin**: mitigación por re-auth con contraseña en cada acción crítica.
- **Abuso masivo desde panel comprometido**: rate limit `admin_mutate` a 30/min por admin (compartido entre force-logout y set-ban).
- **Ataque de enumeración vía "account banned"**: el mensaje `account banned` solo se devuelve tras `password_verify` exitoso en login. Un atacante sin credenciales válidas sigue viendo `invalid credentials`. No filtra información.
- **Coste del middleware**: la consulta pasa de 1 columna (`password_changed_at`) a 3 en la misma query (`password_changed_at, banned_at, sessions_invalidated_at`). Coste marginal despreciable.

## Cómo testarlo manualmente

1. Login como admin → panel → abrir menú ⋯ en un usuario cualquiera → **Banear** → confirmar + password.
2. Verificar en BD: `SELECT banned_at, banned_by, sessions_invalidated_at FROM users WHERE id = X`.
3. En otra pestaña con sesión del usuario baneado → cualquier request a API → debe devolver 401 `account banned`.
4. Logout → intentar login del usuario baneado → 403 `account banned`.
5. Intentar registrar de nuevo con el mismo email → respuesta genérica (no se crea registro).
6. Desbanear → login OK de nuevo (pero la sesión previa al ban sigue inválida por `sessions_invalidated_at`).
7. **Cerrar sesión**: abrir menú ⋯ → `Cerrar sesión` → confirmar + password → el usuario debe recibir 401 `session expired` en su próxima request.
8. Intentar `Banear` sobre sí mismo → el botón no debe aparecer; si se fuerza por API → 422 `cannot target self`.

## Archivos tocados

**Backend**:
- `ApiLoging/database/migrate_user_bans.sql` (nuevo)
- `ApiLoging/database/schema.sql` (actualización)
- `ApiLoging/models/User.php`
- `ApiLoging/controllers/AuthController.php`
- `ApiLoging/middleware/AuthMiddleware.php`
- `ApiLoging/index.php`

**Frontend**:
- `GrupoReglado/src/services/auth.js`
- `GrupoReglado/src/pages/AdminView.vue`
