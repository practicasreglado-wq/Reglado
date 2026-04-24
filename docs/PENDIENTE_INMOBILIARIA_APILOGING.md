# Cambios pendientes para Inmobiliaria_Reglados

**Fecha del checklist:** 2026-04-23 (ampliado 2026-04-24)
**Por qué existe este documento:** entre el 2026-04-22 y el 2026-04-24 se aplicaron varios cambios a ApiLoging y al ecosistema Reglado (GrupoReglado, RegladoEnergy, RegladoIngenieria, RegladoMaps). `Inmobiliaria_Reglados` quedó fuera del alcance porque lo mantiene otro equipo. Cuando ese equipo cierre su iteración, aplicar este checklist para alinear Inmobiliaria con el resto del ecosistema.

Todo el trabajo es **frontend**. El backend (ApiLoging) es compartido: Inmobiliaria ya se beneficia server-side de los cambios sin tocar nada. Lo que hay que hacer aquí es que el frontend de Inmobiliaria **reaccione bien** a esos cambios cuando le lleguen desde el servidor.

---

## Qué ya funciona en Inmobiliaria sin tocar nada

Por ser cambios 100% server-side en ApiLoging, Inmobiliaria los hereda gratis:

- **Ban de cuenta** — un usuario baneado no puede loguear; ApiLoging devuelve `403 account banned`.
- **Admin force-logout** — las sesiones de un usuario pueden ser cerradas desde el admin; ApiLoging invalida sus JWTs.
- **Single-session enforcement** — una sola sesión activa por cuenta. El segundo login tumba al primero.
- **Rate limit simplificado** — los logins legítimos ya no suman a los contadores de fuerza bruta.
- **Geo login alerts** — detección de login desde país nuevo; el usuario recibe un email con botones "sí fui yo" / "no fui yo".

## Qué hay que tocar en el frontend de Inmobiliaria

### 1. Interceptor 401 en `auth.js` (o equivalente)

Cuando el servidor devuelve 401 en cualquier request autenticada, significa que el JWT local ya no vale (sesión expirada, cuenta baneada, admin force-logout, kick-old por login en otro dispositivo, o reset de contraseña obligatorio). El cliente tiene que limpiar su estado para no dejar al usuario atrapado.

Localizar la función `request()` del servicio de auth y añadir el check justo DESPUÉS de parsear el payload y ANTES del `if (!response.ok)`:

```js
if (response.status === 401 && state.token) {
  // Sesión invalidada server-side. Limpiamos estado local; la reactividad
  // se encarga de re-promptear login (via modal o ruta).
  clearSession();
  // Opcional: si Inmobiliaria tiene una ruta /login dedicada, añadir
  // aquí el redirect con `?reason=${payload.error}` para mostrar aviso.
}
```

Si Inmobiliaria usa algo distinto de `fetch` (axios, ky, etc.), el interceptor va en el lugar equivalente del cliente HTTP. El comportamiento es idéntico: detectar 401 y llamar a `clearSession()`.

Referencia de cómo quedó en los otros frontends: ver los commits `721d73b` y `825c9a4` en la rama `jorge`.

### 2. Traducciones de mensajes del servidor

Si el `auth.js` tiene un mapa `AUTH_MESSAGE_MAP` (tipo el de GrupoReglado), añadir:

```js
"account banned": "Esta cuenta está suspendida. Contacta con el administrador.",
"session expired": "Tu sesión ha caducado. Vuelve a iniciar sesión.",
"password reset required": "Por seguridad, necesitas cambiar tu contraseña. Te hemos enviado un email con las instrucciones.",
```

Si Inmobiliaria NO traduce mensajes (los muestra tal cual los devuelve ApiLoging), saltarse este paso y aceptar que los mensajes aparezcan en inglés.

### 3. LoginView (si existe)

Si Inmobiliaria tiene una página dedicada `/login` (no solo un modal), leer `?reason=...` de la URL en `onMounted`:

```js
const params = new URLSearchParams(window.location.search);
const reason = params.get("reason");
if (reason) {
  const dictionary = {
    "session expired": "Tu sesión ha caducado. Vuelve a iniciar sesión.",
    "account banned": "Esta cuenta está suspendida. Contacta con el administrador.",
  };
  info.value = dictionary[decodeURIComponent(reason)]
            || "Tu sesión se ha cerrado. Inicia sesión de nuevo.";
}
```

Y mostrar `info` como feedback sobre el formulario. Si Inmobiliaria usa solo un modal (como Energy/Maps/Ingenieria), **no hace falta** — el punto 1 (`clearSession` + reactividad) es suficiente.

### 4. Redirect a home tras login

Verificar que al loguear en la página de login, el usuario termina en la home con una navegación dura (`window.location.href = "/"` o equivalente). Referencia: commit `c42b45d` en GrupoReglado.

No es un cambio de seguridad, es un polish de UX descubierto al probar el flujo de kick-old.

### 5. Política de privacidad

Si Inmobiliaria muestra su propia página de "Política de Privacidad" (no redirige a la de GrupoReglado), añadir en la sección de datos tratados:

