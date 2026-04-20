<!-- pagina 404 en ruta comodin /:pathMatch(.*)* -->
<template>
  <section class="section">
    <div class="container">
      <div class="card glow" v-glow v-reveal="{ from: 'up', delay: 60 }">
        <h1 class="h1" v-reveal="{ from: 'up', delay: 100 }">Página no encontrada</h1>
        <p class="p" v-reveal="{ from: 'up', delay: 140 }">La ruta solicitada no existe. Vuelve al inicio o usa el menú.</p>
        <router-link to="/" class="btn primary glow" v-glow v-reveal="{ from: 'up', delay: 180 }">Ir a Inicio</router-link>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted } from "vue";
import { setSeo } from "../seo.js";

onMounted(() => {
  setSeo({
    title: "Página no encontrada | Reglado Energy",
    description: "La página solicitada no existe. Vuelve al inicio o utiliza el menú para navegar.",
    canonical: "/#/404"
  });
});
</script>
