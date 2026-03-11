<template>
  <section class="dashboard">
    <div class="dashboard-container">
      <!-- Siempre mostramos el carrusel, sin importar si hay categoría seleccionada o no -->
      <Carousel />
    </div>
  </section>
</template>

<script>
import Carousel from "../components/Carousel.vue";

export default {
  name: "Dashboard",
  components: { Carousel },

  setup() {
    return {};
  }
};
</script>

<style scoped>
.dashboard {
  min-height:100vh;
  background: linear-gradient(
    180deg,
    #b6c6d6,
    #eef2f7
  );
  display: flex;
  justify-content: center;
  align-items: center;
}

.dashboard-container {
  text-align: center;
}
</style>