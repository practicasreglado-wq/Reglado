<template>
  <section class="dashboard">
    <!-- Partículas doradas de fondo -->
    <div class="particles">
      <div 
        v-for="p in particles" 
        :key="p.id" 
        class="particle"
        :style="p.style"
      ></div>
    </div>

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
    
    // Partículas de fondo (estáticas/flotantes)
    const particles = Array.from({ length: 80 }).map((_, i) => {
      const size = Math.random() * 8 + 3;
      return {
        id: i,
        style: {
          left: `${Math.random() * 100}%`,
          top: `${Math.random() * 100}%`,
          width: `${size}px`,
          height: `${size}px`,
          animationDelay: `${Math.random() * 10}s`,
          animationDuration: `${Math.random() * 10 + 10}s`,
          filter: Math.random() > 0.5 ? 'blur(1.5px)' : 'blur(0.5px)'
        }
      };
    });

    return { userStore, particles };
  }
};
</script>

<style scoped>
.dashboard {
  position: relative;
  min-height: 100vh;
  width: 100%;
  overflow: hidden;
  display: flex;
  justify-content: center;
  align-items: center;
  background: linear-gradient(180deg, #ffffff 0%, #d9e8ff 100%);
  padding-top: 90px; /* Compensa el header fijo */
}

.particles {
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: 1;
}

.particle {
  position: absolute;
  background: #d4af37;
  border-radius: 50%;
  opacity: 0;
  box-shadow: 0 0 10px rgba(212, 175, 55, 0.4);
  animation: float-particle infinite ease-in-out;
}

@keyframes float-particle {
  0% { transform: translateY(0) scale(0); opacity: 0; }
  50% { opacity: 0.6; }
  100% { transform: translateY(-100px) scale(1.5); opacity: 0; }
}

.dashboard-container {
  position: relative;
  z-index: 2;
  width: 100%;
  max-width: 1400px;
  text-align: center;
}

@media (max-width: 480px) {
  .dashboard {
    align-items: center;
  }
}
</style>