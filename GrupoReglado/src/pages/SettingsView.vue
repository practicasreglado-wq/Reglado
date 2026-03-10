<template>
  <section class="form-page">
    <div class="form-card">
      <h1>Configuracion de cuenta</h1>
      <p>Gestiona tus datos de usuario.</p>

      <div v-if="!auth.state.user" class="feedback error">
        Debes iniciar sesion para ver la configuracion de tu cuenta.
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
              <strong>Email</strong>
              <p>{{ auth.state.user.email || "-" }}</p>
            </div>
            <button class="btn-outline" type="button" @click="openModal('email')">Cambiar</button>
          </div>

          <div class="setting-row">
            <div>
              <strong>Telefono</strong>
              <p>{{ auth.state.user.phone || "-" }}</p>
            </div>
            <button class="btn-outline" type="button" @click="openModal('phone')">Cambiar</button>
          </div>

          <div class="setting-row">
            <div>
              <strong>Contrasena</strong>
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
            Nuevo email
            <input v-model.trim="emailForm.newEmail" type="email" required />
          </label>
          <p class="small-note">Te enviaremos un correo de confirmacion al nuevo email.</p>
        </template>

        <template v-else-if="activeModal === 'phone'">
          <label>
            Telefono actual
            <input type="text" :value="auth.state.user?.phone || ''" disabled />
          </label>
          <label>
            Nuevo telefono
            <input v-model.trim="phoneForm.phone" type="tel" required />
          </label>
        </template>

        <template v-else-if="activeModal === 'password'">
          <label>
            Contrasena actual
            <input v-model="passwordForm.currentPassword" type="password" required />
          </label>
          <label>
            Nueva contrasena
            <input v-model="passwordForm.newPassword" type="password" required minlength="6" />
          </label>
          <label>
            Confirmar nueva contrasena
            <input v-model="passwordForm.newPasswordConfirmation" type="password" required minlength="6" />
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
  if (activeModal.value === "email") return "Cambiar email";
  if (activeModal.value === "phone") return "Cambiar telefono";
  if (activeModal.value === "password") return "Cambiar contrasena";
  return "Configuracion";
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
      success.value = "Te enviamos un correo para confirmar el cambio de email";
    }

    if (activeModal.value === "phone") {
      await auth.updatePhone(phoneForm.phone);
      success.value = "Telefono actualizado";
    }

    if (activeModal.value === "password") {
      await auth.changePassword(
        passwordForm.currentPassword,
        passwordForm.newPassword,
        passwordForm.newPasswordConfirmation
      );
      success.value = "Contrasena actualizada";
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
  background: #fff;
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
  background: #fff;
  cursor: pointer;
  font-weight: 600;
}

.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.38);
  display: grid;
  place-items: center;
  padding: 1rem;
  z-index: 90;
}

.modal-panel {
  width: min(520px, 100%);
  background: #fff;
  border-radius: 14px;
  padding: 1rem;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
}

.modal-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.icon-btn {
  border: 1px solid var(--line);
  background: #fff;
  border-radius: 8px;
  padding: 0.2rem 0.55rem;
  cursor: pointer;
}

.small-note {
  margin: 0;
  color: var(--muted);
  font-size: 0.9rem;
}
</style>
