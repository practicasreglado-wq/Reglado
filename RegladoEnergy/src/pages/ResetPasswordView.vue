<template>
  <section class="section">
    <div class="container">
      <div class="card glow auth-card" v-glow>
        <div class="badge">Recuperación</div>
        <h1 class="h1">Restablecer contraseña</h1>
        <p class="p">Introduce tu nueva contraseña.</p>

        <form class="form" @submit.prevent="submitReset">
          <div class="field">
            <label>Nueva contraseña</label>
            <input v-model="newPassword" type="password" required minlength="8" />
            <span class="hint">Mínimo 8 caracteres, una mayúscula y un número.</span>
          </div>

          <div class="field">
            <label>Confirmar contraseña</label>
            <input v-model="newPasswordConfirmation" type="password" required minlength="8" />
          </div>

          <p v-if="error" class="feedback error">{{ error }}</p>
          <p v-if="success" class="feedback success">{{ success }}</p>

          <button class="btn primary glow" type="submit" :disabled="loading" v-glow>
            {{ loading ? "Guardando..." : "Guardar nueva contraseña" }}
          </button>
        </form>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { auth } from "../services/auth";

const route = useRoute();
const router = useRouter();

const newPassword = ref("");
const newPasswordConfirmation = ref("");
const error = ref("");
const success = ref("");
const loading = ref(false);

async function submitReset() {
  const token = typeof route.query.token === "string" ? route.query.token.trim() : "";

  if (!token) {
    error.value = "El enlace de recuperación no es válido.";
    return;
  }

  loading.value = true;
  error.value = "";
  success.value = "";

  try {
    const response = await auth.resetPassword(token, newPassword.value, newPasswordConfirmation.value);
    success.value = response.message || "Contraseña actualizada.";

    setTimeout(() => {
      router.push("/");
    }, 1500);
  } catch (err) {
    error.value = err instanceof Error ? err.message : "No fue posible restablecer la contraseña.";
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.auth-card {
  max-width: 520px;
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
</style>
