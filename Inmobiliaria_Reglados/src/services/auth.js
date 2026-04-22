import { reactive } from "vue";

const API_BASE = import.meta.env.VITE_AUTH_API_URL || "http://localhost:8000";
const TOKEN_KEY = "inmobiliaria_auth_token";
const COOKIE_TOKEN_KEY = "reglado_auth_token"; // Cookie compartida entre proyectos del ecosistema
const COOKIE_MAX_AGE = 60 * 60 * 24 * 7;

const state = reactive({
  token: localStorage.getItem(TOKEN_KEY) || getCookie(COOKIE_TOKEN_KEY) || "",
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

  if (!response.ok) {
    throw new Error(payload.error || payload.message || "No se pudo completar la solicitud.");
  }

  return payload;
}

function setToken(token) {
  state.token = token || "";

  if (state.token) {
    localStorage.setItem(TOKEN_KEY, state.token);
    setCookie(COOKIE_TOKEN_KEY, state.token, COOKIE_MAX_AGE);
  } else {
    localStorage.removeItem(TOKEN_KEY);
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

async function logout() {
  try {
    if (state.token) {
      await request("/auth/logout", {
        method: "POST",
        headers: authHeaders(),
      });
    }
  } finally {
    clearSession();
  }
}

export const auth = {
  state,
  authHeaders,
  setSession,
  clearSession,
  initialize,
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
    localStorage.removeItem(TOKEN_KEY);
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
