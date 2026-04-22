# RegladoIngenieria — Design Spec
**Fecha:** 2026-04-17  
**Estado:** Aprobado

## Resumen

Web corporativa de ingeniería industrial para el ecosistema Reglado. Presenta los servicios de consultoría, portfolio de proyectos y un área de clientes protegida donde se integrará una funcionalidad de consulta de parcelas industriales (desarrollada por un compañero en stack no-Vue).

Estética: limpia y profesional — blanca, tipografía técnica, minimalista. Estilo consultora de ingeniería industrial.

---

## Arquitectura

**Stack:** Vue 3.5 + Vite 5.4 + PHP puro + MySQL  
**Auth:** ApiLoging centralizado en `https://gruporeglado.com/ApiLoging` (dominio externo)  
**Token key:** `ingenieria_auth_token`  
**BD:** `ingenieria`  
**Email contacto:** `info@regladoingenieria.com`

### Estructura de directorios

```
RegladoIngenieria/
├── src/
│   ├── pages/
│   │   ├── Home.vue
│   │   ├── Servicios.vue
│   │   ├── Proyectos.vue
│   │   ├── Nosotros.vue
│   │   ├── Contacto.vue
│   │   ├── AreaClientes.vue       ← ruta protegida
│   │   ├── Admin.vue              ← ruta protegida (placeholder "en construcción")
│   │   ├── AuthCallback.vue       ← recibe token de ApiLoging
│   │   └── NotFound.vue
│   ├── components/
│   │   ├── ParcelasContainer.vue  ← punto de integración parcelas
│   │   ├── Header.vue
│   │   └── Footer.vue             ← skill-footer-standar
│   ├── services/
│   │   ├── auth.js                ← gestión token ApiLoging
│   │   └── api.js                 ← llamadas al backend PHP
│   ├── router/index.js            ← guards de ruta para rutas protegidas
│   └── assets/
│       └── main.css               ← variables CSS, tipografía, paleta
├── BACKEND/
│   ├── auth.php
│   ├── contact.php
│   ├── db.php
│   ├── security.php
│   ├── bootstrap.php
│   └── sql/schema.sql
├── .env.example
├── .env.production
├── vite.config.js
├── index.html
└── package.json
```

---

## Páginas

### Públicas

| Página | Ruta | Descripción |
|--------|------|-------------|
| Home | `/` | Hero con tagline de ingeniería industrial, servicios destacados, CTA a contacto |
| Servicios | `/servicios` | Cards con los servicios: consultoría industrial, proyectos técnicos, etc. |
| Proyectos | `/proyectos` | Portfolio estático de proyectos realizados |
| Nosotros | `/nosotros` | Descripción de la empresa y equipo |
| Contacto | `/contacto` | Formulario de contacto |
| NotFound | `/:pathMatch(.*)` | Página 404 |

### Protegidas (requieren token ApiLoging válido)

| Página | Ruta | Descripción |
|--------|------|-------------|
| AreaClientes | `/area-clientes` | Monta `ParcelasContainer.vue` con el token del usuario |
| Admin | `/admin` | Placeholder "Sección en construcción" |
| AuthCallback | `/auth/callback` | Recibe `?token=` de ApiLoging y redirige |

---

## Integración de parcelas (Opción C — Contenedor flexible)

`ParcelasContainer.vue` actúa como contrato de integración:
- Recibe la prop `token` (string) del usuario autenticado
- Por ahora renderiza un placeholder con mensaje informativo
- Cuando el compañero entregue la app, se conecta aquí (iframe, script injection o wrapper Vue)
- La interfaz del componente no cambia al integrar — solo su implementación interna

---

## Flujo de autenticación

1. Usuario pulsa "Iniciar sesión"
2. Redirige a `https://gruporeglado.com/ApiLoging?redirect=<callback_url>`
3. ApiLoging autentica y redirige a `/auth/callback?token=<jwt>`
4. `AuthCallback.vue` almacena el token en localStorage con key `ingenieria_auth_token`
5. Router guard verifica el token en rutas protegidas
6. `auth.php` en el backend valida el token contra ApiLoging en cada petición protegida

---

## Backend PHP

### Endpoints

| Archivo | Método | Función |
|---------|--------|---------|
| `auth.php` | POST | Valida token JWT con ApiLoging, devuelve datos del usuario |
| `contact.php` | POST | Guarda consulta en BD, envía email a `info@regladoingenieria.com` |
| `db.php` | — | Conexión PDO a MySQL, BD `ingenieria` |
| `security.php` | — | Headers CORS, sanitización de inputs |
| `bootstrap.php` | — | Carga `.env`, incluye `security.php` y `db.php` |

### Schema SQL

```sql
CREATE DATABASE IF NOT EXISTS ingenieria;

CREATE TABLE consultas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL,
  telefono VARCHAR(20),
  empresa VARCHAR(100),
  mensaje TEXT NOT NULL,
  fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  leido TINYINT(1) DEFAULT 0
);
```

---

## Diseño visual

- **Fondo:** blanco `#ffffff`, grises neutros `#f5f5f5` / `#e0e0e0`
- **Acento:** azul acero `#4a9eff` (variable CSS `--steel`)
- **Tipografía:** Inter (sans-serif técnica, Google Fonts)
- **Sin ornamentos** — grid estructurado, líneas limpias
- **Footer:** implementado con `skill-footer-standar` (estándar corporativo Reglado)

---

## Variables de entorno

```env
VITE_API_URL=http://localhost/Reglado/RegladoIngenieria/BACKEND
VITE_APILOGING_URL=https://gruporeglado.com/ApiLoging
VITE_TOKEN_KEY=ingenieria_auth_token
```

---

## Fuera de scope (v1)

- Panel admin funcional (solo placeholder)
- Gestión de proyectos desde admin
- La app de parcelas en sí (viene de otro desarrollador)
