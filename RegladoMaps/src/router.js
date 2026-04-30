import { createRouter, createWebHistory } from 'vue-router';

const DEFAULT_TITLE = "Reglado Maps | Mapa energético de España";

const routes = [
  // Ruta raíz: La Landing Page principal está integrada en App.vue.
  // Su lógica de scroll y animaciones depende del montaje de App.vue.
  { path: '/', component: { render: () => null }, meta: { title: DEFAULT_TITLE } },

  // Rutas provisionales hasta crear los componentes finales en la carpeta views
  { path: '/reglado-group', component: { template: '<div style="padding-top:100px; text-align:center;"><h1>Reglado Group</h1><p>Sección en desarrollo.</p></div>' }, meta: { title: "Reglado Group | Reglado Maps" } },
  { path: '/aviso-legal', component: () => import('./components/AvisoLegal.vue'), meta: { title: "Aviso legal | Reglado Maps" } },
  { path: '/politica-cookies', component: () => import('./components/PoliticaCookies.vue'), meta: { title: "Política de cookies | Reglado Maps" } },
  { path: '/politica-privacidad', component: () => import('./components/PoliticaPrivacidad.vue'), meta: { title: "Política de privacidad | Reglado Maps" } },
  { path: '/admin', component: () => import('./components/AdminPanel.vue'), meta: { title: "Administración | Reglado Maps" } },
  { path: '/mapa', component: () => import('./components/MapView.vue'), meta: { title: "Mapa interactivo | Reglado Maps" } },
  { path: '/registro', component: () => import('./components/RegisterView.vue'), meta: { title: "Registro | Reglado Maps" } },
  { path: '/recuperar-contrasena', component: () => import('./components/ForgotPasswordView.vue'), meta: { title: "Recuperar contraseña | Reglado Maps" } },
  { path: '/restablecer-contrasena', component: () => import('./components/ResetPasswordView.vue'), meta: { title: "Restablecer contraseña | Reglado Maps" } },
  { path: '/verificacion-exitosa', component: () => import('./components/EmailVerifiedView.vue'), meta: { title: "Verificación exitosa | Reglado Maps" } },
  { path: '/confirmar-acceso', component: () => import('./components/ConfirmarAccesoView.vue'), meta: { title: "Confirmar acceso | Reglado Maps" } },
  {
    path: '/auth/callback',
    component: () => import('./components/AuthCallback.vue'),
    meta: { title: "Procesando acceso | Reglado Maps" }
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition;
    } else {
      return { top: 0, behavior: 'instant' };
    }
  }
});

router.afterEach((to) => {
  document.title = to.meta.title || DEFAULT_TITLE;
});

export default router;
