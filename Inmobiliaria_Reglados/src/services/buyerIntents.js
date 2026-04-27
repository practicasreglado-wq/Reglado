/**
 * Service: gestión de buyer_intents (matchmaking comprador → propiedad).
 *
 * Un buyer_intent guarda los criterios del usuario (categoría, ciudad, precio
 * máximo, m² mínimos). Cuando alguien sube una propiedad que matchea, el
 * comprador recibe notificación + email automáticamente (lógica server-side
 * en backend/lib/buyer_intents.php).
 *
 * Distinto de search_history (búsquedas puntuales): los intents son
 * persistentes y disparan notificaciones futuras.
 */

import { auth } from "./auth";

const API_BASE =
  import.meta.env.VITE_API_BASE_URL ||
  "http://localhost/Reglado/Inmobiliaria_Reglados/backend/api";

export async function createBuyerIntent(criteria) {
  const response = await fetch(`${API_BASE}/create_buyer_intent.php`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      ...auth.authHeaders(),
    },
    credentials: "include",
    body: JSON.stringify({
      category: criteria.category || "",
      city: criteria.city || "",
      max_price: criteria.max_price ?? null,
      min_m2: criteria.min_m2 ?? null,
      criteria_display: Array.isArray(criteria.criteria_display)
        ? criteria.criteria_display
        : [],
    }),
  });

  const payload = await response.json().catch(() => ({}));

  if (!response.ok || !payload.success) {
    throw new Error(payload.message || "No se pudo registrar la búsqueda.");
  }

  return payload;
}

export async function fetchBuyerIntent(intentId) {
  const id = Number(intentId);
  if (!Number.isFinite(id) || id <= 0) {
    throw new Error("Identificador de solicitud inválido.");
  }

  const response = await fetch(
    `${API_BASE}/get_buyer_intent.php?id=${encodeURIComponent(id)}`,
    {
      method: "GET",
      headers: auth.authHeaders(),
      credentials: "include",
    }
  );

  const payload = await response.json().catch(() => ({}));

  if (!response.ok || !payload.success) {
    throw new Error(payload.message || "No se pudo cargar la solicitud.");
  }

  return payload.intent;
}
