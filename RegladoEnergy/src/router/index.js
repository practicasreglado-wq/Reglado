import { createRouter, createWebHistory } from "vue-router";
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
import AvisoLegal from "../pages/AvisoLegal.vue";
import PoliticaCookies from "../pages/PoliticaCookies.vue";
import PoliticaPrivacidad from "../pages/PoliticaPrivacidad.vue";
import RegisterView from "../pages/RegisterView.vue";
import ForgotPasswordView from "../pages/ForgotPasswordView.vue";
import EmailVerifiedView from "../pages/EmailVerifiedView.vue";
import ResetPasswordView from "../pages/ResetPasswordView.vue";
import ConfirmarAccesoView from "../pages/ConfirmarAccesoView.vue";

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
  { path: "/aviso-legal", component: AvisoLegal },
  { path: "/politica-cookies", component: PoliticaCookies },
  { path: "/politica-privacidad", component: PoliticaPrivacidad },
  { path: "/registro", component: RegisterView },
  { path: "/recuperar-contrasena", component: ForgotPasswordView },
  { path: "/restablecer-contrasena", component: ResetPasswordView },
  { path: "/verificacion-exitosa", component: EmailVerifiedView },
  { path: "/confirmar-acceso", component: ConfirmarAccesoView },
  { path: "/auth/callback", component: AuthCallback },
  { path: "/:pathMatch(.*)*", component: NotFound },
];

export default createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() { return { top: 0 }; },
});
