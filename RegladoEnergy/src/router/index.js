import { createRouter, createWebHashHistory } from "vue-router";
import Home from "../pages/Home.vue";
import Services from "../pages/Services.vue";
import Clients from "../pages/Clients.vue";
import Individuals from "../pages/Individuals.vue";
import Businesses from "../pages/Businesses.vue";
import PublicSector from "../pages/PublicSector.vue";
import PropertyManagers from "../pages/PropertyManagers.vue";
import Contact from "../pages/Contact.vue";
import Resources from "../pages/Resources.vue";
import About from "../pages/About.vue";
import ClientArea from "../pages/ClientArea.vue";
import NotFound from "../pages/NotFound.vue";
import AuthCallback from "../pages/AuthCallback.vue";
import Admin from "../pages/Admin.vue";

const routes = [
  { path: "/", component: Home },
  { path: "/servicios", component: Services },
  { path: "/clientes", component: Clients },
  { path: "/particulares", component: Individuals },
  { path: "/empresas", component: Businesses },
  { path: "/sector-publico", component: PublicSector },
  { path: "/administradores-fincas", component: PropertyManagers },
  { path: "/contacto", component: Contact },
  { path: "/recursos", component: Resources },
  { path: "/sobre-nosotros", component: About },
  { path: "/area-clientes", component: ClientArea },
  { path: "/admin", component: Admin },
  { path: "/auth/callback", component: AuthCallback },
  { path: "/:pathMatch(.*)*", component: NotFound },
];

export default createRouter({
  history: createWebHashHistory(),
  routes,
  scrollBehavior() { return { top: 0 }; },
});
