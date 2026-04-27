# SSO Hub del ecosistema Reglado

**Fecha del documento:** 2026-04-24
**Estado:** Implementación en curso.
**Complementa:** [ECOSYSTEM_AUTH_MULTI_ORIGIN.md](ECOSYSTEM_AUTH_MULTI_ORIGIN.md).

---

## 1. Contexto y motivación

El trabajo de [ECOSYSTEM_AUTH_MULTI_ORIGIN](ECOSYSTEM_AUTH_MULTI_ORIGIN.md) implementó el multi-origen para emails de auth (registro vuelve a Energy, reset vuelve a Maps, etc.) y añadió `visibilitychange` para detectar logins de otra pestaña leyendo la cookie compartida `reglado_auth_token`.

**Limitación detectada en producción:** los dominios del ecosistema tienen eTLD+1 distintos (`regladogroup.com`, `regladoenergy.com`, etc.). El navegador aísla las cookies por dominio, así que la "cookie compartida" solo se comparte entre puertos de `localhost` en dev. En prod, cada dominio tiene su propia copia de la cookie y `syncWithCookie` no detecta nada.

**Constraint del negocio:** no se puede migrar a subdominios del mismo eTLD+1 (ej. `energy.regladogroup.com`).

**Objetivo:** que una sesión iniciada en cualquier dominio del ecosistema se refleje automáticamente en los demás, sin redirect al login central (que generaba "feeling de phishing") y preservando single-session enforcement.

## 2. Arquitectura: Grupo como SSO Hub

Grupo (`regladogroup.com`) actúa como hub central. Los demás dominios son consumidores/proveedores bidireccionales que sincronizan con Grupo:

```
                    ┌────────────────────────┐
                    │   regladogroup.com     │
                    │       (SSO HUB)        │
                    │  /sso-handshake        │
                    │  /sso-store            │
                    │  /sso-logout           │
                    └───────────┬────────────┘
              ┌─────────────────┼─────────────────┐
              │                 │                 │
       ┌──────┴──────┐   ┌──────┴──────┐   ┌──────┴──────┐
       │ energy.com  │   │  maps.com   │   │ ingen...com │
       └─────────────┘   └─────────────┘   └─────────────┘
```

## 3. Páginas nuevas en Grupo (solo frontend)

### 3.1. `/sso-handshake?return=<url>` — Ceder sesión a otro dominio

```
1. Valida `return` contra allowlist (hardcodeada, ver §5).
2. Lee localStorage + cookie de Grupo.
3. Si hay token local → verificación ligera contra /auth/me.
4. Si válido → redirect a `<return>#token=<jwt>`.
5. Si no válido o no hay sesión → redirect a `<return>?sso_failed=1`.
```

Nota: el token viaja en el **fragmento** `#` (no query `?`) para no aparecer en logs del servidor destino ni cabecera `Referer`.

### 3.2. `/sso-store?token=<jwt>&return=<url>` — Recibir sesión desde otro dominio

```
1. Valida `return` contra allowlist.
2. Llama a /auth/me con el token recibido.
3. Si válido → lo guarda en localStorage + cookie de Grupo.
4. Redirect a `<return>`.
```

### 3.3. `/sso-logout?return=<url>` — Limpiar sesión del hub

```
1. Valida `return` contra allowlist.
2. Limpia localStorage + cookie de Grupo (sin revocar backend — ya lo hizo el origen).
3. Redirect a `<return>`.
```

## 4. Cambios en consumidores (Energy, Maps, Ingeniería)

### 4.1. `auth.initialize()` con detección SSO

```
1. Si hay token local → validar vs /auth/me (igual que antes).
2. Si NO hay token local:
   a. Si sessionStorage.sso_attempted está seteado → no hacer nada (evita bucles).
   b. Si no → setear sso_attempted=1 y redirigir a:
      grupo.com/sso-handshake?return=<current_url>
3. Al volver con fragmento #token=... en URL:
   a. Extraer token, guardar en localStorage + cookie.
   b. Limpiar fragmento con history.replaceState.
   c. Limpiar sessionStorage.sso_attempted.
   d. Re-ejecutar initialize para hidratar.
4. Al volver con ?sso_failed=1 → dejar al usuario como invitado. Limpiar flag.
```

