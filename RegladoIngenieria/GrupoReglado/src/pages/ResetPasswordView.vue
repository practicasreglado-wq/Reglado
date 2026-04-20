<template>
  <section class="form-page">
    <div class="form-card">
      <h1>Restablecer contraseña</h1>
      <p>Introduce tu nueva contraseña.</p>

      <form class="clean-form" @submit.prevent="submitReset">
        <label>
          Nueva contraseña
          <PasswordField v-model="newPassword" placeholder="********" required minlength="6" />
        </label>

        <label>
          Confirmar contraseña
          <PasswordField v-model="newPasswordConfirmation" placeholder="********" required minlength="6" />
        </label>

        <p v-if="error" class="feedback error">{{ error }}</p>
        <p v-if="success" class="feedback success">{{ success }}</p>

        <button class="btn-primary" type="submit" :disabled="loading">
          {{ loading ? "Guardando..." : "Guardar nueva contraseña" }}
        </button>
      </form>
    </div>
  </section>
</template>

<script setup>
import { ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import PasswordField from "../components/PasswordField.vue";
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
      router.push("/login");
    }, 1200);
  } catch (err) {
    error.value = err instanceof Error ? err.message : "No fue posible restablecer la contraseña.";
  } finally {
    loading.value = false;
  }
}
</script>

