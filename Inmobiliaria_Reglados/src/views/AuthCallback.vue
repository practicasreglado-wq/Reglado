<template>
  <div class="auth-callback">
    <p v-if="error">{{ error }}</p>
    <p v-else>Accediendo...</p>
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { auth } from "../services/auth";
import { useUserStore } from "../stores/user";

const route = useRoute();
const router = useRouter();
const userStore = useUserStore();
const error = ref("");

onMounted(async () => {
  const token = typeof route.query.token === "string" ? route.query.token.trim() : "";

  if (!token) {
    error.value = "No se encontró el token de acceso.";
    return;
  }

  try {
    auth.setSession(token, null);
    await userStore.initializeSession();

    if (!userStore.isLoggedIn) {
      throw new Error("No se pudo iniciar la sesión.");
    }

    router.replace("/dashboard");
  } catch (err) {
    auth.clearSession();
    userStore.logoutLocal();
    error.value = err instanceof Error ? err.message : "No se pudo completar el acceso.";
  }
});
</script>

<style scoped>
.auth-callback {
  min-height: calc(100vh - 180px);
  display: grid;
  place-items: center;
  font-size: 1.2rem;
}
</style>
