<template>
  <teleport to="body">
    <transition name="modal">
      <div v-if="modelValue" class="modal-overlay" @click.self="closeModal">
        <div class="modal-content">
          <button class="close-btn" @click="closeModal" aria-label="Cerrar modal">×</button>

          <div class="modal-header">
            <h2>Iniciar sesión</h2>
            <p>Accede al área de Reglado Ingeniería</p>
          </div>

          <form @submit.prevent="handleLogin" class="login-form">
            <div class="field">
              <label for="email">Correo electrónico</label>
              <input
                id="email"
                v-model.trim="form.email"
                type="email"
                autocomplete="email"
                required
                :disabled="loading"
              />
            </div>

            <div class="field">
              <label for="password">Contraseña</label>
              <input
                id="password"
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

            <button type="submit" class="btn primary" :disabled="loading">
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
import { auth } from "@/services/auth.js";

const props = defineProps({
  modelValue: { type: Boolean, required: true },
});
const emit = defineEmits(["update:modelValue", "success"]);

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
    success.value = "Sesión iniciada";
    emit("success");
    closeModal();
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
  background: rgba(26, 31, 46, 0.55);
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
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 36px;
  max-width: 420px;
  width: 100%;
  box-shadow: var(--shadow-lg), 0 24px 60px rgba(26, 31, 46, 0.18);
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
  color: var(--text-muted);
  border-radius: var(--radius-sm);
  transition: all var(--transition);
}

.close-btn:hover {
  background: var(--bg-soft);
  color: var(--text);
}

.modal-header {
  margin-bottom: 24px;
}

.modal-header h2 {
  font-size: 1.375rem;
  font-weight: 700;
  margin: 0 0 6px;
  color: var(--text);
}

.modal-header p {
  font-size: 0.875rem;
  color: var(--text-muted);
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

.field input:disabled {
  background: var(--bg-soft);
  cursor: not-allowed;
}

.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.8125rem;
}

.link {
  color: var(--steel);
  text-decoration: none;
  transition: color var(--transition);
}

.link:hover {
  color: var(--steel-dark);
  text-decoration: underline;
}

.btn.primary {
  width: 100%;
  justify-content: center;
  margin-top: 4px;
}

.btn.primary:disabled {
  opacity: 0.55;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

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

.btn-link {
  background: transparent;
  border: none;
  color: var(--steel);
  font-size: 0.8125rem;
  cursor: pointer;
  padding: 4px 0 0;
  text-decoration: underline;
  text-underline-offset: 3px;
  align-self: center;
  font-family: inherit;
}

.btn-link:hover:not(:disabled) {
  color: var(--steel-dark);
}

.btn-link:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.modal-footer {
  margin-top: 20px;
  padding-top: 18px;
  border-top: 1px solid var(--border);
  text-align: center;
  font-size: 0.8125rem;
  color: var(--text-muted);
}

.modal-footer a {
  color: var(--steel);
  text-decoration: none;
  font-weight: 600;
}

.modal-footer a:hover {
  color: var(--steel-dark);
  text-decoration: underline;
}

.modal-enter-active,
.modal-leave-active { transition: opacity 0.25s ease; }
.modal-enter-from,
.modal-leave-to { opacity: 0; }

@keyframes slideIn {
  from { opacity: 0; transform: scale(0.96) translateY(-12px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}

@media (max-width: 520px) {
  .modal-content { padding: 28px 22px; }
}
</style>
