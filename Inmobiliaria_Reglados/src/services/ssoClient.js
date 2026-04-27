/**
 * SSO Client — handshake contra el hub (Grupo).
 *
 * Ver docs/ECOSYSTEM_AUTH_SSO_HUB.md para la arquitectura completa. Este
 * módulo encapsula:
 *  - Detección de fragmento `#token=...` al cargar la página.
 *  - Redirect al hub para pedir / propagar / limpiar sesión.
 *
 * Anti-loop: usa sessionStorage.sso_attempt_ts (timestamp). Tras un
 * handshake fallido no se reintenta durante el cooldown; pasado ese tiempo
 * se permite reintentar (p.ej. cuando el usuario se loguea en otro dominio
 * y vuelve a esta pestaña).
 */

const HUB_BASE = import.meta.env.VITE_GRUPO_REGLADO_BASE_URL || "http://localhost:5173";
const SSO_ATTEMPT_TS_KEY = "sso_attempt_ts";
const HANDSHAKE_COOLDOWN_MS = 15000;

export function consumeTokenFromFragment() {
  if (typeof window === "undefined" || !window.location.hash) return null;

  const hash = window.location.hash.startsWith("#") ? window.location.hash.slice(1) : window.location.hash;
  const params = new URLSearchParams(hash);
  const token = params.get("token");

  if (!token) return null;

  const cleanUrl = window.location.pathname + window.location.search;
  window.history.replaceState(null, "", cleanUrl);
  return token;
}

export function wasSsoHandshakeFailed() {
  if (typeof window === "undefined") return false;
  const params = new URLSearchParams(window.location.search);
  return params.get("sso_failed") === "1";
}

export function clearSsoFailedFlag() {
  if (typeof window === "undefined") return;
  const params = new URLSearchParams(window.location.search);
  if (!params.has("sso_failed")) return;
  params.delete("sso_failed");
  const qs = params.toString();
  const url = window.location.pathname + (qs ? `?${qs}` : "") + window.location.hash;
  window.history.replaceState(null, "", url);
}

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
    // sessionStorage bloqueado — aceptamos posibles reintentos.
  }
}

export function clearHandshakeAttempt() {
  try {
    sessionStorage.removeItem(SSO_ATTEMPT_TS_KEY);
  } catch {
    // idem
  }
}

export function redirectToHandshake() {
  const currentUrl = window.location.href;
  const handshakeUrl = `${HUB_BASE}/sso-handshake?return=${encodeURIComponent(currentUrl)}`;
  markHandshakeAttempted();
  window.location.replace(handshakeUrl);
}

export function redirectToStore(token, returnTo = null) {
  const finalReturn = returnTo || window.location.origin + "/";
  const storeUrl =
    `${HUB_BASE}/sso-store?token=${encodeURIComponent(token)}` +
    `&return=${encodeURIComponent(finalReturn)}`;
  window.location.replace(storeUrl);
}

export function redirectToLogout(returnTo = null) {
  const finalReturn = returnTo || window.location.origin + "/";
  const logoutUrl = `${HUB_BASE}/sso-logout?return=${encodeURIComponent(finalReturn)}`;
  window.location.replace(logoutUrl);
}
