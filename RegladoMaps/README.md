# RegladoMaps

Visualización cartográfica interactiva del **mapa energético de España**:
plantas de producción (eólica, solar, hidráulica, biodiésel, biometano,
hidrógeno) sobre un mapa navegable.

## Qué hace

- Visor interactivo de plantas energéticas por tipo de tecnología.
- Filtros por categoría, búsqueda por ubicación.
- Páginas legales (aviso legal, privacidad, cookies) con SSO compartido.

## Stack

- Vue 3 + Vite, Vue Router (modo `history`).
- Backend de datos: [`ApiMapa/`](../ApiMapa/) (PHP) — sirve los puntos del mapa.
- SSO compartido vía hub `GrupoReglado` para sesión cross-domain.

## Cómo arrancar (dev)

```bash
npm install                      # solo la primera vez
npm run dev -- --port 5176       # arranca en http://localhost:5176
```

El backend (`ApiMapa/`) se sirve aparte con XAMPP/Apache (no con `php -S`)
porque vive bajo `regladoconsultores.com/mapa/` en producción.

Requisitos: **Node 18+**, **ApiLoging** corriendo (validación JWT) y opcionalmente
**ApiMapa** local si se quieren datos en dev.

## Servicio en el ecosistema

- **Consume**: `ApiLoging` (identidad) y `ApiMapa` (datos geográficos).
- **No expone APIs propias**: es solo frontend.
- Sesión sincronizada con el resto del ecosistema vía SSO Hub de Grupo.

## Dominio en producción

`https://regladomaps.com` (apex y `www.`)

## Variables de entorno

```
VITE_AUTH_API_URL=http://localhost:8000
VITE_GRUPO_REGLADO_BASE_URL=http://localhost:5173
VITE_API_MAPA_URL=...
```

Ejemplo completo en `.env.example` (si existe; añadir cuando se necesite).

## Estructura

```
RegladoMaps/
├── public/
│   ├── video/video.mp4         # hero del home (loop de 21s, 7.5 MB sin audio)
│   ├── apimapa/                # bundle estático del backend antiguo
│   └── favicon.png, sitemap.xml, robots.txt
├── src/
│   ├── components/             # MapView, AdminPanel, PoliticaCookies, ...
│   ├── services/               # auth.js, ssoClient.js
│   ├── App.vue
│   ├── main.js
│   └── router.js               # rutas + meta.title
├── index.html                  # con CSP, JSON-LD, meta tags SEO
└── package.json
```

## Más documentación

- [FUNCIONALIDAD.md](FUNCIONALIDAD.md) — funcionalidades por vista.
- [ApiMapa/README.md](../ApiMapa/README.md) — backend que sirve los datos del mapa.
- [README raíz del repo](../README.md) — visión global del ecosistema.
