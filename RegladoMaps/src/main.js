import { createApp } from 'vue'
import App from './App.vue'
import router from './router' // Importación del enrutador

createApp(App)
  .use(router) // Usa Vue Router
  .mount('#app')
