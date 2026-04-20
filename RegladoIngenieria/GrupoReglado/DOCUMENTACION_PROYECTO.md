# Documentacion del proyecto RegladoGroup

## 1. Resumen
`RegladoGroup` es una aplicacion web en Vue 3 (Vite) que funciona como portal corporativo con autenticacion de usuarios.

Incluye:
- Portal de accesos (home) con enlaces a plataformas internas.
- Registro e inicio de sesion.
- Verificacion de email.
- Pantalla de configuracion de cuenta (usuario, nombre, email, telefono, contrasena).

## 2. Stack y entrada de la app
- Framework: Vue 3 (Composition API + `<script setup>`).
- Bundler: Vite.
- Router: Vue Router.
- Estilos: CSS global en `src/styles.css` + estilos `scoped` en algunos componentes.

Flujo de arranque:
1. `src/main.js` crea la app.
2. Monta `App.vue`.
3. Registra el router.
4. Carga estilos globales.

## 3. Estructura de carpetas
```text
src/
  assets/                # Logos e iconos
  components/            # Componentes reutilizables de UI
    auth/                # Componentes de autenticacion
    portal/              # Componentes de la home/portal
  pages/                 # Vistas de ruta (pantallas principales)
  router/                # Definicion de rutas
  services/              # Logica de acceso a API (auth)
  App.vue                # Layout principal
  main.js                # Punto de entrada
  styles.css             # Estilos globales
```

## 4. Donde se alojan los componentes y que hace cada uno

### 4.1 Componentes globales (`src/components`)

`src/components/SiteHeader.vue`
- Header fijo superior.
- Muestra logo y enlaces a otras plataformas (`Realstate`, `Energy`, `Mapas`, `EnProceso`).
- Si hay usuario logueado, muestra menu de usuario (configuracion/cerrar sesion).
- Si no hay usuario, muestra boton `Login`.
- Emite eventos:
  - `open-login`
  - `logout`

`src/components/SiteFooter.vue`
- Footer global con año dinamico.
- Mensaje corporativo y derechos reservados.

`src/components/LoginModal.vue`
- Modal de inicio de sesion reutilizable.
- Usa `auth.login()` y `auth.resendVerification()`.
- Props:
  - `open` (Boolean) para mostrar/ocultar.
- Emite:
  - `close`
  - `success`

### 4.2 Componentes de autenticacion (`src/components/auth`)

`src/components/auth/RegisterForm.vue`
- Formulario de alta de usuario.
- Valida coincidencia de contrasenas.
- Llama `auth.register()` para crear la cuenta.
- Es reutilizable porque recibe textos por props:
  - `title`
  - `description`
  - `buttonText`

### 4.3 Componentes del portal/home (`src/components/portal`)

`src/components/portal/PortalHero.vue`
- Bloque superior de bienvenida.
- Recibe `title` y `subtitle`.

`src/components/portal/PortalGrid.vue`
- Contenedor en rejilla para tarjetas de accesos.
- Recibe un array `portals`.
- Renderiza una `PortalCard` por cada item.

`src/components/portal/PortalCard.vue`
- Tarjeta individual de acceso.
- Recibe:
  - `title`
  - `description`
  - `href`
- Abre el enlace en nueva pestaña.

`src/components/portal/HelpSection.vue`
- Bloque final de ayuda/contacto.
- Recibe `title` y `description`.

## 5. Paginas (vistas de ruta) en `src/pages`

`src/pages/PortalView.vue`
- Es la home (`/`).
- Compone:
  - `PortalHero`
  - `PortalGrid`
  - `HelpSection`
- Define el array `portals` con los enlaces del portal.

`src/pages/LoginView.vue`
- Pantalla completa de login (alternativa al modal).
- Soporta `returnTo` por query param para redireccion externa con token.
- Permite reenviar correo de verificacion.

`src/pages/RegisterView.vue`
- Pantalla de registro.
- Usa `RegisterForm`.
- Si llega `returnTo`, lo guarda en `sessionStorage` (`auth_return_to`).

`src/pages/EmailVerifiedView.vue`
- Procesa verificacion de email via query `token`.
- Inicializa sesion con ese token.
- Redirecciona automaticamente tras cuenta regresiva.

`src/pages/SettingsView.vue`
- Pantalla de configuracion de cuenta.
- Requiere usuario autenticado.
- Permite actualizar:
  - Nombre de usuario
  - Nombre/apellido
  - Email (solicitud de cambio)
  - Telefono
  - Contrasena

## 6. Layout principal y composicion

`src/App.vue` monta la estructura global:
1. `SiteHeader`
2. `RouterView` (la pagina actual)
3. `SiteFooter`
4. `LoginModal` (controlado por estado local `showLogin`)

Ademas, en `onMounted` ejecuta `auth.initialize()` para recuperar sesion si existe token.

## 7. Servicio de autenticacion (`src/services/auth.js`)

Este archivo concentra toda la logica de auth:
- Estado reactivo:
  - `token`
  - `user`
  - `loading`
- Persistencia de token:
  - `localStorage` (`auth_token`)
  - Cookie (`reglado_auth_token`)
- Metodos principales:
  - `initialize`, `login`, `logout`, `register`
  - `updateUsername`, `updateName`, `updatePhone`
  - `requestEmailChange`, `changePassword`
  - `resendVerification`

El backend base sale de `VITE_AUTH_API_URL` o usa `http://localhost:8000` por defecto.

## 8. Router y rutas

Archivo: `src/router/index.js`

Rutas definidas:
- `/` -> Portal
- `/login` -> Login
- `/registro` -> Registro
- `/configuracion` -> Configuracion
- `/verificacion-exitosa` -> Verificacion de email

Nota tecnica importante:
- Actualmente el router importa desde `../views/...`.
- En la estructura actual los archivos estan en `src/pages/...`.
- Conviene alinear imports del router a `../pages/...` para evitar errores de resolucion.

## 9. Variables de entorno relevantes

En `.env`/`.env.example`:
- `VITE_AUTH_API_URL`
- `VITE_REGLADO_REALSTATE_URL`
- `VITE_REGLADO_ENERGY_URL`
- `VITE_REGLADO_MAPAS_URL`
- `VITE_REGLADO_ENPROCESO_URL`

## 10. Resumen rapido (lo mas importante)
- Las pantallas principales viven en `src/pages`.
- Los componentes reutilizables viven en `src/components`.
- La home se construye en `src/pages/PortalView.vue` usando componentes de `src/components/portal`.
- La autenticacion se centraliza en `src/services/auth.js`.
