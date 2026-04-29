/**
 * Router de RegladoEnergy.
 *
 * Home se importa estáticamente (es la landing más visitada — vale la pena
 * que viaje en el bundle inicial). El resto va con lazy import: cada página
 * se descarga bajo demanda en su primer hit, reduciendo el peso del bundle
 * inicial y mejorando Core Web Vitals (que pesa en el ranking de Google).
 */

import { createRouter, createWebHistory } from "vue-router";
import Home from "../pages/Home.vue";

const routes = [
  { path: "/", component: Home },
  { path: "/servicios", component: () => import("../pages/Services.vue") },
  { path: "/clientes", component: () => import("../pages/Clients.vue") },
  { path: "/particulares", component: () => import("../pages/Individuals.vue") },
  { path: "/empresas", component: () => import("../pages/Businesses.vue") },
  { path: "/sector-publico", component: () => import("../pages/PublicSector.vue") },
  { path: "/administradores-fincas", component: () => import("../pages/PropertyManagers.vue") },
  { path: "/contacto", component: () => import("../pages/Contact.vue") },
  { path: "/recursos", component: () => import("../pages/Resources.vue") },
  { path: "/sobre-nosotros", component: () => import("../pages/About.vue") },
  { path: "/area-clientes", component: () => import("../pages/ClientArea.vue") },
  { path: "/admin", component: () => import("../pages/Admin.vue") },
  { path: "/aviso-legal", component: () => import("../pages/AvisoLegal.vue") },
  { path: "/politica-cookies", component: () => import("../pages/PoliticaCookies.vue") },
  { path: "/politica-privacidad", component: () => import("../pages/PoliticaPrivacidad.vue") },
  { path: "/registro", component: () => import("../pages/RegisterView.vue") },
  { path: "/recuperar-contrasena", component: () => import("../pages/ForgotPasswordView.vue") },
  { path: "/restablecer-contrasena", component: () => import("../pages/ResetPasswordView.vue") },
  { path: "/verificacion-exitosa", component: () => import("../pages/EmailVerifiedView.vue") },
  { path: "/confirmar-acceso", component: () => import("../pages/ConfirmarAccesoView.vue") },
  { path: "/auth/callback", component: () => import("../pages/AuthCallback.vue") },
  { path: "/:pathMatch(.*)*", component: () => import("../pages/NotFound.vue") },
];

export default createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() { return { top: 0 }; },
});
