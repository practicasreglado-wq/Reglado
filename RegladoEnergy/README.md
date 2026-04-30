# RegladoEnergy

Web corporativa de Reglado Energy: consultoría energética independiente para
particulares, empresas, sector público y administradores de fincas.
Autenticación delegada en el SSO Hub de `GrupoReglado`.

## Qué hace

- Sitio corporativo (servicios, casos, contacto).
- Formulario de contacto con backend PHP propio.
- Panel admin de solicitudes (visible solo para usuarios con rol `admin`).
- Integración con SSO Hub para sesión compartida con el resto del ecosistema.

## Stack

- **Frontend**: Vue 3 + Vite, Vue Router (modo `history`).
- **Backend** (`BACKEND/`): PHP 8 + MySQL/MariaDB para gestionar formularios y panel admin.
- JWT firmado por ApiLoging, validado por el backend de Energy.

## Cómo arrancar (dev)

**Frontend:**

```bash
npm install                      # solo la primera vez
cp .env.example .env             # ajustar valores
npm run dev                      # arranca en http://localhost:5174
```

**Backend PHP:**

```bash
cd BACKEND
cp .env.example .env             # ajustar BD + JWT_SECRET (mismo que ApiLoging)
php -S localhost:8001            # endpoint usado por el formulario de contacto
```

Requisitos: **Node 18+**, **PHP 8.1+**, **MySQL/MariaDB**, **ApiLoging** y **GrupoReglado** corriendo.

Primera vez con BD: `SOURCE BACKEND/sql/facturas.sql;` desde MySQL.

## Servicio en el ecosistema

- **Consume**: `ApiLoging` (identidad) y `GrupoReglado` (SSO Hub para login/registro).
- **No expone APIs públicas**: el `BACKEND/` es de uso interno (form de contacto, panel admin).
- El frontend NO tiene login propio — todo redirige al hub.

## Dominio en producción

`https://regladoenergy.com` (apex y `www.`)

## Variables de entorno

**Frontend (`.env`):**

```
VITE_AUTH_API_URL=http://localhost:8000
VITE_GRUPO_REGLADO_BASE_URL=http://localhost:5173
VITE_BACKEND_BASE=http://localhost:8001
VITE_CONTACT_ENDPOINT=/contact.php
```

**Backend (`BACKEND/.env`):**

```
APP_ENV=development
DB_HOST=localhost
DB_NAME=facturas
DB_USER=...
DB_PASS=...
JWT_SECRET=EL_MISMO_QUE_APILOGING
CORS_ALLOWED_ORIGINS=http://localhost:5174,https://regladoenergy.com,https://www.regladoenergy.com
CONTACT_MAIL_TO=info@regladoenergy.com
```

Ejemplos completos en [`.env.example`](.env.example) y [`BACKEND/.env.example`](BACKEND/.env.example).

## Estructura

```
RegladoEnergy/
├── public/                     # assets estáticos (favicon, og-image, sitemap.xml, robots.txt)
├── src/
│   ├── components/             # UI reutilizable (FeatureCard, LoginModal, SiteHeader, ...)
│   ├── pages/                  # vistas por ruta (Home, Services, Contact, Admin, ...)
│   ├── services/               # auth.js, ssoClient.js
│   ├── router/                 # con meta.title por ruta
│   ├── App.vue
│   └── main.js
├── BACKEND/
│   ├── contact.php             # recibe formularios con adjuntos
│   ├── admin_list.php          # lista solicitudes (rol admin)
│   ├── admin_download.php      # descarga adjuntos (rol admin)
│   ├── auth.php                # validación JWT + rol
│   ├── db.php                  # conexión MySQL
│   └── sql/facturas.sql        # esquema BD
├── index.html                  # con CSP, JSON-LD LocalBusiness, meta tags SEO
└── package.json
```

## Seguridad relevante

- El botón "Admin" en el frontend es solo UX — no aporta seguridad.
- La protección real está en `BACKEND/admin_*.php`: ambos exigen JWT válido + rol `admin`.

## Más documentación

- [DOCUMENTACION_PROYECTO.md](DOCUMENTACION_PROYECTO.md) — arquitectura del proyecto.
- [README raíz del repo](../README.md) — visión global del ecosistema.
- [docs/ECOSYSTEM_AUTH_SSO_HUB.md](../docs/ECOSYSTEM_AUTH_SSO_HUB.md) — spec del SSO Hub.
