<template>
  <teleport to="body">
    <transition name="modal">
      <div v-if="modelValue" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
          <button class="close-btn" @click="closeModal" aria-label="Cerrar modal">×</button>

          <div class="modal-header">
            <h2>Iniciar sesión</h2>
            <p>Accede a tu cuenta de Reglado RealState</p>
          </div>

          <form @submit.prevent="handleLogin" class="login-form">
            <div class="field">
              <label for="modal-email">Correo electrónico</label>
              <input
                id="modal-email"
                v-model.trim="form.email"
                type="email"
                autocomplete="email"
                required
                :disabled="loading"
              />
            </div>

            <div class="field">
              <label for="modal-password">Contraseña</label>
              <input
                id="modal-password"
                v-model="form.password"
                type="password"
                autocomplete="current-password"
                required
                :disabled="loading"
              />
            </div>

            <div class="form-options">
              <span></span>
              <router-link to="/recuperar-contrasena" class="link" @click="closeModal">
                ¿Olvidaste tu contraseña?
              </router-link>
            </div>

            <p v-if="error" class="feedback error">{{ error }}</p>
            <p v-if="success" class="feedback success">{{ success }}</p>

            <button type="submit" class="btn-primary" :disabled="loading">
              {{ loading ? "Entrando..." : "Iniciar sesión" }}
            </button>

            <button
              v-if="error"
              type="button"
              class="btn-link"
              :disabled="loading"
              @click="resendMail"
            >
              Reenviar correo de verificación
            </button>
          </form>

          <div class="modal-footer">
            <p>
              ¿No tienes cuenta?
              <router-link to="/registro" @click="closeModal">Regístrate aquí</router-link>
            </p>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { ref, watch } from "vue";
import { auth } from "../services/auth";
import { redirectToStore } from "../services/ssoClient";
import { useUserStore } from "../stores/user";

const props = defineProps({
  modelValue: { type: Boolean, required: true },
});
const emit = defineEmits(["update:modelValue", "success"]);

const userStore = useUserStore();
const form = ref({ email: "", password: "" });
const loading = ref(false);
const error = ref("");
const success = ref("");

watch(
  () => props.modelValue,
  (isOpen) => {
    if (!isOpen) {
      error.value = "";
      success.value = "";
      form.value.password = "";
    }
  }
);

function closeModal() {
  emit("update:modelValue", false);
}

async function handleLogin() {
  error.value = "";
  success.value = "";
  loading.value = true;

  try {
    await auth.login(form.value.email, form.value.password);
    // Hidrata userStore con datos del backend de Inmobiliaria.
    await userStore.initializeSession();
    success.value = "Sesión iniciada";
    emit("success");
    closeModal();
    // Propaga el token al hub para que el ecosistema herede la sesión.
    const returnUrl = window.location.origin + window.location.pathname;
    redirectToStore(auth.state.token, returnUrl);
  } catch (err) {
    error.value = err instanceof Error ? err.message : "No fue posible iniciar sesión";
  } finally {
    loading.value = false;
  }
}

async function resendMail() {
  if (!form.value.email) {
    error.value = "Indica un correo para reenviar la verificación";
    return;
  }

  loading.value = true;
  error.value = "";
  success.value = "";

  try {
    const response = await auth.resendVerification(form.value.email);
    success.value = response.message || "Correo de verificación reenviado.";
  } catch (err) {
    error.value = err instanceof Error ? err.message : "No fue posible reenviar el correo";
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal-content {
  position: relative;
  background: #ffffff;
  border-radius: 16px;
  padding: 36px;
  max-width: 420px;
  width: 100%;
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.25);
  animation: slideIn 0.25s ease-out;
}

.close-btn {
  position: absolute;
  top: 12px;
  right: 12px;
  background: transparent;
  border: none;
  cursor: pointer;
  width: 36px;
  height: 36px;
  font-size: 24px;
  color: #64748b;
  border-radius: 8px;
  transition: all 0.15s ease;
}

.close-btn:hover {
  background: #f1f5f9;
  color: #24386b;
}

.modal-header {
  margin-bottom: 24px;
}

.modal-header h2 {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 6px;
  color: #24386b;
}

.modal-header p {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0;
}

.login-form {
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
  font-size: 0.8125rem;
  font-weight: 600;
  color: #1a1f2e;
}

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

.field input:disabled {
  background: #f8fafc;
  cursor: not-allowed;
}

.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.8125rem;
}

.link {
  color: #24386b;
  text-decoration: none;
  transition: color 0.18s ease;
}

.link:hover {
  color: #1a2b54;
  text-decoration: underline;
}

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

.btn-primary:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.feedback {
  margin: 0;
  padding: 10px 12px;
  border-radius: 10px;
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

.btn-link {
  background: transparent;
  border: none;
  color: #24386b;
  font-size: 0.8125rem;
  cursor: pointer;
  padding: 4px 0 0;
  text-decoration: underline;
  text-underline-offset: 3px;
  align-self: center;
  font-family: inherit;
}

.btn-link:hover:not(:disabled) {
  color: #1a2b54;
}

.btn-link:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.modal-footer {
  margin-top: 20px;
  padding-top: 18px;
  border-top: 1px solid #e0e4ea;
  text-align: center;
  font-size: 0.8125rem;
  color: #64748b;
}

.modal-footer a {
  color: #24386b;
  text-decoration: none;
  font-weight: 600;
}

.modal-footer a:hover {
  color: #1a2b54;
  text-decoration: underline;
}

.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.25s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

@keyframes slideIn {
  from { opacity: 0; transform: scale(0.96) translateY(-12px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}

@media (max-width: 520px) {
  .modal-content { padding: 28px 22px; }
}
</style>
