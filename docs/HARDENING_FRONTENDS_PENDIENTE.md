# Hardening de Frontends — Tareas Pendientes

**Fecha del documento:** 2026-04-24
**Estado:** Pendiente. Complementa [HARDENING_APILOGING_PENDIENTE.md](HARDENING_APILOGING_PENDIENTE.md) que cubre el backend.
**Condición para retomar:** No bloqueado por nada externo, pueden aplicarse cuando haya ventana.

---

## 1. Contexto

El `HARDENING_APILOGING_PENDIENTE.md` cubre hardening del backend (refresh tokens, cookie HttpOnly, GET→POST en verificación). Este documento recoge las mitigaciones que viven **en el frontend**, detectadas en auditoría informal del 2026-04-24 centrada en "¿puede alguien robar el token y acceder a la sesión?".

Hoy el token se almacena en:

- **localStorage** (`energy_auth_token`, `maps_auth_token`, `ingenieria_auth_token`, `auth_token`) — accesible por cualquier JS de la página.
- **Cookie compartida** `reglado_auth_token` con `SameSite=Lax` + `Secure` (HTTPS), **sin** `HttpOnly` porque el frontend la lee para construir el header `Authorization: Bearer`.

El vector más realista es **XSS**: si alguien ejecuta JavaScript arbitrario en la página, roba el token en un renglón. Los items de este documento cierran esa ventana por capas.

## 2. Tareas pendientes

### 🔴 F1 — Content Security Policy (CSP) en los `index.html`

**Estado:** ✅ Hecho. Los 4 frontends internos (Energy, Grupo, Maps, Ingenieria) tienen `<meta http-equiv="Content-Security-Policy">` en su `index.html`. Pendiente coordinar con Inmobiliaria.

**Severidad:** Alta (mitiga XSS).
**Motivación:** Hoy los `index.html` de los frontends (Energy, Grupo, Maps, Ingenieria) **no tienen CSP**. Cualquier vulnerabilidad de escape de template o dependencia comprometida puede inyectar `<script>`/`eval` arbitrario y leer `localStorage`.

**Qué implementar:**

Añadir un `<meta http-equiv="Content-Security-Policy">` al `<head>` de cada `index.html` con una política restrictiva. Ejemplo base (ajustar recursos reales):

```html
<meta http-equiv="Content-Security-Policy" content="
  default-src 'self';
  script-src 'self';
  style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;
  font-src 'self' https://fonts.gstatic.com;
  img-src 'self' data: https:;
  connect-src 'self' https://regladogroup.com https://api.regladogroup.com;
  frame-ancestors 'none';
  base-uri 'none';
  form-action 'self';
">
```

**Puntos a ajustar por proyecto:**

- `connect-src` debe listar los endpoints XHR reales (ApiLoging, chatbot, mapas, etc.).
- `style-src 'unsafe-inline'` suele ser necesario para Vue (estilos inyectados dinámicamente por componentes `scoped`). Investigar si se puede quitar.
- Si el chatbot se auto-hospeda (ver F2), `script-src 'self'` basta. Si se mantiene el CDN, añadir `script-src 'self' https://chatbot.regladogroup.com`.

**Proyectos afectados:** los 4 frontends internos (Energy, Grupo, Maps, Ingenieria) + coordinar con Inmobiliaria.

**Coste estimado:** 1-2 horas por proyecto, incluyendo encontrar la lista exacta de orígenes legítimos que usa cada app y ajustar hasta que no salten violaciones en consola.

**Verificación:** abrir DevTools → Console durante navegación normal. Cualquier "Refused to load..." indica un origen que falta en la política. Iterar hasta que no haya violaciones.

---

### 🟡 F2 — Auditoría de dependencias npm (supply chain)

**Estado:** ✅ Primera pasada hecha el 2026-04-30. Los 4 frontends internos en **0 vulnerabilidades** (`npm audit`). Se subió Vite de 5.4.x → 6.4.2 en Maps, Ingenieria y Energy (Energy estaba además desincronizado: `package.json` pedía Vite 8 pero el lock seguía en 5.4.21). **CI activa desde 2026-04-30** en [`.github/workflows/audit.yml`](../.github/workflows/audit.yml): ejecuta `npm audit --audit-level=high` en los 4 frontends en cada push y PR a `main`, bloqueando el merge si aparece una vuln high/critical. Dependabot/Snyk siguen sin implementar — opcional como mejora futura (PRs automáticas de bumps).

**Baseline de versiones acordada (usar en proyectos nuevos del ecosistema):**

- `vite`: `^6.4.2`
- `@vitejs/plugin-vue`: `^5.2.0`

Esta es la versión mínima a la que están alineados los 4 frontends internos (Grupo, Energy, Maps, Ingenieria) tras F2. Cualquier proyecto nuevo del ecosistema Reglado debe partir de aquí para evitar volver a la situación previa de versiones dispersas. Si en el futuro se decide subir la baseline (p. ej. a Vite 7.x), hacerlo coordinadamente en todos los frontends de una sola pasada — y actualizar este apartado.

