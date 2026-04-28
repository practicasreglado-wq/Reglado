<template>
  <div class="auth-page">
    <div class="overlay"></div>

    <div class="auth-hero">
      <h1>
        <span class="highlight">Crea tu cuenta</span>
      </h1>
      <p>
        Accede al ecosistema de Reglado Real Estate. Te enviaremos un correo
        para activar tu cuenta una vez completes el registro.
      </p>
      <p class="login-link">
        ¿Ya tienes cuenta?
        <span @click="goToLogin">Inicia sesión</span>
      </p>
    </div>

    <div class="auth-card">
      <h2>Registro</h2>
      <p class="subject-pill">Cuenta personal</p>

      <div v-if="registrationComplete" class="confirmation">
        <p class="confirmation-title">Se ha enviado un correo de confirmación.</p>
        <p>
          Hemos enviado un enlace a <strong>{{ submittedEmail }}</strong>.
          Pulsa el enlace para activar tu cuenta.
        </p>
        <button class="auth-btn" type="button" @click="resetFormState">
          Volver al formulario
        </button>
      </div>

      <form v-else class="formulario" @submit.prevent="submitRegister">
        <div class="row">
          <div class="field">
            <label for="reg-username">Nombre de usuario *</label>
            <input
              id="reg-username"
              v-model.trim="username"
              type="text"
              required
              minlength="3"
            />
          </div>
          <div class="field">
            <label for="reg-email">Correo electrónico *</label>
            <input
              id="reg-email"
              v-model.trim="email"
              type="email"
              required
            />
          </div>
        </div>

        <div class="row">
          <div class="field">
            <label for="reg-firstname">Nombre *</label>
            <input
              id="reg-firstname"
              v-model.trim="firstName"
              type="text"
              required
            />
          </div>
          <div class="field">
            <label for="reg-lastname">Apellido *</label>
            <input
              id="reg-lastname"
              v-model.trim="lastName"
              type="text"
              required
            />
          </div>
        </div>

        <div class="field">
          <label for="reg-phone">Teléfono (opcional)</label>
          <input
            id="reg-phone"
            v-model.trim="phone"
            type="tel"
            inputmode="numeric"
            pattern="[0-9]*"
            maxlength="15"
            @input="handlePhoneInput"
          />
        </div>

        <div class="row">
          <div class="field">
            <label for="reg-password">Contraseña *</label>
            <input
              id="reg-password"
              v-model="password"
              type="password"
              required
              minlength="8"
            />
            <span class="hint">Mínimo 8 caracteres, una mayúscula y un número.</span>
          </div>
          <div class="field">
            <label for="reg-password2">Repetir contraseña *</label>
            <input
              id="reg-password2"
              v-model="passwordConfirmation"
              type="password"
              required
              minlength="8"
            />
          </div>
        </div>

        <label class="checkbox-row">
          <input v-model="acceptPrivacy" type="checkbox" required />
          <span>
            Acepto la
            <router-link to="/legal" target="_blank">Política de Privacidad</router-link>
            y el tratamiento de mis datos personales.
          </span>
        </label>

        <p v-if="error" class="feedback error">{{ error }}</p>
        <p v-if="success" class="feedback success">{{ success }}</p>

        <button class="auth-btn" type="submit" :disabled="loading">
          {{ loading ? "Creando..." : "Crear cuenta" }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { auth } from "../services/auth";

const router = useRouter();

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

function goToLogin() {
  // Vuelve a home con el query flag que App.vue intercepta para abrir el modal.
  router.push({ path: "/", query: { login: "required" } });
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
  position: relative;
  min-height: 100vh;
  background-color: var(--gris-claro);
  background-image: url("@/assets/fondito.png");
  background-size: cover;
  background-position: center;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 72px;
  padding: 128px 72px 40px;
}

.overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.516);
  z-index: 0;
}

.auth-hero,
.auth-card {
  position: relative;
  z-index: 1;
}

