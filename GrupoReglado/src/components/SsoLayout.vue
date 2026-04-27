<template>
  <div class="sso-layout" :style="themeVars">
    <div class="sso-card">
      <div v-if="!error" class="spinner" aria-hidden="true"></div>
      <p class="sso-text">{{ message }}</p>
      <p v-if="error" class="sso-error">{{ error }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { useRoute } from "vue-router";
import { getThemeForReturn } from "../services/ssoHub";

defineProps({
  message: {
    type: String,
    default: "Sincronizando sesión…",
  },
  error: {
    type: String,
    default: "",
  },
});

const route = useRoute();

const theme = computed(() => {
  const returnUrl = typeof route.query.return === "string" ? route.query.return : "";
  return getThemeForReturn(returnUrl);
});

const themeVars = computed(() => ({
  "--sso-bg": theme.value.bg,
  "--sso-surface": theme.value.surface,
  "--sso-text": theme.value.text,
  "--sso-text-muted": theme.value.textMuted,
  "--sso-accent": theme.value.accent,
  "--sso-accent-soft": theme.value.accentSoft,
  "--sso-border": theme.value.border,
}));
</script>

<style scoped>
.sso-layout {
  position: fixed;
  inset: 0;
  background: var(--sso-bg);
  color: var(--sso-text);
  display: grid;
  place-items: center;
  z-index: 9999; /* por encima del header (z-40) y cualquier banner */
}

.sso-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
  padding: 40px 48px;
  background: var(--sso-surface);
  border: 1px solid var(--sso-border);
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
  min-width: 280px;
}

.spinner {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 3px solid var(--sso-accent-soft);
  border-top-color: var(--sso-accent);
  animation: sso-spin 0.8s linear infinite;
}

@keyframes sso-spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.sso-text {
  margin: 0;
  font-size: 0.95rem;
  color: var(--sso-text-muted);
  font-family: "Outfit", "Inter", "Trebuchet MS", sans-serif;
  letter-spacing: 0.01em;
}

.sso-error {
  margin: 0;
  font-size: 0.875rem;
  color: #f87171;
  max-width: 280px;
  text-align: center;
}
</style>
