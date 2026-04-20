<template>
  <div v-if="open" class="modal-backdrop" @click.self="closeModal">
    <div class="modal-card" role="dialog" aria-modal="true" aria-label="Iniciar sesión">
      <div class="modal-head">
        <h2>Iniciar sesión</h2>
        <button class="icon-btn close-btn" @click="closeModal" aria-label="Cerrar">×</button>
      </div>

      <form class="clean-form" @submit.prevent="submitLogin">
        <label>
          Correo Electrónico
          <input v-model.trim="email" type="email" placeholder="" required />
        </label>

        <label>
          Contraseña
          <PasswordField v-model="password" placeholder="" required />
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
        Reenviar correo de verificación
      </button>

      <p class="helper-text">
        <RouterLink to="/recuperar-contrasena" @click="closeModal">¿Has olvidado tu contraseña?</RouterLink>
      </p>

      <p class="register-text">
        ¿No tienes cuenta?
        <RouterLink to="/registro" @click="closeModal">Regístrate</RouterLink>
      </p>

      <p class="privacy-modal-link">
        Al entrar, aceptas nuestra 
        <RouterLink to="/politica-privacidad" @click="closeModal">Política de Privacidad</RouterLink>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { RouterLink } from "vue-router";
import PasswordField from "./PasswordField.vue";
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
    success.value = "Sesión iniciada";
    emit("success");
  } catch (err) {
    const message = err instanceof Error ? err.message : "No fue posible iniciar sesión";
    error.value = message;
    canResend.value = message === "Debes confirmar tu correo antes de iniciar sesión.";
  } finally {
    loading.value = false;
  }
}

async function resendMail() {
  if (!email.value) {
    error.value = "Indica un correo para reenviar la verificación";
    return;
  }

  loading.value = true;
  error.value = "";
  success.value = "";

  try {
    const response = await auth.resendVerification(email.value);
    success.value = response.message || "Correo de verificación reenviado.";
  } catch (err) {
    error.value = err instanceof Error ? err.message : "No fue posible reenviar el correo";
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.modal-card {
  position: relative;
}

.close-btn {
  position: absolute;
  top: 0.55rem;
  right: 0.75rem;
  border: none;
  background: transparent;
  box-shadow: none;
  padding: 0;
  border-radius: 0;
  font-size: 2rem;
  line-height: 1;
  font-weight: 500;
  color: var(--text);
}

.close-btn:hover {
  opacity: 0.75;
}
</style>
