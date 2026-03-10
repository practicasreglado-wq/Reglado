<!-- componente de botón redondo para subir al inicio de la página. -->
<template>
  <button
    class="scroll-top glow"
    :class="{ 'cta-closed': isCTAClosed, visible: isVisible }"
    v-glow
    type="button"
    aria-label="Volver arriba"
    @click="scrollToTop"
  >
    <span class="arrow" aria-hidden="true">&uarr;</span>
  </button>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from "vue";

const props = defineProps({
  isCTAClosed: {
    type: Boolean,
    default: false
  }
});

const isVisible = ref(false);
const SHOW_AFTER_SCROLL = 80;

function handleScroll() {
  isVisible.value = window.scrollY > SHOW_AFTER_SCROLL;
}

function scrollToTop() {
  window.scrollTo({ top: 0, behavior: "smooth" });
}

onMounted(() => {
  handleScroll();
  window.addEventListener("scroll", handleScroll, { passive: true });
});

onBeforeUnmount(() => {
  window.removeEventListener("scroll", handleScroll);
});
</script>

<style scoped>
.scroll-top {
  --st-ink: #0b0d10;
  position: fixed;
  right: 30px;
  bottom: 27px;
  z-index: 70;
  width: 44px;
  height: 44px;
  border-radius: 999px;
  border: 1px solid rgba(242, 197, 61, .42);
  background: linear-gradient(180deg, rgba(242, 197, 61, .96), rgba(242, 197, 61, .72));
  color: var(--st-ink);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 18px 44px rgba(242, 197, 61, .2);
  backdrop-filter: blur(8px);
  opacity: 0;
  transform: translateY(14px);
  pointer-events: none;
  transition: opacity .55s ease, transform .55s ease, box-shadow .16s ease, border-color .16s ease, background .16s ease;
}

.scroll-top.visible {
  opacity: 1;
  transform: translateY(0);
  pointer-events: auto;
}

.scroll-top::before {
  content: "";
  position: absolute;
  inset: 2px;
  border-radius: inherit;
  border: 1px solid rgba(255, 255, 255, .14);
  pointer-events: none;
}

.scroll-top::after {
  content: "";
  position: absolute;
  inset: -5px;
  border-radius: inherit;
  background: radial-gradient(circle, rgba(242, 197, 61, .14) 0%, rgba(242, 197, 61, 0) 70%);
  opacity: 0;
  pointer-events: none;
  transition: opacity .3s ease;
}

.scroll-top:hover {
  transform: translateY(-2px);
  box-shadow: 0 14px 40px rgba(242, 197, 61, .18);
  border-color: rgba(242, 197, 61, .42);
}

.scroll-top:hover::after {
  opacity: 1;
}

.scroll-top:active {
  transform: translateY(0);
}

.scroll-top.cta-closed {
  right: 10px;
  bottom: 10px;
}

.arrow {
  font-size: 20px;
  line-height: 1;
  font-weight: 900;
  transform: translateY(-1px) scaleX(1.05);
  text-shadow: 0 1px 0 rgba(0, 0, 0, .25);
}

@media (max-width: 980px) {
  .scroll-top {
    right: 14px;
    bottom: 125px;
    width: 46px;
    height: 46px;
  }

}

@media (min-width: 768px) and (max-width: 1356px) {
  .scroll-top {
    right: 20px;
    bottom: 90px;
    width: 45px;
    height: 45px;
  }
}

@media (min-width: 768px) and (max-width: 980px) {
  .scroll-top {
    right: 20px;
    bottom: 125px;
    width: 45px;
    height: 45px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .scroll-top {
    transition: opacity .2s linear;
  }
}
</style>
