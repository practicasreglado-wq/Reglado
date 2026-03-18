<template>
  <section class="dashboard">
    <div class="dashboard-container">
      <!-- Show restricted access message if role is user -->
      <RestrictedAccess v-if="userStore.userRole === 'user'" />
      
      <!-- Normal carousel for real/admin -->
      <Carousel v-else />
    </div>
  </section>
</template>

<script>
import Carousel from "../components/Carousel.vue";
import RestrictedAccess from "../components/RestrictedAccess.vue";
import { useUserStore } from "../stores/user";

export default {
  name: "Dashboard",
  components: { Carousel, RestrictedAccess },

  setup() {
    const userStore = useUserStore();
    return { userStore };
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

@media (max-width: 480px) {
  .dashboard {
    padding-top: 40px;
    align-items: center;
  }
}
</style>