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

export async function fetchProperties(category) {
  const payload = await backendJson(
    `api/get-propiedades.php?categoria=${encodeURIComponent(category || "")}`
  );

  return (payload.properties || []).map(normalizeProperty);
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

