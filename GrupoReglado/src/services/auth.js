import { reactive } from "vue";

const API_BASE = import.meta.env.VITE_AUTH_API_URL || "http://localhost:8000";
const TOKEN_KEY = "auth_token";
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
  "user not found": "No se ha encontrado ninguna cuenta con esos datos.",
  "email already registered": "Ya existe una cuenta con ese correo.",
  "username already taken": "Ese nombre de usuario ya está en uso.",
  "pending registration already exists": "Ya existe un registro pendiente para ese correo.",
  "verification email sent": "Se ha enviado el correo de verificación.",
  "password reset email sent": "Si la cuenta existe, te hemos enviado un correo.",
  "password reset successful": "La contraseña se ha actualizado correctamente.",
  "password changed successfully": "La contraseña se ha actualizado correctamente.",
  "email change verification sent": "Te hemos enviado un correo para confirmar el cambio de correo.",
  "email updated": "El correo se ha actualizado correctamente.",
  "current password is incorrect": "La contraseña actual no es correcta.",
  "password confirmation does not match": "Las contraseñas no coinciden.",
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
    const message = translateAuthMessage(payload.error || payload.message || "request failed");
    throw new Error(message);
  }

  if (typeof payload.message === "string") {
    payload.message = translateAuthMessage(payload.message);
  }

  return payload;
}

function setToken(token) {
  state.token = token || "";
  if (state.token) {
    // Se persiste en ambos sitios para compartir sesion entre proyectos en el mismo navegador.
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

function clearSession() {
  setToken("");
  state.user = null;
}

function applySessionPayload(payload) {
  if (!payload || !payload.token) {
    return payload;
  }

  setSession(payload.token, payload.user || null);
  return payload;
}

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

async function updateUsername(username) {
  const payload = await request("/auth/update-username", {
    method: "POST",
    headers: authHeaders(),
    body: JSON.stringify({ username }),
  });
  return applySessionPayload(payload);
}

async function updateName(firstName, lastName) {
  const payload = await request("/auth/update-name", {
    method: "POST",
    headers: authHeaders(),
    body: JSON.stringify({ first_name: firstName, last_name: lastName }),
  });
  return applySessionPayload(payload);
}

async function updatePhone(phone) {
  const payload = await request("/auth/update-phone", {
    method: "POST",
    headers: authHeaders(),
    body: JSON.stringify({ phone }),
  });
  return applySessionPayload(payload);
}

async function requestEmailChange(newEmail) {
  return request("/auth/request-email-change", {
    method: "POST",
    headers: authHeaders(),
    body: JSON.stringify({ new_email: newEmail }),
  });
}

async function changePassword(currentPassword, newPassword, newPasswordConfirmation) {
  const payload = await request("/auth/change-password", {
    method: "POST",
    headers: authHeaders(),
    body: JSON.stringify({
      current_password: currentPassword,
      new_password: newPassword,
      new_password_confirmation: newPasswordConfirmation,
    }),
  });
  return applySessionPayload(payload);
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

async function adminUsers() {
  return request("/auth/admin/users", {
    method: "GET",
    headers: authHeaders(),
  });
}

async function adminUpdateRole(userId, role) {
  return request("/auth/admin/update-role", {
    method: "POST",
    headers: authHeaders(),
    body: JSON.stringify({ user_id: userId, role }),
  });
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
  setSession,
  clearSession,
  initialize,
  login,
  register,
  updateUsername,
  updateName,
  updatePhone,
  requestEmailChange,
  changePassword,
  resendVerification,
  requestPasswordReset,
  resetPassword,
  adminUsers,
  adminUpdateRole,
  logout,
  translateMessage: translateAuthMessage,
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

  // La API mantiene mensajes tecnicos en ingles; el frontend los traduce antes de pintarlos.
  return AUTH_MESSAGE_MAP[message.toLowerCase()] || message;
}
