import { createApp } from "vue";
import App from "./App.vue";
import router from "./router/index.js";
import "./assets/styles.css";

import reveal from "./directives/reveal.js";
import glow from "./directives/glow.js";

createApp(App)
  .use(router)
  .directive("reveal", reveal)
  .directive("glow", glow)
  .mount("#app");
