import { backendJson } from "./backend";
import { auth } from "./auth";

const API_BASE =
  import.meta.env.VITE_API_BASE_URL ||
  "http://localhost/Reglado/Inmobiliaria_Reglados/backend/api";

export async function updatePropertyStatus(propertyId, estado) {
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
    }),
  });

  return response.json();
}

export async function deletePropertyAsAdmin(propertyId) {
  const response = await fetch(`${API_BASE}/delete_property.php`, {
    method: "POST",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      ...auth.authHeaders(),
    },
    body: JSON.stringify({
      property_id: propertyId,
    }),
  });

  return response.json();
}
export async function fetchAllProperties() {
  const payload = await backendJson("api/get_all_properties.php");
  console.log("PAYLOAD ADMIN:", payload);

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

export async function approvePendingRequest(requestId) {
  const response = await fetch(`${API_BASE}/approve_pending_request.php`, {
    method: "POST",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      ...auth.authHeaders(),
    },
    body: JSON.stringify({ request_id: requestId }),
  });
  return response.json();
}

export async function rejectPendingRequest(requestId) {
  const response = await fetch(`${API_BASE}/reject_pending_request.php`, {
    method: "POST",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      ...auth.authHeaders(),
    },
    body: JSON.stringify({ request_id: requestId }),
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

export async function approveDocumentReviewAsAdmin(reviewId) {
  const response = await fetch(`${API_BASE}/approve_document_review_admin.php`, {
    method: "POST",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      ...auth.authHeaders(),
    },
    body: JSON.stringify({ review_id: reviewId }),
  });
  return response.json();
}

export async function rejectDocumentReviewAsAdmin(reviewId) {
  const response = await fetch(`${API_BASE}/reject_document_review_admin.php`, {
    method: "POST",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      ...auth.authHeaders(),
    },
    body: JSON.stringify({ review_id: reviewId }),
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

export async function updatePurchaseRequestStatus(requestId, status, notes = "") {
  const response = await fetch(`${API_BASE}/update_purchase_request_status.php`, {
    method: "POST",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      ...auth.authHeaders(),
    },
    body: JSON.stringify({ request_id: requestId, status, notes }),
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

export async function updateUserRole(userId, newRole) {
  const response = await fetch(`${API_BASE}/update_user_role.php`, {
    method: "POST",
    credentials: "include",
    headers: { "Content-Type": "application/json", ...auth.authHeaders() },
    body: JSON.stringify({ user_id: userId, role: newRole }),
  });
  return response.json();
}

export async function blockInmoUser(userId, notes = "") {
  const response = await fetch(`${API_BASE}/block_inmo_user.php`, {
    method: "POST",
    credentials: "include",
    headers: { "Content-Type": "application/json", ...auth.authHeaders() },
    body: JSON.stringify({ user_id: userId, notes }),
  });
  return response.json();
}

export async function unblockInmoUser(userId) {
  const response = await fetch(`${API_BASE}/unblock_inmo_user.php`, {
    method: "POST",
    credentials: "include",
    headers: { "Content-Type": "application/json", ...auth.authHeaders() },
    body: JSON.stringify({ user_id: userId }),
  });
  return response.json();
}

export async function forceUserRelogin(userId) {
  const response = await fetch(`${API_BASE}/force_user_relogin.php`, {
    method: "POST",
    credentials: "include",
    headers: { "Content-Type": "application/json", ...auth.authHeaders() },
    body: JSON.stringify({ user_id: userId }),
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