/**
 * Servicio de Autenticación para el frontend corporativo (RegladoEnergy).
 * 
 * Este servicio no maneja el login explícito (que recae en GrupoReglado),
 * sino que recupera la sesión a partir de una cookie compartida o token JWT
 * y solicita el perfil del usuario a ApiLoging para hidratar el estado local.
 */
import { reactive } from "vue";
import { redirectToLogout } from "./ssoClient";

const API_BASE = import.meta.env.VITE_AUTH_API_URL || "http://localhost:8000";
const COOKIE_TOKEN_KEY = "reglado_auth_token";
const COOKIE_MAX_AGE = 60 * 60 * 24 * 7;

const state = reactive({
  token: getCookie(COOKIE_TOKEN_KEY) || "",
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
    // reactivo y el LoginModal se encargan de prompted al usuario a re-loguear.
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
    // Cookie como única fuente de persistencia. Hardening F3: se eliminó
    // localStorage para reducir superficie ante XSS. La cookie cubre tanto
    // la persistencia entre recargas como la sincronización entre pestañas
    // del mismo dominio; la cross-domain va por SSO Hub.
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

/**
 * Inicializa el estado reactivo del usuario. 
 * Busca un token en localStorage o en la cookie compartida (dejada por GrupoReglado),
 * y obtiene el perfil del usuario verificando el JWT contra el backend.
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
 * Pensado para llamarse cuando la pestaña vuelve a ser visible: si Grupo
 * inició (o cerró) sesión en otra pestaña, aquí detectamos el cambio sin
 * necesidad de recargar.
 */
async function syncWithCookie() {
  const cookieToken = getCookie(COOKIE_TOKEN_KEY);

  // Si la cookie del propio dominio desapareció (logout desde otra pestaña
  // de este mismo dominio), cerramos localmente y salimos.
  if (!cookieToken && state.token) {
    clearSession();
    return;
  }

  // Si la cookie cambió (login desde otra pestaña del mismo dominio),
  // adoptamos el token nuevo antes de revalidar.
  if (cookieToken && cookieToken !== state.token) {
    setToken(cookieToken);
  }

  // Si tras lo anterior no hay token, no hay nada que validar.
  if (!state.token) return;

  // Revalidación siempre contra /auth/me: cubre el caso en el que el
  // servidor haya invalidado el token server-side (logout en otro dominio
  // vía hub, ban, admin force-logout, password change). Si el middleware
  // devuelve 401, el interceptor en request() limpia la sesión.
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
 * Autentica al usuario contra ApiLoging y, si tiene éxito, persiste el
 * token en el estado local y en la cookie compartida con el resto del
 * ecosistema (mismo flujo que GrupoReglado).
 */
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
 * Cierra la sesión activa revocando el token en el backend y borrando
 * los datos locales. Tras ello, devuelve al usuario al portal principal.
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
    // Redirige al hub (Grupo) para que también limpie su almacenamiento
    // local. El hub acabará devolviéndonos al home con la sesión cerrada.
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
