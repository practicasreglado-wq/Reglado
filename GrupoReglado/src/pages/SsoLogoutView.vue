<template>
  <SsoLayout
    :message="'Cerrando sesión…'"
    :error="state === 'invalid' ? 'El enlace de cierre de sesión no es válido.' : ''"
  />
</template>

<script setup>
import { onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import { auth } from "../services/auth";
import SsoLayout from "../components/SsoLayout.vue";
import { isAllowedReturnUrl } from "../services/ssoHub";

const route = useRoute();
const state = ref("processing");

onMounted(() => {
  const returnUrl = typeof route.query.return === "string" ? route.query.return : "";

  if (!isAllowedReturnUrl(returnUrl)) {
    state.value = "invalid";
    return;
  }

  // No llamamos a auth.logout() del backend: el dominio origen ya revocó
  // el token. Aquí solo limpiamos el almacenamiento local de Grupo para
  // que la cookie y localStorage queden vacíos tras el cierre distribuido.
  auth.clearSession();
  window.location.replace(returnUrl);
});
</script>
