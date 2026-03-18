<template>
  <transition name="app-loader">
    <div v-if="visible" class="loading-screen" aria-live="polite" aria-busy="true">
      <div class="loading-screen__panel">
        <img
          class="loading-screen__logo"
          :src="logoSrc"
          alt="Reglado"
          draggable="false"
        />
      </div>
    </div>
  </transition>
</template>

<script setup>
import logoSrc from "../assets/reglado-RS-logo.png";

defineProps({
  visible: {
    type: Boolean,
    default: false,
  },
});
</script>

<style scoped>
.loading-screen {
  position: fixed;
  inset: 0;
  z-index: 3000;
  display: grid;
  place-items: center;
  background:
    radial-gradient(circle at top, rgba(74, 114, 198, 0.14), transparent 34%),
    linear-gradient(180deg, rgba(247, 250, 252, 0.96), rgba(236, 242, 248, 0.96));
  user-select: none;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  -webkit-touch-callout: none;
  will-change: opacity;
}

.loading-screen__panel {
  display: grid;
  justify-items: center;
  gap: 14px;
  user-select: none;
  -webkit-user-select: none;
  pointer-events: none;
  will-change: transform, opacity;
}

.loading-screen__logo {
  width: clamp(108px, 13vw, 148px);
  height: auto;
  animation: app-loader-spin 1.2s linear infinite;
  filter: drop-shadow(0 8px 18px rgba(23, 48, 94, 0.12));
  user-select: none;
  -webkit-user-select: none;
  pointer-events: none;
  -webkit-user-drag: none;
  will-change: transform;
}

.app-loader-enter-active,
.app-loader-leave-active {
  transition: opacity 0.22s ease;
}

.app-loader-enter-from,
.app-loader-leave-to {
  opacity: 0;
}

.app-loader-enter-from .loading-screen__panel,
.app-loader-leave-to .loading-screen__panel {
  transform: translateY(8px) scale(0.99);
}

@keyframes app-loader-spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
