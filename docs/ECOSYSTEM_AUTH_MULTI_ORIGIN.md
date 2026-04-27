# Auth multi-origen del ecosistema Reglado

**Fecha del documento:** 2026-04-24
**Estado:** Implementado y probado en **dev** (localhost). **Pendiente** de configurar env vars de producción en ApiLoging cuando los dominios de Maps e Ingeniería estén disponibles.

---

## 1. Contexto

Hasta ahora, cuando un usuario hacía login / registro / reset de contraseña desde un proyecto del ecosistema distinto de Grupo (Energy, Maps, Ingeniería), el flujo **salía del dominio actual y volvía a Grupo** — tanto el formulario como los emails de verificación/reset apuntaban siempre a `regladogroup.com`. Esto generaba dos problemas:

- **Percepción "phishing"**: el usuario está en `regladoenergy.com`, pulsa "Iniciar sesión" y acaba en `regladogroup.com`.
- **Emails que siempre vuelven a Grupo**: el link del email de verificación llevaba al usuario a Grupo aunque hubiese iniciado el registro desde Energy.

El objetivo fue que **cada proyecto gestione login / registro / recuperación de forma autónoma**, y que los emails transaccionales vuelvan al proyecto que inició la petición.

Este trabajo se dividió en tres pasos:

- **Paso 1 — Backend (ApiLoging)**: que el servidor construya dinámicamente las URLs de los emails según el origen de la petición, validando contra una allowlist.
- **Paso 2 — Frontends**: replicar en Energy, Maps e Ingeniería los componentes de auth (modal login, register, forgot, verify, reset, confirm-access).
- **Paso 3 — `visibilitychange` sync**: que cambios de sesión (login/logout) en una pestaña se detecten en las demás sin recargar.

---

## 2. Backend — cambios aplicados a ApiLoging

### 2.1. Helpers nuevos en [`utils/Security.php`](../ApiLoging/utils/Security.php)

Tres métodos públicos para resolver la URL del frontend dinámicamente:

- **`resolveFrontendUrl(string $fixedPath, string $envFallbackKey): string`**
  Lee el header `Origin` de la request. Si está en `REDIRECT_ALLOWED_ORIGINS`, devuelve `{origin}{fixedPath}`. Si no, cae al env var. Pensado para endpoints XHR desde el frontend (register, request-password-reset, login).

- **`resolveFrontendUrlFromCandidate(?string $candidate, string $fixedPath, string $envFallbackKey): string`**
  Idéntico pero el origen viene en query string en lugar de header. Caso del verify email: cuando el usuario pulsa el link del email, la request llega sin `Origin`, pero el link ya incluye `return_origin=...` (propagado cuando se construyó el email).

- **`validatedRequestOrigin(): ?string`**
  Devuelve el `Origin` solo si es válido. Útil para embeber el origen en el link del email al construirlo.

Todos los métodos reutilizan `isAllowedAbsoluteUrl()` para validar contra la allowlist de `REDIRECT_ALLOWED_ORIGINS`, evitando open-redirects.

### 2.2. Cambios en [`controllers/AuthController.php`](../ApiLoging/controllers/AuthController.php)

Cuatro sitios modificados:

1. **`verify()`** (línea ~230): lee `return_origin` del query string del link de email y, si es válido, redirige al frontend de ese origen (`{origin}/verificacion-exitosa?token=...`). Fallback al env var `EMAIL_VERIFY_REDIRECT_URL` si no hay query param válido.

2. **`buildVerificationUrl()`** (línea ~860): al construir el link del email de verificación, añade `&return_origin={origin}` (validado) para que `verify()` sepa a qué frontend devolver al usuario.

3. **`buildPasswordResetUrl()`** (línea ~884): usa `Security::resolveFrontendUrl('/restablecer-contrasena', 'PASSWORD_RESET_URL_BASE')`. El link del email lleva al frontend correcto.

4. **`resolveLoginAlertBaseUrl()`** (línea ~1120): idem para la alerta de nueva ubicación — usa el origen del POST `/auth/login` para decidir a qué frontend vuelve el email "¿fuiste tú?".

### 2.3. Env vars — lo que hay que revisar en producción

Los env vars siguen siendo el **fallback**. El comportamiento preferente es el `Origin` header. Configuración:

#### `CORS_ALLOWED_ORIGINS` — debe incluir TODOS los dominios que vayan a llamar a ApiLoging

**Actual en [`.env.production.example`](../ApiLoging/.env.production.example):**
```
CORS_ALLOWED_ORIGINS=https://regladogroup.com,https://regladoenergy.com,https://realstate.com
```

**Pendiente añadir** cuando se conozcan los dominios:
- `https://regladomaps.com` (o el dominio real de Maps)
- `https://regladoingenieria.com` (o el dominio real de Ingeniería)

#### `REDIRECT_ALLOWED_ORIGINS` — debe incluir los mismos dominios

