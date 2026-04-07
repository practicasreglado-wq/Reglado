# Reglado Energy

Sitio corporativo del ecosistema Reglado para la gestión energética.

## Integración con el Ecosistema
- **Autenticación Delegada**: Utiliza [GrupoReglado](file:///c:/xampp/htdocs/Reglado/GrupoReglado) para el inicio de sesión centralizado.
- **Sesión Compartida**: Detecta automáticamente la identidad mediante la cookie `reglado_auth_token`.
- **Backend Robusto**: Procesa formularios de contacto y mantiene un panel de administración para solicitudes de servicios energéticos.

## Requisitos

- Node.js 18+
- `ApiLoging` funcionando
- `GrupoReglado` funcionando
- XAMPP o servidor PHP para `BACKEND/`
- MySQL o MariaDB para la base del formulario de contacto

## Instalacion

1. Instalar dependencias:

```bash
npm install
```

2. Crear `RegladoEnergy/.env` a partir de `RegladoEnergy/.env.example`.

3. Arrancar el frontend:

```bash
npm run dev
```

4. Generar build:

```bash
npm run build
```

## Variables de entorno del frontend

- `VITE_AUTH_API_URL`
- `VITE_GRUPO_REGLADO_BASE_URL`
- `VITE_GRUPO_REGLADO_LOGIN_PATH`
- `VITE_GRUPO_REGLADO_REGISTER_PATH`
- `VITE_GRUPO_REGLADO_SETTINGS_PATH`
- `VITE_BACKEND_BASE`
- `VITE_CONTACT_ENDPOINT`

## Autenticacion

Energy no tiene login propio.

Flujo:
1. El usuario pulsa `Iniciar sesion / registrarse`.
2. Se redirige a `GrupoReglado`.
3. `GrupoReglado` autentica contra `ApiLoging`.
4. El usuario vuelve a `#/auth/callback?token=...`.
5. Energy inicializa sesion con `/auth/me`.

## Rutas principales

- `#/`
- `#/servicios`
- `#/clientes`
- `#/contacto`
- `#/admin`
- `#/auth/callback`

## Backend PHP

La carpeta `BACKEND/` contiene:
- [contact.php](file:///c:/xampp/htdocs/Reglado/RegladoEnergy/BACKEND/contact.php): recibe formularios con adjuntos.
- [admin_list.php](file:///c:/xampp/htdocs/Reglado/RegladoEnergy/BACKEND/admin_list.php): lista solicitudes.
- [admin_download.php](file:///c:/xampp/htdocs/Reglado/RegladoEnergy/BACKEND/admin_download.php): descarga adjuntos.
- [auth.php](file:///c:/xampp/htdocs/Reglado/RegladoEnergy/BACKEND/auth.php): validacion de JWT y rol admin.
- [db.php](file:///c:/xampp/htdocs/Reglado/RegladoEnergy/BACKEND/db.php): conexion a base de datos.
- [sql/facturas.sql](file:///c:/xampp/htdocs/Reglado/RegladoEnergy/BACKEND/sql/facturas.sql): script de tablas.

## Configuracion del backend

1. Ejecutar:

```sql
SOURCE BACKEND/sql/facturas.sql;
```

2. Crear `BACKEND/.env` con la configuracion real del servidor.

3. Configurar `JWT_SECRET` en el backend para que coincida con `ApiLoging`.

Ejemplo de `BACKEND/.env`:

```env
APP_ENV=production
DB_HOST=localhost
DB_PORT=3306
DB_NAME=facturas
DB_USER=TU_USUARIO
DB_PASS=TU_PASSWORD
JWT_SECRET=EL_MISMO_SECRET_DE_APILOGING
CORS_ALLOWED_ORIGINS=https://regladoenergy.com,https://regladogroup.com
CONTACT_MAIL_TO=formulario@regladoenergy.com
CONTACT_MAIL_FROM=no-reply@regladoenergy.com
```

## Seguridad relevante

- El boton admin en frontend es solo UX.
- La proteccion real esta en `BACKEND/admin_list.php` y `BACKEND/admin_download.php`.
- Ambos exigen token valido y rol `admin`.

## Archivos clave

- [src/components/SiteHeader.vue](file:///c:/xampp/htdocs/Reglado/RegladoEnergy/src/components/SiteHeader.vue)
- [src/pages/AuthCallback.vue](file:///c:/xampp/htdocs/Reglado/RegladoEnergy/src/pages/AuthCallback.vue)
- [src/pages/Admin.vue](file:///c:/xampp/htdocs/Reglado/RegladoEnergy/src/pages/Admin.vue)
- [src/services/auth.js](file:///c:/xampp/htdocs/Reglado/RegladoEnergy/src/services/auth.js)
- [src/router/index.js](file:///c:/xampp/htdocs/Reglado/RegladoEnergy/src/router/index.js)
