<template>
  <main class="auth-page">
    <div class="container">
      <div class="auth-card">
        <span class="badge">Recuperación</span>
        <h1 class="h1">Recuperar contraseña</h1>
        <p class="lead">Introduce tu correo y te enviaremos un enlace para restablecer la contraseña.</p>

        <form class="form" @submit.prevent="submitRequest">
          <div class="field">
            <label>Correo electrónico</label>
            <input v-model.trim="email" type="email" required placeholder="tu@email.com" />
          </div>

          <p v-if="error" class="feedback error">{{ error }}</p>
          <p v-if="success" class="feedback success">{{ success }}</p>

          <p v-if="success" class="text-muted helper">
            Haz clic en el enlace del correo para introducir la nueva contraseña y volver a Ingeniería.
          </p>

          <button class="btn primary" type="submit" :disabled="loading">
            {{ loading ? "Enviando..." : "Enviar enlace" }}
          </button>
        </form>

        <p class="back">
          <router-link to="/">Volver al inicio</router-link>
        </p>
      </div>
    </div>
  </main>
</template>

<script setup>
import { ref } from "vue";
import { auth } from "@/services/auth.js";

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

.h1 { font-size: 1.75rem; margin: 0 0 8px; }
.lead { font-size: 0.9375rem; margin: 0 0 24px; }

.form {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.field label { font-size: 0.8125rem; font-weight: 600; color: var(--text); }

.field input {
  padding: 11px 14px;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  background: var(--bg);
  color: var(--text);
  font-size: 0.9375rem;
  font-family: inherit;
  transition: all var(--transition);
}

.field input:focus {
  outline: none;
  border-color: var(--steel);
  box-shadow: 0 0 0 3px rgba(74, 158, 255, 0.12);
}

.feedback {
  margin: 0;
  padding: 10px 12px;
  border-radius: var(--radius-sm);
  font-size: 0.8125rem;
  border: 1px solid transparent;
}

.feedback.error { color: #b91c1c; background: #fee2e2; border-color: #fecaca; }
.feedback.success { color: #15803d; background: #dcfce7; border-color: #bbf7d0; }

.helper { font-size: 0.8125rem; margin: 0; }

.btn.primary { width: 100%; justify-content: center; margin-top: 4px; }
.btn.primary:disabled { opacity: 0.55; cursor: not-allowed; transform: none; box-shadow: none; }

.back {
  margin-top: 20px;
  text-align: center;
  font-size: 0.875rem;
}

.back a { color: var(--steel); text-decoration: none; font-weight: 600; }
.back a:hover { text-decoration: underline; }
</style>
