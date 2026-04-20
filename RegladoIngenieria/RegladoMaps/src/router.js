import { createRouter, createWebHistory } from 'vue-router';
const routes = [
  // Ruta raíz: La Landing Page principal está integrada en App.vue.
  // Su lógica de scroll y animaciones depende del montaje de App.vue.
  { path: '/', component: { render: () => null } },
  
  // Rutas provisionales hasta crear los componentes finales en la carpeta views
  { path: '/reglado-group', component: { template: '<div style="padding-top:100px; text-align:center;"><h1>Reglado Group</h1><p>Sección en desarrollo.</p></div>' } },
  { path: '/login', component: { template: '<div style="padding-top:100px; text-align:center;"><h1>Iniciar Sesión</h1><p>Área de clientes en desarrollo.</p></div>' } },
  { path: '/aviso-legal', component: () => import('./components/AvisoLegal.vue') },
  { path: '/politica-cookies', component: () => import('./components/PoliticaCookies.vue') },
  { path: '/politica-privacidad', component: () => import('./components/PoliticaPrivacidad.vue') },
  { path: '/admin', component: () => import('./components/AdminPanel.vue') },
  { path: '/mapa', component: () => import('./components/MapView.vue') },
  { 
    path: '/auth/callback', 
    component: () => import('./components/AuthCallback.vue')
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

export default router;