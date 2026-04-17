# RegladoIngenieria Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Crear desde cero el proyecto RegladoIngenieria — web corporativa de ingeniería industrial con auth centralizada (ApiLoging), área de clientes protegida y backend PHP.

**Architecture:** Vue 3.5 SPA con Vue Router 4. Rutas protegidas via `router.beforeEach` que verifican token en localStorage. La funcionalidad de parcelas industriales se encapsula en `ParcelasContainer.vue` (contrato de integración flexible). Backend PHP puro con PDO MySQL.

**Tech Stack:** Vue 3.5, Vue Router 4.4, Vite 5.4, PHP 8+, MySQL (BD: `ingenieria`), Inter (Google Fonts), ApiLoging en `gruporeglado.com`

---

## File Map

| Archivo | Responsabilidad |
|---------|----------------|
| `package.json` | Dependencias y scripts |
| `vite.config.js` | Config Vite mínima |
| `index.html` | Entry HTML con SEO base |
| `.env.example` | Variables de entorno frontend |
| `src/main.js` | Bootstrap Vue app |
| `src/App.vue` | Shell con `<RouterView>` |
| `src/assets/main.css` | Variables CSS, tipografía Inter, utilidades |
| `src/router/index.js` | Rutas + guard de auth |
| `src/services/auth.js` | Estado reactivo de sesión, token `ingenieria_auth_token` |
| `src/services/api.js` | Llamadas al backend PHP |
| `src/components/Header.vue` | Navegación principal |
| `src/components/Footer.vue` | Footer corporativo (skill-footer-standar) |
| `src/components/ParcelasContainer.vue` | Punto de integración app parcelas |
| `src/pages/Home.vue` | Hero + servicios destacados + CTA |
| `src/pages/Servicios.vue` | Cards de servicios industriales |
| `src/pages/Proyectos.vue` | Portfolio estático |
| `src/pages/Nosotros.vue` | Empresa y equipo |
| `src/pages/Contacto.vue` | Formulario → `contact.php` |
| `src/pages/AuthCallback.vue` | Recibe `?token=` de ApiLoging |
| `src/pages/AreaClientes.vue` | Monta `ParcelasContainer` (protegida) |
| `src/pages/Admin.vue` | Placeholder "en construcción" (protegida) |
| `src/pages/NotFound.vue` | 404 |
| `BACKEND/bootstrap.php` | Carga `.env` del backend |
| `BACKEND/db.php` | Conexión PDO a BD `ingenieria` |
| `BACKEND/security.php` | Headers CORS + helpers de seguridad |
| `BACKEND/auth.php` | Verificación JWT HS256 |
| `BACKEND/contact.php` | Guarda consulta en BD + envía email |
| `BACKEND/.env.example` | Variables de entorno backend |
| `BACKEND/.htaccess` | Bloquea acceso directo al directorio |
| `BACKEND/sql/schema.sql` | Esquema BD `ingenieria` |
| `.htaccess` (raíz) | Reescritura SPA para Apache/XAMPP |

---

## Task 1: Scaffold del proyecto

**Files:**
- Create: `RegladoIngenieria/package.json`
- Create: `RegladoIngenieria/vite.config.js`
- Create: `RegladoIngenieria/.gitignore`
- Create: `RegladoIngenieria/.env.example`

- [ ] **Step 1: Crear directorio raíz**

```bash
mkdir -p c:/xampp/htdocs/Reglado/RegladoIngenieria/src/pages
mkdir -p c:/xampp/htdocs/Reglado/RegladoIngenieria/src/components
mkdir -p c:/xampp/htdocs/Reglado/RegladoIngenieria/src/services
mkdir -p c:/xampp/htdocs/Reglado/RegladoIngenieria/src/router
mkdir -p c:/xampp/htdocs/Reglado/RegladoIngenieria/src/assets
mkdir -p c:/xampp/htdocs/Reglado/RegladoIngenieria/public
mkdir -p c:/xampp/htdocs/Reglado/RegladoIngenieria/BACKEND/sql
```

- [ ] **Step 2: Crear package.json**

Contenido de `RegladoIngenieria/package.json`:
```json
{
  "name": "reglado-ingenieria-web",
  "private": true,
  "version": "1.0.0",
  "type": "module",
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "preview": "vite preview"
  },
  "dependencies": {
    "vue": "^3.5.0",
    "vue-router": "^4.4.0"
  },
  "devDependencies": {
    "@vitejs/plugin-vue": "^5.1.0",
    "vite": "^5.4.0"
  },
  "engines": {
    "node": ">=18"
  }
}
```

- [ ] **Step 3: Crear vite.config.js**

Contenido de `RegladoIngenieria/vite.config.js`:
```javascript
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
export default defineConfig({ plugins: [vue()] })
```

- [ ] **Step 4: Crear .gitignore**

Contenido de `RegladoIngenieria/.gitignore`:
```
node_modules/
dist/
.env
.env.local
.env.production
BACKEND/.env
```

- [ ] **Step 5: Crear .env.example**

Contenido de `RegladoIngenieria/.env.example`:
```
VITE_AUTH_API_URL=https://gruporeglado.com
VITE_TOKEN_KEY=ingenieria_auth_token
VITE_BACKEND_BASE=http://localhost/Reglado/RegladoIngenieria/BACKEND
```

- [ ] **Step 6: Instalar dependencias**

```bash
cd c:/xampp/htdocs/Reglado/RegladoIngenieria && npm install
```

Expected: carpeta `node_modules/` creada, sin errores.

- [ ] **Step 7: Commit**

```bash
cd c:/xampp/htdocs/Reglado && git add RegladoIngenieria/package.json RegladoIngenieria/vite.config.js RegladoIngenieria/.gitignore RegladoIngenieria/.env.example
git commit -m "feat(ingenieria): scaffold inicial del proyecto"
```

---

## Task 2: index.html y CSS base

**Files:**
- Create: `RegladoIngenieria/index.html`
- Create: `RegladoIngenieria/src/assets/main.css`

- [ ] **Step 1: Crear index.html**

Contenido de `RegladoIngenieria/index.html`:
```html
<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reglado Ingeniería | Consultoría de Ingeniería Industrial</title>
    <meta name="description" content="Consultoría especializada en ingeniería industrial. Análisis técnicos, proyectos y soluciones para empresas del sector." />
    <meta name="robots" content="index, follow" />
    <meta name="author" content="Reglado Ingeniería" />
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="Reglado Ingeniería" />
    <meta property="og:title" content="Reglado Ingeniería | Consultoría de Ingeniería Industrial" />
    <meta property="og:description" content="Consultoría especializada en ingeniería industrial." />
    <meta property="og:url" content="https://regladoingenieria.com/" />
    <meta name="twitter:card" content="summary_large_image" />
    <link rel="icon" type="image/png" href="/favicon.png" />
    <link rel="canonical" href="https://regladoingenieria.com/" />
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "Reglado Ingeniería",
      "url": "https://regladoingenieria.com/",
      "description": "Consultoría especializada en ingeniería industrial."
    }
    </script>
  </head>
  <body>
    <div id="app"></div>
    <script type="module" src="/src/main.js"></script>
  </body>
</html>
```

- [ ] **Step 2: Crear main.css**

