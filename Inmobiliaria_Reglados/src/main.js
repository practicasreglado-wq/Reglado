import { createApp } from 'vue';
import { createPinia } from 'pinia';
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate'; // Importamos el plugin
import App from './App.vue';
import router from './router';
import { revealDirective } from './directives/reveal';
import './styles/style.css';

const app = createApp(App);

const pinia = createPinia();
pinia.use(piniaPluginPersistedstate);  // Usamos el plugin

app.use(pinia);
app.use(router);
app.directive('reveal', revealDirective);

app.mount('#app');
