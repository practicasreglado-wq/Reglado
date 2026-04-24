/**
 * SSO Client — lógica de handshake contra el hub (Grupo).
 *
 * Ver docs/ECOSYSTEM_AUTH_SSO_HUB.md para la arquitectura completa.
 *
 * Este módulo encapsula:
 *  - Detección de fragmento `#token=...` al cargar la página (alguien nos
 *    acaba de ceder sesión desde Grupo).
 *  - Redirect al hub para pedir sesión cuando no tenemos token local.
 *  - Redirect al hub para propagar una sesión que acabamos de crear.
 *  - Redirect al hub para limpiar sesión tras logout.
 *
 * Anti-loop: usa sessionStorage.sso_attempted para no reintentar el
 * handshake más de una vez por pestaña.
 */

const HUB_BASE = import.meta.env.VITE_GRUPO_REGLADO_BASE_URL || "http://localhost:5173";
const SSO_ATTEMPT_TS_KEY = "sso_attempt_ts";
// Tras un handshake fallido, no reintentamos durante este intervalo para
// evitar loops cuando Grupo no tiene sesión. Pasado ese tiempo, permitimos
// reintentar (p.ej. cuando el usuario se loguea en otro dominio y vuelve a
// esta pestaña).
const HANDSHAKE_COOLDOWN_MS = 15000;

/**
 * Extrae el token del fragmento #token=... si está presente. Limpia el
 * fragmento de la URL usando history.replaceState para que no quede en
 * los bookmarks ni se lea por error en navegaciones sucesivas.
 */
export function consumeTokenFromFragment() {
  if (typeof window === "undefined" || !window.location.hash) return null;

  const hash = window.location.hash.startsWith("#") ? window.location.hash.slice(1) : window.location.hash;
  const params = new URLSearchParams(hash);
  const token = params.get("token");

  if (!token) return null;

  // Limpia el fragmento para no filtrar el token en navegaciones posteriores.
  const cleanUrl = window.location.pathname + window.location.search;
  window.history.replaceState(null, "", cleanUrl);

  return token;
}

/**
 * Devuelve true si el query string contiene sso_failed=1 — el hub nos ha
 * dicho "no tengo sesión, pídeselo al usuario con un login normal".
 */
export function wasSsoHandshakeFailed() {
  if (typeof window === "undefined") return false;
  const params = new URLSearchParams(window.location.search);
  return params.get("sso_failed") === "1";
}

/**
 * Limpia el parámetro sso_failed de la URL tras procesarlo.
 */
export function clearSsoFailedFlag() {
  if (typeof window === "undefined") return;
  const params = new URLSearchParams(window.location.search);
  if (!params.has("sso_failed")) return;
  params.delete("sso_failed");
  const qs = params.toString();
  const url = window.location.pathname + (qs ? `?${qs}` : "") + window.location.hash;
  window.history.replaceState(null, "", url);
}

/**
 * True si aún estamos dentro del cooldown tras el último handshake fallido.
 * Mientras devuelva true, el código no debe disparar un nuevo redirect a
 * `/sso-handshake` (evita loops cuando Grupo no tiene sesión).
 */
export function wasHandshakeAttempted() {
  try {
    const ts = parseInt(sessionStorage.getItem(SSO_ATTEMPT_TS_KEY) || "0", 10);
    if (!ts) return false;
    return (Date.now() - ts) < HANDSHAKE_COOLDOWN_MS;
  } catch {
    return false;
  }
}

export function markHandshakeAttempted() {
  try {
    sessionStorage.setItem(SSO_ATTEMPT_TS_KEY, String(Date.now()));
  } catch {
    // sessionStorage bloqueado (ITP/private mode) — aceptamos posibles
    // reintentos; el peor caso es un flash de más al cambiar de pestaña.
  }
}

export function clearHandshakeAttempt() {
  try {
    sessionStorage.removeItem(SSO_ATTEMPT_TS_KEY);
  } catch {
    // idem
  }
}

/**
 * Redirige a Grupo para que nos ceda el token si tiene sesión activa.
 * La URL actual se pasa como `return` para volver al mismo sitio.
 */
export function redirectToHandshake() {
  const currentUrl = window.location.href;
  const handshakeUrl = `${HUB_BASE}/sso-handshake?return=${encodeURIComponent(currentUrl)}`;
  markHandshakeAttempted();
  window.location.replace(handshakeUrl);
}

/**
 * Redirige a Grupo para que guarde el token en su almacenamiento local.
 * Se llama tras un login / registro / verificación en el dominio actual.
 * Tras procesar, Grupo redirige a `returnTo` (por defecto home).
 */
export function redirectToStore(token, returnTo = null) {
  const finalReturn = returnTo || window.location.origin + "/";
  const storeUrl =
    `${HUB_BASE}/sso-store?token=${encodeURIComponent(token)}` +
    `&return=${encodeURIComponent(finalReturn)}`;
  window.location.replace(storeUrl);
}

/**
 * Redirige a Grupo para que limpie su sesión. Se llama tras logout en el
 * dominio actual (que ya revocó el token en el backend).
 */
export function redirectToLogout(returnTo = null) {
  const finalReturn = returnTo || window.location.origin + "/";
  const logoutUrl = `${HUB_BASE}/sso-logout?return=${encodeURIComponent(finalReturn)}`;
  window.location.replace(logoutUrl);
}
