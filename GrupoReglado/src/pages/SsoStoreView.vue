<template>
  <SsoLayout :error="state === 'invalid' ? 'El enlace de sincronización no es válido.' : ''" />
</template>

<script setup>
import { onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import { auth } from "../services/auth";
import SsoLayout from "../components/SsoLayout.vue";
import { isAllowedReturnUrl } from "../services/ssoHub";

const route = useRoute();
const state = ref("processing");

onMounted(async () => {
  const returnUrl = typeof route.query.return === "string" ? route.query.return : "";
  const token = typeof route.query.token === "string" ? route.query.token : "";

  if (!isAllowedReturnUrl(returnUrl) || token === "") {
    state.value = "invalid";
    return;
  }

  // Validamos el token recibido contra /auth/me antes de guardarlo en el
  // almacenamiento de Grupo. Si el backend lo rechaza, no queremos dejar
  // un token inválido ocupando el slot de la cookie compartida.
  auth.setSession(token, null);
  try {
    await auth.initialize();
    if (!auth.state.user) {
      auth.clearSession();
    }
  } catch {
    auth.clearSession();
  }

  // Redirect incondicional: si el token no era válido la sesión queda
  // limpia pero igualmente devolvemos al usuario a donde venía — no es
  // nuestro trabajo informar del fallo, el origen ya tiene su copia.
  window.location.replace(returnUrl);
});
</script>
