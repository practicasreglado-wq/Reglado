<!--
  Endpoint de "vuelta" tras el login externo en GrupoReglado.

  Recibe el JWT en query string, lo guarda en auth.state (auth.js setSession)
  y redirige al usuario a la página de origen (returnTo) o /profile por defecto.

  Si llega sin token o con uno inválido, muestra mensaje de error.
-->
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
import { redirectToStore } from "../services/ssoClient";
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
    // El callback solo guarda el token y delega toda la carga real de perfil al store.
    auth.setSession(token, null);
    await userStore.initializeSession();

    if (!userStore.isLoggedIn) {
      throw new Error("No se pudo iniciar la sesión.");
    }

    // Antes de mostrar al usuario su dashboard, propagamos la sesión al
    // hub (Grupo). Así el resto de dominios del ecosistema heredan el
    // login sin necesidad de que el usuario vuelva a entrar en cada uno.
    // El hub redirige de vuelta a la URL que le pasamos (perfil/dashboard).
    const targetPath = userStore.userRole === "user" ? "/profile" : "/dashboard";
    const returnUrl = window.location.origin + targetPath;
    redirectToStore(token, returnUrl);
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

