<template>
  <main class="auth-page">
    <div class="container">
      <div class="auth-card center">
        <span class="badge">Verificación</span>
        <h1 class="h1">Verificación de correo</h1>

        <p v-if="loading" class="lead">Validando acceso…</p>

        <template v-else-if="error">
          <p class="feedback error">{{ error }}</p>
          <router-link class="btn primary" to="/">Ir al inicio</router-link>
        </template>

        <template v-else>
          <p class="feedback success">Se ha verificado tu correo correctamente.</p>
          <p class="lead">Serás redirigido en {{ countdown }} segundos…</p>
          <router-link class="btn primary" to="/">Ir ahora</router-link>
        </template>
      </div>
    </div>
  </main>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { auth } from "@/services/auth.js";
import { redirectToStore } from "@/services/ssoClient.js";

const route = useRoute();
const router = useRouter();
const loading = ref(true);
const error = ref("");
const countdown = ref(5);

let countdownInterval = null;
let redirectTimeout = null;

function startAutoRedirect() {
  countdownInterval = setInterval(() => {
    if (countdown.value > 1) countdown.value -= 1;
  }, 1000);

  redirectTimeout = setTimeout(() => {
    // Propaga la sesión recién verificada al hub antes de enviar al home.
    redirectToStore(auth.state.token, window.location.origin + "/");
  }, 5000);
}

onMounted(async () => {
  const token = typeof route.query.token === "string" ? route.query.token : "";

  if (!token) {
    loading.value = false;
    error.value = "No se encontró el token de verificación.";
    return;
  }

  try {
    auth.setSession(token, null);
    await auth.initialize();

    if (!auth.state.user) {
      throw new Error("No se pudo iniciar la sesión con el token recibido.");
    }

    startAutoRedirect();
  } catch (err) {
    auth.clearSession();
    error.value = err instanceof Error ? err.message : "No se pudo completar la verificación";
  } finally {
    loading.value = false;
  }
});

onUnmounted(() => {
  if (countdownInterval) clearInterval(countdownInterval);
  if (redirectTimeout) clearTimeout(redirectTimeout);
});
</script>

<style scoped>
.auth-page {
  padding: 80px 0;
  background: var(--bg-soft);
  min-height: calc(100vh - 160px);
}

.auth-card {
  max-width: 520px;
  margin: 0 auto;
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 40px;
  box-shadow: var(--shadow);
}

.auth-card.center { text-align: center; }

.badge {
  display: inline-block;
  padding: 4px 12px;
  background: var(--steel-light);
  color: var(--steel-dark);
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  margin-bottom: 14px;
}

.h1 { font-size: 1.625rem; margin: 0 0 12px; }
.lead { margin: 12px 0; font-size: 0.9375rem; }

.feedback {
  margin: 14px 0;
  padding: 12px 14px;
  border-radius: var(--radius-sm);
  font-size: 0.875rem;
  border: 1px solid transparent;
}

.feedback.error { color: #b91c1c; background: #fee2e2; border-color: #fecaca; }
.feedback.success { color: #15803d; background: #dcfce7; border-color: #bbf7d0; }

.btn.primary { margin-top: 8px; }
</style>
