<template>
  <section class="form-page">
    <div class="form-card">
      <h1>Verificacion de correo</h1>

      <p v-if="loading">Validando acceso...</p>

      <template v-else-if="error">
        <p class="feedback error">{{ error }}</p>
        <RouterLink class="btn-primary inline-btn" to="/">Ir al portal</RouterLink>
      </template>

      <template v-else>
        <p class="feedback success">Se ha verificado tu email correctamente.</p>
        <p>Seras redirigido al portal en {{ countdown }} segundos...</p>
        <RouterLink class="btn-primary inline-btn" to="/">Ir ahora</RouterLink>
      </template>
    </div>
  </section>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";
import { auth } from "../services/auth";

const route = useRoute();
const router = useRouter();
const loading = ref(true);
const error = ref("");
const countdown = ref(5);
const destination = ref("/");
const verifiedToken = ref("");

let countdownInterval = null;
let redirectTimeout = null;

function startAutoRedirect() {
  countdownInterval = setInterval(() => {
    if (countdown.value > 1) {
      countdown.value -= 1;
    }
  }, 1000);

  redirectTimeout = setTimeout(() => {
    if (destination.value.startsWith("http://") || destination.value.startsWith("https://")) {
      const separator = destination.value.includes("?") ? "&" : "?";
      const target = verifiedToken.value
        ? `${destination.value}${separator}token=${encodeURIComponent(verifiedToken.value)}`
        : destination.value;
      window.location.href = target;
      return;
    }
    router.replace(destination.value);
  }, 5000);
}

onMounted(async () => {
  const token = typeof route.query.token === "string" ? route.query.token : "";
  verifiedToken.value = token;

  if (!token) {
    loading.value = false;
    error.value = "No se encontro el token de verificacion.";
    return;
  }

  try {
    auth.setSession(token, null);
    await auth.initialize();

    if (!auth.state.user) {
      throw new Error("No se pudo iniciar la sesion con el token recibido.");
    }

    const queryReturnTo = typeof route.query.returnTo === "string" ? route.query.returnTo.trim() : "";
    const storedReturnTo = sessionStorage.getItem("auth_return_to") || "";
    destination.value = queryReturnTo || storedReturnTo || "/";
    if (storedReturnTo) {
      sessionStorage.removeItem("auth_return_to");
    }

    startAutoRedirect();
  } catch (err) {
    auth.clearSession();
    error.value = err instanceof Error ? err.message : "No se pudo completar la verificacion";
  } finally {
    loading.value = false;
  }
});

onUnmounted(() => {
  if (countdownInterval) {
    clearInterval(countdownInterval);
  }
  if (redirectTimeout) {
    clearTimeout(redirectTimeout);
  }
});
</script>
