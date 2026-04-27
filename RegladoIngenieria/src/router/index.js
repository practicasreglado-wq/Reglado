import { createRouter, createWebHistory } from "vue-router";
import { auth } from "@/services/auth.js";
import Home from "@/pages/Home.vue";
import Servicios from "@/pages/Servicios.vue";
import Proyectos from "@/pages/Proyectos.vue";
import Nosotros from "@/pages/Nosotros.vue";
import AreaClientes from "@/pages/AreaClientes.vue";
import Admin from "@/pages/Admin.vue";
import AuthCallback from "@/pages/AuthCallback.vue";
import LegalNotice from "@/pages/LegalNotice.vue";
import PrivacyPolicy from "@/pages/PrivacyPolicy.vue";
import CookiePolicy from "@/pages/CookiePolicy.vue";
import NotFound from "@/pages/NotFound.vue";
import RegisterView from "@/pages/RegisterView.vue";
import ForgotPasswordView from "@/pages/ForgotPasswordView.vue";
import ResetPasswordView from "@/pages/ResetPasswordView.vue";
import EmailVerifiedView from "@/pages/EmailVerifiedView.vue";
import ConfirmarAccesoView from "@/pages/ConfirmarAccesoView.vue";

const routes = [
  { path: "/", component: Home },
  { path: "/servicios", component: Servicios },
  { path: "/proyectos", component: Proyectos },
  { path: "/nosotros", component: Nosotros },
  { path: "/area-clientes", component: AreaClientes, meta: { requiresAuth: true } },
  { path: "/admin", component: Admin, meta: { requiresAuth: true, requiresAdmin: true } },
  { path: "/auth/callback", component: AuthCallback },
  { path: "/aviso-legal", component: LegalNotice },
  { path: "/politica-privacidad", component: PrivacyPolicy },
  { path: "/politica-cookies", component: CookiePolicy },
  { path: "/registro", component: RegisterView },
  { path: "/recuperar-contrasena", component: ForgotPasswordView },
  { path: "/restablecer-contrasena", component: ResetPasswordView },
  { path: "/verificacion-exitosa", component: EmailVerifiedView },
  { path: "/confirmar-acceso", component: ConfirmarAccesoView },
  { path: "/:pathMatch(.*)*", component: NotFound },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() { return { top: 0 }; },
});

router.beforeEach(async (to) => {
  if (!to.meta.requiresAuth) return true;

  if (!auth.state.token) {
    await auth.initialize();
  }

  if (!auth.state.token) {
    // En vez de redirigir a Grupo para loguear, mandamos a home con un
    // query flag que App.vue intercepta para abrir el modal de login y
    // recuperar la ruta original tras autenticar.
    return { path: "/", query: { login: "required", returnTo: to.fullPath } };
  }

  if (to.meta.requiresAdmin && auth.state.user?.role !== "admin") {
    return "/";
  }

  return true;
});

export default router;
