<template>
  <section class="section">
    <div class="container">
      <div class="card" style="max-width:560px; margin:0 auto; text-align:center; display:grid; gap:16px">
        <template v-if="error">
          <h1 class="h2">Acceso no disponible</h1>
          <p class="text-muted">{{ error }}</p>
          <router-link to="/" class="btn primary" style="margin:0 auto">Ir al inicio</router-link>
        </template>
        <template v-else>
          <p class="text-muted">Iniciando sesión...</p>
        </template>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { auth } from "@/services/auth.js";

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

    if (!auth.state.user) throw new Error("No se pudo validar la sesión.");

    await router.replace("/area-clientes");
  } catch (err) {
    auth.clearSession();
    error.value = err instanceof Error ? err.message : "No se pudo iniciar sesión.";
  }
});
</script>