Mismo set que `CORS_ALLOWED_ORIGINS`. Se usa como allowlist para validar el `Origin` antes de construir URLs de email. Si un dominio no está aquí, el código cae al env var de fallback (Grupo) — es decir, si se olvida un dominio, el único síntoma es que los emails desde ese frontend volverán a Grupo en lugar de a sí mismo. No hay fallo de seguridad, solo degradación al comportamiento antiguo.

#### `EMAIL_VERIFY_URL_BASE`, `EMAIL_VERIFY_REDIRECT_URL`, `PASSWORD_RESET_URL_BASE`

Siguen siendo los valores por defecto (Grupo). Solo se usan cuando el `Origin` no está en la allowlist o cuando no hay `Origin` (ej. cronjobs). **No hay que cambiarlos**.

### 2.4. Checklist de despliegue a producción

Cuando se quiera cerrar el loop en prod:

1. [ ] Obtener los dominios reales de Maps e Ingeniería.
2. [ ] Actualizar `CORS_ALLOWED_ORIGINS` y `REDIRECT_ALLOWED_ORIGINS` en el `.env` del servidor de producción de ApiLoging.
3. [ ] Actualizar `.env.production.example` con el set completo (opcional pero recomendable para futuros deploys).
4. [ ] Redeploy (o recargar el proceso PHP-FPM, según infra).
5. [ ] Verificar con los tests de §5.

---

## 3. Frontends — qué se aplicó en cada proyecto

Patrón replicado idéntico en Energy, Maps e Ingeniería, respetando la estética de cada uno:

| Proyecto | Puerto dev | Estética | Estado |
|---|---|---|---|
| **Grupo** | 5173 | Light + azul corporativo | Ya existía — no se tocó |
| **Energy** | 5174 | Dark + dorado | ✅ Completo |
| **Maps** | 5176 | Dark + cyan | ✅ Completo |
| **Ingeniería** | 5177 | Light + steel blue | ✅ Completo |
| **Inmobiliaria** | — | — | Fuera del alcance (otro equipo) |

### 3.1. En `services/auth.js` de cada proyecto

Se añadieron los métodos:

```js
syncWithCookie()          // Reconcilia estado local con la cookie compartida cuando la pestaña vuelve a visible
login(email, password)    // POST /auth/login y setea sesión
register(payload)         // POST /auth/register
resendVerification(email) // POST /auth/resend-verification
requestPasswordReset(email)                           // POST /auth/request-password-reset
resetPassword(token, newPassword, newPasswordConfirmation)  // POST /auth/reset-password
confirmLoginLocation(token, decision)                 // POST /auth/confirm-login-location
```

### 3.2. Componentes nuevos

En cada proyecto:

- **`LoginModal.vue`** — modal con email+password, "reenviar verificación" tras error, enlaces internos a register y forgot-password.
- **`RegisterView.vue`** — formulario completo (username, nombre, apellido, email, teléfono, contraseña, confirmación, checkbox privacidad). Tras POST exitoso muestra confirmación con email recibido.
- **`ForgotPasswordView.vue`** — formulario simple email → enlace de reset.
- **`EmailVerifiedView.vue`** — recibe `?token=...` del 302 post-verificación, hidrata sesión, countdown 5s al home.
- **`ResetPasswordView.vue`** — recibe `?token=...` del email, formulario nueva contraseña + confirmación, POST a `/auth/reset-password`.
- **`ConfirmarAccesoView.vue`** — recibe `?token=...&decision=me|not-me`, llama a `confirm-login-location`, muestra estado con iconos (confirmed/rejected/expired/invalid/error).

### 3.3. Rutas añadidas

```
/registro
/recuperar-contrasena
/restablecer-contrasena
/verificacion-exitosa
/confirmar-acceso
```

### 3.4. App.vue y Header

- `App.vue` monta `<LoginModal v-model="showLogin" />` y escucha `@open-login` del header.
- `App.vue` suscribe `visibilitychange` → `auth.syncWithCookie()` para detectar cambios de sesión en otras pestañas del ecosistema sin recargar.
- El botón "Iniciar sesión" del header emite `open-login` en lugar de redirigir a Grupo.

### 3.5. Caso especial — Ingeniería

Ingeniería tiene un router guard (`beforeEach`) con rutas `requiresAuth` (`/area-clientes`, `/admin`). Antes redirigía a `gruporeglado.com/login` cuando no había sesión; ahora redirige a `/?login=required&returnTo={to.fullPath}`. En `App.vue`:

```js
watch(() => route.query.login, (flag) => {
  if (flag === "required") {
    pendingReturnTo.value = route.query.returnTo || "";
    showLogin.value = true;
    router.replace({ path: route.path, query: {} });
  }
}, { immediate: true });
```

Tras `@success` del modal, el `App.vue` navega al `pendingReturnTo`. Esto permite que `/area-clientes` siga funcionando como bookmark: el usuario aterriza, el guard lo intercepta, modal se abre, tras login aterriza donde quería ir.

### 3.6. Env vars limpiadas en frontends

