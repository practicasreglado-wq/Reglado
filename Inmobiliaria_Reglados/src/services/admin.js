/**
 * Service: llamadas a endpoints administrativos del backend.
 *
 * Engloba acciones que solo están disponibles para role=admin: cambio de
 * estado de propiedad, borrado, gestión de usuarios, audit log, gestión
 * de citas y solicitudes de compra, etc.
 *
 * Patrón de uso desde las vistas:
 *   import { fetchAuditLog, deletePropertyAsAdmin } from "@/services/admin";
 *
 * Las funciones que cambian estado (POST) suelen pedir adminPassword como
 * argumento — es la confirmación que valida server-side
 * lib/admin_password_check.php.
 */

import { backendJson } from "./backend";
import { auth } from "./auth";

const API_BASE =
  import.meta.env.VITE_API_BASE_URL ||
  "http://localhost/Reglado/Inmobiliaria_Reglados/backend/api";

export async function updatePropertyStatus(propertyId, estado, adminPassword) {
  const response = await fetch(`${API_BASE}/update_property_status.php`, {
    method: "POST",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      ...auth.authHeaders(),
    },
    body: JSON.stringify({
      property_id: propertyId,
      estado,
      admin_password: adminPassword,
    }),
  });

  return response.json();
}

export async function deletePropertyAsAdmin(propertyId, adminPassword) {
  const response = await fetch(`${API_BASE}/delete_property.php`, {
    method: "POST",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      ...auth.authHeaders(),
    },
    body: JSON.stringify({
      property_id: propertyId,
      admin_password: adminPassword,
    }),
  });

  return response.json();
}
export async function fetchAllProperties() {
  const payload = await backendJson("api/get_all_properties.php");

  if (payload.success && Array.isArray(payload.properties)) {
    return payload.properties;
  }

  throw new Error(payload.message || "Error al cargar las propiedades");
}

export async function fetchPendingRequests() {
  const payload = await backendJson("api/get_pending_requests.php");
  if (!payload.success) {
    throw new Error(payload.message || "Error al cargar las solicitudes pendientes");
  }
  return payload;
}

export async function approvePendingRequest(requestId, adminPassword) {
  const response = await fetch(`${API_BASE}/approve_pending_request.php`, {
    method: "POST",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      ...auth.authHeaders(),
    },
    body: JSON.stringify({ request_id: requestId, admin_password: adminPassword }),
  });
  return response.json();
}

export async function rejectPendingRequest(requestId, adminPassword) {
  const response = await fetch(`${API_BASE}/reject_pending_request.php`, {
    method: "POST",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      ...auth.authHeaders(),
    },
    body: JSON.stringify({ request_id: requestId, admin_password: adminPassword }),
  });
  return response.json();
}

export async function fetchPendingDocumentReviews() {
  const payload = await backendJson("api/get_pending_document_reviews.php");
  if (!payload.success) {
    throw new Error(payload.message || "Error al cargar las revisiones pendientes");
  }
  return payload;
}

export async function approveDocumentReviewAsAdmin(reviewId, adminPassword) {
  const response = await fetch(`${API_BASE}/approve_document_review_admin.php`, {
    method: "POST",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      ...auth.authHeaders(),
    },
    body: JSON.stringify({ review_id: reviewId, admin_password: adminPassword }),
  });
  return response.json();
}

export async function rejectDocumentReviewAsAdmin(reviewId, adminPassword) {
  const response = await fetch(`${API_BASE}/reject_document_review_admin.php`, {
    method: "POST",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      ...auth.authHeaders(),
    },
    body: JSON.stringify({ review_id: reviewId, admin_password: adminPassword }),
  });
  return response.json();
}

export async function fetchPurchaseRequests(onlyPending = false) {
  const path = "api/get_purchase_requests.php" + (onlyPending ? "?only_pending=1" : "");
  const payload = await backendJson(path);
  if (!payload.success) {
    throw new Error(payload.message || "Error al cargar las solicitudes de compra");
  }
  return payload;
}

