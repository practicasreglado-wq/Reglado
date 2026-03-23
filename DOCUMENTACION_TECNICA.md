# Documentacion Tecnica Del Ecosistema Reglado

## 1. Vision general

El ecosistema esta dividido en cuatro proyectos principales:

- [ApiLoging](c:\xampp\htdocs\Reglado\ApiLoging): API central de autenticacion.
- [GrupoReglado](c:\xampp\htdocs\Reglado\GrupoReglado): portal principal y punto comun de login, registro y configuracion.
- [RegladoEnergy](c:\xampp\htdocs\Reglado\RegladoEnergy): web corporativa de energy con backend PHP propio.
- [Inmobiliaria_Reglados](c:\xampp\htdocs\Reglado\Inmobiliaria_Reglados): producto inmobiliario con backend PHP propio.

La regla principal es esta:

- la identidad del usuario vive en `ApiLoging`
- el acceso de usuarios se hace visualmente desde `GrupoReglado`
- cada producto mantiene su propia base de datos para sus datos de negocio
- cada backend de producto valida el JWT por su cuenta

## 2. Arquitectura

### 2.1 Identidad central

`ApiLoging` es la unica fuente de verdad para:

- usuarios
- contrasenas
- roles
- verificacion de correo
- recuperacion de contrasena
- cambio de email

Su base de datos principal es `regladousers`.

### 2.2 Portal comun

`GrupoReglado` actua como frontend comun para:

- login
- registro
- confirmacion de email
- recuperacion de contrasena
- configuracion del usuario
- administracion de usuarios

No guarda datos de negocio de otros productos.

### 2.3 Productos

`RegladoEnergy` e `Inmobiliaria_Reglados` consumen la identidad central, pero cada uno guarda sus propios datos en su propia base de datos.

Ejemplos:

- `RegladoEnergy` guarda solicitudes del formulario de contacto en su propia base.
- `Inmobiliaria_Reglados` guarda `categoria`, `preferencias` y datos propios del producto en su base `inmobiliaria`.

## 3. Flujo de autenticacion

### 3.1 Login desde un producto

Flujo comun para `RegladoEnergy` e `Inmobiliaria_Reglados`:

1. El usuario pulsa `Iniciar sesion` en el producto.
2. El producto redirige a `GrupoReglado /login?returnTo=...`.
3. `GrupoReglado` llama a `ApiLoging /auth/login`.
4. `ApiLoging` devuelve un JWT.
5. `GrupoReglado` redirige a `returnTo` con `?token=...`.
6. El producto recibe el token en su ruta callback.
7. El producto llama a `ApiLoging /auth/me`.
8. El producto hidrata su sesion local.

### 3.2 Registro

1. El usuario entra en `GrupoReglado /registro`.
2. Se envia el formulario a `ApiLoging /auth/register`.
3. `ApiLoging` guarda el registro pendiente.
4. Se envia correo de confirmacion.
5. El usuario confirma el correo.
6. `ApiLoging` crea el usuario real y emite JWT.
7. `GrupoReglado` inicia sesion y, si hay `returnTo`, devuelve al producto origen.

### 3.3 Recuperacion de contrasena

1. El usuario entra en `GrupoReglado /recuperar-contrasena`.
2. `ApiLoging` envia correo de recuperacion.
3. El usuario entra en `GrupoReglado /restablecer-contrasena?token=...`.
4. Se guarda la nueva contrasena en `ApiLoging`.

## 4. JWT y sesiones

El JWT se genera en `ApiLoging` e incluye al menos:

- `sub`: id del usuario
- `email`
- `role`
- datos basicos usados por los frontends

Puntos importantes:

- si cambias `role` en la base de datos, el usuario debe volver a iniciar sesion
- el frontend no es seguridad; la seguridad real esta en el backend
- cada backend de producto debe validar firma, expiracion y permisos

## 5. Roles y permisos

Actualmente el rol operativo principal es:

- `user`
- `admin`

Uso del rol:

- `GrupoReglado` muestra el acceso a `/admin` solo si `role === "admin"`
- `RegladoEnergy` muestra el boton de panel admin solo si `role === "admin"`
- los backends protegidos deben comprobar tambien `role === "admin"`

## 6. Bases de datos

### 6.1 Base de autenticacion

Proyecto:
- [ApiLoging](c:\xampp\htdocs\Reglado\ApiLoging)

Base principal:
- `regladousers`

Tablas relevantes:
- `users`
- `email_verification_tokens`
- `email_change_tokens`
- `password_reset_tokens`
- `revoked_tokens`
- `rate_limits`
- `security_events`

### 6.2 Base de Energy

Proyecto:
- [RegladoEnergy](c:\xampp\htdocs\Reglado\RegladoEnergy)

Base local:
- `facturas`

Uso:
- solicitudes de contacto
- adjuntos del formulario

### 6.3 Base de Inmobiliaria

Proyecto:
- [Inmobiliaria_Reglados](c:\xampp\htdocs\Reglado\Inmobiliaria_Reglados)

Base local:
- `inmobiliaria`

Uso esperado:
- `iduser`
- `categoria`
- `preferencias`
- relaciones con propiedades o favoritos

Regla de integracion:

- `iduser` debe guardar el `id` real del usuario de `ApiLoging`
- no se debe duplicar el sistema de login en la base local

## 7. Responsabilidad por proyecto

### 7.1 ApiLoging

Responsable de:

- autenticar
- emitir tokens
- gestionar perfil global
- gestionar correo y seguridad de cuenta

No debe guardar datos de negocio de productos.

### 7.2 GrupoReglado

Responsable de:

