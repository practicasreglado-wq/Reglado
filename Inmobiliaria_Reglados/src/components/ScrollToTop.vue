<!--
  Botón flotante "subir arriba" que aparece después de hacer scroll una
  cierta distancia. Renderizado en App.vue, presente en todas las páginas.
  Sin estado de negocio, solo UX.
-->
<template>
  <transition name="scroll-to-top">
    <button
      v-if="isVisible"
      class="scroll-to-top"
      :class="{ 
        'is-active': isActive,
        'at-footer': isNearBottom 
      }"
      type="button"
      aria-label="Volver arriba"
      @click="handleScrollToTop"
    >
      <!-- Efecto Viento -->
      <div class="wind-effect">
        <svg viewBox="0 0 100 100" class="wind-svg">
          <line class="wind-line w1" x1="50" y1="20" x2="50" y2="0" />
          <line class="wind-line w2" x1="30" y1="40" x2="30" y2="10" />
          <line class="wind-line w3" x1="70" y1="40" x2="70" y2="10" />
        </svg>
      </div>
      <span class="scroll-to-top__icon" aria-hidden="true">&uarr;</span>
    </button>
  </transition>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";

const route = useRoute();
const scrollY = ref(0);
const isNearBottom = ref(false);
const isActive = ref(false);

const updateVisibility = () => {
  const currentScroll = window.scrollY || window.pageYOffset || 0;
  const viewportHeight = window.innerHeight || 0;
  
  const footer = document.querySelector('.footer');
  if (footer) {
    const footerRect = footer.getBoundingClientRect();
    const isMobile = window.innerWidth <= 640;
    const buttonBottomMargin = isMobile ? 15 : 30;
    const buttonHalfHeight = isMobile ? 20 : 26;
    const buttonCenter = viewportHeight - (buttonBottomMargin + buttonHalfHeight);

    // Trigger exactly when the button's midpoint hits the footer's top edge
    isNearBottom.value = footerRect.top <= buttonCenter;
  } else {
    const documentHeight = document.documentElement.scrollHeight || 0;
    const distanceToBottom = documentHeight - (currentScroll + viewportHeight);
    isNearBottom.value = distanceToBottom <= 160;
  }

  scrollY.value = currentScroll;
};

const isVisible = computed(() => {
  // Ocultar la flecha en Home y Dashboard
  if (route.path === '/' || route.path === '/dashboard') return false;

  if (isActive.value) return true; // Mantener visible durante la animación
  if (scrollY.value <= 0) return false;
  return scrollY.value >= 300 || isNearBottom.value;
});

const handleScrollToTop = () => {
  if (isActive.value) return;
  
  isActive.value = true;
  
  window.scrollTo({
    top: 0,
    behavior: "smooth",
  });

  // Resetear estado después de la animación de viento
  setTimeout(() => {
    isActive.value = false;
  }, 1000);
};

onMounted(() => {
  window.addEventListener("scroll", updateVisibility, { passive: true });
  window.addEventListener("resize", updateVisibility, { passive: true });
  updateVisibility();
});

onBeforeUnmount(() => {
  window.removeEventListener("scroll", updateVisibility);
  window.removeEventListener("resize", updateVisibility);
});

watch(
  () => route.fullPath,
  () => {
    requestAnimationFrame(() => {
      updateVisibility();
    });
  }
);
</script>

<style scoped>
.scroll-to-top {
  position: fixed;
  right: 30px;
  bottom: 80px; /* Raised to avoid overlapping footer text */
  z-index: 1200;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 52px;
  height: 52px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 999px;
  background: rgba(11, 42, 95, 0.4);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  color: #fff;
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.25);
  cursor: pointer;
  transition:
    transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1),
    box-shadow 0.3s ease,
    background 0.3s ease;
  overflow: visible;
}

.scroll-to-top:hover {
  transform: translateY(-6px) scale(1.1);
  background: rgba(9, 39, 92, 1);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
}

.scroll-to-top.is-active {
  transform: translateY(-80px) scale(1.2);
  opacity: 0;
  transition: transform 0.7s ease-in, opacity 0.7s ease-in;
}

/* ESTILO EN EL FOOTER */
.scroll-to-top.at-footer {
  background: rgba(255, 255, 255, 0.4);
  color: #0b2a5f;
  border-color: rgba(11, 42, 95, 0.2);
  box-shadow: 0 12px 32px rgba(11, 42, 95, 0.2);
}

.scroll-to-top.at-footer:hover {
  background: rgba(248, 250, 255, 1);
}

.scroll-to-top.at-footer .wind-svg {
  stroke: rgba(11, 42, 95, 0.6);
}

/* EFECTO VIENTO */
.wind-effect {
  position: absolute;
  top: -20px;
  left: 0;
  width: 100%;
  height: 40px;
  pointer-events: none;
  opacity: 0;
}

.wind-svg {
  width: 100%;
  height: 100%;
  stroke: rgba(255, 255, 255, 0.8);
  stroke-width: 2.5;
  stroke-linecap: round;
}

.scroll-to-top.is-active .wind-effect {
  opacity: 1;
}

.scroll-to-top.is-active .wind-line {
  animation: windFlow 0.5s ease-out forwards;
}

@keyframes windFlow {
  0% {
    stroke-dasharray: 0 100;
    stroke-dashoffset: 0;
    opacity: 1;
  }
  50% {
    stroke-dasharray: 40 100;
    stroke-dashoffset: -20;
    opacity: 1;
  }
  100% {
    stroke-dasharray: 0 100;
    stroke-dashoffset: -80;
    opacity: 0;
  }
}

.w2 { animation-delay: 0.05s; }
.w3 { animation-delay: 0.1s; }

.scroll-to-top__icon {
  font-size: 1.35rem;
  font-weight: 700;
  line-height: 1;
  transition: transform 0.3s ease;
}

.scroll-to-top.is-active .scroll-to-top__icon {
  transform: translateY(-15px);
}

.scroll-to-top:focus-visible {
  outline: 3px solid rgba(255, 255, 255, 0.4);
  outline-offset: 3px;
}

.scroll-to-top-enter-active,
.scroll-to-top-leave-active {
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
}

.scroll-to-top-enter-from,
.scroll-to-top-leave-to {
  opacity: 0;
  transform: translateY(10px) scale(0.9);
}

@media (max-width: 640px) {
  .scroll-to-top {
    right: 15px;
    bottom: 75px; /* Raised for mobile */
    width: 40px;
    height: 40px;
  }
  .scroll-to-top__icon {
    font-size: 1.1rem;
  }
}
</style>
