// Simple SEO helper for a Vue SPA (no external deps)
function upsertMeta(attrName, attrValue, content, isProperty = false) {
  const selector = isProperty
    ? `meta[property="${attrValue}"]`
    : `meta[${attrName}="${attrValue}"]`;
  let el = document.querySelector(selector);
  if (!el) {
    el = document.createElement("meta");
    if (isProperty) el.setAttribute("property", attrValue);
    else el.setAttribute(attrName, attrValue);
    document.head.appendChild(el);
  }
  el.setAttribute("content", content);
}

// Normaliza el href:
//   - Limpia el prefijo `/#/` (residuo del antiguo hash-mode del router) y
//     cualquier `#fragmento` posterior, que Lighthouse marca como inválido.
//   - Si el path es relativo, lo monta sobre window.location.origin para
//     garantizar URL absoluta (requerida por Google y Lighthouse para canonical).
function normalizeCanonical(href) {
  if (!href) return href;
  let path = href.trim();
  // Quita el primer "#" y la barra inicial duplicada que deja el legacy hash.
  path = path.replace(/^\/?#\/?/, "/");
  // Quita cualquier fragmento residual.
  path = path.split("#")[0];
  // Si ya es absoluta, devolverla tal cual.
  if (/^https?:\/\//i.test(path)) return path;
  if (typeof window === "undefined") return path;
  if (!path.startsWith("/")) path = "/" + path;
  return window.location.origin + path;
}

function setCanonical(href) {
  let link = document.querySelector('link[rel="canonical"]');
  if (!link) {
    link = document.createElement("link");
    link.setAttribute("rel", "canonical");
    document.head.appendChild(link);
  }
  link.setAttribute("href", normalizeCanonical(href));
}

export function setSeo({ title, description, canonical, ogImage, ogTitle, ogDescription }) {
  if (title) document.title = title;

  if (description) {
    upsertMeta("name", "description", description);
    upsertMeta("name", "twitter:description", description);
    upsertMeta("property", "og:description", description, true);
  }

  if (ogTitle || title) {
    const t = ogTitle || title;
    if (t) {
      upsertMeta("property", "og:title", t, true);
      upsertMeta("name", "twitter:title", t);
    }
  }

  if (ogImage) {
    upsertMeta("property", "og:image", ogImage, true);
    upsertMeta("name", "twitter:image", ogImage);
  }

  if (canonical) {
    const normalized = normalizeCanonical(canonical);
    setCanonical(normalized);
    upsertMeta("property", "og:url", normalized, true);
  }
}
