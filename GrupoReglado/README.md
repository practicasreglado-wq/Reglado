# GrupoReglado

Frontend principal del ecosistema Reglado y **hub central de identidad/SSO**:
todos los demás frontends redirigen aquí para login, registro y propagación
de sesión cross-domain.

## Qué hace

- Portal corporativo con enlaces al resto de productos (Energy, Maps, Ingeniería, Inmobiliaria).
- Login, registro, verificación de email, recuperación de contraseña.
- Configuración de cuenta del usuario.
- Panel admin de usuarios.
- **SSO Hub**: vistas `/sso-handshake`, `/sso-store`, `/sso-logout` que sincronizan sesión entre dominios independientes vía fragment-token.

## Stack

- Vue 3 (Composition API) + Vite
- Vue Router (modo `history`)
- Cliente HTTP nativo `fetch`
- Cookie compartida `reglado_auth_token` (autenticación local del propio dominio)

## Cómo arrancar (dev)

```bash
npm install                      # solo la primera vez
cp .env.example .env             # ajustar valores
npm run dev                      # arranca en http://localhost:5173
```

Requisitos: **Node 18+**, **ApiLoging** corriendo en `http://localhost:8000`.

## Servicio en el ecosistema

- **Consume**: `ApiLoging` (puerto 8000) — única fuente de verdad de identidad.
- **Es consumido por**: Energy, Maps, Ingeniería, Inmobiliaria — todos redirigen aquí para login y propagación de sesión vía SSO Hub.
- No valida permisos de productos ajenos: solo autentica y devuelve el JWT.

## Dominio en producción

`https://regladogroup.com` (apex y `www.`)

## Variables de entorno

```
VITE_AUTH_API_URL=http://localhost:8000
VITE_REGLADO_REALSTATE_URL=...
VITE_REGLADO_ENERGY_URL=...
VITE_REGLADO_MAPAS_URL=...
VITE_REGLADO_ENPROCESO_URL=...
```

Ejemplos completos en [`.env.example`](.env.example) y [`.env.production.example`](.env.production.example).

## Estructura

```
GrupoReglado/
├── public/             # assets estáticos (favicon, og-image, sitemap.xml, robots.txt)
├── src/
│   ├── components/     # UI reutilizable (LoginModal, SiteHeader, SiteFooter, ...)
│   ├── pages/          # vistas asociadas a rutas (Login, Register, Portal, SsoHandshake, ...)
│   ├── views/          # vistas legacy (en migración a pages/)
│   ├── services/       # auth.js, ssoHub.js, ssoClient.js
│   ├── router/         # vue-router con meta.title por ruta
│   ├── App.vue
│   └── main.js
├── index.html          # con CSP, JSON-LD Organization, meta tags SEO
├── package.json
└── vite.config.js
```

## Más documentación

- [DOCUMENTACION_PROYECTO.md](DOCUMENTACION_PROYECTO.md) — arquitectura interna y flujos detallados.
- [FUNCIONALIDAD.md](FUNCIONALIDAD.md) — funcionalidades por vista.
- [README raíz del repo](../README.md) — visión global del ecosistema.
- [docs/ECOSYSTEM_AUTH_SSO_HUB.md](../docs/ECOSYSTEM_AUTH_SSO_HUB.md) — spec del SSO Hub.

## Nota operativa

Tras un cambio de rol en BBDD, el usuario debe cerrar sesión y volver a
iniciarla para recibir un JWT con el rol actualizado.
