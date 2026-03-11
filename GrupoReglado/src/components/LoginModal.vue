<template>
  <div v-if="open" class="modal-backdrop" @click.self="closeModal">
    <div class="modal-card" role="dialog" aria-modal="true" aria-label="Iniciar sesion">
      <div class="modal-head">
        <h2>Iniciar sesion</h2>
        <button class="icon-btn" @click="closeModal" aria-label="Cerrar">x</button>
      </div>

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

      <p class="helper-text">
        <RouterLink to="/recuperar-contrasena" @click="closeModal">Has olvidado tu contrasena?</RouterLink>
      </p>

      <p class="register-text">
        No tienes cuenta?
        <RouterLink to="/registro" @click="closeModal">Registrate</RouterLink>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { RouterLink } from "vue-router";
import { auth } from "../services/auth";

defineProps({
  open: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["close", "success"]);

const email = ref("");
const password = ref("");
const error = ref("");
const success = ref("");
const canResend = ref(false);
const loading = ref(false);

function closeModal() {
  emit("close");
}

async function submitLogin() {
  error.value = "";
  success.value = "";
  canResend.value = false;
  loading.value = true;

  try {
    await auth.login(email.value, password.value);
    success.value = "Sesion iniciada";
    emit("success");
  } catch (err) {
    const message = err instanceof Error ? err.message : "No fue posible iniciar sesion";
    error.value = message;
    canResend.value = message === "Debes confirmar tu correo antes de iniciar sesion.";
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
    success.value = response.message || "Correo de verificacion reenviado.";
  } catch (err) {
    error.value = err instanceof Error ? err.message : "No fue posible reenviar el correo";
  } finally {
    loading.value = false;
  }
}
</script>
