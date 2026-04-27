<template>
  <main class="auth-page">
    <div class="container">
      <div class="auth-card">
        <span class="badge">Recuperación</span>
        <h1 class="h1">Restablecer contraseña</h1>
        <p class="lead">Introduce tu nueva contraseña.</p>

        <form class="form" @submit.prevent="submitReset">
          <div class="field">
            <label>Nueva contraseña</label>
            <input v-model="newPassword" type="password" required minlength="8" />
            <span class="hint">Mínimo 8 caracteres, una mayúscula y un número.</span>
          </div>

          <div class="field">
            <label>Confirmar contraseña</label>
            <input v-model="newPasswordConfirmation" type="password" required minlength="8" />
          </div>

          <p v-if="error" class="feedback error">{{ error }}</p>
          <p v-if="success" class="feedback success">{{ success }}</p>

          <button class="btn primary" type="submit" :disabled="loading">
            {{ loading ? "Guardando..." : "Guardar nueva contraseña" }}
          </button>
        </form>
      </div>
    </div>
  </main>
</template>

<script setup>
import { ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { auth } from "@/services/auth.js";

const route = useRoute();
const router = useRouter();

const newPassword = ref("");
const newPasswordConfirmation = ref("");
const error = ref("");
const success = ref("");
const loading = ref(false);

async function submitReset() {
  const token = typeof route.query.token === "string" ? route.query.token.trim() : "";

  if (!token) {
    error.value = "El enlace de recuperación no es válido.";
    return;
  }

  loading.value = true;
  error.value = "";
  success.value = "";

  try {
    const response = await auth.resetPassword(token, newPassword.value, newPasswordConfirmation.value);
    success.value = response.message || "Contraseña actualizada.";

    setTimeout(() => {
      router.push("/");
    }, 1500);
  } catch (err) {
    error.value = err instanceof Error ? err.message : "No fue posible restablecer la contraseña.";
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
.lead { margin: 0 0 24px; font-size: 0.9375rem; }

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

.hint { font-size: 0.75rem; color: var(--text-muted); }

.feedback {
  margin: 0;
  padding: 10px 12px;
  border-radius: var(--radius-sm);
  font-size: 0.8125rem;
  border: 1px solid transparent;
}

.feedback.error { color: #b91c1c; background: #fee2e2; border-color: #fecaca; }
.feedback.success { color: #15803d; background: #dcfce7; border-color: #bbf7d0; }

.btn.primary { width: 100%; justify-content: center; margin-top: 4px; }
.btn.primary:disabled { opacity: 0.55; cursor: not-allowed; transform: none; box-shadow: none; }
</style>
