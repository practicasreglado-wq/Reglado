<template>
  <main class="auth-page">
    <div class="container">
      <div class="auth-card">
        <span class="badge">Registro</span>
        <h1 class="h1">Crear cuenta</h1>
        <p class="lead">Completa tus datos. Te enviaremos un correo para activar tu cuenta.</p>

        <div v-if="registrationComplete" class="confirmation">
          <h2 class="h3">Se ha enviado un correo de confirmación.</h2>
          <p>
            Hemos enviado un enlace de confirmación a <strong>{{ submittedEmail }}</strong>.
            Confirma tu correo para completar el registro.
          </p>
          <p class="text-muted helper">
            Haz clic en el enlace del correo para completar la verificación y volver a Ingeniería.
          </p>
          <button class="btn primary" type="button" @click="resetFormState">
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

          <button class="btn primary" type="submit" :disabled="loading">
            {{ loading ? "Creando..." : "Crear cuenta" }}
          </button>
        </form>
      </div>
    </div>
  </main>
</template>

<script setup>
import { ref } from "vue";
import { auth } from "@/services/auth.js";

const username = ref("");
const firstName = ref("");
const lastName = ref("");
const email = ref("");
const phone = ref("");
const password = ref("");
const passwordConfirmation = ref("");
const acceptPrivacy = ref(false);
const loading = ref(false);
const error = ref("");
const success = ref("");
const registrationComplete = ref(false);
const submittedEmail = ref("");

function handlePhoneInput(event) {
  const target = event.target;
  if (!(target instanceof HTMLInputElement)) return;
  phone.value = target.value.replace(/\D/g, "").slice(0, 15);
}

async function submitRegister() {
  error.value = "";
  success.value = "";

  if (password.value !== passwordConfirmation.value) {
    error.value = "Las contraseñas no coinciden";
    return;
  }

  if (!acceptPrivacy.value) {
    error.value = "Debes aceptar la política de privacidad";
    return;
  }

  loading.value = true;
  try {
    await auth.register({
      username: username.value,
      first_name: firstName.value,
      last_name: lastName.value,
      email: email.value,
      phone: phone.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    });

    submittedEmail.value = email.value;
    registrationComplete.value = true;
    success.value = "Se ha enviado un correo de confirmación.";
    password.value = "";
    passwordConfirmation.value = "";
  } catch (err) {
    error.value = err instanceof Error ? err.message : "No fue posible registrar el usuario";
  } finally {
    loading.value = false;
  }
}

function resetFormState() {
  registrationComplete.value = false;
  success.value = "";
  submittedEmail.value = "";
}
</script>

<style scoped>
.auth-page {
  padding: 80px 0;
  background: var(--bg-soft);
  min-height: calc(100vh - 160px);
}

.auth-card {
  max-width: 720px;
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

.h1 {
  font-size: 1.75rem;
  margin: 0 0 8px;
}

.lead {
  font-size: 0.9375rem;
  margin: 0 0 24px;
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
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--text);
}

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

.hint {
  font-size: 0.75rem;
  color: var(--text-muted);
}

.checkbox-row {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  font-size: 0.875rem;
  color: var(--text-muted);
  line-height: 1.5;
}

.checkbox-row input {
  width: 16px;
  height: 16px;
  margin-top: 2px;
  accent-color: var(--steel);
  flex-shrink: 0;
}

.checkbox-row a {
  color: var(--steel);
  text-decoration: none;
  font-weight: 600;
}

.checkbox-row a:hover { text-decoration: underline; }

.feedback {
  margin: 0;
  padding: 10px 12px;
  border-radius: var(--radius-sm);
  font-size: 0.8125rem;
  border: 1px solid transparent;
}

.feedback.error {
  color: #b91c1c;
  background: #fee2e2;
  border-color: #fecaca;
}

.feedback.success {
  color: #15803d;
  background: #dcfce7;
  border-color: #bbf7d0;
}

.btn.primary { width: 100%; justify-content: center; margin-top: 4px; }
.btn.primary:disabled { opacity: 0.55; cursor: not-allowed; transform: none; box-shadow: none; }

.confirmation {
  display: grid;
  gap: 14px;
  padding: 22px;
  margin-top: 10px;
  border: 1px solid var(--steel-light);
  border-radius: var(--radius);
  background: #f0f7ff;
}

.confirmation h2 { margin: 0; color: var(--text); }

.confirmation .helper {
  font-size: 0.8125rem;
  margin: 0;
}

@media (max-width: 640px) {
  .grid-2 { grid-template-columns: 1fr; }
  .auth-card { padding: 28px 22px; }
}
</style>
