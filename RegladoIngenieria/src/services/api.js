const BACKEND_BASE = import.meta.env.VITE_BACKEND_BASE;

export async function submitContact(data) {
  const response = await fetch(`${BACKEND_BASE}/contact.php`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data),
  });
  const payload = await response.json();
  if (!response.ok) throw new Error(payload.message || "Error al enviar el formulario.");
  return payload;
}
