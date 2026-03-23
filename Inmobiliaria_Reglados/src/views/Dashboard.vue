<template>
  <section class="dashboard">
    <div class="particles">
      <div
        v-for="particle in particles"
        :key="particle.id"
        class="particle"
        :style="particle.style"
      ></div>
    </div>

    <div class="dashboard-container">
      <RestrictedAccess v-if="userStore.userRole === 'user'" />
      <Carousel v-else />
    </div>
  </section>
</template>

<script>
import { onBeforeUnmount, onMounted, ref } from "vue";
import Carousel from "../components/Carousel.vue";
import RestrictedAccess from "../components/RestrictedAccess.vue";
import { useUserStore } from "../stores/user";

export default {
  name: "Dashboard",
  components: { Carousel, RestrictedAccess },

  setup() {
    const userStore = useUserStore();
    const particles = ref([]);
    const particleBudget = ref({
      initial: 15,
      max: 282,
      delay: 15000, 
      growthDuration: 20000, 
    });

    let nextParticleId = 0;
    let growthFrame = null;
    let interactionBoost = 0;
    let growthStart = 0;
    let lastInteractionAt = 0;

    const buildParticleBudget = () => {
      const viewportWidth = window.innerWidth;
      const cores = navigator.hardwareConcurrency || 4;
      const isMobile = viewportWidth <= 768;
      const isLowPower = cores <= 4;

      let initial = isMobile ? 12 : 15;
      let max = isMobile ? 140 : 282;
      let growthDuration = isMobile ? 25000 : 20000;
      let delay = 15000; // 15 segundos constantes

      if (isLowPower) {
        max = isMobile ? 48 : 110;
        growthDuration += 4000;
      }

      return { initial, max, growthDuration, delay };
    };

    const createParticle = () => {
      const size = Math.random() * 10 + 4; // Ligeramente más grandes

      return {
        id: nextParticleId++,
        style: {
          left: `${(Math.random() * 100).toFixed(2)}%`,
          top: `${(Math.random() * 100).toFixed(2)}%`,
          width: `${size.toFixed(1)}px`,
          height: `${size.toFixed(1)}px`,
          "--float-delay": `${(Math.random() * -12).toFixed(2)}s`,
          "--float-duration": `${(Math.random() * 10 + 11).toFixed(2)}s`,
          filter: Math.random() > 0.5 ? "blur(1.5px)" : "blur(0.5px)",
        },
      };
    };

    const addParticle = () => {
      if (particles.value.length >= particleBudget.value.max) {
        return;
      }

      particles.value.push(createParticle());
    };

    const syncParticleBudget = () => {
      particleBudget.value = buildParticleBudget();

      if (particles.value.length > particleBudget.value.max) {
        particles.value = particles.value.slice(0, particleBudget.value.max);
      }

      while (particles.value.length < particleBudget.value.initial) {
        addParticle();
      }
    };

    const registerInteraction = (amount = 1.8) => {
      const now = performance.now();

      if (now - lastInteractionAt < 180) {
        return;
      }

      lastInteractionAt = now;
      interactionBoost = Math.min(10, interactionBoost + amount);
    };

    const handleResize = () => {
      syncParticleBudget();
    };

    const handlePointerMove = () => {
      registerInteraction(1.6);
    };

    const handleScroll = () => {
      registerInteraction(1.1);
    };

    const runGrowthLoop = (timestamp) => {
      if (!growthStart) {
        growthStart = timestamp;
      }

      const { initial, max, delay, growthDuration } = particleBudget.value;
      const elapsed = timestamp - growthStart;

      let progress = 0;
      if (elapsed > delay) {
        // Solo empieza a crecer después del delay
        progress = Math.min((elapsed - delay) / growthDuration, 1);
      }

      const desiredCount =
        initial + (max - initial) * progress + interactionBoost;
      const targetCount = Math.min(max, Math.floor(desiredCount));

      if (particles.value.length < targetCount) {
        const additions = Math.min(targetCount - particles.value.length, 2);
        for (let index = 0; index < additions; index += 1) {
          addParticle();
        }
      }

      interactionBoost = Math.max(0, interactionBoost - 0.035);
      growthFrame = window.requestAnimationFrame(runGrowthLoop);
    };

    onMounted(() => {
      syncParticleBudget();
      growthFrame = window.requestAnimationFrame(runGrowthLoop);
      window.addEventListener("resize", handleResize, { passive: true });
      window.addEventListener("mousemove", handlePointerMove, { passive: true });
      window.addEventListener("scroll", handleScroll, { passive: true });
    });

    onBeforeUnmount(() => {
      if (growthFrame) {
        window.cancelAnimationFrame(growthFrame);
      }

      window.removeEventListener("resize", handleResize);
      window.removeEventListener("mousemove", handlePointerMove);
      window.removeEventListener("scroll", handleScroll);
    });

    return { userStore, particles };
  },
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
  padding-top: 90px;
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
  animation:
    particle-appear 0.55s ease-out forwards,
    float-particle var(--float-duration, 16s) ease-in-out var(--float-delay, 0s) infinite;
  will-change: transform, opacity;
}

@keyframes particle-appear {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes float-particle {
  0% {
    transform: translateY(0) scale(0);
    opacity: 0;
  }
  50% {
    opacity: 0.6;
  }
  100% {
    transform: translateY(-100px) scale(1.5);
    opacity: 0;
  }
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
