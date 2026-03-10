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

function setCanonical(href) {
  let link = document.querySelector('link[rel="canonical"]');
  if (!link) {
    link = document.createElement("link");
    link.setAttribute("rel", "canonical");
    document.head.appendChild(link);
  }
  link.setAttribute("href", href);
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
    // If running on localhost, keep canonical relative to avoid wrong domain.
    // If in production, you can set canonical to your real domain via env.
    setCanonical(canonical);
    upsertMeta("property", "og:url", canonical, true);
  }
}
