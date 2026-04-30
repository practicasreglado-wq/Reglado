# SEO — Pendientes tras la jornada del 2026-04-30

Este doc recoge mejoras SEO identificadas durante la jornada del 2026-04-30 que no se aplicaron por falta de tiempo o porque no son críticas. Ordenadas por impacto.

---

## 🟠 Canonical y og:url dinámicos por ruta

**Estado:** Pendiente. Detectado en Lighthouse de RegladoMaps en producción (SEO 92 con esta única queja: *"Apunta a la URL raíz del dominio (la página principal), en lugar de a una página de contenido equivalente"*).

**Problema:** En SPA Vue + Vite, el `index.html` es compartido por todas las rutas. Los meta tags estáticos están fijos:

```html
<link rel="canonical" href="https://regladomaps.com/" />
<meta property="og:url" content="https://regladomaps.com/" />
```

Cuando Lighthouse audita `/mapa`, ve estos valores apuntando a la home en lugar de a `/mapa`. Lo mismo aplicará a Energy en `/servicios`, `/contacto`, etc., y a Grupo en `/configuracion`, etc.

**Fix propuesto:** En el `router.afterEach` de cada frontend, actualizar dinámicamente esos atributos:

```js
const SITE_URL = "https://regladomaps.com";  // por proyecto

router.afterEach((to) => {
  document.title = to.meta.title || DEFAULT_TITLE;

  const fullUrl = SITE_URL + to.path;
  const canonical = document.querySelector('link[rel="canonical"]');
  if (canonical) canonical.setAttribute('href', fullUrl);
  const ogUrl = document.querySelector('meta[property="og:url"]');
  if (ogUrl) ogUrl.setAttribute('content', fullUrl);
});
```

**Aplica a:** RegladoMaps, RegladoEnergy, GrupoReglado, RegladoIngenieria (cuando tenga dominio), Inmobiliaria_Reglados (externo).

**Coste:** ~10 minutos por frontend. Requiere build + nueva release para que llegue a producción.

**Nota:** Lighthouse y Googlebot moderno ejecutan JS y leen el DOM final, así que esta solución dinámica es suficiente. Crawlers menos sofisticados (algunos bots de redes sociales antiguos) podrían leer solo el HTML estático — para esos haría falta SSR/SSG, lo que es un cambio arquitectónico grande fuera del alcance.

---

## 🟡 Performance — pesos de fuente Outfit innecesarios

**Estado:** Pendiente.

**Problema:** Los `index.html` de los frontends que usan Outfit (Maps, Grupo) cargan desde Google Fonts pesos `300;400;500;600;700;800;900` (siete pesos). Probablemente solo se usan 3-4 en el código real. Cargar siete = más archivos de fuente bloqueando render.

**Fix propuesto:**
1. Auditar qué `font-weight` se usa realmente en el CSS de cada frontend (con `grep -rE "font-weight:" src/`).
2. Reducir la URL de Google Fonts a solo los pesos en uso.

**Aplica a:** Cualquier frontend que cargue Outfit con muchos pesos (verificar Energy, Maps, Grupo, Ingeniería, Inmobiliaria).

**Coste:** ~30 min por frontend (auditoría + cambio + verificación visual).

---

## 🟡 bfcache (Back-Forward Cache) bloqueado

**Estado:** Pendiente.

**Problema:** Lighthouse en RegladoMaps dice *"La página ha impedido la restauración de la caché de páginas completas — 1 motivo del error"*. Algún listener (probablemente `unload`/`beforeunload`) bloquea el bfcache, lo que hace que la navegación back-forward sea más lenta para el usuario.

**Diagnóstico:** Abrir DevTools → Application → Back/Forward Cache. El panel indica el motivo exacto del bloqueo.

**Aplica a:** Probable también en Energy y Grupo (no medido).

**Coste:** Variable. Si es un listener obvio, 10-15 min. Si es algo de una librería externa, puede requerir más.

---

## 🟡 Compresión más agresiva del vídeo de Maps

**Estado:** Pendiente.

**Problema:** El hero de Maps está en `public/video/video.mp4` con 7.5 MB tras la compresión H.264 CRF 28 que aplicamos hoy. Un loop hero podría bajarse a ~2-3 MB con CRF 32 sin pérdida visual perceptible (especialmente con el `video-overlay` oscuro encima que esconde artefactos).

**Fix propuesto:** Recompresión con `ffmpeg -i ... -crf 32 -preset slow -an ...`. Verificar visualmente y, si OK, sustituir.

**Coste:** 15 min.

---

## 🟢 Bundle analyzer (visualizar qué pesa)

**Estado:** Pendiente, opcional. Diagnóstico, no fix.

**Idea:** Añadir `rollup-plugin-visualizer` temporalmente al `vite.config.js` para generar un treemap del bundle JS y entender qué dependencias pesan más. Útil si en una iteración futura se quiere reducir el tamaño del bundle inicial (que está en ~150 KB / ~55 KB gzip — no urgente).

**Coste:** ~15 min para añadir + lanzar. Eliminar después.

---

## Convenciones aplicadas hoy (referencia)

Para que cualquier proyecto nuevo del ecosistema parta del mismo nivel SEO:

- `<title>` específico por proyecto (40-60 chars).
- `meta description` 120-160 chars.
- `meta robots`, `author`, `keywords` siempre presentes.
- Open Graph completo: `og:type`, `og:site_name`, `og:title`, `og:description`, `og:url`, `og:image` (1200×630px en `public/og-image.png`).
- Twitter Cards completo: `twitter:card=summary_large_image` + título/desc/imagen.
- JSON-LD Schema.org `Organization` o `LocalBusiness`.
- Favicon en SVG y PNG.
- Canonical apuntando al dominio prod.
- `public/robots.txt` con `Disallow:` de rutas internas + `Sitemap:` reference.
- `public/sitemap.xml` con todas las rutas públicas + `lastmod` opcional.
- Router con `meta.title` por ruta + `router.afterEach` que sincroniza `document.title`.
- Estructura HTML semántica: `<main>`, `<header>`, `<nav>`, `<footer>`, `<section>` por bloque temático, `<h1>` único por vista.
- Lazy import de rutas no-home en el router.
- Imágenes pesadas en WebP, redimensionadas al tamaño de uso real.
- Vídeos comprimidos con bitrate razonable, sin audio si son loops decorativos.
- `<img>` con `width`/`height` explícitos para evitar CLS.
- Contraste de botones con texto blanco ≥ 4.5:1 en cualquier modo (light/dark) para WCAG AA.