Contenido de `RegladoIngenieria/src/assets/main.css`:
```css
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --steel: #4a9eff;
  --steel-dark: #2b7de9;
  --steel-light: #e8f2ff;
  --bg: #ffffff;
  --bg-soft: #f5f7fa;
  --border: #e0e4ea;
  --text: #1a1f2e;
  --text-muted: #6b7280;
  --radius: 12px;
  --radius-sm: 8px;
  --shadow: 0 1px 3px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.05);
  --shadow-lg: 0 8px 32px rgba(74,158,255,.14);
  --transition: .2s ease;
}

html { scroll-behavior: smooth; }

body {
  font-family: 'Inter', sans-serif;
  background: var(--bg);
  color: var(--text);
  line-height: 1.6;
  -webkit-font-smoothing: antialiased;
}

a { color: inherit; text-decoration: none; }
img { max-width: 100%; display: block; }

.container {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
}

.section { padding: 80px 0; }
.section-sm { padding: 48px 0; }
.bg-soft { background: var(--bg-soft); }

/* Typography */
.h1 { font-size: clamp(2rem, 4vw, 3.25rem); font-weight: 700; line-height: 1.15; }
.h2 { font-size: clamp(1.5rem, 3vw, 2.25rem); font-weight: 700; line-height: 1.2; }
.h3 { font-size: clamp(1.125rem, 2vw, 1.375rem); font-weight: 600; line-height: 1.3; }
.lead { font-size: 1.125rem; color: var(--text-muted); line-height: 1.7; }
.text-muted { color: var(--text-muted); }
.accent { color: var(--steel); }
.section-title { margin-bottom: 40px; }

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  border-radius: var(--radius-sm);
  font-family: 'Inter', sans-serif;
  font-weight: 600;
  font-size: 0.9375rem;
  cursor: pointer;
  border: 2px solid transparent;
  text-decoration: none;
  transition: all var(--transition);
  white-space: nowrap;
}
.btn.primary { background: var(--steel); color: #fff; border-color: var(--steel); }
.btn.primary:hover { background: var(--steel-dark); border-color: var(--steel-dark); transform: translateY(-1px); box-shadow: var(--shadow-lg); }
.btn.outline { background: transparent; border-color: var(--steel); color: var(--steel); }
.btn.outline:hover { background: var(--steel); color: #fff; }
.btn.ghost { background: transparent; border-color: transparent; color: var(--text-muted); }
.btn.ghost:hover { color: var(--steel); }

/* Cards */
.card {
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 32px;
  box-shadow: var(--shadow);
  transition: box-shadow var(--transition), transform var(--transition);
}
.card:hover { box-shadow: var(--shadow-lg); transform: translateY(-2px); }

/* Grids */
.grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; }
.grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 32px; }

/* Badge */
.badge {
  display: inline-block;
  padding: 4px 12px;
  background: var(--steel-light);
  color: var(--steel-dark);
  border-radius: 999px;
  font-size: 0.8125rem;
  font-weight: 600;
  letter-spacing: .025em;
  text-transform: uppercase;
}

/* Forms */
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-weight: 500; font-size: 0.9375rem; }
.form-group input,
.form-group textarea,
.form-group select {
  padding: 10px 14px;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  font-family: 'Inter', sans-serif;
  font-size: 0.9375rem;
  background: var(--bg);
  color: var(--text);
  transition: border-color var(--transition);
  outline: none;
}
.form-group input:focus,
.form-group textarea:focus { border-color: var(--steel); box-shadow: 0 0 0 3px rgba(74,158,255,.12); }
.form-group textarea { resize: vertical; min-height: 120px; }

/* Alerts */
.alert { padding: 12px 16px; border-radius: var(--radius-sm); font-size: 0.9375rem; }
.alert.success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
.alert.error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

/* Responsive */
@media (max-width: 768px) {
  .section { padding: 56px 0; }
  .section-sm { padding: 32px 0; }
}
```

- [ ] **Step 3: Commit**

```bash
cd c:/xampp/htdocs/Reglado && git add RegladoIngenieria/index.html RegladoIngenieria/src/assets/main.css
git commit -m "feat(ingenieria): index.html y CSS base con paleta azul acero"
```

---

## Task 3: Servicio de autenticación y API

**Files:**
- Create: `RegladoIngenieria/src/services/auth.js`
- Create: `RegladoIngenieria/src/services/api.js`

- [ ] **Step 1: Crear auth.js**

Contenido de `RegladoIngenieria/src/services/auth.js`:
```javascript
import { reactive } from "vue";

const API_BASE = import.meta.env.VITE_AUTH_API_URL || "https://gruporeglado.com";
const TOKEN_KEY = import.meta.env.VITE_TOKEN_KEY || "ingenieria_auth_token";
const COOKIE_MAX_AGE = 60 * 60 * 24 * 7;

const state = reactive({
  token: localStorage.getItem(TOKEN_KEY) || getCookie(TOKEN_KEY) || "",
  user: null,
  loading: false,
});

function authHeaders() {
  return state.token ? { Authorization: `Bearer ${state.token}` } : {};
}

async function request(path, options = {}) {
  const response = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers: { "Content-Type": "application/json", ...(options.headers || {}) },
  });
  let payload = {};
  try { payload = await response.json(); } catch { payload = {}; }
  if (!response.ok) throw new Error(payload.error || payload.message || "La solicitud no se pudo completar.");
  return payload;
}

function setToken(token) {
  state.token = token || "";
  if (state.token) {
    localStorage.setItem(TOKEN_KEY, state.token);
    setCookie(TOKEN_KEY, state.token, COOKIE_MAX_AGE);
  } else {
    localStorage.removeItem(TOKEN_KEY);
    clearCookie(TOKEN_KEY);
  }
}

function setSession(token, user = null) {
  setToken(token);
  state.user = user;
}

function clearSession() {
  setToken("");
  state.user = null;
}

async function initialize() {
  if (!state.token) {
    const cookieToken = getCookie(TOKEN_KEY);
    if (cookieToken) setToken(cookieToken);
  }
  if (!state.token) { state.user = null; return; }

  state.loading = true;
  try {
    const payload = await request("/auth/me", { method: "GET", headers: authHeaders() });
    state.user = payload.user || null;
  } catch {
    clearSession();
  } finally {
    state.loading = false;
  }
}

async function logout() {
  try {
    if (state.token) {
      await request("/auth/logout", { method: "POST", headers: authHeaders() });
    }
  } finally {
    clearSession();
    window.location.href = import.meta.env.VITE_AUTH_API_URL || "https://gruporeglado.com";
  }
}

export const auth = { state, setSession, clearSession, initialize, logout };

function setCookie(name, value, maxAge) {
  document.cookie = `${name}=${encodeURIComponent(value)}; Max-Age=${maxAge}; Path=/; SameSite=Lax`;
}
function clearCookie(name) {
  document.cookie = `${name}=; Max-Age=0; Path=/; SameSite=Lax`;
}
function getCookie(name) {
  const prefix = `${name}=`;
  for (const part of (document.cookie || "").split("; ")) {
    if (part.startsWith(prefix)) return decodeURIComponent(part.slice(prefix.length));
  }
  return "";
}
```

- [ ] **Step 2: Crear api.js**

Contenido de `RegladoIngenieria/src/services/api.js`:
```javascript
const BACKEND_BASE = import.meta.env.VITE_BACKEND_BASE || "http://localhost/Reglado/RegladoIngenieria/BACKEND";

export async function submitContact(data) {
  const response = await fetch(`${BACKEND_BASE}/contact.php`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data),
  });
  const payload = await response.json();
  if (!response.ok) throw new Error(payload.message || "Error al enviar el formulario.");
  return payload;
}
```

- [ ] **Step 3: Commit**

```bash
cd c:/xampp/htdocs/Reglado && git add RegladoIngenieria/src/services/
git commit -m "feat(ingenieria): servicios auth y api"
```

---

## Task 4: Router con guard de autenticación

**Files:**
- Create: `RegladoIngenieria/src/router/index.js`

- [ ] **Step 1: Crear router/index.js**

Contenido de `RegladoIngenieria/src/router/index.js`:
```javascript
import { createRouter, createWebHistory } from "vue-router";
import { auth } from "../services/auth.js";
import Home from "../pages/Home.vue";
import Servicios from "../pages/Servicios.vue";
import Proyectos from "../pages/Proyectos.vue";
import Nosotros from "../pages/Nosotros.vue";
import Contacto from "../pages/Contacto.vue";
import AreaClientes from "../pages/AreaClientes.vue";
import Admin from "../pages/Admin.vue";
import AuthCallback from "../pages/AuthCallback.vue";
import NotFound from "../pages/NotFound.vue";

const routes = [
  { path: "/", component: Home },
  { path: "/servicios", component: Servicios },
  { path: "/proyectos", component: Proyectos },
  { path: "/nosotros", component: Nosotros },
  { path: "/contacto", component: Contacto },
  { path: "/area-clientes", component: AreaClientes, meta: { requiresAuth: true } },
  { path: "/admin", component: Admin, meta: { requiresAuth: true } },
  { path: "/auth/callback", component: AuthCallback },
  { path: "/:pathMatch(.*)*", component: NotFound },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() { return { top: 0 }; },
});

router.beforeEach(async (to) => {
  if (!to.meta.requiresAuth) return true;

  if (!auth.state.token) {
    await auth.initialize();
  }

  if (!auth.state.token) {
    const callbackUrl = encodeURIComponent(window.location.origin + "/auth/callback");
    window.location.href = `${import.meta.env.VITE_AUTH_API_URL || "https://gruporeglado.com"}/login?redirect=${callbackUrl}`;
    return false;
  }

  return true;
});

export default router;
```

