/**
 * Service: acciones puras sobre usuarios (sin lógica de inmobiliaria).
 *
 * Estas llamadas van DIRECTAS a ApiLogin (regladogroup.com) en lugar de pasar
 * por el backend de inmobiliaria. Razón: estos endpoints ya existen en
 * ApiLogin y duplicarlos en inmobiliaria solo añadiría un proxy que se
 * desactualizaría con el tiempo.
 *
 * El JWT del admin (auth.authHeaders) ya autoriza estas acciones — ApiLogin
 * comprueba role=admin antes de aceptar la mutación. La password del admin
 * se envía como current_password para reauth, igual que la versión vieja.
 */

import { auth } from "./auth";

const AUTH_API = import.meta.env.VITE_AUTH_API_URL || "http://localhost:8000";

async function postToApiLogin(path, body) {
  const response = await fetch(`${AUTH_API}${path}`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      ...auth.authHeaders(),
    },
    body: JSON.stringify(body),
  });

  let payload = null;
  try {
    payload = await response.json();
  } catch {
    // Respuesta no-JSON (ej. 502): nos quedamos con el status para el caller.
  }

  return { ok: response.ok, status: response.status, payload };
}

/**
 * Cambia el rol de un usuario. Roles válidos en ApiLogin: 'user', 'real',
 * 'admin'. La adminPassword es la del admin que está haciendo la acción
 * — ApiLogin la verifica server-side antes de aplicar el cambio.
 *
 * Devuelve { success, message } para mantener el shape que las vistas
 * de inmobiliaria esperaban del antiguo /backend/api/update_user_role.php.
 */
export async function updateUserRole(userId, newRole, adminPassword) {
  const { ok, payload } = await postToApiLogin("/auth/admin/update-role", {
    user_id: userId,
    role: newRole,
    current_password: adminPassword,
  });

  if (!ok) {
    return {
      success: false,
      message: payload?.error || "No se pudo cambiar el rol.",
    };
  }

  return {
    success: true,
    message: payload?.message || "Rol actualizado correctamente.",
  };
}
