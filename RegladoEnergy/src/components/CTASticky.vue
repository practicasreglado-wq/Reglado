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
          <div class="t">¿Quieres saber si estás pagando de más?</div>
          <div class="s">Análisis gratuito de facturas · Respuesta rápida</div>
        </div>
      </div>
      <div class="right">
        <router-link to="/contacto" class="btn primary glow" v-glow>Solicitar análisis</router-link>
        <button class="btn glow whatsapp" v-glow type="button" @click="isContactModalOpen = true">
          WhatsApp
        </button>
      </div>
    </div>
  </div>

  <div
    v-if="isContactModalOpen"
    class="modal-backdrop"
    role="dialog"
    aria-modal="true"
    aria-labelledby="cta-contact-title"
    @click.self="isContactModalOpen = false"
  >
    <div class="modal-card">
      <button class="modal-close" type="button" aria-label="Cerrar" @click="isContactModalOpen = false">x</button>
      <div class="modal-badge">Contacto</div>
      <h2 id="cta-contact-title" class="modal-title">Hablemos de tu suministro</h2>
      <p class="modal-text">
        Podemos revisar tus facturas y orientarte sin compromiso. Escríbenos o llámanos y te contamos cómo empezar.
      </p>

      <div class="contact-list">
        <a class="contact-item" :href="whatsAppHref" target="_blank" rel="noopener">
          <strong>WhatsApp</strong>
          <span>{{ phoneDisplay }}</span>
        </a>
        <a class="contact-item" :href="`tel:${phoneRaw}`">
          <strong>Teléfono</strong>
          <span>{{ phoneDisplay }}</span>
        </a>
        <a class="contact-item" :href="`mailto:${email}`">
          <strong>Email</strong>
          <span>{{ email }}</span>
        </a>
      </div>

      <div class="modal-actions">
        <router-link to="/contacto" class="btn primary glow" @click="isContactModalOpen = false">
          Ir al formulario
        </router-link>
        <a class="btn glow" :href="whatsAppHref" target="_blank" rel="noopener">Abrir WhatsApp</a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from "vue";

const emit = defineEmits(["close"]);

const isVisible = ref(true);
const isContactModalOpen = ref(false);

const phoneRaw = "+34634165145";
const phoneDisplay = "+34 634 16 51 45";
const email = "info@regladoconsultores.com";
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

.modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 120;
  display: grid;
  place-items: center;
  background: rgba(7, 10, 14, 0.72);
  backdrop-filter: blur(8px);
  padding: 18px;
}

.modal-card {
  width: min(520px, 100%);
  position: relative;
  border-radius: 24px;
  border: 1px solid rgba(242, 197, 61, 0.24);
  background: linear-gradient(180deg, rgba(16, 19, 24, 0.98), rgba(26, 20, 11, 0.98));
  box-shadow: 0 30px 70px rgba(0, 0, 0, 0.45);
  padding: 28px;
}

.modal-close {
  position: absolute;
  top: 14px;
  right: 14px;
  width: 34px;
  height: 34px;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(255, 255, 255, 0.05);
  color: #fff;
  cursor: pointer;
}

.modal-badge {
  display: inline-flex;
  padding: 8px 12px;
  border-radius: 999px;
  border: 1px solid rgba(242, 197, 61, 0.26);
  background: rgba(242, 197, 61, 0.08);
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.modal-title { margin: 16px 0 10px; font-size: clamp(1.5rem, 3vw, 2rem); }
.modal-text { margin: 0 0 18px; color: rgba(233, 238, 246, 0.78); line-height: 1.65; }

.contact-list {
  display: grid;
  gap: 12px;
  margin-bottom: 18px;
}

.contact-item {
  display: grid;
  gap: 4px;
  padding: 14px 16px;
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.04);
  color: #fff;
  text-decoration: none;
}

.contact-item span { color: rgba(233, 238, 246, 0.76); }

.modal-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
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

@media (max-width: 640px) {
  .modal-card { padding: 22px 18px; }
  .modal-actions { flex-direction: column; }
  .modal-actions .btn { width: 100%; }
}
</style>
