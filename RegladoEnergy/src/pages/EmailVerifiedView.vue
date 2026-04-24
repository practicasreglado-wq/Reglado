<template>
  <section class="section">
    <div class="container">
      <div class="card glow auth-card" v-glow>
        <div class="badge">Verificación</div>
        <h1 class="h1">Verificación de correo</h1>

        <p v-if="loading" class="p">Validando acceso…</p>

        <template v-else-if="error">
          <p class="feedback error">{{ error }}</p>
          <router-link class="btn primary glow" to="/" v-glow>Ir al inicio</router-link>
        </template>

        <template v-else>
          <p class="feedback success">Se ha verificado tu correo correctamente.</p>
          <p class="p">Serás redirigido en {{ countdown }} segundos…</p>
          <router-link class="btn primary glow" to="/" v-glow>Ir ahora</router-link>
        </template>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { auth } from "../services/auth";

const route = useRoute();
const router = useRouter();
const loading = ref(true);
const error = ref("");
const countdown = ref(5);

let countdownInterval = null;
let redirectTimeout = null;

function startAutoRedirect() {
  countdownInterval = setInterval(() => {
    if (countdown.value > 1) {
      countdown.value -= 1;
    }
  }, 1000);

  redirectTimeout = setTimeout(() => {
    router.replace("/");
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
.auth-card {
  max-width: 520px;
  margin: 0 auto;
  text-align: center;
}

.auth-card .badge {
  margin: 0 auto 12px;
}

.feedback {
  margin: 14px 0;
  padding: 10px 12px;
  border-radius: 10px;
  font-size: 14px;
  border: 1px solid transparent;
}

.feedback.error {
  color: #ffb7b7;
  background: rgba(183, 28, 28, 0.15);
  border-color: rgba(255, 183, 183, 0.25);
}

.feedback.success {
  color: #b7ffc7;
  background: rgba(28, 183, 87, 0.12);
  border-color: rgba(183, 255, 199, 0.25);
}

.btn {
  margin-top: 12px;
}
</style>
