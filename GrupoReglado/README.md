# GrupoReglado

Frontend principal del ecosistema Reglado.

Se encarga de:
- portal corporativo con enlaces al resto de proyectos
- login y registro centralizados
- verificacion de correo
- recuperacion de contrasena
- configuracion de cuenta
- acceso a panel admin de usuarios

## Requisitos

- Node.js 18+
- `ApiLoging` funcionando

## Instalacion

1. Instalar dependencias:

```bash
npm install
```

2. Crear `GrupoReglado/.env` a partir de `GrupoReglado/.env.example`.
   Para despliegue en Hostinger puedes partir de `GrupoReglado/.env.production.example`.

3. Arrancar en desarrollo:

```bash
npm run dev
```

4. Build:

```bash
npm run build
```

## Variables de entorno

- `VITE_AUTH_API_URL`
- `VITE_REGLADO_REALSTATE_URL`
- `VITE_REGLADO_ENERGY_URL`
- `VITE_REGLADO_MAPAS_URL`
- `VITE_REGLADO_ENPROCESO_URL`

Ejemplo de produccion:

```env
VITE_AUTH_API_URL=https://regladogroup.com
VITE_REGLADO_REALSTATE_URL=https://regladorealestate.com
VITE_REGLADO_ENERGY_URL=https://regladoenergy.com
VITE_REGLADO_MAPAS_URL=#
VITE_REGLADO_ENPROCESO_URL=#
```

## Rutas principales

- `/`
- `/login`
- `/registro`
- `/recuperar-contrasena`
- `/restablecer-contrasena`
- `/configuracion`
- `/verificacion-exitosa`
- `/admin`

## Flujo de autenticacion

1. El usuario entra en `GrupoReglado`.
2. El frontend llama a `ApiLoging`.
3. Si el login es correcto, se guarda JWT en `localStorage` y cookie.
4. Si llega una URL `returnTo`, el usuario vuelve al proyecto origen con `?token=...`.

## Estructura util

- [src/pages/PortalView.vue](c:\xampp\htdocs\Reglado\GrupoReglado\src\pages\PortalView.vue): home del portal.
- [src/components/SiteHeader.vue](c:\xampp\htdocs\Reglado\GrupoReglado\src\components\SiteHeader.vue): cabecera y sesion.
- [src/components/SiteFooter.vue](c:\xampp\htdocs\Reglado\GrupoReglado\src\components\SiteFooter.vue): pie global.
- [src/components/LoginModal.vue](c:\xampp\htdocs\Reglado\GrupoReglado\src\components\LoginModal.vue): modal de login.
- [src/components/auth/RegisterForm.vue](c:\xampp\htdocs\Reglado\GrupoReglado\src\components\auth\RegisterForm.vue): formulario de registro.
- [src/services/auth.js](c:\xampp\htdocs\Reglado\GrupoReglado\src\services\auth.js): cliente de autenticacion.
- [src/router/index.js](c:\xampp\htdocs\Reglado\GrupoReglado\src\router\index.js): rutas.

## Relacion con otros proyectos

- `RegladoEnergy` y `Inmobiliaria_Reglados` redirigen aqui para login y registro.
- `GrupoReglado` no valida permisos de producto ajenos: solo autentica y devuelve el token.

## Nota operativa

Si cambias el rol de un usuario en base de datos, ese usuario debe cerrar sesion y volver a iniciar sesion para recibir un JWT nuevo con el rol actualizado.
