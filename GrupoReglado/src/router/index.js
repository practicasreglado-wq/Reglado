import { createRouter, createWebHistory } from "vue-router";
import PortalView from "../pages/PortalView.vue";
import RegisterView from "../pages/RegisterView.vue";
import EmailVerifiedView from "../pages/EmailVerifiedView.vue";
import LoginView from "../pages/LoginView.vue";
import SettingsView from "../pages/SettingsView.vue";
import ForgotPasswordView from "../pages/ForgotPasswordView.vue";
import ResetPasswordView from "../pages/ResetPasswordView.vue";
import AdminView from "../pages/AdminView.vue";
import AvisoLegalView from "../pages/AvisoLegalView.vue";
import PoliticaCookiesView from "../pages/PoliticaCookiesView.vue";
import PoliticaPrivacidadView from "../pages/PoliticaPrivacidadView.vue";
import ConfirmarAccesoView from "../pages/ConfirmarAccesoView.vue";
import SsoHandshakeView from "../pages/SsoHandshakeView.vue";
import SsoStoreView from "../pages/SsoStoreView.vue";
import SsoLogoutView from "../pages/SsoLogoutView.vue";

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
