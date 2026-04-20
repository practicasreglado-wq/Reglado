<template>
  <section class="form-page">
    <div class="form-card">
      <h1>{{ title }}</h1>
      <p>{{ description }}</p>

      <div v-if="registrationComplete" class="register-confirmation">
        <p class="confirmation-title">Se ha enviado un correo de confirmación.</p>
        <p class="confirmation-text">
          Hemos enviado un enlace de confirmación a <strong>{{ submittedEmail }}</strong>. Confirma tu correo
          para completar el registro.
        </p>

        <button class="btn-primary" type="button" @click="resetFormState">Volver al formulario</button>
      </div>

      <form v-else class="clean-form" @submit.prevent="submitRegister">
        <label>
          Nombre de usuario *
          <input v-model.trim="username" type="text" placeholder="" required minlength="3" />
        </label>

        <label>
          Nombre *
          <input v-model.trim="firstName" type="text" placeholder="" required />
        </label>

        <label>
          Apellido *
          <input v-model.trim="lastName" type="text" placeholder="" required />
        </label>

        <label>
          Correo Electrónico *
          <input v-model.trim="email" type="email" placeholder="" required />
        </label>

        <label>
          Teléfono (Opcional)
          <input
            v-model.trim="phone"
            type="tel"
            inputmode="numeric"
            pattern="[0-9]*"
            maxlength="15"
            placeholder=""
            @input="handlePhoneInput"
          />
        </label>

        <label>
          Contraseña *
          <PasswordField v-model="password" placeholder="" required minlength="8" />
          <span class="password-hint">Mínimo 8 caracteres, una mayúscula y un número.</span>
        </label>

        <label>
          Repetir contraseña *
          <PasswordField v-model="passwordConfirmation" placeholder="" required minlength="8" />
        </label>

        <div class="privacy-note">
          <label class="checkbox-label">
            <input v-model="acceptPrivacy" type="checkbox" required />
            <span>
              Acepto la
              <router-link to="/politica-privacidad" target="_blank">Política de Privacidad</router-link>
              y el tratamiento de mis datos personales.
            </span>
          </label>
        </div>

        <p v-if="error" class="feedback error">{{ error }}</p>
        <p v-if="success" class="feedback success">{{ success }}</p>

        <button class="btn-primary" type="submit" :disabled="loading">
          {{ loading ? "Creando..." : buttonText }}
        </button>
      </form>
    </div>
  </section>
</template>

<script setup>
import { ref } from "vue";
import PasswordField from "../PasswordField.vue";
import { auth } from "../../services/auth";

defineProps({
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    required: true,
  },
  buttonText: {
    type: String,
    default: "Guardar",
  },
});

const username = ref("");
const firstName = ref("");
const lastName = ref("");
const email = ref("");
const phone = ref("");
const password = ref("");
const passwordConfirmation = ref("");
const loading = ref(false);
const error = ref("");
const success = ref("");
const registrationComplete = ref(false);
const submittedEmail = ref("");
const acceptPrivacy = ref(false);

function handlePhoneInput(event) {
  const target = event.target;
  if (!(target instanceof HTMLInputElement)) {
    return;
  }

  const sanitized = target.value.replace(/\D/g, "").slice(0, 15);
  phone.value = sanitized;
}

async function submitRegister() {
  loading.value = true;
  error.value = "";
  success.value = "";

  if (password.value !== passwordConfirmation.value) {
    error.value = "Las contraseñas no coinciden";
    loading.value = false;
    return;
  }

  if (!acceptPrivacy.value) {
    error.value = "Debes aceptar la política de privacidad";
    loading.value = false;
    return;
  }

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
.register-confirmation {
  display: grid;
  gap: 1rem;
  padding: 1.5rem;
  border: 1px solid rgba(15, 23, 42, 0.12);
  border-radius: 1rem;
  background: var(--surface-soft);
}

.confirmation-title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 700;
}

.confirmation-text {
  margin: 0;
  color: var(--text);
}

.privacy-note {
  margin: 1rem 0;
  font-size: 0.9rem;
}

.checkbox-label {
  display: flex !important;
  flex-direction: row !important;
  align-items: center;
  gap: 0.6rem;
  cursor: pointer;
  color: var(--muted);
  font-weight: 400 !important;
}

.checkbox-label input {
  width: auto !important;
  margin: 0;
}

.checkbox-label a {
  color: var(--secondary);
  text-decoration: none;
  font-weight: 600;
}

.password-hint {
  display: block;
  margin-top: 0.3rem;
  font-size: 0.8rem;
  color: var(--muted);
  font-weight: 400;
}
</style>

