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
                v-model="form.email"
                type="email"
                placeholder="tu@email.com"
                required
              />
            </div>

            <div class="form-group">
              <label for="password">Contraseña</label>
              <input
                id="password"
                v-model="form.password"
                type="password"
                placeholder="••••••••"
                required
              />
            </div>

            <div class="form-options">
              <label class="remember-me">
                <input v-model="form.rememberMe" type="checkbox" />
                <span>Recuérdame</span>
              </label>
              <a href="#" class="forgot-password">¿Olvidaste tu contraseña?</a>
            </div>

            <button type="submit" class="btn-login">Iniciar sesión</button>
          </form>

          <div class="modal-footer">
            <p>
              ¿No tienes cuenta?
              <a href="#" @click.prevent="toggleSignup">Regístrate aquí</a>
            </p>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { ref } from "vue";

defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
});

const emit = defineEmits(["update:modelValue"]);

const form = ref({
  email: "",
  password: "",
  rememberMe: false,
});

const closeModal = () => {
  emit("update:modelValue", false);
};

const handleLogin = () => {
  console.log("Login attempt:", form.value);
  // Aquí irá la lógica de autenticación
  // Por ahora solo cerramos el modal
  closeModal();
};

const toggleSignup = () => {
  console.log("Toggle signup mode");
  // Aquí puedes agregar lógica para cambiar a modo registro
};
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