Se eliminaron de `.env` (dev y prod) de Energy, Maps y ahora ya son dead config:

```
VITE_GRUPO_REGLADO_LOGIN_PATH       ← eliminada (el login ya no redirige a Grupo)
VITE_GRUPO_REGLADO_REGISTER_PATH    ← eliminada (register es ruta interna)
VITE_GRUPO_REGLADO_RECOVER_PATH     ← eliminada (forgot es ruta interna)
```

Se conservan las que siguen usándose:
- `VITE_AUTH_API_URL` — URL del backend ApiLoging.
- `VITE_GRUPO_REGLADO_BASE_URL` + `VITE_GRUPO_REGLADO_SETTINGS_PATH` — Energy/Maps redirigen a Grupo `/configuracion` (la pantalla de settings sigue viviendo solo en Grupo).
- Ingeniería conserva `VITE_AUTH_FRONTEND_URL` para el mismo redirect a settings.

---

## 4. Sesión compartida entre pestañas (visibilitychange)

Problema independiente que se resolvió en el mismo ciclo: antes, si tenías Grupo y Energy abiertos en pestañas distintas y hacías login en Grupo, al volver a la pestaña de Energy la sesión no aparecía hasta recargar.

Solución: cada frontend suscribe `document.addEventListener("visibilitychange", ...)` y, cuando la pestaña vuelve a `visible`, llama a `auth.syncWithCookie()`:

1. Lee la cookie compartida `reglado_auth_token`.
2. Si ha cambiado vs. el `state.token` local, actualiza y re-fetchea `/auth/me`.
3. Si la cookie desapareció (logout en otro proyecto), limpia la sesión local.
4. Si no hay cambios, no hace nada (no spamea al backend).

Funciona bidireccionalmente: login/logout en cualquier proyecto se refleja en todos los demás sin recargar.

---

## 5. Verificación end-to-end

Cuando llegue el momento de probar en producción:

### 5.1. Por proyecto (Energy, Maps, Ingeniería)

1. **Registro**: completar formulario → recibir email → el link debe apuntar al dominio del proyecto (`https://regladoenergy.com/verificacion-exitosa?token=...`, no a Grupo).
2. **Verificación**: al pulsar el link, el usuario aterriza en el propio proyecto con sesión iniciada.
3. **Password reset**: "¿Olvidé contraseña?" → email → link al propio proyecto (`https://regladoenergy.com/restablecer-contrasena?token=...`).
4. **Login desde IP/país nueva**: en el email de alerta, los botones "Sí he sido yo" / "No he sido yo" deben apuntar al propio proyecto (`/confirmar-acceso`).

### 5.2. Cross-project (ecosistema)

5. **Login en Grupo → Energy detecta la sesión**: abrir Grupo y Energy en pestañas distintas. Login en Grupo. Volver a Energy: debe mostrar la sesión sin recargar.
6. **Logout en Grupo → Energy detecta el logout**: logout en Grupo. Volver a Energy: la sesión debe cerrarse automáticamente.
7. **Lo mismo con Maps e Ingeniería** en ambas direcciones.

### 5.3. Fallback (caso degradado)

8. Si un dominio nuevo no está en `REDIRECT_ALLOWED_ORIGINS`, el email debe ir al fallback (Grupo) y seguir funcionando — no debe dar error 500 ni bloquear el registro. Útil como safety net: si se olvida añadir un dominio, solo pierdes el multi-origen, no la funcionalidad base.

---

## 6. Lo que NO se tocó

- **Flujo de cambio de email** desde `/configuracion` (`confirmEmailChange`, línea ~548 de `AuthController.php`). Sigue redirigiendo a Grupo. Motivo: `/configuracion` solo vive en Grupo, así que el flujo completo ocurre dentro de Grupo. Si en algún momento se replica `/configuracion` a los demás proyectos, habrá que aplicar el mismo patrón (leer `Origin` al construir el email, leer `return_origin` al redirigir).

- **Inmobiliaria**. Lo mantiene otro equipo. Cuando esté disponible, aplicar el mismo patrón usando Energy como referencia (similar en estructura: `pages/` folder, `<script setup>`, un `auth.js` con los mismos métodos).

- **ApiLoging backend** más allá de los 4 sitios listados en §2.2. Todo lo demás (rate limits, JWT, ban, single-session, geo alerts) está intacto.

---

## 7. Commits relevantes

El trabajo se hizo en la rama `jorge`. Los commits están agrupados por paso:

- Paso 1 (backend): cambios en `utils/Security.php` y `controllers/AuthController.php`.
- Paso 2 (Energy): B1 (login modal + register + forgot) y luego B2 (verify + reset + confirm).
- Paso 3 (Maps): B1 + B2 en un solo ciclo.
- Paso 4 (Ingeniería): B1 + B2 + adaptación del router guard.

Para ver el diff exacto: `git log --oneline main..jorge` y `git diff main..jorge -- ApiLoging/ RegladoEnergy/ RegladoMaps/ RegladoIngenieria/`.
