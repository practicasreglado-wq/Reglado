<template>
  <transition name="slide-in-right">
    <div v-if="isVisible" class="cookie-banner-wrapper">
      <div class="cookie-banner card glow">
        <div class="cookie-content">
          <p class="cookie-text">
            Utilizamos cookies de sesión (estrictamente funcionales) para garantizar el correcto funcionamiento y la seguridad de nuestra web. Estas cookies están siempre activas. Al continuar navegando, consideramos que aceptas su uso. Puedes leer más en nuestra <router-link to="/politica-cookies">Política de Cookies</router-link>.
          </p>
        </div>
        <div class="cookie-actions">
          <button @click="acceptCookies" class="btn primary">Entendido</button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const isVisible = ref(false);

onMounted(() => {
  const hasAccepted = localStorage.getItem('reglado_cookies_accepted');
  if (!hasAccepted) {
    setTimeout(() => {
      isVisible.value = true;
    }, 500);
  }
});

const acceptCookies = () => {
  localStorage.setItem('reglado_cookies_accepted', 'true');
  isVisible.value = false;
};
</script>

<style scoped>
.cookie-banner-wrapper {
  position: fixed;
  bottom: 24px;
  right: 24px;
  width: calc(100% - 48px);
  max-width: 380px;
  z-index: 9999;
}

.cookie-banner {
  display: flex;
  flex-direction: column;
  gap: 16px;
  background: #1e1b0f; /* Tono oscuro propio de la estética Energy */
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(242, 197, 61, 0.3); /* Dorado / Amarillo de Energy */
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
  color: #fff;
}

.cookie-content {
  flex: 1;
}

.cookie-text {
  margin: 0;
  font-size: 0.9rem;
  line-height: 1.5;
  color: rgba(255, 255, 255, 0.85);
}

.cookie-text a {
  color: #f2c53d;
  text-decoration: underline;
  text-underline-offset: 2px;
  transition: color 0.2s ease;
  font-weight: 500;
}

.cookie-text a:hover {
  color: #fff;
}

.cookie-actions {
  flex-shrink: 0;
  margin-top: 8px;
}

.btn.primary {
  width: 100%;
  background: #f2c53d;
  color: #111;
  border: none;
  padding: 10px 24px;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn.primary:hover {
  background: #f5d469;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(242, 197, 61, 0.3);
}

/* Transición slide-in desde la derecha */
.slide-in-right-enter-active,
.slide-in-right-leave-active {
  transition: all 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.slide-in-right-enter-from,
.slide-in-right-leave-to {
  opacity: 0;
  transform: translateX(120%);
}

@media (max-width: 480px) {
  .cookie-banner-wrapper {
    bottom: 20px;
    right: 20px;
    width: calc(100% - 40px);
  }
}
</style>