- [ ] **Step 2: Commit**

```bash
cd c:/xampp/htdocs/Reglado && git add RegladoIngenieria/src/router/
git commit -m "feat(ingenieria): router con guard de autenticacion"
```

---

## Task 5: App.vue y main.js

**Files:**
- Create: `RegladoIngenieria/src/App.vue`
- Create: `RegladoIngenieria/src/main.js`

- [ ] **Step 1: Crear App.vue**

Contenido de `RegladoIngenieria/src/App.vue`:
```vue
<template>
  <div id="layout">
    <Header />
    <RouterView />
    <Footer />
  </div>
</template>

<script setup>
import { onMounted } from "vue";
import { auth } from "./services/auth.js";
import Header from "./components/Header.vue";
import Footer from "./components/Footer.vue";

onMounted(() => {
  auth.initialize();
});
</script>

<style>
#layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}
#layout > main,
#layout > section {
  flex: 1;
}
</style>
```

- [ ] **Step 2: Crear main.js**

Contenido de `RegladoIngenieria/src/main.js`:
```javascript
import { createApp } from "vue";
import App from "./App.vue";
import router from "./router/index.js";
import "./assets/main.css";

createApp(App).use(router).mount("#app");
```

- [ ] **Step 3: Verificar que el proyecto arranca**

```bash
cd c:/xampp/htdocs/Reglado/RegladoIngenieria && npm run dev
```

Expected: servidor en `http://localhost:5173` — pantalla en blanco sin errores de consola (las páginas no existen aún).

- [ ] **Step 4: Commit**

```bash
cd c:/xampp/htdocs/Reglado && git add RegladoIngenieria/src/App.vue RegladoIngenieria/src/main.js
git commit -m "feat(ingenieria): App.vue y main.js"
```

---

## Task 6: Header

**Files:**
- Create: `RegladoIngenieria/src/components/Header.vue`

- [ ] **Step 1: Crear Header.vue**

Contenido de `RegladoIngenieria/src/components/Header.vue`:
```vue
<template>
  <header class="site-header">
    <div class="container header-inner">
      <router-link to="/" class="logo">
        <span class="logo-text">Reglado <strong>Ingeniería</strong></span>
      </router-link>

      <nav class="nav-desktop" aria-label="Navegación principal">
        <router-link to="/servicios">Servicios</router-link>
        <router-link to="/proyectos">Proyectos</router-link>
        <router-link to="/nosotros">Nosotros</router-link>
        <router-link to="/contacto">Contacto</router-link>
      </nav>

      <div class="header-actions">
        <template v-if="auth.state.user">
          <router-link to="/area-clientes" class="btn outline btn-sm">Área Clientes</router-link>
          <button class="btn ghost btn-sm" @click="auth.logout()">Salir</button>
        </template>
        <template v-else>
          <router-link to="/area-clientes" class="btn primary btn-sm">Acceder</router-link>
        </template>
      </div>

      <button class="nav-toggle" :class="{ open: mobileOpen }" @click="mobileOpen = !mobileOpen" aria-label="Menú">
        <span></span><span></span><span></span>
      </button>
    </div>

    <nav class="nav-mobile" :class="{ open: mobileOpen }" aria-label="Navegación móvil">
      <router-link to="/servicios" @click="mobileOpen = false">Servicios</router-link>
      <router-link to="/proyectos" @click="mobileOpen = false">Proyectos</router-link>
      <router-link to="/nosotros" @click="mobileOpen = false">Nosotros</router-link>
      <router-link to="/contacto" @click="mobileOpen = false">Contacto</router-link>
      <template v-if="auth.state.user">
        <router-link to="/area-clientes" @click="mobileOpen = false">Área Clientes</router-link>
        <button @click="auth.logout()">Salir</button>
      </template>
      <template v-else>
        <router-link to="/area-clientes" @click="mobileOpen = false">Acceder</router-link>
      </template>
    </nav>
  </header>
</template>

<script setup>
import { ref } from "vue";
import { auth } from "../services/auth.js";
const mobileOpen = ref(false);
</script>

<style scoped>
.site-header {
  position: sticky;
  top: 0;
  z-index: 100;
  background: rgba(255,255,255,.95);
  backdrop-filter: blur(8px);
  border-bottom: 1px solid var(--border);
}
.header-inner {
  display: flex;
  align-items: center;
  gap: 32px;
  height: 64px;
}
.logo { display: flex; align-items: center; }
.logo-text { font-size: 1.125rem; color: var(--text); }
.logo-text strong { color: var(--steel); }
.nav-desktop { display: flex; gap: 28px; margin-right: auto; }
.nav-desktop a { font-size: 0.9375rem; font-weight: 500; color: var(--text-muted); transition: color var(--transition); }
.nav-desktop a:hover,
.nav-desktop a.router-link-active { color: var(--steel); }
.header-actions { display: flex; gap: 8px; align-items: center; }
.btn-sm { padding: 8px 16px; font-size: 0.875rem; }
.nav-toggle { display: none; flex-direction: column; gap: 5px; background: none; border: none; cursor: pointer; padding: 4px; }
.nav-toggle span { display: block; width: 22px; height: 2px; background: var(--text); border-radius: 2px; transition: all var(--transition); }
.nav-mobile { display: none; flex-direction: column; padding: 16px 24px 20px; border-top: 1px solid var(--border); gap: 4px; }
.nav-mobile a, .nav-mobile button { padding: 10px 0; font-size: 1rem; font-weight: 500; color: var(--text-muted); background: none; border: none; cursor: pointer; text-align: left; transition: color var(--transition); }
.nav-mobile a:hover, .nav-mobile button:hover, .nav-mobile a.router-link-active { color: var(--steel); }

@media (max-width: 768px) {
  .nav-desktop, .header-actions { display: none; }
  .nav-toggle { display: flex; margin-left: auto; }
  .nav-mobile.open { display: flex; }
}
</style>
```

- [ ] **Step 2: Verificar header en http://localhost:5173**

Navegar a `http://localhost:5173`. Expected: header sticky con logo "Reglado Ingeniería", links de nav, botón "Acceder". En móvil (<768px): hamburger menu funcional.

- [ ] **Step 3: Commit**

```bash
cd c:/xampp/htdocs/Reglado && git add RegladoIngenieria/src/components/Header.vue
git commit -m "feat(ingenieria): Header con nav responsive y auth state"
```

---

## Task 7: Footer (skill-footer-standar)

**Files:**
- Create: `RegladoIngenieria/src/components/Footer.vue`

- [ ] **Step 1: Invocar skill-footer-standar**

Invocar el skill `skill-footer-standar` para generar el footer corporativo estándar del ecosistema Reglado, adaptado al proyecto RegladoIngenieria con los siguientes datos:
- Nombre: "Reglado Ingeniería"
- Email: `info@regladoingenieria.com`
- Acento: `--steel: #4a9eff`

Guardar el resultado en `RegladoIngenieria/src/components/Footer.vue`.

- [ ] **Step 2: Commit**

```bash
cd c:/xampp/htdocs/Reglado && git add RegladoIngenieria/src/components/Footer.vue
git commit -m "feat(ingenieria): Footer corporativo estandar"
```

---

## Task 8: Página Home

**Files:**
- Create: `RegladoIngenieria/src/pages/Home.vue`

- [ ] **Step 1: Crear Home.vue**

