/**
 * Configuración del router de la SPA + guards de acceso por rol.
 *
 * Convención de meta-fields:
 *  - requiresAuth:  necesita estar logueado (cualquier rol).
 *  - requiresReal:  necesita rol 'real' o 'admin' (Premium o superior).
 *  - requiresAdmin: necesita rol 'admin' exclusivamente.
 *
 * El guard global (beforeEach al final del archivo) lee meta y redirige a:
 *  - /login → si requiresAuth y no hay sesión.
 *  - RestrictedAccessView → si requiresReal y el usuario es 'basic'.
 *  - RestrictedAdminView → si requiresAdmin y el usuario no es admin.
 *
 * Las subrutas anidadas bajo /profile heredan el meta de la padre + añaden
 * los suyos propios (ej. /profile/favorite-properties exige Real porque
 * lo hereda PERO también tiene su propio meta).
 */

import { createRouter, createWebHistory } from "vue-router";
import { useUserStore } from "../stores/user";
import Home from "../views/Home.vue";
import Register from "../views/Register.vue";
import Dashboard from "../views/Dashboard.vue";
import UserProfile from "../views/Profile.vue";
import AboutUs from "../views/AboutUs.vue";
import Contacto from "../views/Contacto.vue";
import Questions from "../views/Questions.vue";
import Metodologia from "../views/Metodologia.vue";
import Team from "../views/Team.vue";
import GiveInfo from "../views/GiveInfo.vue";
import ContributeAssets from "../views/ContributeAssets.vue";
import FavoriteProperties from "../views/FavoriteProperties.vue";
import PropertiesForSale from "../views/PropertiesForSale.vue";
import MyPropertiesForSale from "../views/MyPropertiesForSale.vue";
import CreateProperty from "../views/CreateProperty.vue";
import SearchHistory from "../views/SearchHistory.vue";
import AuthCallback from "../views/AuthCallback.vue";
import RestrictedAccessView from "../views/RestrictedAccessView.vue";
import PropertyDetail from "../views/PropertyDetail.vue";
import Legal from "../views/Legal.vue";
import ForgotPasswordView from "../views/ForgotPasswordView.vue";
import ResetPasswordView from "../views/ResetPasswordView.vue";
import EmailVerifiedView from "../views/EmailVerifiedView.vue";
import ConfirmarAccesoView from "../views/ConfirmarAccesoView.vue";

const routes = [
  { path: "/", component: Home },
  // /login ahora dispara el modal local desde App.vue via query flag.
  { path: "/login", redirect: { path: "/", query: { login: "required" } } },
  // /register se mantiene por compat con bookmarks antiguos; /registro es la canónica.
  { path: "/register", redirect: "/registro" },
  { path: "/registro", component: Register },
  { path: "/recuperar-contrasena", component: ForgotPasswordView },
  { path: "/restablecer-contrasena", component: ResetPasswordView },
  { path: "/verificacion-exitosa", component: EmailVerifiedView },
  { path: "/confirmar-acceso", component: ConfirmarAccesoView },
  { path: "/auth/callback", component: AuthCallback },
  { path: "/dashboard", component: Dashboard, meta: { requiresAuth: true, requiresReal: true } },
  {
    path: "/profile",
    component: UserProfile,
    meta: { requiresAuth: true },
    children: [
      { path: "", redirect: "/profile/properties-for-sale" },
      { path: "properties-for-sale", component: PropertiesForSale },
      { path: "favorite-properties", component: FavoriteProperties, meta: { requiresReal: true } },
      { path: "search-history", component: SearchHistory, meta: { requiresReal: true } },
      { path: "my-properties-for-sale", component: MyPropertiesForSale },
      { path: "create-property", component: CreateProperty },
    ],
  },
  { path: "/questions", component: Questions, meta: { requiresAuth: true, requiresReal: true } },
  { path: "/about-us", component: AboutUs },
  { path: "/contacto", component: Contacto },
  { path: "/metodologia", component: Metodologia },
  { path: "/team", component: Team },
  { path: "/give-info", component: GiveInfo },
  { path: "/contribute-assets", component: ContributeAssets, meta: { requiresAuth: true, requiresReal: true } },
  { path: "/restricted", component: RestrictedAccessView, meta: { requiresAuth: true } },
  {
    path: "/admin/properties",
    component: () => import("../views/AdminPropertiesView.vue"),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: "/admin/audit",
    component: () => import("../views/AdminAuditView.vue"),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: "/admin/pending-requests",
    component: () => import("../views/AdminPendingRequestsView.vue"),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: "/admin/users",
    component: () => import("../views/AdminUsersView.vue"),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  { path: "/admin/restricted", component: () => import("../views/RestrictedAdminView.vue"), meta: { requiresAuth: true } },
  { path: "/property/:id", component: PropertyDetail, meta: { requiresAuth: true, requiresReal: true } },
  { path: "/legal", component: Legal },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition;
    }

    if (to.hash) {
      return new Promise((resolve) => {
        // Small delay to ensure the component is mounted & rendered
        setTimeout(() => {
          resolve({
            el: to.hash,
            behavior: "smooth",
          });
        }, 500); // 500ms allows for component mounting and initial transitions
      });
    }

    return { top: 0 };
  },
});

router.beforeEach((to, from, next) => {
  const userStore = useUserStore();

  const isAuthRoute =
    to.path === "/login" ||
    to.path === "/register" ||
    to.path === "/registro";

  if (to.meta.requiresAuth && !userStore.isLoggedIn) {
    // En vez de redirigir a /login (que era una vista de "vete a Grupo"),
    // mandamos al home con un query flag que App.vue intercepta para
    // abrir el modal de login local; tras autenticar, App.vue navega a
    // returnTo automáticamente.
    return next({ path: "/", query: { login: "required", returnTo: to.fullPath } });
  }

  if (isAuthRoute && userStore.isLoggedIn) {
    return next(userStore.isAdmin ? "/admin/properties" : "/dashboard");
  }

  // Admin based protection
  if (to.meta.requiresAdmin && !userStore.isAdmin) {
    return next("/admin/restricted");
  }

  // Role based protection
  if (to.meta.requiresReal && !userStore.isReal) {
    return next("/restricted");
  }

  next();
});

export default router;
