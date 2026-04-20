import { createRouter, createWebHistory } from "vue-router";
import { auth } from "@/services/auth.js";
import Home from "@/pages/Home.vue";
import Servicios from "@/pages/Servicios.vue";
import Proyectos from "@/pages/Proyectos.vue";
import Nosotros from "@/pages/Nosotros.vue";
import Contacto from "@/pages/Contacto.vue";
import AreaClientes from "@/pages/AreaClientes.vue";
import Admin from "@/pages/Admin.vue";
import AuthCallback from "@/pages/AuthCallback.vue";
import NotFound from "@/pages/NotFound.vue";

const routes = [
  { path: "/", component: Home },
  { path: "/servicios", component: Servicios },
  { path: "/proyectos", component: Proyectos },
  { path: "/nosotros", component: Nosotros },
  { path: "/contacto", component: Contacto },
  { path: "/area-clientes", component: AreaClientes, meta: { requiresAuth: true } },
  { path: "/admin", component: Admin, meta: { requiresAuth: true, requiresAdmin: true } },
  { path: "/auth/callback", component: AuthCallback },
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
    const callbackUrl = encodeURIComponent(window.location.origin + "/auth/callback");
    window.location.href = `${import.meta.env.VITE_AUTH_FRONTEND_URL || import.meta.env.VITE_AUTH_API_URL || "https://gruporeglado.com"}/login?returnTo=${callbackUrl}`;
    return false;
  }

  if (to.meta.requiresAdmin && auth.state.user?.role !== "admin") {
    return "/";
  }

  return true;
});

export default router;