Contenido de `RegladoIngenieria/src/pages/Home.vue`:
```vue
<template>
  <main>
    <!-- Hero -->
    <section class="hero section">
      <div class="container">
        <span class="badge">Ingeniería Industrial</span>
        <h1 class="h1 hero-title">Soluciones técnicas<br><span class="accent">para la industria</span></h1>
        <p class="lead hero-lead">Consultoría especializada en ingeniería industrial. Análisis técnicos precisos, proyectos a medida y soluciones para empresas del sector.</p>
        <div class="hero-cta">
          <router-link to="/contacto" class="btn primary">Solicitar consulta</router-link>
          <router-link to="/servicios" class="btn outline">Ver servicios</router-link>
        </div>
      </div>
    </section>

    <!-- Servicios destacados -->
    <section class="section-sm bg-soft">
      <div class="container">
        <h2 class="h2 section-title">Nuestros servicios</h2>
        <div class="grid-3">
          <div class="card" v-for="s in services" :key="s.title">
            <div class="service-icon">{{ s.icon }}</div>
            <h3 class="h3">{{ s.title }}</h3>
            <p class="text-muted" style="margin-top:8px">{{ s.desc }}</p>
          </div>
        </div>
        <div style="margin-top:32px; text-align:center">
          <router-link to="/servicios" class="btn outline">Ver todos los servicios</router-link>
        </div>
      </div>
    </section>

    <!-- Por qué nosotros -->
    <section class="section">
      <div class="container">
        <div class="why-grid">
          <div class="why-text">
            <h2 class="h2">Rigor técnico en cada proyecto</h2>
            <p class="lead" style="margin-top:16px">Combinamos experiencia en ingeniería industrial con metodología estructurada para ofrecer resultados verificables y documentados.</p>
            <ul class="why-list">
              <li v-for="p in points" :key="p">{{ p }}</li>
            </ul>
            <router-link to="/nosotros" class="btn outline" style="margin-top:32px">Conocer el equipo</router-link>
          </div>
          <div class="why-stats">
            <div class="stat-card card" v-for="s in stats" :key="s.label">
              <div class="stat-value">{{ s.value }}</div>
              <div class="stat-label text-muted">{{ s.label }}</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA final -->
    <section class="section-sm bg-soft">
      <div class="container">
        <div class="cta-box card">
          <h2 class="h2">¿Tienes un proyecto industrial?</h2>
          <p class="lead" style="margin-top:12px">Cuéntanos tu necesidad y te ofrecemos una consulta inicial sin compromiso.</p>
          <router-link to="/contacto" class="btn primary" style="margin-top:24px">Contactar ahora</router-link>
        </div>
      </div>
    </section>
  </main>
</template>

<script setup>
const services = [
  { icon: "⚙", title: "Consultoría técnica", desc: "Asesoramiento especializado en procesos industriales y optimización de instalaciones." },
  { icon: "📋", title: "Análisis de parcelas", desc: "Estudios técnicos de parcelas industriales para instalaciones, usos y normativa vigente." },
  { icon: "📐", title: "Proyectos de ingeniería", desc: "Desarrollo de proyectos técnicos adaptados a los requisitos y normativa del cliente." },
];

const points = [
  "Informes técnicos con respaldo normativo",
  "Equipo con experiencia en industria real",
  "Plazos y entregables claros desde el inicio",
  "Comunicación directa con el técnico responsable",
];

const stats = [
  { value: "+50", label: "Proyectos realizados" },
  { value: "+10", label: "Años de experiencia" },
  { value: "100%", label: "Documentación técnica" },
];
</script>

<style scoped>
.hero { background: linear-gradient(135deg, #f8faff 0%, #ffffff 60%); }
.hero-title { margin-top: 16px; max-width: 700px; }
.hero-lead { max-width: 600px; margin-top: 20px; }
.hero-cta { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 32px; }
.service-icon { font-size: 1.75rem; margin-bottom: 12px; }
.why-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 64px; align-items: center; }
.why-list { margin-top: 24px; list-style: none; display: flex; flex-direction: column; gap: 10px; }
.why-list li { padding-left: 20px; position: relative; color: var(--text-muted); }
.why-list li::before { content: "—"; position: absolute; left: 0; color: var(--steel); }
.why-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.stat-card { text-align: center; padding: 24px; }
.stat-value { font-size: 2rem; font-weight: 700; color: var(--steel); }
.stat-label { margin-top: 4px; font-size: 0.875rem; }
.cta-box { text-align: center; max-width: 640px; margin: 0 auto; }

@media (max-width: 768px) {
  .why-grid { grid-template-columns: 1fr; gap: 40px; }
  .why-stats { grid-template-columns: repeat(3, 1fr); }
}
</style>
```

- [ ] **Step 2: Verificar Home en el navegador**

Navegar a `http://localhost:5173`. Expected: hero con badge, título, dos botones CTA; sección de 3 cards de servicios; sección "Por qué nosotros" con stats; CTA final.

- [ ] **Step 3: Commit**

```bash
cd c:/xampp/htdocs/Reglado && git add RegladoIngenieria/src/pages/Home.vue
git commit -m "feat(ingenieria): página Home"
```

---

## Task 9: Página Servicios

**Files:**
- Create: `RegladoIngenieria/src/pages/Servicios.vue`

- [ ] **Step 1: Crear Servicios.vue**

Contenido de `RegladoIngenieria/src/pages/Servicios.vue`:
```vue
<template>
  <main>
    <section class="section">
      <div class="container">
        <span class="badge">Lo que hacemos</span>
        <h1 class="h1" style="margin-top:16px; max-width:640px">Servicios de ingeniería industrial</h1>
        <p class="lead" style="margin-top:16px; max-width:580px">Ofrecemos soluciones técnicas integrales para empresas del sector industrial. Cada servicio se entrega con documentación completa y respaldo normativo.</p>
      </div>
    </section>

    <section class="section-sm bg-soft">
      <div class="container">
        <div class="grid-2">
          <div class="card service-card" v-for="s in services" :key="s.title">
            <div class="service-tag">{{ s.tag }}</div>
            <h2 class="h3" style="margin-top:12px">{{ s.title }}</h2>
            <p class="text-muted" style="margin-top:10px">{{ s.desc }}</p>
            <ul class="service-list">
              <li v-for="item in s.items" :key="item">{{ item }}</li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <section class="section-sm">
      <div class="container" style="text-align:center">
        <h2 class="h2">¿Necesitas un servicio personalizado?</h2>
        <p class="lead" style="margin-top:12px">Contacta con nosotros y analizamos tu caso concreto.</p>
        <router-link to="/contacto" class="btn primary" style="margin-top:24px">Solicitar información</router-link>
      </div>
    </section>
  </main>
</template>

<script setup>
const services = [
  {
    tag: "Consultoría",
    title: "Consultoría técnica industrial",
    desc: "Asesoramiento especializado en el análisis, diseño y optimización de procesos e instalaciones industriales.",
    items: ["Diagnóstico de instalaciones", "Optimización de procesos productivos", "Cumplimiento normativo", "Informes técnicos"],
  },
  {
    tag: "Parcelas",
    title: "Análisis de parcelas industriales",
    desc: "Estudios técnicos de parcelas para determinar viabilidad de uso industrial, requisitos de instalación y adecuación normativa.",
    items: ["Análisis urbanístico y de usos", "Viabilidad de instalaciones", "Estudio de normativa sectorial", "Informe de adecuación"],
  },
  {
    tag: "Proyectos",
    title: "Proyectos de ingeniería",
    desc: "Desarrollo completo de proyectos técnicos desde la definición hasta la documentación final lista para tramitación.",
    items: ["Memoria técnica y planos", "Cálculos justificativos", "Documentación para licencias", "Seguimiento de ejecución"],
  },
  {
    tag: "Informes",
    title: "Informes y peritaciones",
    desc: "Elaboración de informes técnicos y peritaciones industriales con respaldo normativo y firma de técnico competente.",
    items: ["Peritaciones industriales", "Informes de daños", "Certificados técnicos", "Valoraciones"],
  },
];
</script>

<style scoped>
.service-tag {
  display: inline-block;
  padding: 3px 10px;
  background: var(--steel-light);
  color: var(--steel-dark);
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .04em;
}
.service-list {
  margin-top: 16px;
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.service-list li {
  padding-left: 16px;
  position: relative;
  font-size: 0.9375rem;
  color: var(--text-muted);
}
.service-list li::before {
  content: "·";
  position: absolute;
  left: 4px;
  color: var(--steel);
  font-weight: 700;
}
</style>
```

- [ ] **Step 2: Verificar en http://localhost:5173/servicios**

Expected: badge, título, 4 cards de servicios en grid de 2 columnas, CTA final.

- [ ] **Step 3: Commit**

```bash
cd c:/xampp/htdocs/Reglado && git add RegladoIngenieria/src/pages/Servicios.vue
git commit -m "feat(ingenieria): página Servicios"
```

---

## Task 10: Páginas Proyectos y Nosotros

**Files:**
- Create: `RegladoIngenieria/src/pages/Proyectos.vue`
- Create: `RegladoIngenieria/src/pages/Nosotros.vue`