- UX comun de acceso
- portal corporativo
- pantalla de configuracion
- administracion de usuarios

No debe asumir reglas de negocio especificas de Energy o Inmobiliaria.

### 7.3 RegladoEnergy

Responsable de:

- contenido corporativo energy
- formulario de contacto
- panel admin de solicitudes
- validacion backend de accesos admin propios

### 7.4 Inmobiliaria_Reglados

Responsable de:

- vistas y logica de producto inmobiliario
- preferencias del usuario
- datos y procesos locales del producto

## 8. Variables de entorno por proyecto

### 8.1 ApiLoging

Claves principales:

- `DB_*`
- `JWT_SECRET`
- `CORS_ALLOWED_ORIGINS`
- `REDIRECT_ALLOWED_ORIGINS`
- `EMAIL_*`
- `MAIL_*`

### 8.2 GrupoReglado

- `VITE_AUTH_API_URL`
- `VITE_REGLADO_REALSTATE_URL`
- `VITE_REGLADO_ENERGY_URL`
- `VITE_REGLADO_MAPAS_URL`
- `VITE_REGLADO_ENPROCESO_URL`

### 8.3 RegladoEnergy

- `VITE_AUTH_API_URL`
- `VITE_GRUPO_REGLADO_BASE_URL`
- `VITE_GRUPO_REGLADO_LOGIN_PATH`
- `VITE_GRUPO_REGLADO_REGISTER_PATH`
- `VITE_GRUPO_REGLADO_SETTINGS_PATH`
- `VITE_CONTACT_ENDPOINT`

### 8.4 Inmobiliaria_Reglados

- `VITE_AUTH_API_URL`
- `VITE_GRUPO_REGLADO_BASE_URL`
- `VITE_GRUPO_REGLADO_LOGIN_PATH`
- `VITE_GRUPO_REGLADO_REGISTER_PATH`
- `VITE_INMOBILIARIA_BACKEND_BASE`

## 9. Rutas tecnicas importantes

### GrupoReglado

- `/login`
- `/registro`
- `/recuperar-contrasena`
- `/restablecer-contrasena`
- `/configuracion`
- `/verificacion-exitosa`
- `/admin`

### RegladoEnergy

- `#/auth/callback`
- `#/admin`

### Inmobiliaria_Reglados

- `/auth/callback`
- `/dashboard`
- `/profile`

## 10. Consideraciones de seguridad

Minimos ya aplicados o esperados:

- JWT validado en servidor
- control de rol en backend para paneles admin
- allowlist de CORS
- allowlist de redirecciones
- tokens revocados en `ApiLoging`

Puntos operativos:

- si un producto valida JWT en su backend, debe usar el mismo `JWT_SECRET`
- no confiar en botones ocultos del frontend
- no usar email como clave tecnica si ya tienes `iduser`

## 11. Como integrar un nuevo producto

Pasos recomendados:

1. Crear el frontend del producto.
2. Añadir boton `Iniciar sesion` que apunte a `GrupoReglado`.
3. Crear una ruta callback propia.
4. Guardar el token recibido.
5. Consultar `/auth/me`.
6. Crear una base de datos propia para datos del producto.
7. Guardar la relacion con el usuario central usando `iduser = sub`.
8. Si existe backend propio, validar JWT en servidor.

## 12. Estructura de Código y Comentarios (DocBlocks)

Los archivos principales de la lógica de negocio de este ecosistema están documentados funcionalmente dentro de su propio código fuente para facilitar su mantenimiento:

- **ApiLoging:** Se documentaron los flujos de entrada (Front Controller en `index.php`) y todos los procesos de validación, registro, restablecimiento de accesos y consumo de perfil del backend ubicados en `AuthController.php`.
- **GrupoReglado:** Cuenta con documentación interna en vistas transversales (como `LoginView.vue` y su gestión de redirección `returnTo`), y el servicio base de abstracción a la API (`auth.js`).
- **RegladoEnergy / RegladoMaps:** Su código documenta estructuralmente cómo delegar el login a GrupoReglado y simplemente consumir la identidad inicializada por la cookie inter-proyecto (descrito detalladamente en sus `src/services/auth.js` y `App.vue`).

---

## 13. Problemas comunes

### El rol admin no funciona

Comprobar:

- el rol en base de datos debe ser exactamente `admin`
- el usuario debe volver a iniciar sesion
- el backend que valida el token debe usar el mismo `JWT_SECRET`

### Un producto no vuelve correctamente tras login

Comprobar:

- `returnTo` correcto
- ruta callback real existente
- URL incluida en `REDIRECT_ALLOWED_ORIGINS`

### Un producto no reconoce la sesion

Comprobar:

- que `ApiLoging /auth/me` responde
- que el token se guarda bien
- que el producto hidrata su store al arrancar

## 14. Archivos de referencia rapida

- [ApiLoging/index.php](c:\xampp\htdocs\Reglado\ApiLoging\index.php)
- [ApiLoging/controllers/AuthController.php](c:\xampp\htdocs\Reglado\ApiLoging\controllers\AuthController.php)
- [GrupoReglado/src/services/auth.js](c:\xampp\htdocs\Reglado\GrupoReglado\src\services\auth.js)
- [RegladoEnergy/src/services/auth.js](c:\xampp\htdocs\Reglado\RegladoEnergy\src\services\auth.js)
- [Inmobiliaria_Reglados/src/services/auth.js](c:\xampp\htdocs\Reglado\Inmobiliaria_Reglados\src\services\auth.js)
- [Inmobiliaria_Reglados/src/stores/user.js](c:\xampp\htdocs\Reglado\Inmobiliaria_Reglados\src\stores\user.js)
