<template>
  <div id="layout">
    <Header @open-login="openLogin" />
    <RouterView />
    <Footer />
    <CookieBanner />
    <LoginModal v-model="showLogin" @success="handleLoginSuccess" />
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { auth } from "@/services/auth.js";
import Header from "@/components/Header.vue";
import Footer from "@/components/Footer.vue";
import CookieBanner from "@/components/CookieBanner.vue";
import LoginModal from "@/components/LoginModal.vue";
import {
  consumeTokenFromFragment,
  wasSsoHandshakeFailed,
  clearSsoFailedFlag,
  clearHandshakeAttempt,
  wasHandshakeAttempted,
  redirectToHandshake,
} from "@/services/ssoClient.js";

const route = useRoute();
const router = useRouter();
const showLogin = ref(false);
const pendingReturnTo = ref("");

function openLogin() {
  showLogin.value = true;
}

function handleLoginSuccess() {
  if (pendingReturnTo.value) {
    const target = pendingReturnTo.value;
    pendingReturnTo.value = "";
    router.push(target);
  }
}

function handleVisibilityChange() {
  if (document.visibilityState !== "visible") return;

  if (auth.state.user) {
    // Revalida contra /auth/me por si el backend invalidó mientras oculta.
    auth.syncWithCookie();
  } else if (!wasHandshakeAttempted()) {
    // Sin sesión local y cooldown expirado: posiblemente el usuario acaba
    // de loguear en otro dominio. Reintentamos handshake.
    redirectToHandshake();
  }
}

watch(
  () => route.query.login,
  (flag) => {
    if (flag === "required") {
      const returnTo = typeof route.query.returnTo === "string" ? route.query.returnTo : "";
      pendingReturnTo.value = returnTo;
      showLogin.value = true;
      router.replace({ path: route.path, query: {} });
    }
  },
  { immediate: true }
);

async function bootstrapAuth() {
  // 1. Token cedido por el hub via fragmento (#token=...).
  const fragmentToken = consumeTokenFromFragment();
  if (fragmentToken) {
    auth.setSession(fragmentToken, null);
    clearHandshakeAttempt();
  }

  // 2. Limpiar flag sso_failed de la URL pero mantener la marca de intento.
  if (wasSsoHandshakeFailed()) {
    clearSsoFailedFlag();
  }

  // 3. Inicialización normal con token local.
  await auth.initialize();

  // 4. Si no hay sesión y no hemos preguntado al hub aún, handshake.
  if (!auth.state.user && !wasHandshakeAttempted()) {
    redirectToHandshake();
  }
}

onMounted(() => {
  bootstrapAuth();
  document.addEventListener("visibilitychange", handleVisibilityChange);
});

onBeforeUnmount(() => {
  document.removeEventListener("visibilitychange", handleVisibilityChange);
});
</script>

<style>
#layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}
</style>