.auth-hero {
  color: white;
  max-width: 560px;
  transform: translateY(-30px);
}

.auth-hero h1 {
  font-size: 4rem;
  margin-bottom: 20px;
  line-height: 1.05;
}

.highlight {
  color: #eabe2f;
}

.auth-hero p {
  font-size: 1.4rem;
  margin-bottom: 18px;
  line-height: 1.5;
}

.login-link {
  font-size: 1.05rem;
  margin-top: 24px;
}

.login-link span {
  color: #eabe2f;
  cursor: pointer;
  font-weight: 700;
  margin-left: 5px;
}

.login-link span:hover {
  text-decoration: underline;
}

.auth-card {
  width: 520px;
  background: rgba(255, 255, 255, 0.96);
  padding: 28px;
  border-radius: 20px;
  box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
}

h2 {
  margin: 0 0 12px;
  font-size: 2rem;
  color: #111;
}

.subject-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
  padding: 8px 16px;
  border-radius: 999px;
  background: rgba(11, 61, 145, 0.08);
  color: var(--azul-principal);
  font-weight: 600;
  font-size: 0.95rem;
  align-self: flex-start;
}

.formulario {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.row {
  display: flex;
  gap: 12px;
}

.field {
  width: 100%;
  display: flex;
  flex-direction: column;
}

.field label {
  display: block;
  margin-bottom: 6px;
  color: #213547;
  font-weight: 600;
  font-size: 0.95rem;
}

.field input {
  width: 100%;
  padding: 10px 12px;
  border: 2px solid var(--azul-principal);
  border-radius: 6px;
  font-size: 0.95rem;
  outline: none;
  box-sizing: border-box;
  transition: border-color 0.18s ease, box-shadow 0.18s ease;
}

.field input:focus {
  border-color: var(--azul-secundario);
  box-shadow: 0 0 0 3px rgba(74, 114, 198, 0.18);
}

.hint {
  margin-top: 4px;
  color: #64748b;
  font-size: 0.78rem;
}

.checkbox-row {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  font-size: 0.9rem;
  color: #334155;
  line-height: 1.5;
  margin-top: 4px;
}

.checkbox-row input {
  width: 16px;
  height: 16px;
  margin-top: 3px;
  accent-color: var(--azul-principal);
  flex-shrink: 0;
}

.checkbox-row a {
  color: var(--azul-principal);
  text-decoration: none;
  font-weight: 600;
}

.checkbox-row a:hover {
  text-decoration: underline;
}

.feedback {
  margin: 0;
  padding: 10px 12px;
  border-radius: 8px;
  font-size: 0.875rem;
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

.auth-btn {
  margin-top: 8px;
  width: 100%;
  padding: 14px 18px;
  border: none;
  border-radius: 10px;
  background: var(--azul-principal);
  color: #fff;
  font-weight: 700;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.18s ease;
}

.auth-btn:hover:not(:disabled) {
  background: #0f1830;
  transform: translateY(-1px);
  box-shadow: 0 8px 20px rgba(26, 37, 69, 0.3);
}

.auth-btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.confirmation {
  display: grid;
  gap: 12px;
  padding: 20px;
  border: 1px solid rgba(11, 61, 145, 0.18);
  border-radius: 12px;
  background: rgba(11, 61, 145, 0.04);
}

.confirmation-title {
  margin: 0;
  font-weight: 700;
  color: var(--azul-principal);
  font-size: 1.05rem;
}

@media (max-width: 1024px) {
  .auth-page {
    flex-direction: column;
    padding: 100px 24px 40px;
    gap: 24px;
  }

  .auth-hero {
    transform: none;
    text-align: center;
    max-width: none;
  }

  .auth-hero h1 {
    font-size: 2.6rem;
  }

  .auth-hero p {
    font-size: 1.05rem;
  }

  .auth-card {
    width: 100%;
    max-width: 520px;
  }

  .row {
    flex-direction: column;
    gap: 12px;
  }
}
</style>
