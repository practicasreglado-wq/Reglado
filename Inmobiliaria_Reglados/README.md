# Inmobiliaria_Reglados

Frontend inmobiliario integrado con `GrupoReglado` y `ApiLoging`.

El proyecto usa autenticacion centralizada:
- login y registro se hacen en `GrupoReglado`
- el JWT lo emite `ApiLoging`
- la base local de inmobiliaria solo guarda datos propios del producto

## Requisitos

- Node.js 18+
- `ApiLoging` funcionando
- `GrupoReglado` funcionando
- servidor PHP para `backend/`
- MySQL o MariaDB para la base local `inmobiliaria`

## Instalacion

1. Instalar dependencias:

```bash
npm install
```

2. Crear `Inmobiliaria_Reglados/.env` a partir de `Inmobiliaria_Reglados/.env.example`.

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
- `VITE_INMOBILIARIA_BACKEND_BASE`

## Flujo de autenticacion

1. El usuario pulsa `Iniciar sesion`.
2. El proyecto redirige a `GrupoReglado`.
3. `GrupoReglado` autentica contra `ApiLoging`.
4. El usuario vuelve a `/auth/callback?token=...`.
5. El callback guarda el token y carga:
   - datos globales desde `ApiLoging`
   - datos locales desde `backend/get_user_data.php`
6. Si todo va bien, se redirige a `/dashboard`.

## Base de datos local esperada

La parte local de inmobiliaria trabaja con una tabla `inmobiliaria` ligada al usuario central por `iduser`.

Campos esperados:
- `id`
- `iduser`
- `categoria`
- `preferencias`

Ademas, los endpoints de propiedades asumen `iduser` en las tablas de propiedades o favoritas si quieres relacionarlas con el usuario autenticado.

## Backend PHP

Archivos principales:
- [backend/config/auth.php](c:\xampp\htdocs\Reglado\Inmobiliaria_Reglados\backend\config\auth.php): valida JWT y asegura un registro local.
- [backend/config/db.php](c:\xampp\htdocs\Reglado\Inmobiliaria_Reglados\backend\config\db.php): conexion MySQL.
- [backend/get_user_data.php](c:\xampp\htdocs\Reglado\Inmobiliaria_Reglados\backend\get_user_data.php): mezcla datos de auth y locales.
- [backend/save_preferences.php](c:\xampp\htdocs\Reglado\Inmobiliaria_Reglados\backend\save_preferences.php): guarda preferencias.
- [backend/api/createProperty.php](c:\xampp\htdocs\Reglado\Inmobiliaria_Reglados\backend\api\createProperty.php)
- [backend/api/get_favorite_properties.php](c:\xampp\htdocs\Reglado\Inmobiliaria_Reglados\backend\api\get_favorite_properties.php)
- [backend/api/get_user_properties.php](c:\xampp\htdocs\Reglado\Inmobiliaria_Reglados\backend\api\get_user_properties.php)

## Rutas principales

- `/`
- `/login`
- `/register`
- `/auth/callback`
- `/dashboard`
- `/profile`
- `/profile/settings`

## Archivos clave del frontend

- [src/services/auth.js](c:\xampp\htdocs\Reglado\Inmobiliaria_Reglados\src\services\auth.js)
- [src/services/backend.js](c:\xampp\htdocs\Reglado\Inmobiliaria_Reglados\src\services\backend.js)
- [src/stores/user.js](c:\xampp\htdocs\Reglado\Inmobiliaria_Reglados\src\stores\user.js)
- [src/views/AuthCallback.vue](c:\xampp\htdocs\Reglado\Inmobiliaria_Reglados\src\views\AuthCallback.vue)
- [src/components/Header.vue](c:\xampp\htdocs\Reglado\Inmobiliaria_Reglados\src\components\Header.vue)
- [src/router/index.js](c:\xampp\htdocs\Reglado\Inmobiliaria_Reglados\src\router\index.js)

## Nota importante

En este repo siguen existiendo archivos del sistema antiguo de login en `backend/`, pero el flujo correcto actual debe pasar siempre por `GrupoReglado` y `ApiLoging`.
