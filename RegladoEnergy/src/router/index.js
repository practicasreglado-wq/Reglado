/**
 * Router de RegladoEnergy.
 *
 * Home se importa estáticamente (es la landing más visitada — vale la pena
 * que viaje en el bundle inicial). El resto va con lazy import: cada página
 * se descarga bajo demanda en su primer hit, reduciendo el peso del bundle
 * inicial y mejorando Core Web Vitals (que pesa en el ranking de Google).
 *
 * Cada ruta declara `meta.title` para que Google indexe títulos
 * diferenciados por página y los usuarios vean en la pestaña/bookmark
 * dónde están exactamente. Si una ruta no declara `meta.title`, se usa el
 * `DEFAULT_TITLE` (la home y fallback).
 */

import { createRouter, createWebHistory } from "vue-router";
import Home from "../pages/Home.vue";

const DEFAULT_TITLE = "Reglado Energy | Consultoría energética independiente";

const routes = [
  { path: "/", component: Home, meta: { title: DEFAULT_TITLE } },
  { path: "/servicios", component: () => import("../pages/Services.vue"), meta: { title: "Servicios de consultoría energética | Reglado Energy" } },
  { path: "/clientes", component: () => import("../pages/Clients.vue"), meta: { title: "Nuestros clientes | Reglado Energy" } },
  { path: "/particulares", component: () => import("../pages/Individuals.vue"), meta: { title: "Energía para particulares | Reglado Energy" } },
  { path: "/empresas", component: () => import("../pages/Businesses.vue"), meta: { title: "Energía para empresas | Reglado Energy" } },
  { path: "/sector-publico", component: () => import("../pages/PublicSector.vue"), meta: { title: "Energía para el sector público | Reglado Energy" } },
  { path: "/administradores-fincas", component: () => import("../pages/PropertyManagers.vue"), meta: { title: "Energía para administradores de fincas | Reglado Energy" } },
  { path: "/contacto", component: () => import("../pages/Contact.vue"), meta: { title: "Contacto | Reglado Energy" } },
  { path: "/recursos", component: () => import("../pages/Resources.vue"), meta: { title: "Recursos energéticos | Reglado Energy" } },
  { path: "/sobre-nosotros", component: () => import("../pages/About.vue"), meta: { title: "Sobre nosotros | Reglado Energy" } },
  { path: "/area-clientes", component: () => import("../pages/ClientArea.vue"), meta: { title: "Área de clientes | Reglado Energy" } },
  { path: "/admin", component: () => import("../pages/Admin.vue"), meta: { title: "Administración | Reglado Energy" } },
  { path: "/aviso-legal", component: () => import("../pages/AvisoLegal.vue"), meta: { title: "Aviso legal | Reglado Energy" } },
  { path: "/politica-cookies", component: () => import("../pages/PoliticaCookies.vue"), meta: { title: "Política de cookies | Reglado Energy" } },
  { path: "/politica-privacidad", component: () => import("../pages/PoliticaPrivacidad.vue"), meta: { title: "Política de privacidad | Reglado Energy" } },
  { path: "/registro", component: () => import("../pages/RegisterView.vue"), meta: { title: "Registro | Reglado Energy" } },
  { path: "/recuperar-contrasena", component: () => import("../pages/ForgotPasswordView.vue"), meta: { title: "Recuperar contraseña | Reglado Energy" } },
  { path: "/restablecer-contrasena", component: () => import("../pages/ResetPasswordView.vue"), meta: { title: "Restablecer contraseña | Reglado Energy" } },
  { path: "/verificacion-exitosa", component: () => import("../pages/EmailVerifiedView.vue"), meta: { title: "Verificación exitosa | Reglado Energy" } },
  { path: "/confirmar-acceso", component: () => import("../pages/ConfirmarAccesoView.vue"), meta: { title: "Confirmar acceso | Reglado Energy" } },
  { path: "/auth/callback", component: () => import("../pages/AuthCallback.vue"), meta: { title: "Procesando acceso | Reglado Energy" } },
  { path: "/:pathMatch(.*)*", component: () => import("../pages/NotFound.vue"), meta: { title: "Página no encontrada | Reglado Energy" } },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() { return { top: 0 }; },
});

router.afterEach((to) => {
  document.title = to.meta.title || DEFAULT_TITLE;
});

export default router;