- [ ] **Step 1: Crear Proyectos.vue**

Contenido de `RegladoIngenieria/src/pages/Proyectos.vue`:
```vue
<template>
  <main>
    <section class="section">
      <div class="container">
        <span class="badge">Portfolio</span>
        <h1 class="h1" style="margin-top:16px; max-width:600px">Proyectos realizados</h1>
        <p class="lead" style="margin-top:16px; max-width:560px">Selección de proyectos de ingeniería industrial desarrollados para clientes del sector.</p>
      </div>
    </section>

    <section class="section-sm">
      <div class="container">
        <div class="grid-3">
          <div class="card project-card" v-for="p in projects" :key="p.title">
            <div class="project-type">{{ p.type }}</div>
            <h2 class="h3" style="margin-top:10px">{{ p.title }}</h2>
            <p class="text-muted" style="margin-top:8px; font-size:.9375rem">{{ p.desc }}</p>
            <div class="project-meta">
              <span>{{ p.sector }}</span>
              <span>{{ p.year }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>
</template>

<script setup>
const projects = [
  { type: "Consultoría", title: "Análisis de instalación matadero industrial", desc: "Estudio técnico de parcela y adecuación normativa para instalación de industria cárnica.", sector: "Industria alimentaria", year: "2024" },
  { type: "Proyecto", title: "Proyecto de instalación eléctrica BT", desc: "Diseño y documentación de instalación eléctrica en nave industrial de 3.000 m².", sector: "Industria general", year: "2024" },
  { type: "Informe", title: "Peritación daños estructura metálica", desc: "Informe pericial de daños estructurales en nave industrial afectada por siniestro.", sector: "Seguros industriales", year: "2023" },
  { type: "Consultoría", title: "Viabilidad uso industrial parcela logística", desc: "Análisis de viabilidad urbanística y técnica para implantación de almacén logístico.", sector: "Logística", year: "2023" },
  { type: "Proyecto", title: "Proyecto contra incendios nave almacén", desc: "Documentación técnica de protección contra incendios para nave de 5.000 m².", sector: "Industria general", year: "2023" },
  { type: "Informe", title: "Certificación eficiencia energética industrial", desc: "Certificado de eficiencia energética de instalaciones en planta de producción.", sector: "Energía", year: "2022" },
];
</script>

<style scoped>
.project-type {
  display: inline-block;
  padding: 3px 10px;
  background: var(--steel-light);
  color: var(--steel-dark);
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .04em;
}
.project-meta {
  display: flex;
  gap: 12px;
  margin-top: 16px;
  font-size: 0.8125rem;
  color: var(--text-muted);
  border-top: 1px solid var(--border);
  padding-top: 12px;
}
</style>
```

- [ ] **Step 2: Crear Nosotros.vue**

Contenido de `RegladoIngenieria/src/pages/Nosotros.vue`:
```vue
<template>
  <main>
    <section class="section">
      <div class="container">
        <span class="badge">El equipo</span>
        <h1 class="h1" style="margin-top:16px; max-width:600px">Ingeniería con experiencia industrial real</h1>
        <p class="lead" style="margin-top:16px; max-width:580px">Somos un equipo de ingenieros especializados en el sector industrial. Aportamos rigor técnico y experiencia práctica en cada proyecto que abordamos.</p>
      </div>
    </section>

    <section class="section-sm bg-soft">
      <div class="container">
        <div class="about-grid">
          <div>
            <h2 class="h2">Nuestra misión</h2>
            <p class="text-muted" style="margin-top:16px; line-height:1.8">Ofrecer consultoría de ingeniería industrial con el mismo nivel de rigor que exige la industria: documentación completa, cálculos justificados y cumplimiento normativo. Sin atajos.</p>
            <p class="text-muted" style="margin-top:12px; line-height:1.8">Trabajamos directamente con el técnico responsable de cada proyecto, sin intermediarios, para garantizar la calidad del resultado.</p>
          </div>
          <div>
            <h2 class="h2">Especialidades</h2>
            <div class="spec-list" style="margin-top:20px">
              <div class="spec-item" v-for="s in specialties" :key="s">
                <span class="spec-dot"></span>
                <span>{{ s }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="section-sm">
      <div class="container" style="text-align:center">
        <h2 class="h2">¿Trabajamos juntos?</h2>
        <p class="lead" style="margin-top:12px">Cuéntanos tu proyecto y te damos una respuesta técnica en 48 horas.</p>
        <router-link to="/contacto" class="btn primary" style="margin-top:24px">Contactar</router-link>
      </div>
    </section>
  </main>
</template>

<script setup>
const specialties = [
  "Ingeniería de instalaciones industriales",
  "Análisis y viabilidad de parcelas industriales",
  "Proyectos técnicos y documentación para licencias",
  "Protección contra incendios en industria",
  "Certificación y eficiencia energética",
  "Peritaciones y valoraciones industriales",
];
</script>

<style scoped>
.about-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 64px; }
.spec-list { display: flex; flex-direction: column; gap: 12px; }
.spec-item { display: flex; align-items: flex-start; gap: 12px; font-size: .9375rem; color: var(--text-muted); }
.spec-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--steel); flex-shrink: 0; margin-top: 6px; }

@media (max-width: 768px) {
  .about-grid { grid-template-columns: 1fr; gap: 40px; }
}
</style>
```

- [ ] **Step 3: Verificar ambas páginas**

- Navegar a `http://localhost:5173/proyectos` — Expected: 6 project cards en grid de 3.
- Navegar a `http://localhost:5173/nosotros` — Expected: sección hero, grid 2col con misión y especialidades.

- [ ] **Step 4: Commit**

```bash
cd c:/xampp/htdocs/Reglado && git add RegladoIngenieria/src/pages/Proyectos.vue RegladoIngenieria/src/pages/Nosotros.vue
git commit -m "feat(ingenieria): páginas Proyectos y Nosotros"
```

---

## Task 11: Página Contacto

**Files:**
- Create: `RegladoIngenieria/src/pages/Contacto.vue`

- [ ] **Step 1: Crear Contacto.vue**

Contenido de `RegladoIngenieria/src/pages/Contacto.vue`:
```vue
<template>
  <main>
    <section class="section">
      <div class="container">
        <div class="contact-layout">
          <div class="contact-info">
            <span class="badge">Contacto</span>
            <h1 class="h1" style="margin-top:16px">Hablemos de tu proyecto</h1>
            <p class="lead" style="margin-top:16px">Cuéntanos tu necesidad y te damos respuesta técnica en menos de 48 horas.</p>
            <div class="info-items" style="margin-top:40px">
              <div class="info-item" v-for="item in infoItems" :key="item.label">
                <div class="info-label">{{ item.label }}</div>
                <div class="info-value">{{ item.value }}</div>
              </div>
            </div>
          </div>

          <div class="contact-form-wrap">
            <div class="card">
              <form @submit.prevent="handleSubmit" novalidate>
                <div style="display:grid; gap:20px">
                  <div class="form-group">
                    <label for="nombre">Nombre *</label>
                    <input id="nombre" v-model="form.nombre" type="text" placeholder="Tu nombre completo" required />
                  </div>
                  <div class="form-row">
                    <div class="form-group">
                      <label for="email">Email *</label>
                      <input id="email" v-model="form.email" type="email" placeholder="tu@empresa.com" required />
                    </div>
                    <div class="form-group">
                      <label for="telefono">Teléfono</label>
                      <input id="telefono" v-model="form.telefono" type="tel" placeholder="600 000 000" />
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="empresa">Empresa</label>
                    <input id="empresa" v-model="form.empresa" type="text" placeholder="Nombre de tu empresa" />
                  </div>
                  <div class="form-group">
                    <label for="mensaje">Mensaje *</label>
                    <textarea id="mensaje" v-model="form.mensaje" placeholder="Describe tu proyecto o necesidad..." required></textarea>
                  </div>
                  <div v-if="alert.msg" :class="['alert', alert.type]">{{ alert.msg }}</div>
                  <button type="submit" class="btn primary" :disabled="sending" style="width:100%; justify-content:center">
                    {{ sending ? "Enviando..." : "Enviar consulta" }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>
</template>

<script setup>
import { ref, reactive } from "vue";
import { submitContact } from "../services/api.js";

const form = reactive({ nombre: "", email: "", telefono: "", empresa: "", mensaje: "" });
const sending = ref(false);
const alert = reactive({ msg: "", type: "" });

const infoItems = [
  { label: "Email", value: "info@regladoingenieria.com" },
  { label: "Tiempo de respuesta", value: "Menos de 48 horas" },
  { label: "Servicio", value: "Ingeniería industrial" },
];

async function handleSubmit() {
  if (!form.nombre.trim() || !form.email.trim() || !form.mensaje.trim()) {
    alert.msg = "Por favor, completa los campos obligatorios.";
    alert.type = "error";
    return;
  }

  sending.value = true;
  alert.msg = "";

  try {
    await submitContact({ ...form });
    alert.msg = "Consulta enviada correctamente. Te responderemos en menos de 48 horas.";
    alert.type = "success";
    Object.assign(form, { nombre: "", email: "", telefono: "", empresa: "", mensaje: "" });
  } catch (err) {
    alert.msg = err.message || "Error al enviar. Inténtalo de nuevo.";
    alert.type = "error";
  } finally {
    sending.value = false;
  }
}
</script>

<style scoped>
.contact-layout { display: grid; grid-template-columns: 1fr 1.2fr; gap: 64px; align-items: start; }
.info-items { display: flex; flex-direction: column; gap: 24px; }
.info-label { font-size: 0.8125rem; text-transform: uppercase; letter-spacing: .06em; color: var(--steel); font-weight: 600; }
.info-value { margin-top: 4px; font-size: 1rem; color: var(--text-muted); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

@media (max-width: 768px) {
  .contact-layout { grid-template-columns: 1fr; gap: 40px; }
  .form-row { grid-template-columns: 1fr; }
}
</style>
```

