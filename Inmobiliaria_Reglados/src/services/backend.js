import { auth } from "./auth";

export const BACKEND_BASE =
  import.meta.env.VITE_INMOBILIARIA_BACKEND_BASE ||
  "http://localhost/Reglado/Inmobiliaria_Reglados/backend"
export const GROUP_BASE =
  import.meta.env.VITE_GRUPO_REGLADO_BASE_URL || "http://localhost:5173";

export function buildBackendUrl(path) {
  return new URL(path, `${BACKEND_BASE}/`).toString();
}

export function getCallbackUrl() {
  return new URL("auth/callback", `${window.location.origin}/`).toString();
}

export function buildExternalAuthUrl(path) {
  const url = new URL(path, GROUP_BASE);
  // El proyecto origen siempre recibe el token en esta ruta unica de callback.
  url.searchParams.set("returnTo", getCallbackUrl());
  return url.toString();
}

export function buildUploadsUrl(fileName) {
  if (!fileName) {
    return null;
  }

  return new URL(`uploads/${fileName}`, `${BACKEND_BASE}/`).toString();
}

export async function backendJson(path, options = {}) {
  const response = await fetch(buildBackendUrl(path), {
    ...options,
    headers: {
      ...(options.headers || {}),
      ...(auth.state.token ? { Authorization: `Bearer ${auth.state.token}` } : {}),
    },
  });

  let payload = {};
  try {
    payload = await response.json();
  } catch {
    payload = {};
  }

  if (!response.ok) {
    throw new Error(payload.message || payload.error || "No se pudo completar la solicitud.");
  }

  return payload;
}
