<template>
  <transition name="scroll-to-top">
    <button
      v-if="isVisible"
      class="scroll-to-top"
      type="button"
      aria-label="Volver arriba"
      @click="scrollToTop"
    >
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

const updateVisibility = () => {
  const currentScroll = window.scrollY || window.pageYOffset || 0;
  const viewportHeight = window.innerHeight || 0;
  const documentHeight = document.documentElement.scrollHeight || 0;
  const distanceToBottom = documentHeight - (currentScroll + viewportHeight);

  scrollY.value = currentScroll;
  isNearBottom.value = distanceToBottom <= 160;
};

const isVisible = computed(() => {
  if (scrollY.value <= 0) {
    return false;
  }

  return scrollY.value >= 300 || isNearBottom.value;
});

const scrollToTop = () => {
  window.scrollTo({
    top: 0,
    behavior: "smooth",
  });
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
  bottom: 30px;
  z-index: 1200;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 52px;
  height: 52px;
  border: 0;
  border-radius: 999px;
  background: linear-gradient(135deg, #163a76, #1d4f91);
  color: #fff;
  box-shadow: 0 12px 28px rgba(22, 58, 118, 0.24);
  cursor: pointer;
  transition:
    transform 0.25s ease,
    box-shadow 0.25s ease,
    opacity 0.25s ease;
}

.scroll-to-top:hover {
  transform: translateY(-4px) scale(1.04);
  box-shadow: 0 16px 34px rgba(22, 58, 118, 0.34);
}

.scroll-to-top:focus-visible {
  outline: 3px solid rgba(29, 79, 145, 0.35);
  outline-offset: 3px;
}

.scroll-to-top__icon {
  font-size: 1.35rem;
  font-weight: 700;
  line-height: 1;
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
    right: 20px;
    bottom: 20px;
    width: 48px;
    height: 48px;
  }
}
</style>
