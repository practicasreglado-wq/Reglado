<template>
  <teleport to="body">
    <transition name="modal">
      <div v-if="modelValue" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
          <button class="close-btn" @click="closeModal" aria-label="Cerrar modal">
            <span></span>
            <span></span>
          </button>

          <div class="modal-header">
            <h2>Inicio de sesión</h2>
            <p>Accede a tu área de cliente</p>
          </div>

          <form @submit.prevent="handleLogin" class="login-form">
            <div class="form-group">
              <label for="email">Correo electrónico</label>
              <input
                id="email"
                v-model.trim="form.email"
                type="email"
                placeholder="tu@email.com"
                autocomplete="email"
                required
                :disabled="loading"
              />
            </div>

            <div class="form-group">
              <label for="password">Contraseña</label>
              <input
                id="password"
                v-model="form.password"
                type="password"
                placeholder="••••••••"
                autocomplete="current-password"
                required
                :disabled="loading"
              />
            </div>

            <div class="form-options">
              <span></span>
              <router-link to="/recuperar-contrasena" class="forgot-password" @click="closeModal">
                ¿Olvidaste tu contraseña?
              </router-link>
            </div>

            <p v-if="error" class="feedback error">{{ error }}</p>
            <p v-if="success" class="feedback success">{{ success }}</p>

            <button type="submit" class="btn-login" :disabled="loading">
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

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
});

const emit = defineEmits(["update:modelValue", "success"]);

const form = ref({
  email: "",
  password: "",
});

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
    success.value = "Sesión iniciada";
    emit("success");
    closeModal();
    // Propaga el token al hub (Grupo) para que el ecosistema comparta la
    // sesión. Usamos origin+pathname (sin query/hash) para que al volver
    // no arrastremos flags sobrantes tipo ?sso_failed=1.
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
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(4px);
}

.modal-content {
  position: relative;
  background: rgba(11, 13, 16, 0.95);
  border: 1px solid #f2c53d;
  border-radius: 20px;
  padding: 40px;
  max-width: 420px;
  width: 90%;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
  animation: slideIn 0.3s ease-out;
}

.close-btn {
  position: absolute;
  top: 20px;
  right: 20px;
  background: transparent;
  border: none;
  cursor: pointer;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  transition: background 0.2s;
}

.close-btn:hover {
  background: rgba(255, 255, 255, 0.1);
}

.close-btn span {
  position: absolute;
  width: 18px;
  height: 2px;
  background: rgba(233, 238, 246, 0.8);
  border-radius: 1px;
}

.close-btn span:first-child {
  transform: rotate(45deg);
}

.close-btn span:last-child {
  transform: rotate(-45deg);
}

.modal-header {
  margin-bottom: 30px;
  text-align: center;
}

.modal-header h2 {
  font-size: 24px;
  font-weight: 700;
  margin-bottom: 8px;
  color: #e9eef6;
}

.modal-header p {
  font-size: 14px;
  color: rgba(233, 238, 246, 0.6);
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: rgba(233, 238, 246, 0.85);
}

.form-group input {
  padding: 12px 14px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.04);
  color: #e9eef6;
  font-size: 14px;
  transition: all 0.2s;
}

.form-group input::placeholder {
  color: rgba(233, 238, 246, 0.4);
}

.form-group input:focus {
  outline: none;
  border-color: rgba(242, 197, 61, 0.5);
  background: rgba(255, 255, 255, 0.06);
  box-shadow: 0 0 0 3px rgba(242, 197, 61, 0.1);
}

.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 13px;
}

.remember-me {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  color: rgba(233, 238, 246, 0.7);
}

.remember-me input[type="checkbox"] {
  width: 16px;
  height: 16px;
  cursor: pointer;
  accent-color: #f2c53d;
}

.forgot-password {
  color: #f2c53d;
  text-decoration: none;
  transition: color 0.2s;
}

.forgot-password:hover {
  color: #ffd966;
}

.btn-login {
  padding: 12px 20px;
  background: linear-gradient(135deg, #f2c53d 0%, #e6b320 100%);
  color: #0b0d10;
  border: none;
  border-radius: 10px;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
  margin-top: 8px;
}

.btn-login:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(242, 197, 61, 0.3);
}

.btn-login:active {
  transform: translateY(0);
}

.btn-login:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
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

.btn-link {
  background: transparent;
  border: none;
  color: #f2c53d;
  font-size: 13px;
  cursor: pointer;
  padding: 4px 0 0;
  text-decoration: underline;
  text-underline-offset: 3px;
  align-self: center;
}

.btn-link:hover:not(:disabled) {
  color: #ffd966;
}

.btn-link:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.modal-footer {
  margin-top: 24px;
  text-align: center;
  font-size: 13px;
  color: rgba(233, 238, 246, 0.7);
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  padding-top: 24px;
}

.modal-footer a {
  color: #f2c53d;
  text-decoration: none;
  transition: color 0.2s;
}

.modal-footer a:hover {
  color: #ffd966;
}

/* Animaciones */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: scale(0.95) translateY(-20px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

@media (max-width: 600px) {
  .modal-content {
    padding: 30px 24px;
    border-radius: 16px;
  }

  .modal-header h2 {
    font-size: 20px;
  }

  .form-options {
    flex-direction: column;
    gap: 12px;
    align-items: flex-start;
  }
}
</style>