- [ ] **Step 2: Verificar formulario en http://localhost:5173/contacto**

Expected: layout 2 columnas (info + formulario). Rellenar campos vacíos y hacer submit → mensaje de error de validación. (El submit real fallará hasta que esté el backend.)

- [ ] **Step 3: Commit**

```bash
cd c:/xampp/htdocs/Reglado && git add RegladoIngenieria/src/pages/Contacto.vue
git commit -m "feat(ingenieria): página Contacto con formulario"
```

---

## Task 12: Páginas de autenticación y área protegida

**Files:**
- Create: `RegladoIngenieria/src/pages/AuthCallback.vue`
- Create: `RegladoIngenieria/src/components/ParcelasContainer.vue`
- Create: `RegladoIngenieria/src/pages/AreaClientes.vue`
- Create: `RegladoIngenieria/src/pages/Admin.vue`
- Create: `RegladoIngenieria/src/pages/NotFound.vue`

- [ ] **Step 1: Crear AuthCallback.vue**

Contenido de `RegladoIngenieria/src/pages/AuthCallback.vue`:
```vue
<template>
  <section v-if="error" class="section">
    <div class="container">
      <div class="card" style="max-width:560px; margin:0 auto; text-align:center; display:grid; gap:16px">
        <h1 class="h2">Acceso no disponible</h1>
        <p class="text-muted">{{ error }}</p>
        <router-link to="/" class="btn primary" style="margin:0 auto">Ir al inicio</router-link>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { auth } from "../services/auth.js";

const route = useRoute();
const router = useRouter();
const error = ref("");

onMounted(async () => {
  const token = typeof route.query.token === "string" ? route.query.token : "";

  if (!token) {
    error.value = "No se encontró el token de acceso.";
    return;
  }

  try {
    auth.setSession(token, null);
    await auth.initialize();

    if (!auth.state.user) throw new Error("No se pudo validar la sesión.");

    await router.replace("/area-clientes");
  } catch (err) {
    auth.clearSession();
    error.value = err instanceof Error ? err.message : "No se pudo iniciar sesión.";
  }
});
</script>
```

- [ ] **Step 2: Crear ParcelasContainer.vue**

Contenido de `RegladoIngenieria/src/components/ParcelasContainer.vue`:
```vue
<template>
  <div class="parcelas-wrap">
    <div class="card placeholder-card">
      <div class="placeholder-icon">⚙</div>
      <h2 class="h3">Consulta de Parcelas Industriales</h2>
      <p class="text-muted" style="margin-top:8px">Esta funcionalidad está siendo integrada y estará disponible próximamente.</p>
      <p class="hint">Cuando esté lista, se montará aquí automáticamente usando el token de sesión.</p>
    </div>
  </div>
</template>

<script setup>
defineProps({ token: { type: String, required: true } });
</script>

<style scoped>
.parcelas-wrap { width: 100%; min-height: 400px; display: flex; align-items: center; justify-content: center; }
.placeholder-card { text-align: center; max-width: 480px; display: grid; gap: 8px; }
.placeholder-icon { font-size: 2.5rem; margin-bottom: 8px; }
.hint { font-size: 0.8125rem; color: var(--text-muted); margin-top: 8px; border-top: 1px solid var(--border); padding-top: 12px; }
</style>
```

- [ ] **Step 3: Crear AreaClientes.vue**

Contenido de `RegladoIngenieria/src/pages/AreaClientes.vue`:
```vue
<template>
  <main>
    <section class="section-sm">
      <div class="container">
        <div class="area-header">
          <div>
            <span class="badge">Área privada</span>
            <h1 class="h2" style="margin-top:12px">Bienvenido, {{ auth.state.user?.name || "cliente" }}</h1>
          </div>
          <button class="btn ghost" @click="auth.logout()">Cerrar sesión</button>
        </div>
      </div>
    </section>

    <section class="section-sm bg-soft">
      <div class="container">
        <ParcelasContainer :token="auth.state.token" />
      </div>
    </section>
  </main>
</template>

<script setup>
import { auth } from "../services/auth.js";
import ParcelasContainer from "../components/ParcelasContainer.vue";
</script>

<style scoped>
.area-header { display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 16px; }
</style>
```

- [ ] **Step 4: Crear Admin.vue**

Contenido de `RegladoIngenieria/src/pages/Admin.vue`:
```vue
<template>
  <main>
    <section class="section">
      <div class="container">
        <div class="card" style="max-width:560px; margin:0 auto; text-align:center; display:grid; gap:16px; padding:48px">
          <div style="font-size:2rem">🔧</div>
          <h1 class="h2">Panel de Administración</h1>
          <p class="text-muted">Esta sección está en construcción. Próximamente podrás gestionar consultas, usuarios y contenidos desde aquí.</p>
          <router-link to="/" class="btn outline" style="margin:0 auto">Volver al inicio</router-link>
        </div>
      </div>
    </section>
  </main>
</template>
```

- [ ] **Step 5: Crear NotFound.vue**

Contenido de `RegladoIngenieria/src/pages/NotFound.vue`:
```vue
<template>
  <main>
    <section class="section">
      <div class="container">
        <div style="max-width:480px; margin:0 auto; text-align:center; display:grid; gap:16px">
          <div style="font-size:4rem; font-weight:700; color:var(--steel)">404</div>
          <h1 class="h2">Página no encontrada</h1>
          <p class="text-muted">La página que buscas no existe o ha sido movida.</p>
          <router-link to="/" class="btn primary" style="margin:0 auto">Volver al inicio</router-link>
        </div>
      </div>
    </section>
  </main>
</template>
```

- [ ] **Step 6: Verificar páginas protegidas**

- Navegar a `http://localhost:5173/admin` sin token → debe redirigir a ApiLoging login.
- Navegar a `http://localhost:5173/404-test` → página 404.

- [ ] **Step 7: Commit**

```bash
cd c:/xampp/htdocs/Reglado && git add RegladoIngenieria/src/pages/AuthCallback.vue RegladoIngenieria/src/pages/AreaClientes.vue RegladoIngenieria/src/pages/Admin.vue RegladoIngenieria/src/pages/NotFound.vue RegladoIngenieria/src/components/ParcelasContainer.vue
git commit -m "feat(ingenieria): páginas auth, área clientes, admin y 404"
```

---

## Task 13: Backend PHP — infraestructura

**Files:**
- Create: `RegladoIngenieria/BACKEND/bootstrap.php`
- Create: `RegladoIngenieria/BACKEND/db.php`
- Create: `RegladoIngenieria/BACKEND/security.php`
- Create: `RegladoIngenieria/BACKEND/.env.example`
- Create: `RegladoIngenieria/BACKEND/.htaccess`

- [ ] **Step 1: Crear bootstrap.php**

