/**
 * Estado global de autenticación (Vue reactive) compartido por toda la SPA.
 *
 * El JWT se obtiene de ApiLoging (servicio separado, ver VITE_AUTH_API_URL)
 * y se persiste en la cookie `reglado_auth_token` (única fuente de verdad
 * cross-tab del mismo dominio). La sincronización entre dominios distintos
 * va por SSO Hub — ver docs/ECOSYSTEM_AUTH_SSO_HUB.md.
 *
 * Hardening F3 (2026-04-27): se eliminó la copia del token en localStorage
 * para reducir la superficie ante XSS. `clearAllAuthArtifacts` mantiene la
 * limpieza agresiva por compatibilidad — si algún navegador conserva un
 * token de una versión anterior, esa función lo elimina.
 *
 * `auth.state` se importa en muchas vistas para mostrar/ocultar UI según
 * sesión y rol; al cambiar `state.token` reactivamente, las vistas se
 * actualizan solas.
 */

import { reactive } from "vue";
import { redirectToLogout } from "./ssoClient";

const API_BASE = import.meta.env.VITE_AUTH_API_URL || "http://localhost:8000";
// Constante mantenida solo para clearAllAuthArtifacts() — no se usa para
// escribir, solo para limpiar tokens viejos en navegadores que aún los
// tengan de versiones anteriores a F3.
const LEGACY_TOKEN_KEY = "inmobiliaria_auth_token";
const COOKIE_TOKEN_KEY = "reglado_auth_token"; // Cookie compartida entre proyectos del ecosistema
const COOKIE_MAX_AGE = 60 * 60 * 24 * 7;

const state = reactive({
  token: getCookie(COOKIE_TOKEN_KEY) || "",
  user: null,
  loading: false,
});

function authHeaders() {
  return state.token ? { Authorization: `Bearer ${state.token}` } : {};
}

async function request(path, options = {}) {
  const response = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers: {
      "Content-Type": "application/json",
      ...(options.headers || {}),
    },
  });

  let payload = {};
  try {
    payload = await response.json();
  } catch {
    payload = {};
  }

  if (response.status === 401 && state.token) {
    // Sesión invalidada server-side (login en otro dispositivo, password
    // change, ban, admin force-logout, kick-old por single-session). Limpiamos
    // estado local; el resto de la SPA reaccionará via reactividad.
    clearSession();
  }

  if (!response.ok) {
    throw new Error(payload.error || payload.message || "No se pudo completar la solicitud.");
  }

  return payload;
}

function setToken(token) {
  state.token = token || "";

  if (state.token) {
    // Cookie como única fuente de persistencia. Hardening F3.
    setCookie(COOKIE_TOKEN_KEY, state.token, COOKIE_MAX_AGE);
  } else {
    clearCookie(COOKIE_TOKEN_KEY);
  }
}

function setSession(token, user = null) {
  setToken(token);
  state.user = user;
}

function clearSession() {
  setToken("");
  state.user = null;
}

async function initialize() {
  if (!state.token) {
    const cookieToken = getCookie(COOKIE_TOKEN_KEY);
    if (cookieToken) {
      setToken(cookieToken);
    }
  }

  if (!state.token) {
    state.user = null;
    return null;
  }

  state.loading = true;

  try {
    const payload = await request("/auth/me", {
      method: "GET",
      headers: authHeaders(),
    });
    state.user = payload.user || null;
    return state.user;
  } catch {
    clearSession();
    return null;
  } finally {
    state.loading = false;
  }
}

/**
 * Reconcilia el estado local con la cookie compartida `reglado_auth_token`
 * y revalida la sesión contra /auth/me. Pensado para ejecutarse cuando la
 * pestaña vuelve a ser visible: detecta logins/logouts ocurridos en otro
 * dominio del ecosistema y propagaciones server-side (ban, force-logout,
 * password change, kick-old por single-session).
 */
async function login(email, password) {
  const payload = await request("/auth/login", {
    method: "POST",
    body: JSON.stringify({ email, password }),
  });
  setSession(payload.token, payload.user || null);
  return payload;
}

