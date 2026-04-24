<template>
  <section class="section">
    <div class="container">
      <div class="card glow auth-card" v-glow>
        <div class="badge">Recuperación</div>
        <h1 class="h1">Recuperar contraseña</h1>
        <p class="p">Introduce tu correo y te enviaremos un enlace para restablecer la contraseña.</p>

        <form class="form" @submit.prevent="submitRequest">
          <div class="field">
            <label>Correo electrónico</label>
            <input v-model.trim="email" type="email" required placeholder="tu@email.com" />
          </div>

          <p v-if="error" class="feedback error">{{ error }}</p>
          <p v-if="success" class="feedback success">{{ success }}</p>

          <p v-if="success" class="p helper">
            Haz clic en el enlace del correo para introducir la nueva contraseña y volver a Energy.
          </p>

          <button class="btn primary glow" type="submit" :disabled="loading" v-glow>
            {{ loading ? "Enviando..." : "Enviar enlace" }}
          </button>
        </form>

        <p class="helper-text">
          <router-link to="/">Volver al inicio</router-link>
        </p>
      </div>
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

<style scoped>
.auth-card {
  max-width: 520px;
  margin: 0 auto;
}

.form {
  display: grid;
  gap: 14px;
  margin-top: 18px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.field label {
  font-size: 13px;
  font-weight: 600;
  color: rgba(233, 238, 246, 0.85);
}

.field input {
  padding: 12px 14px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.04);
  color: #e9eef6;
  font-size: 14px;
  font-family: inherit;
  transition: border-color 0.18s ease, background 0.18s ease;
}

.field input:focus {
  outline: none;
  border-color: rgba(242, 197, 61, 0.5);
  background: rgba(255, 255, 255, 0.07);
}

.feedback {
  margin: 0;
  padding: 10px 12px;
  border-radius: 10px;
  font-size: 13px;
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

.helper {
  font-size: 12px;
  color: rgba(233, 238, 246, 0.6);
}

.helper-text {
  margin-top: 18px;
  text-align: center;
  font-size: 13px;
  color: rgba(233, 238, 246, 0.7);
}

.helper-text a {
  color: #f2c53d;
  text-decoration: none;
  font-weight: 600;
}

.helper-text a:hover {
  text-decoration: underline;
}
</style>
