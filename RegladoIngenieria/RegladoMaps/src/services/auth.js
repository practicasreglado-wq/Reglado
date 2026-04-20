/**
 * Servicio de Autenticación para el sistema cartográfico (RegladoMaps).
 * 
 * Actúa de consumidor Single Sign-On, reutilizando la cookie compartida
 * generada en GrupoReglado. Permite unificar la identidad del usuario y 
 * proteger las vistas del mapa utilizando la sesión global de la api.
 */
import { reactive } from "vue";

const API_BASE = import.meta.env.VITE_AUTH_API_URL || "http://localhost:8000";
const TOKEN_KEY = "maps_auth_token";
const COOKIE_TOKEN_KEY = "reglado_auth_token";
const COOKIE_MAX_AGE = 60 * 60 * 24 * 7;

const state = reactive({
  token: localStorage.getItem(TOKEN_KEY) || getCookie(COOKIE_TOKEN_KEY) || "",
  user: null,
  loading: false,
});

const AUTH_MESSAGE_MAP = {
  "request failed": "La solicitud no se pudo completar.",
  "invalid token": "Tu sesión no es válida. Vuelve a iniciar sesión.",
  "token revoked": "Tu sesión ya no es válida. Vuelve a iniciar sesión.",
  unauthorized: "Debes iniciar sesión para continuar.",
  forbidden: "No tienes permisos para realizar esta acción.",
  "too many requests, try again later": "Has realizado demasiados intentos. Inténtalo más tarde.",
  "email not verified": "Debes confirmar tu correo antes de iniciar sesión.",
  "invalid credentials": "Correo o contraseña incorrectos.",
};

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
    throw new Error(translateAuthMessage(payload.error || payload.message || "request failed"));
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

/**
 * Recupera el inicio de sesión y sincroniza el estado local intentando
 * obtener el perfil del usuario utilizando la cookie o el token existente.
 */
async function initialize() {
  if (!state.token) {
    const cookieToken = getCookie(COOKIE_TOKEN_KEY);
    if (cookieToken) {
      setToken(cookieToken);
    }
  }

  if (!state.token) {
    state.user = null;
    return;
  }

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

/**
 * Realiza el cierre de sesión destruyendo las credenciales almacenadas
 * localmente y redirigiendo al portal corporativo raíz (GrupoReglado).
 */
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
    const mainHub = import.meta.env.VITE_AUTH_API_URL && import.meta.env.VITE_AUTH_API_URL.includes("regladogroup.com") 
      ? "https://regladogroup.com" 
      : "http://localhost:5173";
    window.location.href = mainHub;
  }
}

export const auth = {
  state,
  setSession,
  clearSession,
  initialize,
  logout,
};

function setCookie(name, value, maxAgeSeconds) {
  document.cookie = `${name}=${encodeURIComponent(value)}; Max-Age=${maxAgeSeconds}; Path=/; SameSite=Lax`;
}

function clearCookie(name) {
  document.cookie = `${name}=; Max-Age=0; Path=/; SameSite=Lax`;
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

function translateAuthMessage(message) {
  if (typeof message !== "string") {
    return "La solicitud no se pudo completar.";
  }

  return AUTH_MESSAGE_MAP[message.toLowerCase()] || message;
}
