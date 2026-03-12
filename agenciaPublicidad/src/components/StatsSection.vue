
<!-- 
  Sección de estadísticas y logros.
-->
<script setup>
import { ref, onMounted } from 'vue';

const stats = ref([
  { id: 1, target: 1.5, current: 0, prefix: '+', suffix: 'M', label: 'Seguidores Generados', decimals: 1 },
  { id: 2, target: 100, current: 0, prefix: '', suffix: '%', label: 'Visibilidad Aumentada', decimals: 0 },
  { id: 3, target: 120, current: 0, prefix: '+', suffix: '', label: 'Campañas Exitosas', decimals: 0 },
  { id: 4, target: 24, current: 0, prefix: '', suffix: '/7', label: 'Soporte Creativo', decimals: 0 }
]);

const statsSection = ref(null);
let animated = false;

const animateValue = (stat) => {
  const start = 0;
  const end = stat.target;
  const duration = 2000;
  const startTime = performance.now();

  const update = (currentTime) => {
    const elapsed = currentTime - startTime;
    const progress = Math.min(elapsed / duration, 1);
    
    // Easing function: easeOutExpo
    const easeProgress = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress);
    
    stat.current = (easeProgress * (end - start) + start).toFixed(stat.decimals);

    if (progress < 1) {
      requestAnimationFrame(update);
    } else {
      stat.current = end.toFixed(stat.decimals);
    }
  };

  requestAnimationFrame(update);
};

onMounted(() => {
  const observer = new IntersectionObserver((entries) => {
    if (entries[0].isIntersecting && !animated) {
      animated = true;
      stats.value.forEach(stat => animateValue(stat));
    }
  }, { threshold: 0.5 });

  if (statsSection.value) {
    observer.observe(statsSection.value);
  }
});
</script>

<template>
  <section ref="statsSection" class="stats">
    <div class="container stats-grid">
      <template v-for="(stat, index) in stats" :key="stat.id">
        <div class="stat-item">
          <span class="number">{{ stat.prefix }}{{ stat.current }}{{ stat.suffix }}</span>
          <span class="label">{{ stat.label }}</span>
        </div>
        <div v-if="index < stats.length - 1" class="stat-divider"></div>
      </template>
    </div>
  </section>
</template>

<style scoped>
.stats {
  background: var(--dark-blue);
  padding: 60px 0;
  color: var(--white);
}

.stats-grid {
  display: flex;
  justify-content: space-around;
  align-items: center;
  flex-wrap: wrap;
  gap: 2rem;
}

.stat-item {
  text-align: center;
  flex: 1;
  min-width: 200px;
}

.number {
  display: block;
  font-size: 3.5rem;
  font-weight: 800;
  margin-bottom: 0.5rem;
  background: linear-gradient(135deg, #ffffff, #d1d1d1, #ffffff);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  text-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.label {
  font-size: 1rem;
  text-transform: uppercase;
  letter-spacing: 2px;
  font-weight: 600;
  opacity: 0.9;
}

.stat-divider {
  width: 1px;
  height: 80px;
  background: rgba(255, 255, 255, 0.2);
}

@media (max-width: 992px) {
  .stat-divider {
    display: none;
  }
}
</style>
