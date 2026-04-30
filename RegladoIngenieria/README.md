# RegladoIngenieria

Web corporativa de Reglado Ingeniería: consultoría técnica industrial,
análisis de parcelas y proyectos de ingeniería. Autenticación delegada en
el SSO Hub de `GrupoReglado`.

## Qué hace

- Sitio corporativo (servicios, casos, contacto).
- Formulario de contacto con backend PHP propio.
- Integración con SSO Hub para sesión compartida con el resto del ecosistema.

## Stack

- **Frontend**: Vue 3 + Vite, Vue Router (modo `history`).
- **Backend** (`BACKEND/`): PHP 8 + MySQL/MariaDB.
- JWT firmado por ApiLoging, validado por el backend de Ingeniería.

## Cómo arrancar (dev)

**Frontend:**

```bash
npm install                      # solo la primera vez
cp .env.example .env             # ajustar valores
npm run dev -- --port 5177       # arranca en http://localhost:5177
```

**Backend PHP:**

```bash
cd BACKEND
cp .env.example .env             # ajustar BD + JWT_SECRET (mismo que ApiLoging)
php -S localhost:8003            # endpoint usado por el formulario de contacto
```

Requisitos: **Node 18+**, **PHP 8.1+**, **MySQL/MariaDB**, **ApiLoging** y **GrupoReglado** corriendo.

## Servicio en el ecosistema

- **Consume**: `ApiLoging` (identidad) y `GrupoReglado` (SSO Hub).
- **No expone APIs públicas**: el `BACKEND/` es de uso interno (formulario de contacto).
- El frontend NO tiene login propio — todo redirige al hub.

## Dominio en producción

Pendiente de asignar dominio definitivo.

## Variables de entorno

**Frontend (`.env`):**

```
VITE_AUTH_API_URL=http://localhost:8000
VITE_GRUPO_REGLADO_BASE_URL=http://localhost:5173
VITE_BACKEND_BASE=http://localhost:8003
```

**Backend (`BACKEND/.env`):**

```
APP_ENV=development
DB_HOST=localhost
DB_NAME=...
DB_USER=...
DB_PASS=...
JWT_SECRET=EL_MISMO_QUE_APILOGING
CORS_ALLOWED_ORIGINS=http://localhost:5177
```

Ejemplos completos en [`.env.example`](.env.example) y [`BACKEND/.env.example`](BACKEND/.env.example).

## Estructura

```
RegladoIngenieria/
├── public/                     # assets estáticos
├── src/
│   ├── components/             # Header, Footer, Icon, LoginModal, ...
│   ├── pages/                  # vistas por ruta (Home, Admin, ...)
│   ├── services/               # auth.js, ssoClient.js
│   ├── router/                 # con meta.title por ruta
│   ├── App.vue
│   └── main.js
├── BACKEND/
│   ├── contact.php             # recibe formularios
│   ├── auth.php                # validación JWT
│   ├── bootstrap.php           # init común
│   ├── db.php                  # conexión MySQL
│   ├── security.php            # utilidades de seguridad
│   └── sql/                    # esquema BD
├── docs/                       # documentación interna
├── index.html                  # con CSP, JSON-LD, meta tags SEO
└── package.json
```

## Más documentación

- [docs/](docs/) — documentación interna del proyecto.
- [README raíz del repo](../README.md) — visión global del ecosistema.
- [docs/ECOSYSTEM_AUTH_SSO_HUB.md](../docs/ECOSYSTEM_AUTH_SSO_HUB.md) — spec del SSO Hub.
