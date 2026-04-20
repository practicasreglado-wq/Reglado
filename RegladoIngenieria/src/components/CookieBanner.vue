<template>
  <transition name="slide-up">
    <div v-if="isVisible" class="cookie-banner-wrapper">
      <div class="cookie-banner" :class="{ 'with-settings': showSettings }">
        <div class="cookie-main">
          <div class="cookie-content">
            <p class="cookie-text">
              Utilizamos cookies para mejorar tu experiencia en Reglado Ingeniería. 
              Las cookies técnicas son necesarias para el funcionamiento del sitio.
              Puedes personalizar tus preferencias o aceptar todas. 
              <router-link to="/politica-cookies">Más información</router-link>.
            </p>
          </div>
          
          <div v-if="!showSettings" class="cookie-actions">
            <button @click="showSettings = true" class="btn-settings">Personalizar</button>
            <button @click="acceptAll" class="btn-accept">Aceptar todas</button>
          </div>
        </div>

        <div v-if="showSettings" class="cookie-settings">
          <div class="settings-grid">
            <label class="setting-item disabled">
              <input type="checkbox" checked disabled />
              <div class="setting-info">
                <strong>Necesarias</strong>
                <span>Esenciales para el funcionamiento básico y seguridad.</span>
              </div>
            </label>
            <label class="setting-item">
              <input type="checkbox" v-model="consent.preferences" />
              <div class="setting-info">
                <strong>Preferencias</strong>
                <span>Recuerdan tus ajustes de navegación.</span>
              </div>
            </label>
            <label class="setting-item">
              <input type="checkbox" v-model="consent.analytics" />
              <div class="setting-info">
                <strong>Estadísticas</strong>
                <span>Nos ayudan a mejorar analizando el uso de la web.</span>
              </div>
            </label>
          </div>
          <div class="settings-actions">
            <button @click="saveSelected" class="btn-primary">Guardar selección</button>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import { auth } from '@/services/auth.js';

const CONSENT_KEY = 'reglado_consent_settings';

const isVisible = ref(false);
const showSettings = ref(false);
const consent = reactive({
  necessary: true,
  preferences: true,
  analytics: false
});

onMounted(() => {
  // Intentar cargar consentimiento actual
  const saved = auth.getCookie(CONSENT_KEY) || localStorage.getItem(CONSENT_KEY);
  
  if (saved) {
    try {
      const parsed = JSON.parse(saved);
      Object.assign(consent, parsed.categories);
      return; // Ya tiene consentimiento, no mostrar banner
    } catch (e) {
      console.error("Error parsing consent:", e);
    }
  }

  // Mostrar banner si no hay nada
  setTimeout(() => {
    isVisible.value = true;
  }, 1000);
});

const saveConsent = (all = false) => {
  const finalCategories = all ? {
    necessary: true,
    preferences: true,
    analytics: true
  } : { ...consent };

  const payload = {
    accepted: true,
    timestamp: new Date().toISOString(),
    categories: finalCategories
  };

  const json = JSON.stringify(payload);
  
  // Guardar en Cookie y LocalStorage
  auth.setCookie(CONSENT_KEY, json, 60 * 60 * 24 * 365); // 1 año
  localStorage.setItem(CONSENT_KEY, json);
  
  isVisible.value = false;
};

const acceptAll = () => saveConsent(true);
const saveSelected = () => saveConsent(false);
</script>

<style scoped>
.cookie-banner-wrapper {
  position: fixed;
  bottom: 24px;
  left: 50%;
  transform: translateX(-50%);
  width: calc(100% - 40px);
  max-width: 850px;
  z-index: 9999;
}

.cookie-banner {
  display: flex;
  flex-direction: column;
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: 20px;
  padding: 24px 32px;
  box-shadow: var(--shadow-lg);
  color: var(--text);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.cookie-main {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

@media (min-width: 768px) {
  .cookie-main {
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
  font-size: 0.95rem;
  line-height: 1.6;
  color: var(--text);
}

.cookie-text a {
  color: var(--steel);
  text-decoration: none;
  font-weight: 600;
  border-bottom: 1px solid transparent;
  transition: all 0.2s ease;
}

.cookie-text a:hover {
  border-bottom-color: var(--steel);
}

.cookie-actions {
  display: flex;
  gap: 12px;
  flex-shrink: 0;
}

.btn-accept, .btn-settings, .btn-primary {
  padding: 12px 24px;
  border-radius: var(--radius-sm);
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-accept {
  background: var(--steel);
  color: #fff;
  border: none;
  box-shadow: 0 4px 12px rgba(74, 158, 255, 0.2);
}

.btn-accept:hover {
  background: var(--steel-dark);
  transform: translateY(-2px);
  box-shadow: 0 6px 15px rgba(74, 158, 255, 0.3);
}

.btn-settings {
  background: var(--bg-soft);
  color: var(--text);
  border: 1px solid var(--border);
}

.btn-settings:hover {
  background: var(--border);
  border-color: var(--text-muted);
}

.cookie-settings {
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid var(--border);
  display: grid;
  gap: 20px;
}

.settings-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.setting-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  cursor: pointer;
  padding: 16px;
  border-radius: 16px;
  background: var(--bg-soft);
  border: 1px solid transparent;
  transition: all 0.2s ease;
}

.setting-item:hover:not(.disabled) {
  background: var(--bg);
  border-color: var(--border);
  box-shadow: var(--shadow);
}

.setting-item.disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.setting-item input[type="checkbox"] {
  width: 20px;
  height: 20px;
  accent-color: var(--steel);
  margin-top: 2px;
}

.setting-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.setting-info strong {
  font-size: 0.95rem;
  color: var(--text);
}

.setting-info span {
  font-size: 0.8rem;
  color: var(--text-muted);
  line-height: 1.4;
}

.settings-actions {
  display: flex;
  justify-content: flex-end;
}

.btn-primary {
  background: var(--steel);
  color: #fff;
  border: none;
}

.btn-primary:hover {
  background: var(--steel-dark);
  transform: translateY(-2px);
}

/* Transición slide-up */
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.6s cubic-bezier(0.23, 1, 0.32, 1);
}

.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translate(-50%, 100px);
}
</style>
