<template>
  <div class="app-shell">
    <SiteHeader :user="auth.state.user" @open-login="showLogin = true" />
    <main class="main">
      <div class="page-wrapper">
        <router-view v-slot="{ Component, route }">
          <div v-if="Component" class="route-content" :key="route.fullPath">
            <component :is="Component" />
          </div>
          <section v-else class="section">
          <div class="container">
            <div class="card glow" v-glow>
              <h1 class="h1">Cargando…</h1>
              <p class="p">Si ves esto de forma permanente, hay un error en la ruta o en la carga del componente.</p>
            </div>
          </div>
          </section>
        </router-view>
      </div>
    </main>
    <SiteFooter />
    <CTASticky @close="isCTAClosed = true" />
    <ScrollTopButton :isCTAClosed="isCTAClosed" />
    <CookieBanner />
    <LoginModal v-model="showLogin" />
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from "vue";
import SiteHeader from "./components/SiteHeader.vue";
import SiteFooter from "./components/SiteFooter.vue";
import CTASticky from "./components/CTASticky.vue";
import ScrollTopButton from "./components/ScrollTopButton.vue";
import CookieBanner from "./components/CookieBanner.vue";
import LoginModal from "./components/LoginModal.vue";
import { auth } from "./services/auth";
import {
  consumeTokenFromFragment,
  wasSsoHandshakeFailed,
  clearSsoFailedFlag,
  clearHandshakeAttempt,
  wasHandshakeAttempted,
  redirectToHandshake,
} from "./services/ssoClient";

const isCTAClosed = ref(false);
const showLogin = ref(false);

function handleVisibilityChange() {
  if (document.visibilityState !== "visible") return;

  if (auth.state.user) {
    // Sesión local presente — revalida contra /auth/me por si el backend
    // la invalidó mientras la pestaña estaba oculta (logout remoto, ban...).
    auth.syncWithCookie();
  } else if (!wasHandshakeAttempted()) {
    // Sin sesión local y fuera del cooldown del último intento fallido:
    // puede que el usuario acabe de loguear en otro dominio del ecosistema.
    // Reintentamos el handshake para capturar esa sesión.
    redirectToHandshake();
  }
}

async function bootstrapAuth() {
  // 1. Token cedido por el hub via fragmento (#token=...) — lo guardamos
  //    antes de cualquier otra cosa y limpiamos la marca de intento.
  const fragmentToken = consumeTokenFromFragment();
  if (fragmentToken) {
    auth.setSession(fragmentToken, null);
    clearHandshakeAttempt();
  }

  // 2. Si el hub dijo `sso_failed=1` en una navegación anterior, limpiamos
  //    el flag de la URL para que no quede sucio. MANTENEMOS la marca de
  //    handshake intentado en sessionStorage (si no, entraríamos en bucle
  //    en el siguiente ciclo). No hacemos early-return aquí: el usuario
  //    puede haber completado un login localmente que tenemos que hidratar.
  if (wasSsoHandshakeFailed()) {
    clearSsoFailedFlag();
  }

  // 3. Inicialización normal con el token local (puede haber venido del
  //    fragmento que acabamos de consumir, de localStorage previo, o de
  //    la cookie del propio dominio).
  await auth.initialize();

  // 4. Si tras todo lo anterior no hay sesión y todavía no hemos preguntado
  //    al hub en esta pestaña, iniciamos el handshake.
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

