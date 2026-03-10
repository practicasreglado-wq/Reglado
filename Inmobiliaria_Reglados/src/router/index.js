import { createRouter, createWebHistory } from "vue-router";
import { useUserStore } from "../stores/user";
import Home from "../views/Home.vue";
import Login from "../views/Login.vue";
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
import Messages from "../views/Messages.vue";
import PropertiesForSale from "../views/PropertiesForSale.vue";
import Settings from "../views/Settings.vue";
import MyPropertiesForSale from "../views/MyPropertiesForSale.vue";
import CreateProperty from "../views/CreateProperty.vue";
import ForgotPassword from "../views/ForgotPassword.vue";
import ResetPassword from "../views/ResetPassword.vue";
import AuthCallback from "../views/AuthCallback.vue";

const routes = [
  { path: "/", component: Home },
  { path: "/login", component: Login },
  { path: "/register", component: Register },
  { path: "/auth/callback", component: AuthCallback },
  { path: "/forgot-password", component: ForgotPassword },
  { path: "/reset-password", component: ResetPassword },
  { path: "/dashboard", component: Dashboard, meta: { requiresAuth: true } },
  {
    path: "/profile",
    component: UserProfile,
    meta: { requiresAuth: true },
    children: [
      { path: "", redirect: "/profile/properties-for-sale" },
      { path: "favorite-properties", component: FavoriteProperties },
      { path: "messages", component: Messages },
      { path: "properties-for-sale", component: PropertiesForSale },
      { path: "settings", component: Settings },
      { path: "my-properties-for-sale", component: MyPropertiesForSale },
      { path: "create-property", component: CreateProperty },
    ],
  },
  { path: "/questions", component: Questions, meta: { requiresAuth: true } },
  { path: "/about-us", component: AboutUs },
  { path: "/contacto", component: Contacto },
  { path: "/metodologia", component: Metodologia },
  { path: "/team", component: Team },
  { path: "/give-info", component: GiveInfo },
  { path: "/contribute-assets", component: ContributeAssets, meta: { requiresAuth: true } },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition;
    }

    if (to.hash) {
      return {
        el: to.hash,
        behavior: "smooth",
      };
    }

    return { top: 0 };
  },
});

router.beforeEach((to, from, next) => {
  const userStore = useUserStore();

  if (to.meta.requiresAuth && !userStore.isLoggedIn) {
    next("/login");
    return;
  }

  next();
});

export default router;
