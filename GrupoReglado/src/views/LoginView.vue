<template>
  <section class="form-page">
    <div class="form-card">
      <h1>Iniciar sesion</h1>
      <p>Accede con tu cuenta de ApiLoging.</p>

      <form class="clean-form" @submit.prevent="submitLogin">
        <label>
          Email
          <input v-model.trim="email" type="email" placeholder="usuario@tuempresa.com" required />
        </label>

        <label>
          Contrasena
          <input v-model="password" type="password" placeholder="********" required />
        </label>

        <p v-if="error" class="feedback error">{{ error }}</p>
        <p v-if="success" class="feedback success">{{ success }}</p>

        <button class="btn-primary" type="submit" :disabled="loading">
          {{ loading ? "Entrando..." : "Entrar" }}
        </button>
      </form>

      <button
        v-if="canResend"
        class="btn-link"
        type="button"
        :disabled="loading"
        @click="resendMail"
      >
        Reenviar correo de verificacion
      </button>

      <p class="register-text">
        No tienes cuenta?
        <RouterLink :to="registerUrl">Registrate</RouterLink>
      </p>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { RouterLink, useRoute } from "vue-router";
import { auth } from "../services/auth";

const route = useRoute();

const email = ref("");
const password = ref("");
const error = ref("");
const success = ref("");
const canResend = ref(false);
const loading = ref(false);

const returnTo = computed(() => {
  return typeof route.query.returnTo === "string" ? route.query.returnTo.trim() : "";
});

const registerUrl = computed(() => {
  return returnTo.value ? `/registro?returnTo=${encodeURIComponent(returnTo.value)}` : "/registro";
});

function appendToken(url, token) {
  const separator = url.includes("?") ? "&" : "?";
  return `${url}${separator}token=${encodeURIComponent(token)}`;
}

function redirectIfAlreadyAuthenticated() {
  if (!returnTo.value || !auth.state.token || !auth.state.user) {
    return false;
  }

  window.location.href = appendToken(returnTo.value, auth.state.token);
  return true;
}

async function submitLogin() {
  error.value = "";
  success.value = "";
  canResend.value = false;
  loading.value = true;

  try {
    const payload = await auth.login(email.value, password.value);
    success.value = "Sesion iniciada";

    if (returnTo.value && payload?.token) {
      window.location.href = appendToken(returnTo.value, payload.token);
      return;
    }
  } catch (err) {
    const message = err instanceof Error ? err.message : "No fue posible iniciar sesion";
    error.value = message;
    canResend.value = message === "email not verified";
  } finally {
    loading.value = false;
  }
}

async function resendMail() {
  if (!email.value) {
    error.value = "Indica un email para reenviar la verificacion";
    return;
  }

  loading.value = true;
  error.value = "";
  success.value = "";

  try {
    const response = await auth.resendVerification(email.value);
    success.value = response.message || "Correo de verificacion reenviado";
  } catch (err) {
    error.value = err instanceof Error ? err.message : "No fue posible reenviar el correo";
  } finally {
    loading.value = false;
  }
}

onMounted(async () => {
  if (!returnTo.value) {
    return;
  }

  if (!auth.state.user && auth.state.token) {
    await auth.initialize();
  }

  redirectIfAlreadyAuthenticated();
});
</script>
