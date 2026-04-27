/**
 * Service: llamadas relacionadas con propiedades + helpers de imagen.
 *
 * Incluye un mapping `categoryToImage` (Hoteles → hotel.png, Fincas →
 * finca.png, etc.) que las vistas usan como placeholder/fallback cuando
 * una propiedad no tiene foto subida.
 *
 * También expone funciones para acciones del USUARIO sobre sus propias
 * propiedades (delete, update). Para acciones admin usa services/admin.js.
 */

import hotelImage from "../assets/hotel.png";
import fincaImage from "../assets/finca.png";
import parkingImage from "../assets/parking.png";
import edificiosImage from "../assets/edificios.png";
import activosImage from "../assets/activos.png";
import { backendJson } from "./backend";

export async function deleteUserProperty(propertyId) {
  if (!propertyId) {
    throw new Error("ID de propiedad no válido.");
  }

  const response = await backendJson("api/delete_property.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      property_id: propertyId,
    }),
  });

  if (!response?.success) {
    throw new Error(response?.message || "No se pudo eliminar la propiedad.");
  }

  return response;
}

export async function requestUserPropertyDeletion(propertyId, reason = "") {
  if (!propertyId) {
    throw new Error("ID de propiedad no válido.");
  }

  const response = await backendJson("api/request_property_deletion.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      property_id: propertyId,
      reason,
    }),
  });

  if (!response?.success) {
    throw new Error(response?.message || "No se pudo enviar la solicitud de eliminación.");
  }

  return response;
}

const categoryImageMap = {
  hoteles: hotelImage,
  fincas: fincaImage,
  parking: parkingImage,
  edificios: edificiosImage,
  activos: activosImage,
};

function normalizeCategory(value) {
  return String(value ?? "").trim().toLowerCase();
}

function parseCharacteristics(property) {
  const raw =
    property?.caracteristicas ??
    property?.caracteristicas_json ??
    null;

  if (!raw) {
    return {};
  }

  if (typeof raw === "object") {
    return raw;
  }

  try {
    const parsed = JSON.parse(raw);
    return parsed && typeof parsed === "object" ? parsed : {};
  } catch {
    return {};
  }
}

export function normalizeProperty(property) {
  const categoria = property?.categoria ?? "activos";
  const normalizedCategory = normalizeCategory(categoria);

  return {
    ...property,
    categoria,
    caracteristicas: parseCharacteristics(property),
    imageUrl:
      property.image_url ||
      property.imageUrl ||
      categoryImageMap[normalizedCategory] ||
      activosImage,
    titulo:
      property.titulo ||
      property.nombre ||
      property.tipo_propiedad ||
      "Activo inmobiliario",
    ubicacion_general:
      property.ubicacion_general ||
      property.ubicacion ||
      property.direccion ||
      "",
  };
}

export async function fetchProperties(filters = {}) {
  const queryParams = new URLSearchParams();

  Object.entries(filters).forEach(([key, value]) => {
    if (value === undefined || value === null || value === "") {
      return;
    }
    queryParams.set(key, String(value));
  });

  const queryString = queryParams.toString();
  const query = queryString ? `?${queryString}` : "";
  const payload = await backendJson(`api/get_properties.php${query}`);

  return (payload.properties || []).map(normalizeProperty);
}

export async function fetchPropertyDetail(propertyId) {
  if (!propertyId) {
    return null;
  }

  const payload = await backendJson(
    `api/get_properties.php?id=${encodeURIComponent(propertyId)}`
  );

  return payload.property ? normalizeProperty(payload.property) : null;
}

export async function processPropertyFromText(description) {
  return backendJson("api/create_property_from_text.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ descripcion: description }),
  });
}

export async function uploadSignedDocuments(propertyId, ndaFile, loiFile) {
  const formData = new FormData();
  formData.append("property_id", String(propertyId));

  if (ndaFile) {
    formData.append("signed_nda", ndaFile);
  }

  if (loiFile) {
    formData.append("signed_loi", loiFile);
  }

  return backendJson("api/upload_signed_documents.php", {
    method: "POST",
    body: formData,
  });
}

export async function checkSignedAccess(propertyId) {
  const formData = new FormData();
  formData.append("property_id", String(propertyId));

  return backendJson("api/check_signed_access.php", {
    method: "POST",
    body: formData,
  });
}

export async function reviewSignedDocuments(data) {
  const formData = new FormData();
  formData.append("property_id", String(data.property_id));
  formData.append("buyer_user_id", String(data.buyer_user_id));
  formData.append("document_type", data.document_type);
  formData.append("action", data.action);

  return backendJson("api/review_signed_documents.php", {
    method: "POST",
    body: formData,
  });
}

export async function requestPropertyPurchase(propertyId, options = {}) {
  const {
    appointmentDate = null,
    notes = "",
    notaryName = "",
    notaryAddress = "",
    notaryCity = "",
    notaryPhone = "",
  } = options;

  return backendJson("api/request_purchase.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      property_id: propertyId,
      appointment_date: appointmentDate,
      notary_name: notaryName,
      notary_address: notaryAddress,
      notary_city: notaryCity,
      notary_phone: notaryPhone,
      notes,
    }),
  });
}

export async function fetchFavoriteProperties() {
  const payload = await backendJson("api/get_favorite_properties.php");
  const list = Array.isArray(payload?.properties) ? payload.properties : [];
  return list.map(normalizeProperty);
}

export async function saveFavorite(propertyId) {
  if (!propertyId) {
    throw new Error("Identificador de propiedad requerido");
  }

  return backendJson("api/save_favorite.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ property_id: propertyId }),
  });
}

export async function removeFavorite(propertyId) {
  if (!propertyId) {
    throw new Error("Identificador de propiedad requerido");
  }

  return backendJson("api/remove_favorite.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ property_id: propertyId }),
  });
}

export async function fetchUserPropertiesForSale() {
  const payload = await backendJson("api/get_user_properties.php");
  const list = Array.isArray(payload?.properties) ? payload.properties : [];

  return list.map((p) =>
    normalizeProperty({
      ...p,
      titulo: p.titulo || p.nombre || p.tipo_propiedad,
      categoria: p.categoria || p.tipo || "activos",
      ubicacion_general:
        p.ubicacion_general || p.ubicacion || p.direccion || "",
    })
  );
}