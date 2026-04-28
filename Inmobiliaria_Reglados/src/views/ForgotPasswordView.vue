<template>
  <main class="auth-page">
    <div class="auth-card">
      <span class="badge">Recuperación</span>
      <h1>Recuperar contraseña</h1>
      <p class="lead">Introduce tu correo y te enviaremos un enlace para restablecer la contraseña.</p>

      <form class="form" @submit.prevent="submitRequest">
        <div class="field">
          <label>Correo electrónico</label>
          <input v-model.trim="email" type="email" required placeholder="tu@email.com" />
        </div>

        <p v-if="error" class="feedback error">{{ error }}</p>
        <p v-if="success" class="feedback success">{{ success }}</p>

        <p v-if="success" class="text-muted helper">
          Haz clic en el enlace del correo para introducir la nueva contraseña y volver a Reglado RealEstate.
        </p>

        <button class="btn-primary" type="submit" :disabled="loading">
          {{ loading ? "Enviando..." : "Enviar enlace" }}
        </button>
      </form>

      <p class="back">
        <router-link to="/">Volver al inicio</router-link>
      </p>
    </div>
  </main>
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
.auth-page {
  padding: 80px 20px;
  background: #f5f7fa;
  min-height: calc(100vh - 160px);
}

.auth-card {
  max-width: 520px;
  margin: 0 auto;
  background: #ffffff;
  border-radius: 16px;
  padding: 40px;
  box-shadow: 0 4px 20px rgba(15, 23, 42, 0.08);
}

.badge {
  display: inline-block;
  padding: 4px 12px;
  background: rgba(36, 56, 107, 0.1);
  color: #24386b;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  margin-bottom: 14px;
}

h1 { margin: 0 0 8px; font-size: 1.75rem; color: #24386b; }
.lead { font-size: 0.9375rem; margin: 0 0 24px; color: #64748b; }

.form { display: flex; flex-direction: column; gap: 14px; }
.field { display: flex; flex-direction: column; gap: 6px; }
.field label { font-size: 0.8125rem; font-weight: 600; color: #1a1f2e; }

.field input {
  padding: 11px 14px;
  border: 1px solid #e0e4ea;
  border-radius: 10px;
  background: #ffffff;
  color: #1a1f2e;
  font-size: 0.9375rem;
  font-family: inherit;
  transition: all 0.18s ease;
}

.field input:focus {
  outline: none;
  border-color: #24386b;
  box-shadow: 0 0 0 3px rgba(36, 56, 107, 0.12);
}

.feedback {
  margin: 0;
  padding: 10px 12px;
  border-radius: 10px;
  font-size: 0.8125rem;
  border: 1px solid transparent;
}

.feedback.error { color: #b91c1c; background: #fee2e2; border-color: #fecaca; }
.feedback.success { color: #15803d; background: #dcfce7; border-color: #bbf7d0; }

.helper { font-size: 0.8125rem; margin: 0; color: #64748b; }

.btn-primary {
  width: 100%;
  padding: 12px 20px;
  background: #24386b;
  color: #ffffff;
  border: none;
  border-radius: 10px;
  font-weight: 700;
  font-size: 0.9375rem;
  cursor: pointer;
  transition: all 0.18s ease;
  margin-top: 4px;
  font-family: inherit;
}

.btn-primary:hover:not(:disabled) {
  background: #1a2b54;
  transform: translateY(-1px);
  box-shadow: 0 8px 20px rgba(36, 56, 107, 0.3);
}
.btn-primary:disabled { opacity: 0.55; cursor: not-allowed; }

.back { margin-top: 20px; text-align: center; font-size: 0.875rem; }
.back a { color: #24386b; text-decoration: none; font-weight: 600; }
.back a:hover { text-decoration: underline; }
</style>
