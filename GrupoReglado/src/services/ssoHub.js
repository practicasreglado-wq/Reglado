/**
 * SSO Hub — utilidades compartidas de las 3 páginas SSO de Grupo.
 *
 * Grupo actúa como hub central: los demás dominios del ecosistema redirigen
 * aquí para ceder / recibir / limpiar sesión. Ver docs/ECOSYSTEM_AUTH_SSO_HUB.md.
 *
 * La allowlist es HARDCODEADA a propósito: simplifica auditoría y evita
 * drift con env vars. Al añadir un dominio nuevo al ecosistema hay que
 * modificar este archivo y redeployar Grupo.
 */

const SSO_ALLOWED_RETURNS = [
  // Dev
  "http://localhost:5173",
  "http://127.0.0.1:5173",
  "http://localhost:5174",
  "http://127.0.0.1:5174",
  "http://localhost:5175",
  "http://127.0.0.1:5175",
  "http://localhost:5176",
  "http://127.0.0.1:5176",
  "http://localhost:5177",
  "http://127.0.0.1:5177",
  // Prod
  "https://regladogroup.com",
  "https://www.regladogroup.com",
  "https://regladoenergy.com",
  "https://teal-bat-675895.hostingersite.com", // Reglado Maps (dominio Hostinger provisional)
  "https://regladorealestate.com", // Inmobiliaria_Reglados
  // TODO: añadir dominio final de Maps y el de Ingeniería cuando estén listos.
];

/**
 * Devuelve true si la URL de retorno apunta a un origen permitido.
 * Valida scheme + host + puerto (reconstruido del parseado) contra la lista.
 */
export function isAllowedReturnUrl(returnUrl) {
  if (typeof returnUrl !== "string" || returnUrl === "") return false;
  let parsed;
  try {
    parsed = new URL(returnUrl);
  } catch {
    return false;
  }

  const scheme = parsed.protocol.replace(/:$/, "").toLowerCase();
  if (!["http", "https"].includes(scheme)) return false;

  const host = parsed.hostname.toLowerCase();
  const port = parsed.port ? `:${parsed.port}` : "";
  const origin = `${scheme}://${host}${port}`;

  return SSO_ALLOWED_RETURNS.includes(origin);
}

/**
 * Serializa params en query string sin incluir los que sean undefined/null.
 */
export function buildReturnUrlWithParams(returnUrl, params = {}) {
  const url = new URL(returnUrl);
  for (const [k, v] of Object.entries(params)) {
    if (v !== undefined && v !== null) url.searchParams.set(k, String(v));
  }
  return url.toString();
}

/**
 * Añade un token al fragmento (#) del returnUrl. El fragmento NO se envía
 * al servidor destino — ni en logs ni en Referer — así que es más seguro
 * que pasar el token por query.
 */
export function buildReturnUrlWithTokenFragment(returnUrl, token) {
  const url = new URL(returnUrl);
  url.hash = `token=${encodeURIComponent(token)}`;
  return url.toString();
}

/**
 * Temas por origen del dominio que inició el handshake. Se usa en las
 * 3 páginas SSO para que el flash visible durante el redirect coincida
 * con la estética del proyecto de origen (Energy dorado, Maps cyan, etc.)
 * en vez de mostrar los colores de Grupo.
 *
 * Claves: mismas cadenas `scheme://host[:port]` que SSO_ALLOWED_RETURNS.
 */
const SSO_THEMES = {
  // Energy — dark + dorado
  "http://localhost:5174": ENERGY_THEME(),
  "http://127.0.0.1:5174": ENERGY_THEME(),
  "https://regladoenergy.com": ENERGY_THEME(),
  // Maps — dark + cyan
  "http://localhost:5176": MAPS_THEME(),
  "http://127.0.0.1:5176": MAPS_THEME(),
  "https://teal-bat-675895.hostingersite.com": MAPS_THEME(),
  // Ingeniería — light + steel blue
  "http://localhost:5177": INGENIERIA_THEME(),
  "http://127.0.0.1:5177": INGENIERIA_THEME(),
  // Inmobiliaria — light + navy
  "http://localhost:5175": INMOBILIARIA_THEME(),
  "http://127.0.0.1:5175": INMOBILIARIA_THEME(),
  "https://regladorealestate.com": INMOBILIARIA_THEME(),
};

function ENERGY_THEME() {
  return {
    name: "energy",
    bg: "#0b0d10",
    surface: "#1c1f25",
    text: "#e9eef6",
    textMuted: "rgba(233, 238, 246, 0.65)",
    accent: "#c5a021",
    accentSoft: "rgba(197, 160, 33, 0.2)",
    border: "rgba(255, 255, 255, 0.12)",
  };
}

function MAPS_THEME() {
  return {
    name: "maps",
    bg: "#02060f",
    surface: "#0a0f19",
    text: "#e9eef6",
    textMuted: "rgba(233, 238, 246, 0.65)",
    accent: "#00E5FF",
    accentSoft: "rgba(0, 229, 255, 0.2)",
    border: "rgba(0, 229, 255, 0.22)",
  };
}

function INGENIERIA_THEME() {
  return {
    name: "ingenieria",
    bg: "#f5f7fa",
    surface: "#ffffff",
    text: "#1a1f2e",
    textMuted: "#6b7280",
    accent: "#4a9eff",
    accentSoft: "rgba(74, 158, 255, 0.12)",
    border: "#e0e4ea",
  };
}

function INMOBILIARIA_THEME() {
  return {
    name: "inmobiliaria",
    bg: "#f5f7fa",
    surface: "#ffffff",
    text: "#1a1f2e",
    textMuted: "#64748b",
    accent: "#24386b",
    accentSoft: "rgba(36, 56, 107, 0.12)",
    border: "rgba(36, 56, 107, 0.18)",
  };
}

function GRUPO_THEME() {
  return {
    name: "grupo",
    bg: "#ffffff",
    surface: "#ffffff",
    text: "#0f172a",
    textMuted: "#64748b",
    accent: "#273d5c",
    accentSoft: "rgba(39, 61, 92, 0.1)",
    border: "rgba(39, 61, 92, 0.15)",
  };
}

/**
 * Devuelve el tema visual correspondiente al origen del returnUrl, o el
 * tema de Grupo por defecto si no se reconoce (puede ser un handshake
 * dentro del propio Grupo o un URL fuera de la allowlist).
 */
export function getThemeForReturn(returnUrl) {
  if (typeof returnUrl !== "string" || returnUrl === "") return GRUPO_THEME();
  let parsed;
  try {
    parsed = new URL(returnUrl);
  } catch {
    return GRUPO_THEME();
  }

  const scheme = parsed.protocol.replace(/:$/, "").toLowerCase();
  const host = parsed.hostname.toLowerCase();
  const port = parsed.port ? `:${parsed.port}` : "";
  const origin = `${scheme}://${host}${port}`;

  return SSO_THEMES[origin] || GRUPO_THEME();
}
