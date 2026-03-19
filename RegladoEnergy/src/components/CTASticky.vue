<!-- CTA flotante global -->
<template>
  <div v-if="isVisible" class="sticky">
    <div class="container inner glow" v-glow>
      <button class="close-btn" @click="closeCTA" aria-label="Cerrar">
        <span></span>
        <span></span>
      </button>
      <div class="left">
        <div class="dot"></div>
        <div>
          <div class="t">Quieres saber si estas pagando de mas?</div>
          <div class="s">Analisis gratuito de facturas · Respuesta rapida</div>
        </div>
      </div>
      <div class="right">
        <router-link to="/contacto" class="btn primary glow" v-glow>Solicitar analisis</router-link>
        <a class="btn glow whatsapp" v-glow :href="whatsAppHref" target="_blank" rel="noopener">WhatsApp</a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from "vue";

const emit = defineEmits(["close"]);

const isVisible = ref(true);
const phoneRaw = "+34634165145";
const whatsAppHref = computed(
  () => `https://wa.me/${phoneRaw.replace(/\D/g, "")}?text=${encodeURIComponent("Hola, quiero informacion sobre Reglado Energy.")}`
);

function closeCTA() {
  isVisible.value = false;
  emit("close");
}
</script>

<style scoped>
.sticky { position: fixed; left: 0; right: 0; bottom: 14px; z-index: 60; pointer-events: none; }
.inner {
  pointer-events: all;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  position: relative;
  background: rgba(11, 13, 16, 0.78);
  border: 4px solid rgba(242, 197, 61, 0.5);
  backdrop-filter: blur(12px);
  border-radius: 20px;
  padding: 8px 12px;
  box-shadow: 0 18px 60px rgba(0, 0, 0, 0.55), inset 0 0 0 2px #fff;
}
.inner::before { display: none; }
.left { display: flex; align-items: center; gap: 12px; }
.dot { width: 10px; height: 10px; border-radius: 999px; background: var(--gold); box-shadow: 0 0 0 6px rgba(242, 197, 61, 0.12); }
.t { font-weight: 800; }
.s { color: rgba(233, 238, 246, 0.72); font-size: 13px; }
.right { display: flex; gap: 10px; margin-right: 40px; }

.close-btn {
  position: absolute;
  top: 8px;
  right: 8px;
  background: transparent;
  border: none;
  cursor: pointer;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  transition: background 0.2s;
  z-index: 10;
}

.close-btn:hover { background: rgba(255, 255, 255, 0.1); }
.close-btn span { position: absolute; width: 14px; height: 2px; background: rgba(233, 238, 246, 0.8); border-radius: 1px; }
.close-btn span:first-child { transform: rotate(45deg); }
.close-btn span:last-child { transform: rotate(-45deg); }

.whatsapp {
  background: rgba(37, 211, 102, 0.85);
  color: #fff;
  border-color: rgba(37, 211, 102, 0.85);
}

.whatsapp:hover {
  background: rgba(37, 211, 102, 0.85) !important;
  color: #fff !important;
  border-color: rgba(37, 211, 102, 0.85) !important;
}

@media (max-width: 980px) {
  .inner { flex-direction: column; align-items: stretch; }
  .t { font-size: 14px; line-height: 1.25; }
  .s { font-size: 12px; }
  .right { gap: 8px; margin-right: 0; }
  .right .btn {
    width: 100%;
    min-height: 38px;
    padding: 8px 12px;
    font-size: 13px;
    border-radius: 12px;
  }
}
</style>
