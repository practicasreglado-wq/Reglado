# Cambios pendientes para Inmobiliaria_Reglados

**Fecha del checklist:** 2026-04-23
**Por qué existe este documento:** entre el 2026-04-22 y el 2026-04-23 se aplicaron varios cambios a ApiLoging y al ecosistema Reglado (GrupoReglado, RegladoEnergy, RegladoIngenieria, RegladoMaps). `Inmobiliaria_Reglados` quedó fuera del alcance porque lo mantiene otro equipo. Cuando ese equipo cierre su iteración, aplicar este checklist para alinear Inmobiliaria con el resto del ecosistema.

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

Los planes (con el paso-a-paso exacto de los otros frontends) están en:

- [docs/superpowers/plans/2026-04-22-admin-ban-force-logout.md](superpowers/plans/2026-04-22-admin-ban-force-logout.md) — Tasks 9 y 10 cubren los frontends.
- [docs/superpowers/plans/2026-04-23-single-session-enforcement.md](superpowers/plans/2026-04-23-single-session-enforcement.md) — Tasks 9 y 10 cubren los frontends.

Para Inmobiliaria, seguir el patrón del Task 10 de cada plan (versión compacta sin LoginView dedicado), NO el del Task 9 (versión con LoginView, específica para GrupoReglado).

## Estimación de tiempo

Para un dev que ya conozca Inmobiliaria_Reglados: **30-60 minutos**. El grueso del tiempo lo lleva localizar el fichero `auth.js` o equivalente y verificar que el patrón de `request()` es similar al de los otros frontends. El cambio en sí son 5-10 líneas de código.