async function register(payload) {
  return request("/auth/register", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

async function resendVerification(email) {
  return request("/auth/resend-verification", {
    method: "POST",
    body: JSON.stringify({ email }),
  });
}

async function requestPasswordReset(email) {
  return request("/auth/request-password-reset", {
    method: "POST",
    body: JSON.stringify({ email }),
  });
}

async function resetPassword(token, newPassword, newPasswordConfirmation) {
  return request("/auth/reset-password", {
    method: "POST",
    body: JSON.stringify({
      token,
      new_password: newPassword,
      new_password_confirmation: newPasswordConfirmation,
    }),
  });
}

async function confirmLoginLocation(token, decision) {
  return request("/auth/confirm-login-location", {
    method: "POST",
    body: JSON.stringify({ token, decision }),
  });
}

async function syncWithCookie() {
  const cookieToken = getCookie(COOKIE_TOKEN_KEY);

  // Cookie desapareció del propio dominio (logout en otra pestaña o
  // limpieza vía /sso-logout desde otro dominio) → cerrar local.
  if (!cookieToken && state.token) {
    clearSession();
    return;
  }

  // Cookie cambió (login en otra pestaña del mismo dominio) → adoptamos
  // el nuevo token antes de revalidar.
  if (cookieToken && cookieToken !== state.token) {
    setToken(cookieToken);
  }

  if (!state.token) return;

  // Revalidación incondicional contra /auth/me — el interceptor 401 de
  // request() limpiará la sesión si el backend la rechaza.
  state.loading = true;
  try {
    const payload = await request("/auth/me", {
      method: "GET",
      headers: authHeaders(),
    });
    state.user = payload.user || null;
  } catch {
    clearSession();
  } finally {
    state.loading = false;
  }
}

async function logout({ skipHubRedirect = false } = {}) {
  try {
    if (state.token) {
      await request("/auth/logout", {
        method: "POST",
        headers: authHeaders(),
      });
    }
  } finally {
    clearSession();
    if (!skipHubRedirect) {
      // Propaga el cierre al hub para que también limpie el almacenamiento
      // local de Grupo. La página navega fuera; los caller que necesiten
      // ejecutar limpieza adicional (p.ej. user store) deben pasar
      // skipHubRedirect:true y gestionar el redirect manualmente.
      redirectToLogout();
    }
  }
}

export const auth = {
  state,
  authHeaders,
  setSession,
  clearSession,
  initialize,
  syncWithCookie,
  login,
  register,
  resendVerification,
  requestPasswordReset,
  resetPassword,
  confirmLoginLocation,
  logout,
};

function setCookie(name, value, maxAgeSeconds) {
  document.cookie = `${name}=${encodeURIComponent(value)}; Max-Age=${maxAgeSeconds}; Path=/; SameSite=Lax`;
}

function clearCookie(name) {
  // Borrado agresivo — intenta múltiples combinaciones path/domain
  // por si la cookie fue puesta por otro proyecto del grupo con otros atributos
  const combinations = [
    `${name}=; Max-Age=0; Path=/; SameSite=Lax`,
    `${name}=; Max-Age=0; Path=/; SameSite=None; Secure`,
    `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; Path=/`,
    `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; Path=/; Domain=${window.location.hostname}`,
    `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; Path=/; Domain=.${window.location.hostname}`,
  ];
  for (const c of combinations) {
    document.cookie = c;
  }
}

export function clearAllAuthArtifacts() {
  // Limpieza agresiva de todo lo relacionado con auth: cookies, localStorage, sessionStorage
  clearCookie(COOKIE_TOKEN_KEY);
  clearCookie("reglado_session");
  clearCookie("reglado_auth");

  try {
    localStorage.removeItem(LEGACY_TOKEN_KEY);
    localStorage.removeItem("user");
    localStorage.removeItem("auth_token");
    localStorage.removeItem("token");
    localStorage.removeItem("jwt");
    localStorage.removeItem("selectedCategory");
    localStorage.removeItem("preferences");
    // Borra cualquier clave que parezca de auth
    Object.keys(localStorage).forEach((key) => {
      const lower = key.toLowerCase();
      if (lower.includes("token") || lower.includes("auth") || lower.includes("jwt") || lower.includes("session")) {
        localStorage.removeItem(key);
      }
    });
  } catch {}

  try {
    sessionStorage.clear();
  } catch {}
}

function getCookie(name) {
  const prefix = `${name}=`;
  const parts = document.cookie ? document.cookie.split("; ") : [];
  for (const part of parts) {
    if (part.startsWith(prefix)) {
      return decodeURIComponent(part.slice(prefix.length));
    }
  }
  return "";
}
