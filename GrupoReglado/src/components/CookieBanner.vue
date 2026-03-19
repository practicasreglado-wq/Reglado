<template>
  <transition name="slide-up">
    <div v-if="isVisible" class="cookie-banner-wrapper">
      <div class="cookie-banner">
        <div class="cookie-content">
          <p class="cookie-text">
            Utilizamos cookies de sesión (estrictamente funcionales) para garantizar el correcto funcionamiento y la seguridad de nuestra web. Estas cookies están siempre activas. Al continuar navegando, consideramos que aceptas su uso. Puedes leer más en nuestra <router-link to="/politica-cookies">Política de Cookies</router-link>.
          </p>
        </div>
        <div class="cookie-actions">
          <button @click="acceptCookies" class="btn-accept">Entendido</button>
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
  left: 50%;
  transform: translateX(-50%);
  width: calc(100% - 40px);
  max-width: 800px;
  z-index: 9999;
}

.cookie-banner {
  display: flex;
  flex-direction: column;
  gap: 16px;
  background: rgba(26, 36, 50, 0.85);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 12px;
  padding: 20px 24px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
  color: #fff;
}

@media (min-width: 640px) {
  .cookie-banner {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
  }
}

.cookie-content {
  flex: 1;
}

.cookie-text {
  margin: 0;
  font-size: 0.9rem;
  line-height: 1.5;
  color: rgba(255, 255, 255, 0.9);
}

.cookie-text a {
  color: #a3c5ff;
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
}

.btn-accept {
  width: 100%;
  background: #273d5c;
  color: #fff;
  border: 1px solid rgba(255, 255, 255, 0.3);
  padding: 10px 24px;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.btn-accept:hover {
  background: #344f76;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

@media (min-width: 640px) {
  .btn-accept {
    width: auto;
  }
}

/* Transición slide-up que respeta el translateX central */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translate(-50%, 150%);
}
</style>
