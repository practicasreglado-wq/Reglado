<template>
  <section class="auth-page">
    <div class="auth-card">
      <div class="badge">Registro</div>
      <h1>Crear cuenta</h1>
      <p class="lead">Completa tus datos. Te enviaremos un correo para activar tu cuenta.</p>

      <div v-if="registrationComplete" class="confirmation">
        <h2>Se ha enviado un correo de confirmación.</h2>
        <p>
          Hemos enviado un enlace de confirmación a <strong>{{ submittedEmail }}</strong>.
          Confirma tu correo para completar el registro.
        </p>
        <p class="helper">
          Haz clic en el enlace del correo para completar la verificación y volver a Maps.
        </p>
        <button class="btn-primary" type="button" @click="resetFormState">
          Volver al formulario
        </button>
      </div>

      <form v-else class="form" @submit.prevent="submitRegister">
        <div class="grid-2">
          <div class="field">
            <label>Nombre de usuario *</label>
            <input v-model.trim="username" type="text" required minlength="3" />
          </div>
          <div class="field">
            <label>Correo electrónico *</label>
            <input v-model.trim="email" type="email" required />
          </div>
        </div>

        <div class="grid-2">
          <div class="field">
            <label>Nombre *</label>
            <input v-model.trim="firstName" type="text" required />
          </div>
          <div class="field">
            <label>Apellido *</label>
            <input v-model.trim="lastName" type="text" required />
          </div>
        </div>

        <div class="field">
          <label>Teléfono (opcional)</label>
          <input
            v-model.trim="phone"
            type="tel"
            inputmode="numeric"
            pattern="[0-9]*"
            maxlength="15"
            @input="handlePhoneInput"
          />
        </div>

        <div class="grid-2">
          <div class="field">
            <label>Contraseña *</label>
            <input v-model="password" type="password" required minlength="8" />
            <span class="hint">Mínimo 8 caracteres, una mayúscula y un número.</span>
          </div>
          <div class="field">
            <label>Repetir contraseña *</label>
            <input v-model="passwordConfirmation" type="password" required minlength="8" />
          </div>
        </div>

        <label class="checkbox-row">
          <input v-model="acceptPrivacy" type="checkbox" required />
          <span>
            Acepto la
            <router-link to="/politica-privacidad" target="_blank">Política de Privacidad</router-link>
            y el tratamiento de mis datos personales.
          </span>
        </label>

        <p v-if="error" class="feedback error">{{ error }}</p>
        <p v-if="success" class="feedback success">{{ success }}</p>

        <button class="btn-primary" type="submit" :disabled="loading">
          {{ loading ? "Creando..." : "Crear cuenta" }}
        </button>
      </form>

      <p class="helper-text">
        ¿Ya tienes cuenta?
        <router-link to="/">Volver al inicio</router-link>
      </p>
    </div>
  </section>
</template>

<script>
import { auth } from "../services/auth";

export default {
  name: "RegisterView",
  data() {
    return {
      username: "",
      firstName: "",
      lastName: "",
      email: "",
      phone: "",
      password: "",
      passwordConfirmation: "",
      acceptPrivacy: false,
      loading: false,
      error: "",
      success: "",
      registrationComplete: false,
      submittedEmail: "",
    };
  },
  methods: {
    handlePhoneInput(event) {
      const target = event.target;
      if (!(target instanceof HTMLInputElement)) return;
      this.phone = target.value.replace(/\D/g, "").slice(0, 15);
    },
    async submitRegister() {
      this.error = "";
      this.success = "";

      if (this.password !== this.passwordConfirmation) {
        this.error = "Las contraseñas no coinciden";
        return;
      }

      if (!this.acceptPrivacy) {
        this.error = "Debes aceptar la política de privacidad";
        return;
      }

      this.loading = true;
      try {
        await auth.register({
          username: this.username,
          first_name: this.firstName,
          last_name: this.lastName,
          email: this.email,
          phone: this.phone,
          password: this.password,
          password_confirmation: this.passwordConfirmation,
        });

        this.submittedEmail = this.email;
        this.registrationComplete = true;
        this.success = "Se ha enviado un correo de confirmación.";
        this.password = "";
        this.passwordConfirmation = "";
      } catch (err) {
        this.error = err instanceof Error ? err.message : "No fue posible registrar el usuario";
      } finally {
        this.loading = false;
      }
    },
    resetFormState() {
      this.registrationComplete = false;
      this.success = "";
      this.submittedEmail = "";
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
  max-width: 720px;
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

.grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
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

.hint {
  font-size: 11px;
  color: rgba(233, 238, 246, 0.55);
}

.checkbox-row {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  font-size: 13px;
  color: rgba(233, 238, 246, 0.78);
  line-height: 1.4;
}

.checkbox-row input {
  width: 16px;
  height: 16px;
  margin-top: 2px;
  accent-color: #00E5FF;
  flex-shrink: 0;
}

.checkbox-row a {
  color: #00E5FF;
  text-decoration: none;
  font-weight: 600;
}

.checkbox-row a:hover { text-decoration: underline; }

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

.confirmation {
  display: grid;
  gap: 14px;
  padding: 22px;
  margin-top: 10px;
  border: 1px solid rgba(0, 229, 255, 0.28);
  border-radius: 14px;
  background: rgba(0, 229, 255, 0.05);
}

.confirmation h2 {
  font-size: 17px;
  margin: 0;
  color: #ffffff;
}

.confirmation .helper {
  color: rgba(233, 238, 246, 0.65);
  font-size: 13px;
  margin: 0;
}

@media (max-width: 640px) {
  .grid-2 { grid-template-columns: 1fr; }
  .auth-card { padding: 28px 22px; }
}
</style>
