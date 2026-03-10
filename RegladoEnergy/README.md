# RegladoEnergy

Frontend Vue de Reglado Energy, integrado con `ApiLoging` para autenticacion y con backend PHP propio para el formulario de contacto y el panel admin.

## Requisitos

- Node.js 18+
- `ApiLoging` funcionando
- XAMPP o servidor PHP para `BACKEND/`
- MySQL o MariaDB para la base `facturas`

## Instalacion

1. Instala dependencias:

```bash
npm install
```

2. Crea `\.env` a partir de `\.env.example`.

3. Arranca el frontend:

```bash
npm run dev
```

4. Build de produccion:

```bash
npm run build
```

## Variables de entorno del frontend

- `VITE_AUTH_API_URL`: URL base de `ApiLoging`
- `VITE_GRUPO_REGLADO_BASE_URL`: URL base de `GrupoReglado`
- `VITE_GRUPO_REGLADO_LOGIN_PATH`: ruta de login en `GrupoReglado`
- `VITE_GRUPO_REGLADO_REGISTER_PATH`: ruta de registro en `GrupoReglado`
- `VITE_GRUPO_REGLADO_SETTINGS_PATH`: ruta de configuracion en `GrupoReglado`
- `VITE_CONTACT_ENDPOINT`: endpoint PHP que recibe el formulario de contacto

## Rutas principales

- `#/`
- `#/contacto`
- `#/area-clientes`
- `#/admin`
- `#/auth/callback`

Usa `hash history`, por eso las rutas locales incluyen `#`.

## Autenticacion

Energy no tiene formulario propio. El flujo actual es:

1. El usuario pulsa `Iniciar sesion / registrarse`
2. Se redirige a `GrupoReglado`
3. `GrupoReglado` autentica al usuario con `ApiLoging`
4. Se vuelve a Energy por `#/auth/callback?token=...`
5. Energy guarda token, inicializa sesion y vuelve al inicio

Detalles ya integrados:

- lectura del JWT
- carga de sesion con `/auth/me`
- logout
- acceso a configuracion redirigiendo a `GrupoReglado`
- icono de usuario en el header

## Header y roles

- el boton de usuario abre menu con `Configuracion` y `Cerrar sesion`
- existe un boton de admin visible solo si `user.role === "admin"`
- el panel admin real no depende del frontend: el backend valida JWT y exige rol `admin`

## Contacto y backend PHP

La carpeta `BACKEND/` contiene:

- `contact.php`: recibe formularios con archivo adjunto
- `admin_list.php`: lista solicitudes para administracion
- `admin_download.php`: descarga adjuntos
- `auth.php`: valida JWT y permisos de admin
- `db.php`: conexion a MySQL
- `sql/facturas.sql`: script de creacion de base de datos

## Configuracion del backend PHP

1. Ejecuta:

```sql
SOURCE BACKEND/sql/facturas.sql;
```

2. Ajusta credenciales de DB en `BACKEND/db.php` si hace falta.

3. Configura `BACKEND/.env` a partir de `BACKEND/.env.example` con:

```env
JWT_SECRET=tu_mismo_jwt_secret_de_apiloging
```

Ese valor debe coincidir con `JWT_SECRET` de `ApiLoging`, o la validacion de tokens fallara.

## Seguridad

- ocultar el boton admin en frontend es solo UX
- la proteccion real esta en `BACKEND/admin_list.php` y `BACKEND/admin_download.php`
- ambos exigen `Authorization: Bearer <token>` valido y rol `admin`

## Integracion local recomendada

Ejemplo habitual:

- `ApiLoging`: `http://localhost:8000`
- `GrupoReglado`: `http://localhost:5173`
- `RegladoEnergy`: `http://localhost:5174`
- backend PHP contacto/admin: `http://localhost/Reglado/RegladoEnergy/BACKEND/...`
