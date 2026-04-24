import { reactive } from "vue";
 
const API_BASE = import.meta.env.VITE_AUTH_API_URL || "http://localhost:8000";
const TOKEN_KEY = import.meta.env.VITE_TOKEN_KEY || "ingenieria_auth_token";
const COOKIE_TOKEN_KEY = "reglado_auth_token";
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
    headers: { "Content-Type": "application/json", ...(options.headers || {}) },
  });
  let payload = {};
  try { payload = await response.json(); } catch { payload = {}; }

  if (response.status === 401 && state.token) {
    // Sesión invalidada server-side (login en otro dispositivo, password
    // change, ban, admin force-logout). Limpiamos el token local; el estado
    // reactivo y los guards del router se encargan del resto.
    clearSession();
  }

  if (!response.ok) throw new Error(payload.error || payload.message || "La solicitud no se pudo completar.");
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
 
async function syncWithCookie() {
  const cookieToken = getCookie(COOKIE_TOKEN_KEY);
  if (cookieToken === state.token && state.user) return;

  if (!cookieToken) {
    if (state.token) clearSession();
    return;
  }

  if (cookieToken !== state.token) setToken(cookieToken);

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

async function login(email, password) {
  const payload = await request("/auth/login", {
    method: "POST",
    body: JSON.stringify({ email, password }),
  });
  setSession(payload.token, payload.user || null);
  return payload;
}

async function resendVerification(email) {
  return request("/auth/resend-verification", {
    method: "POST",
    body: JSON.stringify({ email }),
  });
}

async function register(payload) {
  return request("/auth/register", {
    method: "POST",
    body: JSON.stringify(payload),
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
 
export const auth = {
  state,
  setSession,
  clearSession,
  initialize,
  syncWithCookie,
  login,
  resendVerification,
  register,
  requestPasswordReset,
  resetPassword,
  confirmLoginLocation,
  logout,
  getCookie,
  setCookie,
};
 
function setCookie(name, value, maxAge) {
  const secure = window.location.protocol === "https:" ? "; Secure" : "";
  document.cookie = `${name}=${encodeURIComponent(value)}; Max-Age=${maxAge}; Path=/; SameSite=Lax${secure}`;
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
