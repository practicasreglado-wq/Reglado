<template>
  <SsoLayout :error="state === 'invalid' ? 'El enlace de sincronización no es válido.' : ''" />
</template>

<script setup>
import { onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import { auth } from "../services/auth";
import SsoLayout from "../components/SsoLayout.vue";
import {
  isAllowedReturnUrl,
  buildReturnUrlWithParams,
  buildReturnUrlWithTokenFragment,
} from "../services/ssoHub";

const route = useRoute();
const state = ref("processing");

onMounted(async () => {
  const returnUrl = typeof route.query.return === "string" ? route.query.return : "";

  if (!isAllowedReturnUrl(returnUrl)) {
    state.value = "invalid";
    return;
  }

  // Recupera el token local de Grupo (localStorage o cookie compartida del
  // propio dominio) y lo valida con /auth/me antes de cederlo. Así evitamos
  // pasar tokens caducados / revocados a otros dominios.
  await auth.initialize();

  if (auth.state.user && auth.state.token) {
    const next = buildReturnUrlWithTokenFragment(returnUrl, auth.state.token);
    window.location.replace(next);
    return;
  }

  // No hay sesión válida en Grupo — se lo comunicamos al origen para que
  // sepa que debe pintar la vista de invitado y no reintentar el handshake.
  const failed = buildReturnUrlWithParams(returnUrl, { sso_failed: 1 });
  window.location.replace(failed);
});
</script>
