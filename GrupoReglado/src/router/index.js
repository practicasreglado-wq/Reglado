import { createRouter, createWebHistory } from "vue-router";
import PortalView from "../pages/PortalView.vue";

// Resto de páginas en lazy import: cada una se carga bajo demanda en su
// propio chunk, manteniendo el bundle inicial pequeño (solo home).
const RegisterView = () => import("../pages/RegisterView.vue");
const EmailVerifiedView = () => import("../pages/EmailVerifiedView.vue");
const LoginView = () => import("../pages/LoginView.vue");
const SettingsView = () => import("../pages/SettingsView.vue");
const ForgotPasswordView = () => import("../pages/ForgotPasswordView.vue");
const ResetPasswordView = () => import("../pages/ResetPasswordView.vue");
const AdminView = () => import("../pages/AdminView.vue");
const AvisoLegalView = () => import("../pages/AvisoLegalView.vue");
const PoliticaCookiesView = () => import("../pages/PoliticaCookiesView.vue");
const PoliticaPrivacidadView = () => import("../pages/PoliticaPrivacidadView.vue");
const ConfirmarAccesoView = () => import("../pages/ConfirmarAccesoView.vue");
const SsoHandshakeView = () => import("../pages/SsoHandshakeView.vue");
const SsoStoreView = () => import("../pages/SsoStoreView.vue");
const SsoLogoutView = () => import("../pages/SsoLogoutView.vue");

const routes = [
  { path: "/", name: "portal", component: PortalView, meta: { title: "Reglado Group | Portal Empresarial" } },
  { path: "/login", name: "login", component: LoginView, meta: { title: "Iniciar Sesión | Reglado Group" } },
  { path: "/registro", name: "registro", component: RegisterView, meta: { title: "Registro | Reglado Group" } },
  { path: "/recuperar-contrasena", name: "recuperar-contrasena", component: ForgotPasswordView, meta: { title: "Recuperar Contraseña | Reglado Group" } },
  { path: "/restablecer-contrasena", name: "restablecer-contrasena", component: ResetPasswordView, meta: { title: "Restablecer Contraseña | Reglado Group" } },
  { path: "/configuracion", name: "configuracion", component: SettingsView, meta: { title: "Configuración | Reglado Group" } },
  { path: "/admin", name: "admin", component: AdminView, meta: { title: "Panel Admin | Reglado Group" } },
  { path: "/aviso-legal", name: "aviso-legal", component: AvisoLegalView, meta: { title: "Aviso Legal | Reglado Group" } },
  { path: "/politica-cookies", name: "politica-cookies", component: PoliticaCookiesView, meta: { title: "Política de Cookies | Reglado Group" } },
  {
    path: "/verificacion-exitosa",
    name: "verificacion-exitosa",
    component: EmailVerifiedView,
    meta: { title: "Verificación Exitosa | Reglado Group" },
  },
  {
    path: "/politica-privacidad",
    name: "politica-privacidad",
    component: PoliticaPrivacidadView,
    meta: { title: "Política de Privacidad | Reglado Group" },
  },
  {
    path: "/confirmar-acceso",
    name: "confirmar-acceso",
    component: ConfirmarAccesoView,
    meta: { title: "Confirmar acceso | Reglado Group" },
  },
  { path: "/sso-handshake", name: "sso-handshake", component: SsoHandshakeView, meta: { title: "Sincronizando | Reglado Group" } },
  { path: "/sso-store", name: "sso-store", component: SsoStoreView, meta: { title: "Sincronizando | Reglado Group" } },
  { path: "/sso-logout", name: "sso-logout", component: SsoLogoutView, meta: { title: "Cerrando sesión | Reglado Group" } },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.afterEach((to) => {
  const defaultTitle = "Reglado Group";
  document.title = to.meta.title || defaultTitle;
});

export default router;