### 4.2. Tras login exitoso (LoginModal, RegisterView EmailVerifiedView)

```
1. Obtener token del backend, guardar local.
2. Redirect a grupo.com/sso-store?token=<jwt>&return=<home_o_current>
3. Grupo guarda, vuelve, el otro dominio ya tiene el token Y Grupo también.
```

### 4.3. Tras logout

```
1. Llamar a /auth/logout del backend (revoca token).
2. Limpiar localStorage + cookie locales.
3. Redirect a grupo.com/sso-logout?return=<home>
4. Grupo limpia su sesión y vuelve.
```

## 5. Seguridad

### 5.1. Allowlist hardcodeada de `return` en Grupo

```js
const SSO_ALLOWED_RETURNS = [
  "http://localhost:5173", "http://127.0.0.1:5173",  // Grupo dev
  "http://localhost:5174", "http://127.0.0.1:5174",  // Energy dev
  "http://localhost:5176", "http://127.0.0.1:5176",  // Maps dev
  "http://localhost:5177", "http://127.0.0.1:5177",  // Ingeniería dev
  "https://regladogroup.com", "https://www.regladogroup.com",
  "https://regladoenergy.com",
  // Añadir Maps e Ingeniería prod cuando tengan dominio
];
```

Validación: parse URL del `return`, extraer `scheme://host[:port]`, comparar contra la lista. Cualquier mismatch → rechazar con 400 y no redirigir.

### 5.2. Token en fragmento, no en query

- Fragmento `#token=...`: nunca se envía al servidor, no queda en logs ni en cabecera Referer.
- Query `?token=...`: queda en logs del servidor destino (Nginx/Apache access logs), historial del navegador, y se propaga vía Referer a cualquier recurso que cargue la siguiente página.

### 5.3. Single-session enforcement intacto

- El token compartido es el MISMO JWT (mismo `sid`). No se genera uno nuevo en el handshake.
- Cuando un login rota el `sid`, todos los dominios pierden validez simultáneamente en el siguiente request (middleware devuelve `session expired`).
- Cuando un admin hace ban o force-logout, mismo efecto.

### 5.4. Anti-loop con sessionStorage

- Sin protección: si Grupo también intentara SSO handshake, loop infinito. Por eso Grupo NUNCA inicia handshake — solo responde.
- Sin protección: si Energy hace handshake y Grupo devuelve `sso_failed`, Energy no debe reintentar en esa misma sesión. `sessionStorage.sso_attempted=1` evita el bucle.

## 6. Comportamiento resultante

| Escenario | Resultado |
|---|---|
| Log in en Grupo → abrir Energy | Redirect silencioso → Grupo cede token → Energy logueado. ~300-500ms flash de URL. |
| Log in en Energy → abrir Grupo | Al loguear en Energy, se propagó a Grupo via `/sso-store`. Grupo abre ya logueado. |
| Log in en Energy → abrir Maps | Maps → Grupo/sso-handshake → cede token → Maps logueado. |
| Log out en cualquier dominio | Dominio limpia local + redirect a Grupo/sso-logout → Grupo limpia. Otros dominios descubrirán el 401 al siguiente `/auth/me`. |
| Ban / force-logout / password change | Middleware invalida en TODOS los dominios en el siguiente request. Sin cambios. |
| Usuario no logueado en ningún sitio abre Energy | Energy → Grupo/sso-handshake → `sso_failed=1` → vuelve → muestra home invitado. No reintenta. |

## 7. Orden de implementación

1. [x] Spec (este doc).
2. [ ] Grupo: 3 páginas + rutas + helper de allowlist.
3. [ ] Energy: integrar SSO en initialize + login + logout + App.vue.
4. [ ] Probar end-to-end en dev.
5. [ ] Replicar en Maps (copy/paste adaptado).
6. [ ] Replicar en Ingeniería (copy/paste adaptado).

## 8. Decisiones tomadas

- **Allowlist hardcodeada** (no env var): simplifica auditoría y evita drift entre frontends. Coste: hay que cambiar el código de Grupo al añadir un dominio nuevo.
- **`/sso-logout` incluido en esta iteración**: por seguridad, no deja sesiones vivas en Grupo cuando se cierra desde otro dominio.
- **Token en fragmento, no query**: estándar de la industria para token-in-URL (ver OAuth2 implicit flow).
