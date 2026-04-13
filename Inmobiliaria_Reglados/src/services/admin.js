import { backendJson } from "./backend";

export async function fetchAllProperties() {
  const payload = await backendJson("api/get_all_properties.php");
  console.log("PAYLOAD ADMIN:", payload);

  if (payload.success && Array.isArray(payload.properties)) {
    return payload.properties;
  }

  throw new Error(payload.message || "Error al cargar las propiedades");
}