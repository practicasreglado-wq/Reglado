import hotelImage from "../assets/hotel.png";
import fincaImage from "../assets/finca.png";
import parkingImage from "../assets/parking.png";
import edificiosImage from "../assets/edificios.png";
import activosImage from "../assets/activos.png";
import { backendJson } from "./backend";

const categoryImageMap = {
  Hoteles: hotelImage,
  Fincas: fincaImage,
  Parking: parkingImage,
  Edificios: edificiosImage,
  Activos: activosImage,
};

export function normalizeProperty(property) {
  return {
    ...property,
    imageUrl:
      property.image_url ||
      categoryImageMap[property.categoria] ||
      hotelImage,
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

export async function uploadPropertyPdf(pdfFile) {
  if (!pdfFile) {
    throw new Error("Debes seleccionar un PDF.");
  }

  const formData = new FormData();
  formData.append("pdf", pdfFile);

  return backendJson("api/upload_property_pdf.php", {
    method: "POST",
    body: formData,
  });
}

export async function uploadSignedDocuments(propertyId, ndaFile, loiFile) {
  const formData = new FormData();
  formData.append("property_id", String(propertyId));
  formData.append("nda", ndaFile);
  formData.append("loi", loiFile);

  return backendJson("api/upload_signed_documents.php", {
    method: "POST",
    body: formData,
  });
}

export async function checkSignedAccess(propertyId) {
  return backendJson("api/check_signed_access.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ property_id: propertyId }),
  });
}

export async function requestPropertyPurchase(propertyId) {
  return backendJson("api/request_purchase.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ property_id: propertyId }),
  });
}

export async function fetchFavoriteProperties() {
  const payload = await backendJson("api/get_favorite_properties.php");
  return (payload.properties || []).map(normalizeProperty);
}

export async function saveFavorite(data) {
  return backendJson("api/save-favorite.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data),
  });
}

export async function removeFavorite(propertyId) {
  return backendJson("api/remove-favorite.php", {
    method: "DELETE",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ property_id: propertyId }),
  });
}