Contenido de `RegladoIngenieria/BACKEND/bootstrap.php`:
```php
<?php

declare(strict_types=1);

loadBackendEnv(__DIR__ . DIRECTORY_SEPARATOR . '.env');

function loadBackendEnv(string $path): void
{
    static $loaded = false;
    if ($loaded || !is_file($path)) return;

    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) return;

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;

        $position = strpos($line, '=');
        if ($position === false) continue;

        $key = trim(substr($line, 0, $position));
        $value = trim(substr($line, $position + 1));
        if ($key === '') continue;

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    $loaded = true;
}
```

- [ ] **Step 2: Crear db.php**

Contenido de `RegladoIngenieria/BACKEND/db.php`:
```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function getPdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = (int)(getenv('DB_PORT') ?: 3306);
    $name = getenv('DB_NAME') ?: 'ingenieria';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $name);

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        throw new RuntimeException('No se pudo conectar con la base de datos.', 0, $e);
    }

    return $pdo;
}
```

- [ ] **Step 3: Crear security.php**

Contenido de `RegladoIngenieria/BACKEND/security.php`:
```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function applySecurityHeaders(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

function applyCorsHeaders(array $methods, string $headers = 'Content-Type, Authorization', bool $json = true): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
    if (is_string($origin) && isAllowedOrigin($origin)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    } elseif (is_string($origin) && $origin !== '') {
        respondJson(403, ['ok' => false, 'message' => 'Origen no permitido.']);
    }

    header('Access-Control-Allow-Methods: ' . implode(', ', $methods));
    header('Access-Control-Allow-Headers: ' . $headers);
    if ($json) header('Content-Type: application/json; charset=utf-8');
}

function isAllowedOrigin(string $origin): bool
{
    $allowed = parseCsvEnv('CORS_ALLOWED_ORIGINS');
    if ($allowed === []) {
        $allowed = [
            'http://localhost:5173',
            'http://localhost:5174',
            'http://127.0.0.1:5173',
            'http://127.0.0.1:5174',
        ];
    }
    return in_array($origin, $allowed, true);
}

function parseCsvEnv(string $key): array
{
    $value = trim((string)(getenv($key) ?: ''));
    if ($value === '') return [];
    $parts = array_map('trim', explode(',', $value));
    return array_values(array_unique(array_filter($parts, fn($i) => $i !== '')));
}

function getClientIp(): string
{
    $candidate = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    if (!is_string($candidate) || trim($candidate) === '') return 'unknown';
    foreach (array_map('trim', explode(',', $candidate)) as $part) {
        if (filter_var($part, FILTER_VALIDATE_IP)) return $part;
    }
    return 'unknown';
}

function respondJson(int $status, array $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}
```

- [ ] **Step 4: Crear .env.example (backend)**

Contenido de `RegladoIngenieria/BACKEND/.env.example`:
```
APP_ENV=local
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=ingenieria
DB_USER=root
DB_PASS=

JWT_SECRET=change-this-secret-min-32-chars

CORS_ALLOWED_ORIGINS=http://localhost:5173,http://localhost:5174,http://127.0.0.1:5173

MAIL_FROM=info@regladoingenieria.com
MAIL_TO=info@regladoingenieria.com
MAIL_FROM_NAME=Reglado Ingeniería
```

- [ ] **Step 5: Crear BACKEND/.htaccess**

Contenido de `RegladoIngenieria/BACKEND/.htaccess`:
```apache
Options -Indexes
<Files ".env">
    Order allow,deny
    Deny from all
</Files>
<Files "*.php">
    Order allow,deny
    Allow from all
</Files>
```

- [ ] **Step 6: Commit**

```bash
cd c:/xampp/htdocs/Reglado && git add RegladoIngenieria/BACKEND/bootstrap.php RegladoIngenieria/BACKEND/db.php RegladoIngenieria/BACKEND/security.php RegladoIngenieria/BACKEND/.env.example RegladoIngenieria/BACKEND/.htaccess
git commit -m "feat(ingenieria): backend PHP infraestructura (bootstrap, db, security)"
```

---

## Task 14: Backend PHP — auth.php

**Files:**
- Create: `RegladoIngenieria/BACKEND/auth.php`

- [ ] **Step 1: Crear auth.php**

Contenido de `RegladoIngenieria/BACKEND/auth.php`:
```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/security.php';

function requireAuth(): array
{
    $token = extractBearerToken();
    if ($token === null) {
        respondJson(401, ['ok' => false, 'message' => 'Falta el token de autorización.']);
    }
    return verifyJwt($token);
}

function extractBearerToken(): ?string
{
    $headers = getHeadersLower();
    $authorization = $headers['authorization'] ?? null;
    if (!is_string($authorization)) return null;
    if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) return null;
    $token = trim($matches[1]);
    return $token !== '' ? $token : null;
}

function verifyJwt(string $token): array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) respondJson(401, ['ok' => false, 'message' => 'Token inválido.']);

    [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

    $header  = json_decode(base64UrlDecode($encodedHeader), true);
    $payload = json_decode(base64UrlDecode($encodedPayload), true);

    if (!is_array($header) || !is_array($payload)) {
        respondJson(401, ['ok' => false, 'message' => 'Token inválido.']);
    }

    if (($header['alg'] ?? null) !== 'HS256') {
        respondJson(401, ['ok' => false, 'message' => 'Algoritmo no soportado.']);
    }

    $secret = getJwtSecret();
    $expected = base64UrlEncode(hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $secret, true));

    if (!hash_equals($expected, $encodedSignature)) {
        error_log('AUTH_FAIL ip=' . getClientIp() . ' reason=invalid_signature');
        respondJson(401, ['ok' => false, 'message' => 'Firma de token inválida.']);
    }

    $now = time();
    if (isset($payload['nbf']) && (int)$payload['nbf'] > $now) {
        respondJson(401, ['ok' => false, 'message' => 'Token aún no válido.']);
    }
    if (isset($payload['exp']) && (int)$payload['exp'] < $now) {
        error_log('AUTH_FAIL ip=' . getClientIp() . ' reason=expired');
        respondJson(401, ['ok' => false, 'message' => 'Token expirado.']);
    }

    return $payload;
}

function requireAdminAuth(): array
{
    $payload = requireAuth();
    if (($payload['role'] ?? null) !== 'admin') {
        error_log('AUTH_FAIL ip=' . getClientIp() . ' reason=forbidden_role');
        respondJson(403, ['ok' => false, 'message' => 'Acceso restringido a administradores.']);
    }
    return $payload;
}

function getJwtSecret(): string
{
    static $secret = null;
    if (is_string($secret) && $secret !== '') return $secret;

    $secret = getenv('JWT_SECRET') ?: '';
    if ($secret !== '') return $secret;

    $localEnv = __DIR__ . DIRECTORY_SEPARATOR . '.env';
    if (is_file($localEnv)) {
        $values = parseEnvFile($localEnv);
        $secret = $values['JWT_SECRET'] ?? '';
        if ($secret !== '') return $secret;
    }

    $apiLogingEnv = dirname(__DIR__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'ApiLoging' . DIRECTORY_SEPARATOR . '.env';
    if (is_file($apiLogingEnv)) {
        $values = parseEnvFile($apiLogingEnv);
        $secret = $values['JWT_SECRET'] ?? '';
        if ($secret !== '') return $secret;
    }

    respondJson(500, ['ok' => false, 'message' => 'JWT_SECRET no configurado.']);
}

function parseEnvFile(string $path): array
{
    $result = [];
    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) return $result;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        $pos = strpos($line, '=');
        if ($pos === false) continue;
        $result[trim(substr($line, 0, $pos))] = trim(substr($line, $pos + 1));
    }
    return $result;
}

function getHeadersLower(): array
{
    if (function_exists('getallheaders')) {
        return array_combine(
            array_map('strtolower', array_keys(getallheaders())),
            array_values(getallheaders())
        );
    }
    $headers = [];
    foreach ($_SERVER as $key => $value) {
        if (str_starts_with($key, 'HTTP_')) {
            $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = $value;
        }
    }
    return $headers;
}

function base64UrlDecode(string $value): string
{
    $remainder = strlen($value) % 4;
    if ($remainder > 0) $value .= str_repeat('=', 4 - $remainder);
    $decoded = base64_decode(strtr($value, '-_', '+/'), true);
    if ($decoded === false) respondJson(401, ['ok' => false, 'message' => 'Token malformado.']);
    return $decoded;
}

function base64UrlEncode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}
```

