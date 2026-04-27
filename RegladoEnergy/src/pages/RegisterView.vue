<template>
  <section class="section">
    <div class="container">
      <div class="card glow auth-card" v-glow>
        <div class="badge">Registro</div>
        <h1 class="h1">Crear cuenta</h1>
        <p class="p">Completa tus datos. Te enviaremos un correo para activar tu cuenta.</p>

        <div v-if="registrationComplete" class="confirmation">
          <h2 class="h2">Se ha enviado un correo de confirmación.</h2>
          <p class="p">
            Hemos enviado un enlace de confirmación a <strong>{{ submittedEmail }}</strong>.
            Confirma tu correo para completar el registro.
          </p>
          <p class="p helper">
            Haz clic en el enlace del correo para completar la verificación y volver a Energy.
          </p>
          <button class="btn primary glow" type="button" v-glow @click="resetFormState">
            Volver al formulario
          </button>
        </div>

        <form v-else class="form" @submit.prevent="submitRegister">
          <div class="grid grid-2">
            <div class="field">
              <label>Nombre de usuario *</label>
              <input v-model.trim="username" type="text" required minlength="3" />
            </div>
            <div class="field">
              <label>Correo Electrónico *</label>
              <input v-model.trim="email" type="email" required />
            </div>
          </div>

          <div class="grid grid-2">
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

          <div class="grid grid-2">
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

          <button class="btn primary glow" type="submit" :disabled="loading" v-glow>
            {{ loading ? "Creando..." : "Crear cuenta" }}
          </button>
        </form>

        <p class="helper-text">
          ¿Ya tienes cuenta?
          <router-link to="/">Volver al inicio</router-link>
        </p>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref } from "vue";
import { auth } from "../services/auth";

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
  if (!(target instanceof HTMLInputElement)) {
    return;
  }
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
.auth-card {
  max-width: 720px;
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

.hint {
  font-size: 11px;
  color: rgba(233, 238, 246, 0.55);
}

.checkbox-row {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  font-size: 13px;
  color: rgba(233, 238, 246, 0.8);
  line-height: 1.4;
}

.checkbox-row input {
  width: 16px;
  height: 16px;
  margin-top: 2px;
  accent-color: #f2c53d;
  flex-shrink: 0;
}

.checkbox-row a {
  color: #f2c53d;
  text-decoration: none;
  font-weight: 600;
}

.checkbox-row a:hover {
  text-decoration: underline;
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

.confirmation {
  display: grid;
  gap: 12px;
  padding: 20px;
  margin-top: 18px;
  border: 1px solid rgba(242, 197, 61, 0.28);
  border-radius: 14px;
  background: rgba(242, 197, 61, 0.06);
}

.confirmation .helper {
  color: rgba(233, 238, 246, 0.65);
  font-size: 13px;
}

@media (max-width: 640px) {
  .grid.grid-2 {
    grid-template-columns: 1fr;
  }
}
</style>
