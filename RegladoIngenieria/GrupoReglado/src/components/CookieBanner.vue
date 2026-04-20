<template>
  <transition name="slide-up">
    <div v-if="isVisible" class="cookie-banner-wrapper">
      <div class="cookie-banner" :class="{ 'with-settings': showSettings }">
        <div class="cookie-main">
          <div class="cookie-content">
            <p class="cookie-text">
              Utilizamos cookies para mejorar tu experiencia. Las cookies de sesión son necesarias para el funcionamiento.
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
                <span>Esenciales para el inicio de sesión y seguridad.</span>
              </div>
            </label>
            <label class="setting-item">
              <input type="checkbox" v-model="consent.preferences" />
              <div class="setting-info">
                <strong>Preferencias</strong>
                <span>Recuerdan tus ajustes de tema e idioma.</span>
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
import { auth } from '../services/auth';

const CONSENT_KEY = 'reglado_consent_settings';
const OLD_KEY = 'reglado_cookies_accepted';

const isVisible = ref(false);
const showSettings = ref(false);
const consent = reactive({
  necessary: true,
  preferences: true,
  analytics: false
});

onMounted(() => {
  // 1. Intentar cargar consentimiento actual
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

  // 2. Migración: si aceptó en el sistema viejo, crear el nuevo objeto y no mostrar
  const hasOldAcceptance = localStorage.getItem(OLD_KEY);
  if (hasOldAcceptance) {
    saveConsent(true);
    return;
  }

  // 3. Mostrar banner si no hay nada
  setTimeout(() => {
    isVisible.value = true;
  }, 800);
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
  
  // Guardar en Cookie (para el ecosistema) y LocalStorage (redundancia)
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
  max-width: 800px;
  z-index: 9999;
}

.cookie-banner {
  display: flex;
  flex-direction: column;
  background: rgba(26, 36, 50, 0.95);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.18);
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
  color: #fff;
  transition: all 0.3s ease;
}

.cookie-main {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

@media (min-width: 640px) {
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
  font-size: 0.92rem;
  line-height: 1.5;
  color: rgba(255, 255, 255, 0.95);
}

.cookie-text a {
  color: #a3c5ff;
  text-decoration: underline;
  text-underline-offset: 3px;
  transition: color 0.2s ease;
  font-weight: 600;
}

.cookie-actions {
  display: flex;
  gap: 10px;
  flex-shrink: 0;
}

.btn-accept, .btn-settings {
  padding: 10px 20px;
  border-radius: 10px;
  font-size: 0.92rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-accept {
  background: #a3c5ff;
  color: #1a2b43;
  border: none;
}

.btn-accept:hover {
  background: #fff;
  transform: translateY(-2px);
}

.btn-settings {
  background: transparent;
  color: #fff;
  border: 1px solid rgba(255, 255, 255, 0.3);
}

.btn-settings:hover {
  background: rgba(255, 255, 255, 0.1);
}

.cookie-settings {
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  display: grid;
  gap: 20px;
}

.settings-grid {
  display: grid;
  gap: 14px;
}

.setting-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  cursor: pointer;
  padding: 12px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.05);
  transition: background 0.2s ease;
}

.setting-item:hover:not(.disabled) {
  background: rgba(255, 255, 255, 0.1);
}

.setting-item.disabled {
  cursor: not-allowed;
  opacity: 0.8;
}

.setting-item input[type="checkbox"] {
  width: 18px;
  height: 18px;
  accent-color: #a3c5ff;
  margin-top: 2px;
}

.setting-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.setting-info strong {
  font-size: 0.95rem;
  color: #fff;
}

.setting-info span {
  font-size: 0.82rem;
  color: rgba(255, 255, 255, 0.6);
}

.settings-actions {
  display: flex;
  justify-content: flex-end;
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
