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
            <p>Accede al área de Reglado Maps</p>
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

<script>
import { auth } from "../services/auth";
import { redirectToStore } from "../services/ssoClient";

export default {
  name: "LoginModal",
  props: {
    modelValue: {
      type: Boolean,
      required: true,
    },
  },
  emits: ["update:modelValue", "success"],
  data() {
    return {
      form: { email: "", password: "" },
      loading: false,
      error: "",
      success: "",
    };
  },
  watch: {
    modelValue(isOpen) {
      if (!isOpen) {
        this.error = "";
        this.success = "";
        this.form.password = "";
      }
    },
  },
  methods: {
    closeModal() {
      this.$emit("update:modelValue", false);
    },
    async handleLogin() {
      this.error = "";
      this.success = "";
      this.loading = true;

      try {
        await auth.login(this.form.email, this.form.password);
        this.success = "Sesión iniciada";
        this.$emit("success");
        this.closeModal();
        // Propaga el token al hub para que el ecosistema herede la sesión.
        const returnUrl = window.location.origin + window.location.pathname;
        redirectToStore(auth.state.token, returnUrl);
      } catch (err) {
        this.error = err instanceof Error ? err.message : "No fue posible iniciar sesión";
      } finally {
        this.loading = false;
      }
    },
    async resendMail() {
      if (!this.form.email) {
        this.error = "Indica un correo para reenviar la verificación";
        return;
      }

      this.loading = true;
      this.error = "";
      this.success = "";

      try {
        const response = await auth.resendVerification(this.form.email);
        this.success = response.message || "Correo de verificación reenviado.";
      } catch (err) {
        this.error = err instanceof Error ? err.message : "No fue posible reenviar el correo";
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(2, 6, 23, 0.78);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(6px);
  padding: 1rem;
}

.modal-content {
  position: relative;
  background: rgba(10, 15, 25, 0.96);
  border: 1px solid rgba(0, 229, 255, 0.35);
  border-radius: 20px;
  padding: 40px;
  max-width: 420px;
  width: 100%;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.55), 0 0 24px rgba(0, 229, 255, 0.12);
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
  background: rgba(255, 255, 255, 0.08);
}

.close-btn span {
  position: absolute;
  width: 18px;
  height: 2px;
  background: rgba(233, 238, 246, 0.85);
  border-radius: 1px;
}

.close-btn span:first-child { transform: rotate(45deg); }
.close-btn span:last-child { transform: rotate(-45deg); }

.modal-header {
  margin-bottom: 28px;
  text-align: center;
}

.modal-header h2 {
  font-size: 24px;
  font-weight: 700;
  margin: 0 0 8px;
  color: #e9eef6;
}

.modal-header p {
  font-size: 14px;
  color: rgba(233, 238, 246, 0.65);
  margin: 0;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: rgba(233, 238, 246, 0.85);
}

.form-group input {
  padding: 11px 14px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.04);
  color: #e9eef6;
  font-size: 14px;
  font-family: inherit;
  transition: all 0.2s;
}

.form-group input:focus {
  outline: none;
  border-color: rgba(0, 229, 255, 0.55);
  background: rgba(255, 255, 255, 0.06);
  box-shadow: 0 0 0 3px rgba(0, 229, 255, 0.12);
}

.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 13px;
}

.forgot-password {
  color: #00E5FF;
  text-decoration: none;
  transition: color 0.18s;
}

.forgot-password:hover {
  color: #7af3ff;
  text-decoration: underline;
}

.btn-login {
  padding: 12px 20px;
  background: linear-gradient(135deg, #00E5FF 0%, #0288D1 100%);
  color: #001a2e;
  border: none;
  border-radius: 10px;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
  margin-top: 4px;
}

.btn-login:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 229, 255, 0.35);
}

.btn-login:disabled {
  opacity: 0.55;
  cursor: not-allowed;
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

.btn-link {
  background: transparent;
  border: none;
  color: #00E5FF;
  font-size: 13px;
  cursor: pointer;
  padding: 4px 0 0;
  text-decoration: underline;
  text-underline-offset: 3px;
  align-self: center;
}

.btn-link:hover:not(:disabled) { color: #7af3ff; }
.btn-link:disabled { opacity: 0.5; cursor: not-allowed; }

.modal-footer {
  margin-top: 22px;
  text-align: center;
  font-size: 13px;
  color: rgba(233, 238, 246, 0.7);
  border-top: 1px solid rgba(255, 255, 255, 0.07);
  padding-top: 20px;
}

.modal-footer a {
  color: #00E5FF;
  text-decoration: none;
  font-weight: 600;
}

.modal-footer a:hover {
  color: #7af3ff;
  text-decoration: underline;
}

.modal-enter-active,
.modal-leave-active { transition: opacity 0.3s ease; }
.modal-enter-from,
.modal-leave-to { opacity: 0; }

@keyframes slideIn {
  from { opacity: 0; transform: scale(0.96) translateY(-16px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}

@media (max-width: 600px) {
  .modal-content { padding: 28px 22px; border-radius: 16px; }
  .modal-header h2 { font-size: 20px; }
}
</style>
