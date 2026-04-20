<template>
  <section v-if="error" class="section">
    <div class="container">
      <div class="card soft auth-card">
        <h1 class="h2new">Acceso no disponible</h1>
        <p class="p">{{ error }}</p>
        <router-link to="/" class="btn primary">Ir al inicio</router-link>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { auth } from "../services/auth";

const route = useRoute();
const router = useRouter();
const error = ref("");

onMounted(async () => {
  const token = typeof route.query.token === "string" ? route.query.token : "";

  if (!token) {
    error.value = "No se encontró el token de acceso.";
    return;
  }

  try {
    auth.setSession(token, null);
    await auth.initialize();

    if (!auth.state.user) {
      throw new Error("No se pudo validar la sesión");
    }

    await router.replace("/");
  } catch (err) {
    auth.clearSession();
    error.value = err instanceof Error ? err.message : "No se pudo iniciar sesión";
  }
});
</script>

<style scoped>
.auth-card {
  max-width: 680px;
  margin: 0 auto;
  text-align: center;
  display: grid;
  gap: 14px;
}
</style>
