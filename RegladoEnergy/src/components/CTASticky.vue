<!-- CTA flotante global -->
<template>
  <div class="sticky" :class="{ 'is-collapsed': isCollapsed }">
    <div class="inner glow" v-glow ref="bannerRef" @click="handleBannerClick">
      
      <!-- Contenido en estado normal (expandido) -->
      <div class="full-content" :class="{ 'fade-out': isCollapsed }">
        <button class="close-btn" @click.stop="toggleCollapse" aria-label="Minimizar">
          <span></span>
          <span></span>
        </button>
        <div class="left">
          <div class="dot pulse"></div>
          <div class="texts">
            <div class="t">¿Quieres saber si estás pagando de más?</div>
            <div class="s">Análisis gratuito de facturas · Respuesta rápida</div>
          </div>
        </div>
        <div class="right">
          <router-link to="/contacto" class="btn primary glow" @click.stop v-glow>Solicitar análisis</router-link>
          <a class="btn glow whatsapp" v-glow :href="whatsAppHref" target="_blank" rel="noopener" @click.stop>WhatsApp</a>
        </div>
      </div>

      <!-- Contenido en estado minimizado (encogido al cuarto superior) -->
      <div class="collapsed-content" :class="{ 'fade-in': isCollapsed }">
        <svg class="chevron" viewBox="0 0 24 24"><path d="M12 8l6 6H6z" /></svg>
        <span class="collapsed-text">Análisis gratuito</span>
      </div>

    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from "vue";

const isCollapsed = ref(false);
const bannerRef = ref(null);

const phoneRaw = "+34634165145";
const whatsAppHref = computed(
  () => `https://wa.me/${phoneRaw.replace(/\D/g, "")}?text=${encodeURIComponent("Hola, quiero información sobre Reglado Energy.")}`
);

function toggleCollapse() {
  isCollapsed.value = true;
}

function handleBannerClick() {
  if (isCollapsed.value) {
    isCollapsed.value = false;
  }
}

function handleClickOutside(event) {
  if (!isCollapsed.value && bannerRef.value && !bannerRef.value.contains(event.target)) {
    isCollapsed.value = true;
  }
}

onMounted(() => {
  setTimeout(() => {
    document.addEventListener("click", handleClickOutside);
  }, 100);
});

onUnmounted(() => {
  document.removeEventListener("click", handleClickOutside);
});
</script>

<style scoped>
.sticky {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 24px;
  z-index: 60;
  pointer-events: none;
  display: flex;
  justify-content: center;
}

/* La caja principal */
.inner {
  pointer-events: all;
  position: relative;
  background: rgba(30, 27, 15, 0.85);
  border: 1px solid rgba(242, 197, 61, 0.3);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
  color: #fff;
  
  /* Valores para la animación */
  overflow: hidden;
  width: 92%;
  max-width: 1100px;
  height: 80px; 
  border-radius: 12px;
  transition: all 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
  margin: 0 auto;
  transform: translateY(0);
}

/* Cuando se encoge, mitad de ancho y deslizado hacia abajo */
.sticky.is-collapsed .inner {
  width: 50%;
  max-width: 550px;
  /* 
    Se traslada 100% hacia abajo, pero como .sticky está a bottom: 24px,
    quedarán exactamente 24px asomando desde la parte inferior de la pantalla.
  */
  transform: translateY(100%);
  cursor: pointer;
  border-radius: 12px 12px 0 0;
}

.sticky.is-collapsed .inner:hover {
  background: rgba(45, 41, 23, 0.95);
  /* Pequeño rebote hacia arriba indicando que se puede clicar */
  transform: translateY(calc(100% - 4px));
}

/* Contenido Expandido */
.full-content {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  opacity: 1;
  transition: opacity 0.3s ease 0.2s, visibility 0s 0s;
}

.full-content.fade-out {
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
  transition: opacity 0.2s ease 0s, visibility 0s 0.2s;
}

/* Contenido Encogido visible en el borde superior */
.collapsed-content {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 24px; /* Ocupa exactamente la franja visible */
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
  transition: opacity 0.2s ease 0s, visibility 0s 0.2s;
}

.collapsed-content.fade-in {
  opacity: 1;
  visibility: visible;
  pointer-events: all;
  transition: opacity 0.3s ease 0.2s, visibility 0s 0s;
}

.chevron {
  width: 16px;
  height: 16px;
  fill: #f2c53d;
  transition: transform 0.3s ease;
}

.collapsed-text {
  font-weight: 600;
  color: #fff;
  font-size: 0.8rem;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}

/* Elementos internos */
.left { display: flex; align-items: center; gap: 16px; }
.dot { 
  width: 10px; 
  height: 10px; 
  border-radius: 50%; 
  background: #f2c53d; 
  box-shadow: 0 0 0 4px rgba(242, 197, 61, 0.15); 
  flex-shrink: 0;
}
.dot.pulse {
  animation: pulse-dot 2s infinite ease-in-out;
}
@keyframes pulse-dot {
  0% { box-shadow: 0 0 0 0 rgba(242, 197, 61, 0.4); }
  70% { box-shadow: 0 0 0 8px rgba(242, 197, 61, 0); }
  100% { box-shadow: 0 0 0 0 rgba(242, 197, 61, 0); }
}

.texts { display: flex; flex-direction: column; justify-content: center; }
.t { font-weight: 700; color: rgba(255, 255, 255, 0.95); font-size: 1rem; letter-spacing: 0.2px;}
.s { color: rgba(255, 255, 255, 0.7); font-size: 0.85rem; margin-top: 2px;}

.right { display: flex; gap: 12px; margin-right: 40px; }

.close-btn {
  position: absolute;
  top: 50%;
  right: 16px;
  transform: translateY(-50%);
  background: transparent;
  border: none;
  cursor: pointer;
  width: 28px;
  height: 28px;
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
  background: rgba(37, 211, 102, 1) !important;
  color: #fff !important;
  border-color: rgba(37, 211, 102, 1) !important;
}

@media (max-width: 980px) {
  .inner { height: 138px; }
  .sticky.is-collapsed .inner { width: 80%; transform: translateY(100%); border-radius: 12px 12px 0 0; }
  .full-content { 
    flex-direction: column; 
    align-items: flex-start; 
    justify-content: center; 
    padding: 16px 20px; 
    gap: 12px;
  }
  .left { gap: 12px; }
  .t { font-size: 0.95rem; line-height: 1.2; }
  .s { font-size: 0.8rem; }
  .right { gap: 8px; margin-right: 0; display: grid; grid-template-columns: 1fr 1fr; width: 100%; }
  .right .btn {
    width: 100%;
    min-height: 38px;
    padding: 8px 10px;
    font-size: 13px;
    border-radius: 8px;
    text-align: center;
    white-space: nowrap;
  }
  .close-btn { top: 18px; right: 14px; transform: none; width: 24px; height: 24px; }
}
</style>