**Inmobiliaria_Reglados:** está actualmente en `vite ^7.3.1` + `@vitejs/plugin-vue ^6.0.4` (por encima de la baseline). Cuando el equipo externo esté disponible, **decidir conjuntamente** si: (a) bajan Inmobiliaria a la baseline para alinear, o (b) el ecosistema entero sube a Vite 7.x. Hasta entonces, no se toca. Ver también [PENDIENTE_INMOBILIARIA_APILOGING.md](PENDIENTE_INMOBILIARIA_APILOGING.md).

**Severidad:** Media.
**Motivación:** Una dependencia transitiva maliciosa (caso clásico: `ua-parser-js` 0.7.29 en 2021, `node-ipc` en 2022) puede ejecutar código en build o en runtime. Sin control, cualquier `npm install` puede añadir un payload que lea `localStorage` al cargarse la app.

**Qué implementar:**

1. **En cada frontend** (Energy, Grupo, Maps, Ingenieria):
   - Ejecutar `npm audit` y resolver las vulnerabilidades detectadas. Bloquear PR si quedan high/critical.
   - Fijar `package-lock.json` en el repo (ya lo está — verificar que no se borre).
   - Considerar activar Dependabot / Renovate para PRs automáticas de bumps de seguridad.
2. **Política de adopción de dependencias:**
   - Revisar dependencias nuevas antes de añadir (npmjs.com stats, weekly downloads, última publicación, maintainer).
   - Preferir libs con pocas sub-dependencias (menos superficie).
3. **Opcional:** integrar Snyk o Socket.dev en CI para escaneo continuo. Gratis para proyectos abiertos, planes de pago para privados.

**Proyectos afectados:** los 4 frontends internos.

**Coste estimado:** 1-2 horas la primera pasada de auditoría por proyecto, + ~30 min/mes de mantenimiento si se adopta Dependabot.

**Verificación:** `npm audit --audit-level=high` no debe devolver findings en el CI.

---

### 🟢 F3 — Reducir superficie del token en localStorage

**Estado:** ✅ Hecho en los 4 frontends internos. Los `services/auth.js` de Energy, Grupo, Maps e Ingenieria ya no usan `localStorage` para el JWT — solo la cookie compartida. Pendiente coordinar con Inmobiliaria.

**Severidad:** Leve (mejora incremental mientras no se haga F1 completo ni el L7 del backend).
**Motivación:** Actualmente el token está tanto en `localStorage` como en cookie. La localStorage es estrictamente más débil (siempre accesible a JS). Si se elimina y solo se usa la cookie no-HttpOnly, XSS sigue pudiendo leer, pero es una línea menos de código y un vector menos.

**Qué implementar:**

- Eliminar `localStorage.setItem(TOKEN_KEY, ...)` y `localStorage.getItem(TOKEN_KEY)` en `services/auth.js` de los 4 frontends.
- Dejar solo la cookie compartida como fuente de verdad.
- El `state.token` reactivo sigue existiendo en memoria durante la vida del documento; no es un regresión.

**Proyectos afectados:** los 4 frontends internos.

**Coste estimado:** 15-30 min por frontend.

**Recomendación:** considerar hacerlo junto con F1 (CSP) en la misma iteración, ya que ambos tocan el mismo flujo. Si se va a hacer el L7 del backend a medio plazo (cookie HttpOnly), F3 queda obsoleto — saltárselo y esperar.

---

### 🟡 F4 — Security Headers HTTP en `.htaccess`

**Estado:** ✅ Hecho el 2026-05-04 en Energy, Grupo y Maps. Ingeniería pospuesto (en desarrollo, sin escanear). Inmobiliaria pendiente coordinar (externo). BienesRaices y Chatbot fuera del alcance actual.

