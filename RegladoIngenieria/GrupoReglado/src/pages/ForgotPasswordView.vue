<template>
  <section class="form-page">
    <div class="form-card">
      <h1>Recuperar contraseña</h1>
      <p>Introduce tu correo y te enviaremos un enlace para restablecer la contraseña.</p>

      <form class="clean-form" @submit.prevent="submitRequest">
        <label>
          Email
          <input v-model.trim="email" type="email" placeholder="usuario@tuempresa.com" required />
        </label>

        <p v-if="error" class="feedback error">{{ error }}</p>
        <p v-if="success" class="feedback success">{{ success }}</p>

        <button class="btn-primary" type="submit" :disabled="loading">
          {{ loading ? "Enviando..." : "Enviar enlace" }}
        </button>
      </form>
    </div>
  </section>
</template>

<script setup>
import { ref } from "vue";
import { auth } from "../services/auth";

const email = ref("");
const error = ref("");
const success = ref("");
const loading = ref(false);

async function submitRequest() {
  loading.value = true;
  error.value = "";
  success.value = "";

  try {
    const response = await auth.requestPasswordReset(email.value);
    success.value = response.message || "Si la cuenta existe, te hemos enviado un correo.";
  } catch (err) {
    error.value = err instanceof Error ? err.message : "No fue posible solicitar la recuperación.";
  } finally {
    loading.value = false;
  }
}
</script>
