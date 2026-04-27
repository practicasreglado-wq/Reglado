<template>
  <section class="auth-page">
    <div class="auth-card narrow">
      <div class="badge">Recuperación</div>
      <h1>Recuperar contraseña</h1>
      <p class="lead">Introduce tu correo y te enviaremos un enlace para restablecer la contraseña.</p>

      <form class="form" @submit.prevent="submitRequest">
        <div class="field">
          <label>Correo electrónico</label>
          <input v-model.trim="email" type="email" required placeholder="tu@email.com" />
        </div>

        <p v-if="error" class="feedback error">{{ error }}</p>
        <p v-if="success" class="feedback success">{{ success }}</p>

        <p v-if="success" class="helper">
          Haz clic en el enlace del correo para introducir la nueva contraseña y volver a Maps.
        </p>

        <button class="btn-primary" type="submit" :disabled="loading">
          {{ loading ? "Enviando..." : "Enviar enlace" }}
        </button>
      </form>

      <p class="helper-text">
        <router-link to="/">Volver al inicio</router-link>
      </p>
    </div>
  </section>
</template>

<script>
import { auth } from "../services/auth";

export default {
  name: "ForgotPasswordView",
  data() {
    return {
      email: "",
      error: "",
      success: "",
      loading: false,
    };
  },
  methods: {
    async submitRequest() {
      this.loading = true;
      this.error = "";
      this.success = "";

      try {
        const response = await auth.requestPasswordReset(this.email);
        this.success = response.message || "Si la cuenta existe, te hemos enviado un correo.";
      } catch (err) {
        this.error = err instanceof Error ? err.message : "No fue posible solicitar la recuperación.";
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 120px 20px 60px;
  background: radial-gradient(ellipse at top, rgba(0, 229, 255, 0.08) 0%, transparent 60%), #02060f;
  color: #e9eef6;
}

.auth-card {
  width: 100%;
  max-width: 520px;
  background: rgba(10, 15, 25, 0.92);
  border: 1px solid rgba(0, 229, 255, 0.28);
  border-radius: 20px;
  padding: 36px 40px;
  box-shadow: 0 28px 60px rgba(0, 0, 0, 0.5), 0 0 22px rgba(0, 229, 255, 0.08);
}

.badge {
  display: inline-block;
  padding: 4px 12px;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #00E5FF;
  background: rgba(0, 229, 255, 0.1);
  border: 1px solid rgba(0, 229, 255, 0.3);
  border-radius: 999px;
  margin-bottom: 16px;
}

h1 {
  margin: 0 0 8px;
  font-size: 28px;
  color: #ffffff;
}

.lead {
  margin: 0 0 24px;
  color: rgba(233, 238, 246, 0.7);
  font-size: 15px;
}

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

.field label {
  font-size: 13px;
  font-weight: 600;
  color: rgba(233, 238, 246, 0.85);
}

.field input {
  padding: 11px 14px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.04);
  color: #e9eef6;
  font-size: 14px;
  font-family: inherit;
  transition: all 0.2s;
}

.field input:focus {
  outline: none;
  border-color: rgba(0, 229, 255, 0.55);
  background: rgba(255, 255, 255, 0.06);
  box-shadow: 0 0 0 3px rgba(0, 229, 255, 0.12);
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
  background: rgba(183, 28, 28, 0.18);
  border-color: rgba(255, 183, 183, 0.25);
}

.feedback.success {
  color: #b7ffc7;
  background: rgba(28, 183, 87, 0.15);
  border-color: rgba(183, 255, 199, 0.25);
}

.helper {
  margin: 0;
  font-size: 12px;
  color: rgba(233, 238, 246, 0.6);
}

.btn-primary {
  padding: 12px 20px;
  background: linear-gradient(135deg, #00E5FF 0%, #0288D1 100%);
  color: #001a2e;
  border: none;
  border-radius: 10px;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
  margin-top: 6px;
  font-family: inherit;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 229, 255, 0.35);
}

.btn-primary:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.helper-text {
  margin-top: 20px;
  text-align: center;
  font-size: 13px;
  color: rgba(233, 238, 246, 0.7);
}

.helper-text a {
  color: #00E5FF;
  text-decoration: none;
  font-weight: 600;
}

.helper-text a:hover { text-decoration: underline; }

@media (max-width: 640px) {
  .auth-card { padding: 28px 22px; }
}
</style>
