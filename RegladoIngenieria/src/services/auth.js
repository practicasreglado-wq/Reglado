import { reactive } from "vue";

const API_BASE = import.meta.env.VITE_AUTH_API_URL || "https://gruporeglado.com";
const TOKEN_KEY = import.meta.env.VITE_TOKEN_KEY || "ingenieria_auth_token";
const COOKIE_MAX_AGE = 60 * 60 * 24 * 7;

const state = reactive({
  token: localStorage.getItem(TOKEN_KEY) || getCookie(TOKEN_KEY) || "",
  user: null,
  loading: false,
});

function authHeaders() {
  return state.token ? { Authorization: `Bearer ${state.token}` } : {};
}

async function request(path, options = {}) {
  const response = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers: { "Content-Type": "application/json", ...(options.headers || {}) },
  });
  let payload = {};
  try { payload = await response.json(); } catch { payload = {}; }
  if (!response.ok) throw new Error(payload.error || payload.message || "La solicitud no se pudo completar.");
  return payload;
}

function setToken(token) {
  state.token = token || "";
  if (state.token) {
    localStorage.setItem(TOKEN_KEY, state.token);
    setCookie(TOKEN_KEY, state.token, COOKIE_MAX_AGE);
  } else {
    localStorage.removeItem(TOKEN_KEY);
    clearCookie(TOKEN_KEY);
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
    const cookieToken = getCookie(TOKEN_KEY);
    if (cookieToken) setToken(cookieToken);
  }
  if (!state.token) { state.user = null; return; }

  state.loading = true;
  try {
    const payload = await request("/auth/me", { method: "GET", headers: authHeaders() });
    state.user = payload.user || null;
  } catch {
    clearSession();
  } finally {
    state.loading = false;
  }
}

async function logout() {
  try {
    if (state.token) {
      await request("/auth/logout", { method: "POST", headers: authHeaders() });
    }
  } finally {
    clearSession();
    window.location.href = window.location.origin;
  }
}

export const auth = { state, setSession, clearSession, initialize, logout, getCookie, setCookie };

function setCookie(name, value, maxAge) {
  document.cookie = `${name}=${encodeURIComponent(value)}; Max-Age=${maxAge}; Path=/; SameSite=Lax`;
}
function clearCookie(name) {
  document.cookie = `${name}=; Max-Age=0; Path=/; SameSite=Lax`;
}
function getCookie(name) {
  if (typeof document === "undefined") return "";
  const prefix = `${name}=`;
  for (const part of (document.cookie || "").split("; ")) {
    if (part.startsWith(prefix)) return decodeURIComponent(part.slice(prefix.length));
  }
  return "";
}