> Registramos la IP y el país desde los que inicias sesión. Los usamos para detectar accesos sospechosos y, si detectamos un inicio de sesión desde un país distinto al habitual, te enviamos un correo para que confirmes que has sido tú. La IP se conserva solo asociada a ese evento de acceso.

## SSO Hub — integración (añadido 2026-04-24)

Es un bloque de trabajo aparte del resto del documento. Lo de arriba cubre "adaptar Inmobiliaria a cambios del backend". Esto cubre "incorporar Inmobiliaria al protocolo de sesión compartida del ecosistema".

### Qué es

Hasta 2026-04-24 la sincronización de sesión entre proyectos se basaba en la cookie compartida `reglado_auth_token`. Esto funcionaba en dev (todos los proyectos viven en `localhost` con puertos distintos, mismo host = cookies compartidas), pero **no en producción** (eTLD+1 distintos aislan las cookies).

Se implementó un protocolo SSO con Grupo como hub central. Spec completa en [ECOSYSTEM_AUTH_SSO_HUB.md](ECOSYSTEM_AUTH_SSO_HUB.md). El resumen:

- Grupo expone 3 páginas: `/sso-handshake`, `/sso-store`, `/sso-logout`.
- Los demás dominios (Energy, Maps, Ingeniería, **Inmobiliaria cuando toque**) redirigen a estas páginas para ceder / recibir / limpiar sesión.
- El JWT viaja en el fragmento (`#token=...`) para no filtrarse en logs ni Referer.
- Single-session enforcement intacto: se comparte el MISMO JWT (mismo `sid`).

### Qué hay que tocar en el frontend de Inmobiliaria

Todo esto es copy/paste del patrón aplicado en Energy/Maps/Ingeniería. Referencia directa: los tres archivos de `services/ssoClient.js` de esos proyectos son idénticos — basta con copiar uno.

#### 1. Crear `services/ssoClient.js`

Copiar tal cual de [RegladoEnergy/src/services/ssoClient.js](../RegladoEnergy/src/services/ssoClient.js).

El único punto a revisar: la env var del HUB. Por defecto lee `VITE_GRUPO_REGLADO_BASE_URL`. Si Inmobiliaria usa otro nombre (`VITE_AUTH_FRONTEND_URL` en Ingeniería, por ejemplo), ajustar el fallback.

#### 2. Modificar el bootstrap de auth en `App.vue`

Donde haya `auth.initialize()` al montar, sustituir por un `bootstrapAuth()` que:

```js
async function bootstrapAuth() {
  const fragmentToken = consumeTokenFromFragment();
  if (fragmentToken) {
    auth.setSession(fragmentToken, null);
    clearHandshakeAttempt();
  }
  if (wasSsoHandshakeFailed()) {
    clearSsoFailedFlag();
  }
  await auth.initialize();
  if (!auth.state.user && !wasHandshakeAttempted()) {
    redirectToHandshake();
  }
}
```

Referencia: [RegladoEnergy/src/App.vue](../RegladoEnergy/src/App.vue) (Composition API) o [RegladoMaps/src/App.vue](../RegladoMaps/src/App.vue) (Options API).

#### 3. Propagar tras login exitoso

En el LoginModal (o donde se haga el login), tras `auth.login()` exitoso:

```js
import { redirectToStore } from "./services/ssoClient";
// ...
const returnUrl = window.location.origin + window.location.pathname;
redirectToStore(auth.state.token, returnUrl);
```

Importante: `returnUrl` sin query/hash para no arrastrar flags sobrantes.

#### 4. Propagar tras verificación de email

En `EmailVerifiedView`, cuando se completa la verificación y el usuario queda logueado automáticamente, en el setTimeout final reemplazar `router.replace("/")` por `redirectToStore(auth.state.token, window.location.origin + "/")`.

#### 5. Logout redirige al hub

En `auth.logout()`, tras `clearSession()`, sustituir `window.location.href = "/"` (o equivalente) por `redirectToLogout()`.

#### 6. `syncWithCookie` revalida siempre

El `syncWithCookie` que tuviera Inmobiliaria probablemente hacía early-return si la cookie no cambiaba. En prod eso no detecta invalidaciones cross-domain. Cambio:

```js
// Antes: if (cookieToken === state.token && state.user) return;
// Después: eliminar esa línea — siempre llamar a /auth/me.
```

El 401 interceptor de `request()` se encarga de limpiar si el backend rechaza.

### Qué hay que tocar en Grupo (el hub)

**Añadir el dominio de Inmobiliaria a la allowlist.** Edit [GrupoReglado/src/services/ssoHub.js](../GrupoReglado/src/services/ssoHub.js):

```js
const SSO_ALLOWED_RETURNS = [
  // ... los que ya están
  "https://inmobiliaria-reglados.com",  // ← añadir el dominio real
  "http://localhost:XXXX",              // ← puerto dev de Inmobiliaria
];
```

Y opcionalmente un tema visual para que el flash del redirect use la estética de Inmobiliaria:

```js
const SSO_THEMES = {
  // ... los que ya están
  "https://inmobiliaria-reglados.com": INMOBILIARIA_THEME(),
};

function INMOBILIARIA_THEME() {
  return {
    name: "inmobiliaria",
    bg: "...", surface: "...", text: "...", accent: "...",
    // copiar la estructura de los otros temas
  };
}
```

Esto requiere un **redeploy de Grupo** tras el cambio.

### Verificación del SSO

1. **Login en Inmobiliaria → abrir Grupo**: Grupo debe aparecer ya logueado sin intervención.
2. **Login en Grupo → abrir Inmobiliaria**: Inmobiliaria debe hacer handshake silencioso y aparecer logueado.
3. **Logout en Inmobiliaria → abrir Grupo**: Grupo debe estar sin sesión.
4. **Login en Energy → abrir Inmobiliaria**: Inmobiliaria → Grupo/sso-handshake → logueado (porque Energy propagó a Grupo).

### Estimación de tiempo (SSO hub)

Siguiendo el patrón exacto de los 3 proyectos ya migrados: **1-2 horas** + 30 min para actualizar la allowlist y tema en Grupo + redeploy. Total: medio día.

## Qué NO hay que hacer

- **Tocar schema de BBDD** — Inmobiliaria no tiene BBDD propia para auth; todo va contra la de ApiLoging. El schema ya está migrado.
- **Añadir admin panel** — si Inmobiliaria no tenía admin panel antes, no hay que inventarlo. El admin se gestiona desde GrupoReglado.
- **Instalar MaxMind ni geoip2/geoip2** — es una dependencia del backend ApiLoging, no del frontend.
- **Modificar JWT** — el frontend lo sigue tratando como bearer opaco.

## Verificación tras aplicar

Tests manuales que deberían funcionar sin modificar código adicional:

1. **Kick-old desde Inmobiliaria**:
   - Login en Inmobiliaria con usuario X.
   - En otro navegador, login en GrupoReglado con usuario X.
   - Volver a Inmobiliaria y hacer cualquier acción autenticada → debe salir sesión limpiamente (no error residual en pantalla).

2. **Ban desde admin**:
   - Usuario X logueado en Inmobiliaria.
   - Admin banea al usuario X desde el panel de GrupoReglado.
   - Usuario X intenta una acción → sesión limpiada, al intentar loguear recibe 403 `account banned`.

3. **Geo alert**:
   - Usuario X loguea en Inmobiliaria desde España.
   - Después, loguea desde un país distinto (simulable con VPN).
   - Usuario X recibe el email de alerta (el envío lo gestiona ApiLoging, no depende de Inmobiliaria).

## Referencias cruzadas

Los specs completos de cada cambio están en:

- Ban + admin force-logout: [docs/superpowers/specs/2026-04-22-admin-ban-force-logout-design.md](superpowers/specs/2026-04-22-admin-ban-force-logout-design.md)
- Single-session enforcement: [docs/superpowers/specs/2026-04-23-single-session-enforcement-design.md](superpowers/specs/2026-04-23-single-session-enforcement-design.md)
- Geo login alerts: [docs/superpowers/specs/2026-04-23-geo-login-alerts-design.md](superpowers/specs/2026-04-23-geo-login-alerts-design.md)
- Multi-origen de auth (emails transaccionales): [ECOSYSTEM_AUTH_MULTI_ORIGIN.md](ECOSYSTEM_AUTH_MULTI_ORIGIN.md)
- SSO Hub (sincronización cross-domain): [ECOSYSTEM_AUTH_SSO_HUB.md](ECOSYSTEM_AUTH_SSO_HUB.md)

Los planes (con el paso-a-paso exacto de los otros frontends) están en:

- [docs/superpowers/plans/2026-04-22-admin-ban-force-logout.md](superpowers/plans/2026-04-22-admin-ban-force-logout.md) — Tasks 9 y 10 cubren los frontends.
- [docs/superpowers/plans/2026-04-23-single-session-enforcement.md](superpowers/plans/2026-04-23-single-session-enforcement.md) — Tasks 9 y 10 cubren los frontends.

Para Inmobiliaria, seguir el patrón del Task 10 de cada plan (versión compacta sin LoginView dedicado), NO el del Task 9 (versión con LoginView, específica para GrupoReglado).

## Estimación de tiempo total

Para un dev que ya conozca Inmobiliaria_Reglados:

- **Bloque "adaptar a cambios backend"** (puntos 1-5 del frontend): 30-60 minutos. Cambios pequeños, mayormente en `auth.js`.
- **Bloque "SSO Hub"** (añadido 2026-04-24): 1-2 horas por Inmobiliaria + 30 min por el cambio en Grupo (allowlist + tema) + redeploy de ambos.

**Total: medio día**. El mayor coste es coordinar el redeploy de Grupo con el de Inmobiliaria, y probar end-to-end los 4 flujos de sincronización (login cruzado, logout cruzado, verificación por email, handshake al abrir pestaña).
