<template>
  <section class="form-page">
    <div class="form-card">
      <h1>Configuración de cuenta</h1>
      <p>Gestiona tus datos de usuario.</p>

      <div v-if="!auth.state.user" class="feedback error">
        Debes iniciar sesión para ver la configuración de tu cuenta.
      </div>

      <template v-else>
        <p v-if="success" class="feedback success">{{ success }}</p>
        <p v-if="error" class="feedback error">{{ error }}</p>

        <div class="settings-grid">
          <div class="setting-row">
            <div>
              <strong>Nombre de usuario</strong>
              <p>{{ auth.state.user.username || "-" }}</p>
            </div>
            <button class="btn-outline" type="button" @click="openModal('username')">Cambiar</button>
          </div>

          <div class="setting-row">
            <div>
              <strong>Nombre y apellido</strong>
              <p>{{ fullName }}</p>
            </div>
            <button class="btn-outline" type="button" @click="openModal('name')">Cambiar</button>
          </div>

          <div class="setting-row">
            <div>
              <strong>Correo electrónico</strong>
              <p>{{ auth.state.user.email || "-" }}</p>
            </div>
            <button class="btn-outline" type="button" @click="openModal('email')">Cambiar</button>
          </div>

          <div class="setting-row">
            <div>
              <strong>Teléfono</strong>
              <p>{{ auth.state.user.phone || "-" }}</p>
            </div>
            <button class="btn-outline" type="button" @click="openModal('phone')">Cambiar</button>
          </div>

          <div class="setting-row">
            <div>
              <strong>Contraseña</strong>
              <p>********</p>
            </div>
            <button class="btn-outline" type="button" @click="openModal('password')">Cambiar</button>
          </div>
        </div>
      </template>
    </div>
  </section>

  <div v-if="activeModal" class="modal-backdrop" @click.self="closeModal">
    <div class="modal-panel">
      <div class="modal-head">
        <h2>{{ modalTitle }}</h2>
        <button class="icon-btn" type="button" @click="closeModal">x</button>
      </div>

      <form class="clean-form" @submit.prevent="submitActiveModal">
        <template v-if="activeModal === 'username'">
          <label>
            Nombre de usuario actual
            <input type="text" :value="auth.state.user?.username || ''" disabled />
          </label>
          <label>
            Nuevo nombre de usuario
            <input v-model.trim="usernameForm.username" type="text" required minlength="3" />
          </label>
        </template>

        <template v-else-if="activeModal === 'name'">
          <label>
            Nombre
            <input v-model.trim="nameForm.firstName" type="text" required />
          </label>
          <label>
            Apellido
            <input v-model.trim="nameForm.lastName" type="text" required />
          </label>
        </template>

        <template v-else-if="activeModal === 'email'">
          <label>
            Email actual
            <input type="email" :value="auth.state.user?.email || ''" disabled />
          </label>
          <label>
            Nuevo correo
            <input v-model.trim="emailForm.newEmail" type="email" required />
          </label>
          <p class="small-note">Te enviaremos un correo de confirmación al nuevo correo.</p>
        </template>

        <template v-else-if="activeModal === 'phone'">
          <label>
            Teléfono actual
            <input type="text" :value="auth.state.user?.phone || ''" disabled />
          </label>
          <label>
            Nuevo teléfono
            <input v-model.trim="phoneForm.phone" type="tel" required />
          </label>
        </template>

        <template v-else-if="activeModal === 'password'">
          <label>
            Contraseña actual
            <PasswordField v-model="passwordForm.currentPassword" required />
          </label>
          <label>
            Nueva contraseña
            <PasswordField v-model="passwordForm.newPassword" required minlength="8" />
            <span class="password-hint">Mínimo 8 caracteres, una mayúscula y un número.</span>
          </label>
          <label>
            Confirmar nueva contraseña
            <PasswordField v-model="passwordForm.newPasswordConfirmation" required minlength="8" />
          </label>
        </template>

        <p v-if="modalError" class="feedback error">{{ modalError }}</p>

        <button class="btn-primary" type="submit" :disabled="loading">
          {{ loading ? "Guardando..." : "Guardar cambios" }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import PasswordField from "../components/PasswordField.vue";
import { auth } from "../services/auth";

const success = ref("");
const error = ref("");
const modalError = ref("");
const loading = ref(false);
const activeModal = ref("");

const usernameForm = reactive({ username: "" });
const nameForm = reactive({ firstName: "", lastName: "" });
const emailForm = reactive({ newEmail: "" });
const phoneForm = reactive({ phone: "" });
const passwordForm = reactive({
  currentPassword: "",
  newPassword: "",
  newPasswordConfirmation: "",
});

const modalTitle = computed(() => {
  if (activeModal.value === "username") return "Cambiar nombre de usuario";
  if (activeModal.value === "name") return "Cambiar nombre y apellido";
  if (activeModal.value === "email") return "Cambiar correo";
  if (activeModal.value === "phone") return "Cambiar teléfono";
  if (activeModal.value === "password") return "Cambiar contraseña";
  return "Configuración";
});

const fullName = computed(() => {
  const firstName = auth.state.user?.first_name || "";
  const lastName = auth.state.user?.last_name || "";
  const value = `${firstName} ${lastName}`.trim();
  return value || auth.state.user?.name || "-";
});

function clearMessages() {
  success.value = "";
  error.value = "";
  modalError.value = "";
}

function openModal(type) {
  clearMessages();
  activeModal.value = type;

  if (type === "username") {
    usernameForm.username = auth.state.user?.username || "";
  }

  if (type === "name") {
    nameForm.firstName = auth.state.user?.first_name || "";
    nameForm.lastName = auth.state.user?.last_name || "";
  }

  if (type === "email") {
    emailForm.newEmail = "";
  }

  if (type === "phone") {
    phoneForm.phone = auth.state.user?.phone || "";
  }

  if (type === "password") {
    passwordForm.currentPassword = "";
    passwordForm.newPassword = "";
    passwordForm.newPasswordConfirmation = "";
  }
}

function closeModal() {
  activeModal.value = "";
  modalError.value = "";
}

async function submitActiveModal() {
  if (!activeModal.value) return;

  loading.value = true;
  modalError.value = "";
  success.value = "";
  error.value = "";

  try {
    if (activeModal.value === "username") {
      await auth.updateUsername(usernameForm.username);
      success.value = "Nombre de usuario actualizado";
    }

    if (activeModal.value === "name") {
      await auth.updateName(nameForm.firstName, nameForm.lastName);
      success.value = "Nombre y apellido actualizados";
    }

    if (activeModal.value === "email") {
      await auth.requestEmailChange(emailForm.newEmail);
      success.value = "Te enviamos un correo para confirmar el cambio de correo";
    }

    if (activeModal.value === "phone") {
      await auth.updatePhone(phoneForm.phone);
      success.value = "Teléfono actualizado";
    }

    if (activeModal.value === "password") {
      await auth.changePassword(
        passwordForm.currentPassword,
        passwordForm.newPassword,
        passwordForm.newPasswordConfirmation
      );
      success.value = "Contraseña actualizada";
    }

    closeModal();
  } catch (err) {
    modalError.value = err instanceof Error ? err.message : "No fue posible actualizar";
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  if (!auth.state.user && auth.state.token) {
    auth.initialize();
  }
});
</script>

<style scoped>
.settings-grid {
  display: grid;
  gap: 0.75rem;
  margin-top: 0.7rem;
}

.setting-row {
  border: 1px solid var(--line);
  border-radius: 12px;
  padding: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.8rem;
  background: var(--surface);
  transition: all 0.4s ease;
}

.setting-row strong {
  color: var(--text);
  font-weight: 700;
}

.setting-row p {
  margin: 0.2rem 0 0;
  color: var(--muted);
}

.btn-outline {
  border: 1px solid var(--line);
  border-radius: 10px;
  padding: 0.58rem 0.9rem;
  color: var(--text);
  background: var(--surface-soft);
  cursor: pointer;
  font-weight: 600;
  transition: all 0.2s ease;
}

.btn-outline:hover {
  background: var(--primary);
  color: #fff;
  border-color: var(--primary);
}

.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.48);
  display: grid;
  place-items: center;
  padding: 1rem;
  z-index: 90;
}

.modal-panel {
  width: min(520px, 100%);
  background: var(--surface);
  border: 1px solid var(--line);
  border-radius: 14px;
  padding: 1rem;
  box-shadow: var(--shadow-strong);
  color: var(--text);
}

.modal-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.icon-btn {
  border: 1px solid var(--line);
  background: var(--surface-soft);
  color: var(--text);
  border-radius: 8px;
  padding: 0.2rem 0.55rem;
  cursor: pointer;
}

.small-note {
  margin: 0;
  color: var(--muted);
  font-size: 0.9rem;
}

.password-hint {
  display: block;
  margin-top: 0.3rem;
  font-size: 0.8rem;
  color: var(--muted);
  font-weight: 400;
}
</style>
