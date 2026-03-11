import { createRouter, createWebHistory } from "vue-router";
import PortalView from "../pages/PortalView.vue";
import RegisterView from "../pages/RegisterView.vue";
import EmailVerifiedView from "../pages/EmailVerifiedView.vue";
import LoginView from "../pages/LoginView.vue";
import SettingsView from "../pages/SettingsView.vue";
import ForgotPasswordView from "../pages/ForgotPasswordView.vue";
import ResetPasswordView from "../pages/ResetPasswordView.vue";

const routes = [
  { path: "/", name: "portal", component: PortalView },
  { path: "/login", name: "login", component: LoginView },
  { path: "/registro", name: "registro", component: RegisterView },
  { path: "/recuperar-contrasena", name: "recuperar-contrasena", component: ForgotPasswordView },
  { path: "/restablecer-contrasena", name: "restablecer-contrasena", component: ResetPasswordView },
  { path: "/configuracion", name: "configuracion", component: SettingsView },
  {
    path: "/verificacion-exitosa",
    name: "verificacion-exitosa",
    component: EmailVerifiedView,
  },
];

export default createRouter({
  history: createWebHistory(),
  routes,
});