export async function updatePurchaseRequestStatus(requestId, status, adminPassword, notes = "") {
  const response = await fetch(`${API_BASE}/update_purchase_request_status.php`, {
    method: "POST",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      ...auth.authHeaders(),
    },
    body: JSON.stringify({
      request_id: requestId,
      status,
      admin_password: adminPassword,
      notes,
    }),
  });
  return response.json();
}

export async function fetchScheduledAppointments(status = "scheduled") {
  const payload = await backendJson(`api/get_scheduled_appointments.php?status=${encodeURIComponent(status)}`);
  if (!payload.success) {
    throw new Error(payload.message || "Error al cargar las citas");
  }
  return payload;
}

export async function updateAppointmentStatus(appointmentId, status, adminPassword, adminNotes = "") {
  const response = await fetch(`${API_BASE}/update_appointment_status.php`, {
    method: "POST",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      ...auth.authHeaders(),
    },
    body: JSON.stringify({
      appointment_id: appointmentId,
      status,
      admin_password: adminPassword,
      admin_notes: adminNotes,
    }),
  });
  return response.json();
}

export async function fetchPendingPropertyDeletions() {
  const payload = await backendJson("api/get_pending_property_deletions.php");
  if (!payload.success) {
    throw new Error(payload.message || "Error al cargar las solicitudes de eliminación");
  }
  return payload;
}

export async function approvePropertyDeletion(requestId, adminPassword, adminNotes = "") {
  const response = await fetch(`${API_BASE}/approve_property_deletion.php`, {
    method: "POST",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      ...auth.authHeaders(),
    },
    body: JSON.stringify({
      request_id: requestId,
      admin_password: adminPassword,
      admin_notes: adminNotes,
    }),
  });
  return response.json();
}

export async function rejectPropertyDeletion(requestId, adminPassword, adminNotes = "") {
  const response = await fetch(`${API_BASE}/reject_property_deletion.php`, {
    method: "POST",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      ...auth.authHeaders(),
    },
    body: JSON.stringify({
      request_id: requestId,
      admin_password: adminPassword,
      admin_notes: adminNotes,
    }),
  });
  return response.json();
}

export async function deleteAppointment(appointmentId, adminPassword) {
  const response = await fetch(`${API_BASE}/delete_appointment.php`, {
    method: "POST",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      ...auth.authHeaders(),
    },
    body: JSON.stringify({
      appointment_id: appointmentId,
      admin_password: adminPassword,
    }),
  });
  return response.json();
}

export async function fetchInmoUsers(mode = "active") {
  const payload = await backendJson(`api/get_inmo_users.php?mode=${encodeURIComponent(mode)}`);
  if (!payload.success) {
    throw new Error(payload.message || "Error al cargar los usuarios");
  }
  return payload;
}

export async function updateUserRole(userId, newRole, adminPassword) {
  const response = await fetch(`${API_BASE}/update_user_role.php`, {
    method: "POST",
    credentials: "include",
    headers: { "Content-Type": "application/json", ...auth.authHeaders() },
    body: JSON.stringify({ user_id: userId, role: newRole, admin_password: adminPassword }),
  });
  return response.json();
}

export async function fetchAuditLog(params = {}) {
  const query = new URLSearchParams();
  if (params.page) query.set("page", String(params.page));
  if (params.per_page) query.set("per_page", String(params.per_page));
  if (params.action) query.set("action", params.action);
  if (params.user_email) query.set("user_email", params.user_email);
  if (params.date_from) query.set("date_from", params.date_from);
  if (params.date_to) query.set("date_to", params.date_to);

  const qs = query.toString();
  const path = "api/get_audit_log.php" + (qs ? "?" + qs : "");
  const payload = await backendJson(path);

  if (!payload.success) {
    throw new Error(payload.message || "Error al cargar el registro de auditoría");
  }

  return payload;
}