/**
 * Cliente HTTP genérico contra el backend de Inmobiliaria.
 *
 * Helpers principales:
 *  - BACKEND_BASE / GROUP_BASE: bases de URL configurables vía .env
 *    (VITE_INMOBILIARIA_BACKEND_BASE / VITE_GRUPO_REGLADO_BASE_URL).
 *  - buildBackendUrl(path):  monta URL absoluta contra el backend.
 *  - buildExternalAuthUrl(): monta URL al login externo (GrupoReglado) con
 *    callback de vuelta.
 *  - backendJson(path, opts): fetch JSON con Bearer token automático y
 *    redirección a /login en 401 (centraliza el "logout forzado").
 *
 * El resto de services (admin.js, properties.js, buyerIntents.js) construyen
 * sobre este — no llaman a fetch() crudo.
 */

import { auth, clearAllAuthArtifacts } from "./auth";

export const BACKEND_BASE =
  import.meta.env.VITE_INMOBILIARIA_BACKEND_BASE ||
  "http://localhost/Reglado/Inmobiliaria_Reglados/backend";

export const GROUP_BASE =
  import.meta.env.VITE_GRUPO_REGLADO_BASE_URL ||
  (import.meta.env.DEV ? "http://localhost:5173" : "");

export function buildExternalAuthUrl(path) {
  if (!GROUP_BASE) {
    throw new Error("Falta VITE_GRUPO_REGLADO_BASE_URL en producción");
  }

  const url = new URL(path, GROUP_BASE);
  url.searchParams.set("returnTo", getCallbackUrl());
  return url.toString();
}

export function buildBackendUrl(path) {
  return new URL(path, `${BACKEND_BASE}/`).toString();
}

export function getCallbackUrl() {
  return new URL("auth/callback", `${window.location.origin}/`).toString();
}

export function buildUploadsUrl(fileName) {
  if (!fileName) {
    return null;
  }

  return new URL(`uploads/${fileName}`, `${BACKEND_BASE}/`).toString();
}

let isRedirecting = false;

export async function backendJson(path, options = {}) {
  const finalHeaders = {
    ...(options.headers || {}),
    ...(auth.state.token
      ? { Authorization: `Bearer ${auth.state.token}` }
      : {}),
  };

  const response = await fetch(buildBackendUrl(path), {
    ...options,
    credentials: "include",
    headers: finalHeaders,
  });

  let payload = {};
  try {
    payload = await response.json();
  } catch {
    payload = {};
  }

  if (!response.ok) {
    if (response.status === 401 && !isRedirecting) {
      if (typeof window !== "undefined") {
        isRedirecting = true;
        auth.clearSession();
        clearAllAuthArtifacts();

        setTimeout(() => {
          if (window.location.pathname !== "/login") {
            window.location.replace("/login");
          }
        }, 150);
      }
    }

    throw new Error(
      payload.message ||
      payload.error ||
      JSON.stringify(payload) ||
      "Error desconocido"
    );
  }

  return payload;
}

