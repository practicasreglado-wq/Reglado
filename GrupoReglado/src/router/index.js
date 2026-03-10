import { createRouter, createWebHistory } from "vue-router";
import PortalView from "../pages/PortalView.vue";
import RegisterView from "../pages/RegisterView.vue";
import EmailVerifiedView from "../pages/EmailVerifiedView.vue";
import LoginView from "../pages/LoginView.vue";
import SettingsView from "../pages/SettingsView.vue";

const routes = [
  { path: "/", name: "portal", component: PortalView },
  { path: "/login", name: "login", component: LoginView },
  { path: "/registro", name: "registro", component: RegisterView },
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
