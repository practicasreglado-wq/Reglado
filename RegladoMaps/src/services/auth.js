/**
 * Servicio de Autenticación para el sistema cartográfico (RegladoMaps).
 * 
 * Actúa de consumidor Single Sign-On, reutilizando la cookie compartida
 * generada en GrupoReglado. Permite unificar la identidad del usuario y 
 * proteger las vistas del mapa utilizando la sesión global de la api.
 */
import { reactive } from "vue";
import { redirectToLogout } from "./ssoClient";

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

  if (response.status === 401 && state.token) {
    // Sesión invalidada server-side (login en otro dispositivo, password
    // change, ban, admin force-logout). Limpiamos el token local; el estado
    // reactivo se encarga de prompted al usuario a re-loguear.
    clearSession();
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
 * Reconcilia el estado local con la cookie compartida `reglado_auth_token`.
 * Se llama cuando la pestaña vuelve a ser visible para detectar logins o
 * logouts hechos en otro proyecto del ecosistema sin recargar.
 */
async function syncWithCookie() {
  const cookieToken = getCookie(COOKIE_TOKEN_KEY);

  if (!cookieToken && state.token) {
    clearSession();
    return;
  }

  if (cookieToken && cookieToken !== state.token) {
    setToken(cookieToken);
  }

  if (!state.token) return;

  // Revalidación contra /auth/me: detecta invalidaciones server-side
  // (logout distribuido, ban, password change, rotación sid).
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
    // Propaga el cierre al hub para que también limpie su almacenamiento.
    redirectToLogout();
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