**Severidad:** Media (defensa en profundidad ante clickjacking, MIME sniffing, downgrade HTTP, filtrado de Referer y abuso de APIs del navegador).
**Motivación:** Auditoría con [securityheaders.com](https://securityheaders.com/) dio nota **D** a `regladogroup.com`, `regladoenergy.com` y `regladomaps.com`. Tienen CSP (F1) pero les faltaban los 5 headers HTTP que sí emite ApiLoging desde PHP en `utils/Security.php`. Apache servía los frontends estáticos sin ningún header adicional.

**Qué se implementó:**

Bloque idéntico al inicio del `.htaccess` de los tres proyectos (en `public/.htaccess`, que Vite copia tal cual a `dist/`):

```apache
# Security headers (hardening F4 - 2026-05-04)
<IfModule mod_headers.c>
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Permissions-Policy "geolocation=(), camera=(), microphone=(), payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=(), fullscreen=(self)"
</IfModule>
```

**Decisiones tomadas:**

- **HSTS sin `preload`**: `max-age=31536000; includeSubDomains` cubre apex + subdominios HTTPS (relevante para `chatbot.regladogroup.com`). Se descarta `preload` por irreversibilidad: revertirlo requiere quitar el dominio de la lista de Chrome/Firefox y esperar meses. Si en el futuro se quiere A+ en securityheaders, se valora añadirlo entonces.
- **`X-Frame-Options: SAMEORIGIN`** (no `DENY`): permite el visor de PDFs en iframe `blob:` de Inmobiliaria (mismo origen). Para los demás frontends da igual — no embeben nada. Si en el futuro se quisiera embeber un proyecto en otro (ej. Maps dentro de Group), se cambiaría por `Content-Security-Policy: frame-ancestors` con la lista permitida.
- **Permissions-Policy con `fullscreen=(self)`**: ningún proyecto del ecosistema usa `navigator.geolocation`, `mediaDevices`, etc. (verificado con grep). La geolocalización de ApiLoging es **server-side por IP** (`GeoLite2-Country.mmdb`), no API del navegador, por lo que `geolocation=()` no la afecta.
- **Conflicto Apache↔PHP en Group (asumido)**: Group rewrites `/auth` y `/api/profile` → `api_backend/index.php` (ApiLoging local), que ya emite sus propios headers desde `Security.php`. `Header always set` los pisa para esos endpoints, dejando valores **menos estrictos pero seguros** (ej. `X-Frame-Options: SAMEORIGIN` en vez de `DENY`, `Referrer-Policy: strict-origin-when-cross-origin` en vez de `no-referrer`). Decisión: aceptar el override, no añadir `<If>` de exclusión. Razones: (a) la CSP de PHP (`default-src 'none'; frame-ancestors 'none'`) **no se pisa** porque Apache no añade CSP; (b) los headers degradados son irrelevantes para respuestas JSON (no hay clickjacking sobre JSON, las APIs no llevan tokens en URL); (c) la simplicidad y consistencia entre los tres frontends gana sobre defensa en profundidad redundante.

**Aislamiento cross-proyecto:** los headers están encapsulados en cada eTLD+1. HSTS no se filtra a otros dominios del ecosistema (Maps, Energy, etc. son apex distintos), Permissions-Policy y X-Frame-Options son por origen, y Referrer-Policy `strict-origin-when-cross-origin` no rompe el SSO porque éste viaja por fragment-token (los fragments nunca se envían en `Referer`).

**Proyectos afectados:** Energy, Grupo, Maps. Pendiente Ingeniería (cuando salga de desarrollo) e Inmobiliaria (coordinar externo).

**Coste estimado:** ~5 minutos por proyecto, incluyendo verificación post-deploy.

**Verificación:** escaneo en [securityheaders.com](https://securityheaders.com/) tras `npm run build` + deploy. Esperado: subida de **D → A**. No se alcanza A+ porque falta `preload` en HSTS (descartado).

---

## 3. Matriz de impacto por proyecto

| Tarea | Energy | Grupo | Maps | Ingeniería | Inmobiliaria |
|---|---|---|---|---|---|
| F1 — CSP | ✅ Hecho | ✅ Hecho | ✅ Hecho | ✅ Hecho | ⏳ (externo) |
| F2 — npm audit | ✅ Hecho | ✅ Hecho | ✅ Hecho | ✅ Hecho | ⏳ (externo) |
| F3 — Quitar localStorage | ✅ Hecho | ✅ Hecho | ✅ Hecho | ✅ Hecho | ⏳ (externo) |
| F4 — Security headers HTTP | ✅ Hecho | ✅ Hecho | ✅ Hecho | ⏸️ (en desarrollo) | ⏳ (externo) |

Inmobiliaria_Reglados aparece como **(externo)** por el mismo motivo que en [HARDENING_APILOGING_PENDIENTE.md](HARDENING_APILOGING_PENDIENTE.md): lo mantiene otra persona y habrá que coordinar.

## 4. Decisiones descartadas

### Self-host o SRI del widget del chatbot

Se evaluó el 2026-04-24 y se descartó. Razonamiento: `chatbot.regladogroup.com` está bajo el mismo control administrativo que los frontends y que ApiLoging, no es un tercero real. Tratarlo como CDN externo (con self-host o SRI) es sobre-ingeniería: el riesgo que cierra ("alguien compromete el servidor del chatbot y altera el widget") implica que ya tienen acceso a la infra de Reglado, lo cual es un problema mayor que el robo del token.

Se mantiene la carga vía `<script src="https://chatbot.regladogroup.com/widget/chatbotReglado.js">`. Si en el futuro se quiere añadir defensa en profundidad contra ese vector, la opción menos costosa sería SRI con `crossorigin="anonymous"`, previa verificación de que el server del chatbot devuelve `Access-Control-Allow-Origin` en `/widget/*`.

## 5. Orden recomendado cuando se retome

1. **F2 — npm audit** — primero. Cero impacto multi-proyecto, solo detecta lo que ya hay. Útil para saber si hay alguna bomba de tiempo.
2. **F1 — CSP** — después. Es el de mayor impacto de seguridad. Requiere más iteración pero protege ante XSS.
3. **F4 — Security headers HTTP** — barato y rápido (~5 min/proyecto). Eleva la nota en securityheaders.com de D a A y cubre clickjacking, MIME sniffing y downgrade HTTP. Hacer junto con F1 si se aborda un frontend nuevo.
4. **F3 — Quitar localStorage** — solo si no está previsto atacar **L7** del backend (cookie HttpOnly) a medio plazo. Si L7 está en el roadmap, saltarse F3.
