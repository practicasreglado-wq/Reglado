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
    const message = payload.error || payload.message || "request failed";
    throw new Error(message);
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
