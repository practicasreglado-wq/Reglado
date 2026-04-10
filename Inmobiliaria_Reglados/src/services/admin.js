import { backendJson } from "./backend";
import { normalizeProperty } from "./properties";

export async function fetchAllProperties() {
  const payload = await backendJson("api/get_all_properties.php");
  
  if (payload.success && payload.properties) {
    return payload.properties.map(normalizeProperty);
  }
  
  throw new Error(payload.message || "Error al cargar las propiedades");
}