- [ ] **Step 2: Commit**

```bash
cd c:/xampp/htdocs/Reglado && git add RegladoIngenieria/BACKEND/auth.php
git commit -m "feat(ingenieria): backend auth.php con verificación JWT"
```

---

## Task 15: Backend PHP — contact.php

**Files:**
- Create: `RegladoIngenieria/BACKEND/contact.php`

- [ ] **Step 1: Crear contact.php**

Contenido de `RegladoIngenieria/BACKEND/contact.php`:
```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';

applySecurityHeaders();
applyCorsHeaders(['POST', 'OPTIONS']);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondJson(405, ['ok' => false, 'message' => 'Método no permitido.']);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);

if (!is_array($data)) {
    respondJson(400, ['ok' => false, 'message' => 'Datos inválidos.']);
}

$nombre  = trim((string)($data['nombre']  ?? ''));
$email   = trim((string)($data['email']   ?? ''));
$telefono = trim((string)($data['telefono'] ?? ''));
$empresa  = trim((string)($data['empresa']  ?? ''));
$mensaje  = trim((string)($data['mensaje']  ?? ''));

if ($nombre === '' || $email === '' || $mensaje === '') {
    respondJson(422, ['ok' => false, 'message' => 'Faltan campos obligatorios.']);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respondJson(422, ['ok' => false, 'message' => 'El email no es válido.']);
}

if (mb_strlen($nombre) > 100 || mb_strlen($email) > 150 || mb_strlen($mensaje) > 3000) {
    respondJson(422, ['ok' => false, 'message' => 'Algún campo supera la longitud permitida.']);
}

try {
    $pdo = getPdo();
    $stmt = $pdo->prepare(
        'INSERT INTO consultas (nombre, email, telefono, empresa, mensaje) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$nombre, $email, $telefono, $empresa, $mensaje]);
} catch (Exception $e) {
    error_log('CONTACT_DB_ERROR: ' . $e->getMessage());
    respondJson(500, ['ok' => false, 'message' => 'Error al guardar la consulta.']);
}

sendNotificationEmail($nombre, $email, $telefono, $empresa, $mensaje);

respondJson(200, ['ok' => true, 'message' => 'Consulta recibida correctamente.']);

function sendNotificationEmail(string $nombre, string $email, string $telefono, string $empresa, string $mensaje): void
{
    $mailTo       = getenv('MAIL_TO')        ?: 'info@regladoingenieria.com';
    $mailFrom     = getenv('MAIL_FROM')      ?: 'info@regladoingenieria.com';
    $mailFromName = getenv('MAIL_FROM_NAME') ?: 'Reglado Ingeniería';

    $subject = '=?UTF-8?B?' . base64_encode('Nueva consulta de ' . $nombre) . '?=';

    $html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head><body style="font-family:Arial,sans-serif;color:#1a1f2e">'
        . '<h2 style="color:#4a9eff">Nueva consulta — Reglado Ingeniería</h2>'
        . '<table style="border-collapse:collapse;width:100%">'
        . tableRow('Nombre', htmlspecialchars($nombre))
        . tableRow('Email', htmlspecialchars($email))
        . tableRow('Teléfono', htmlspecialchars($telefono ?: '—'))
        . tableRow('Empresa', htmlspecialchars($empresa ?: '—'))
        . tableRow('Mensaje', nl2br(htmlspecialchars($mensaje)))
        . '</table></body></html>';

    $boundary = 'boundary_' . bin2hex(random_bytes(8));
    $headers = implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        'From: ' . $mailFromName . ' <' . $mailFrom . '>',
        'Reply-To: ' . $email,
        'X-Mailer: PHP/' . PHP_VERSION,
    ]);

    $body = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n"
        . "Nueva consulta de: {$nombre}\nEmail: {$email}\nTeléfono: {$telefono}\nEmpresa: {$empresa}\n\nMensaje:\n{$mensaje}\r\n"
        . "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n{$html}\r\n"
        . "--{$boundary}--";

    @mail($mailTo, $subject, $body, $headers);
}

function tableRow(string $label, string $value): string
{
    return '<tr><td style="padding:8px 12px;border:1px solid #e0e4ea;font-weight:600;width:120px">'
        . $label . '</td><td style="padding:8px 12px;border:1px solid #e0e4ea">' . $value . '</td></tr>';
}
```

- [ ] **Step 2: Commit**

```bash
cd c:/xampp/htdocs/Reglado && git add RegladoIngenieria/BACKEND/contact.php
git commit -m "feat(ingenieria): backend contact.php con guardado en BD y email"
```

---

## Task 16: SQL schema, .env y .htaccess raíz

**Files:**
- Create: `RegladoIngenieria/BACKEND/sql/schema.sql`
- Create: `RegladoIngenieria/.htaccess`

- [ ] **Step 1: Crear schema.sql**

Contenido de `RegladoIngenieria/BACKEND/sql/schema.sql`:
```sql
CREATE DATABASE IF NOT EXISTS ingenieria
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE ingenieria;

CREATE TABLE IF NOT EXISTS consultas (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  nombre          VARCHAR(100)  NOT NULL,
  email           VARCHAR(150)  NOT NULL,
  telefono        VARCHAR(20)   DEFAULT NULL,
  empresa         VARCHAR(100)  DEFAULT NULL,
  mensaje         TEXT          NOT NULL,
  fecha_creacion  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  leido           TINYINT(1)    NOT NULL DEFAULT 0,
  INDEX idx_email (email),
  INDEX idx_leido (leido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **Step 2: Importar schema en MySQL**

Abrir phpMyAdmin en `http://localhost/phpmyadmin` → pestaña SQL → pegar y ejecutar el contenido de `schema.sql`.

Expected: base de datos `ingenieria` creada con tabla `consultas`.

- [ ] **Step 3: Copiar .env desde .env.example y configurar**

```bash
cp c:/xampp/htdocs/Reglado/RegladoIngenieria/BACKEND/.env.example c:/xampp/htdocs/Reglado/RegladoIngenieria/BACKEND/.env
```

Editar `BACKEND/.env` con los valores reales:
- `JWT_SECRET` — copiar el mismo valor que usa ApiLoging (leer de `c:/xampp/htdocs/Reglado/ApiLoging/.env`)
- `DB_USER` / `DB_PASS` — credenciales MySQL locales (habitualmente `root` / ``)
- `DB_NAME=ingenieria`

- [ ] **Step 4: Crear .htaccess raíz (reescritura SPA)**

Contenido de `RegladoIngenieria/.htaccess`:
```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /Reglado/RegladoIngenieria/dist/

  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /Reglado/RegladoIngenieria/dist/index.html [L]
</IfModule>
```

> Nota: en desarrollo (`npm run dev`) no se usa este `.htaccess`. Solo aplica al build de producción servido desde XAMPP.

- [ ] **Step 5: Verificar formulario de contacto end-to-end**

1. Asegurarse de que XAMPP Apache y MySQL están activos.
2. En otra terminal: `cd c:/xampp/htdocs/Reglado/RegladoIngenieria && npm run dev`
3. Navegar a `http://localhost:5173/contacto`.
4. Rellenar el formulario con nombre, email y mensaje → submit.
5. Expected: alerta verde "Consulta recibida correctamente."
6. Verificar en phpMyAdmin → tabla `consultas` → nuevo registro.

- [ ] **Step 6: Commit final**

```bash
cd c:/xampp/htdocs/Reglado && git add RegladoIngenieria/BACKEND/sql/schema.sql RegladoIngenieria/.htaccess
git commit -m "feat(ingenieria): schema SQL, .htaccess SPA y configuración entorno"
```

---

## Checklist de verificación final

Antes de considerar el proyecto listo para v1:

- [ ] `npm run dev` arranca sin errores de consola
- [ ] Todas las rutas públicas renderizan correctamente (Home, Servicios, Proyectos, Nosotros, Contacto)
- [ ] `/area-clientes` sin token redirige a ApiLoging login
- [ ] `/admin` sin token redirige a ApiLoging login
- [ ] `/404-test` muestra página 404
- [ ] Formulario de contacto guarda registro en tabla `consultas`
- [ ] Header responsive: hamburger funciona en móvil
- [ ] Footer corporativo estándar presente en todas las páginas
- [ ] Sin errores de consola en ninguna página
