<template>
  <div id="app">
    <Header @open-login="showLogin = true" />
    <main class="main-content">
      <router-view v-slot="{ Component, route }">
        <transition
          :name="pageTransitionName(route)"
          mode="out-in"
          @after-enter="handleRouteEntered"
        >
          <div :key="pageTransitionKey(route)" class="page-shell">
            <component :is="Component" />
          </div>
        </transition>
      </router-view>
    </main>
    <Footer />
    <ScrollToTop />
    <LoginModal v-model="showLogin" @success="handleLoginSuccess" />
  </div>
</template>

<script>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import Header from "./components/Header.vue";
import Footer from "./components/Footer.vue";
import ScrollToTop from "./components/ScrollToTop.vue";
import LoginModal from "./components/LoginModal.vue";
import { useUserStore } from "./stores/user";
import { auth } from "./services/auth";
import {
  consumeTokenFromFragment,
  wasSsoHandshakeFailed,
  clearSsoFailedFlag,
  clearHandshakeAttempt,
  wasHandshakeAttempted,
  redirectToHandshake,
} from "./services/ssoClient";
import {
  initRevealSystem,
  refreshRevealElements,
  teardownRevealSystem,
} from "./utils/reveal";

export default {
  components: {
    Header,
    Footer,
    ScrollToTop,
    LoginModal,
  },

  setup() {
    const userStore = useUserStore();
    const router = useRouter();
    const route = useRoute();
    const skipNextTransition = ref(false);
    const showLogin = ref(false);
    const pendingReturnTo = ref("");
    let removeBeforeHook;
    let removeAfterHook;

    // Cuando el router guard intercepta una ruta protegida sin sesión,
    // redirige a / con ?login=required&returnTo=ruta-original. Aquí
    // detectamos el flag y abrimos el modal; tras login navegamos a returnTo.
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

    const handleLoginSuccess = () => {
      // El LoginModal redirige al hub via redirectToStore, así que esta
      // navegación solo aplica si el flujo se ejecuta sin redirect (p.ej.
      // si en el futuro el modal deja de propagar). Por ahora es defensivo.
      if (pendingReturnTo.value) {
        const target = pendingReturnTo.value;
        pendingReturnTo.value = "";
        router.push(target);
      }
    };

    const refreshAnimations = () => {
      refreshRevealElements(document.querySelector(".main-content") || document);
    };

    const isProfileRoute = (route) => route.path.startsWith("/profile");

    const handleRouteEntered = () => {
      refreshAnimations();
    };

    const consumeSkipNextTransition = () => {
      if (typeof window === "undefined") {
        return false;
      }

      const shouldSkip =
        window.sessionStorage.getItem("skip-next-page-transition") === "true";

      if (shouldSkip) {
        window.sessionStorage.removeItem("skip-next-page-transition");
      }

      return shouldSkip;
    };

    const pageTransitionName = (route) =>
      isProfileRoute(route) || skipNextTransition.value ? "" : "page-transition";

    const pageTransitionKey = (route) =>
      isProfileRoute(route) ? "/profile" : route.fullPath;

    const handleVisibilityChange = () => {
      if (document.visibilityState !== "visible") return;

      if (userStore.isLoggedIn) {
        // Sesión local presente — revalida vía /auth/me por si el backend
        // la invalidó (logout remoto, ban, force-logout, kick-old).
        auth.syncWithCookie().catch((err) =>
          console.error("syncWithCookie error:", err)
        );
      } else if (!wasHandshakeAttempted()) {
        // Sin sesión local y fuera del cooldown del último intento fallido:
        // puede que el usuario acabe de loguear en otro dominio del ecosistema.
        // Redirigimos al handshake del hub para capturar esa sesión.
        redirectToHandshake();
      }
    };

    onMounted(async () => {
      initRevealSystem();

      removeBeforeHook = router.beforeEach((to, from, next) => {
        skipNextTransition.value = consumeSkipNextTransition();
        next();
      });

      removeAfterHook = router.afterEach(() => {
        if (userStore.isLoggedIn) {
          userStore.initializeSession().catch((err) =>
            console.error("Error refreshing session:", err)
          );
        }

        nextTick(() => {
          refreshAnimations();
          skipNextTransition.value = false;
        });
      });

      // Bootstrap SSO Hub: si venimos del hub con #token=... lo consumimos
      // antes de inicializar la sesión, para que initializeSession lo
      // encuentre directamente en localStorage/cookie.
      const fragmentToken = consumeTokenFromFragment();
      if (fragmentToken) {
        auth.setSession(fragmentToken, null);
        clearHandshakeAttempt();
      }

      // Si el hub respondió `sso_failed=1` en una navegación anterior,
      // limpiamos solo el flag de la URL (mantenemos el cooldown de
      // sessionStorage para evitar bucles).
      if (wasSsoHandshakeFailed()) {
        clearSsoFailedFlag();
      }

      try {
        await userStore.initializeSession();
      } catch (err) {
        console.error("Error cargando sesion:", err);
      } finally {
        refreshAnimations();
      }

      // Si tras todo lo anterior no hay sesión y no hemos preguntado al
      // hub aún, intentamos handshake automático para capturar la sesión
      // que pueda haber en otro dominio del ecosistema.
      if (!userStore.isLoggedIn && !wasHandshakeAttempted()) {
        redirectToHandshake();
      }

      document.addEventListener("visibilitychange", handleVisibilityChange);
    });

    onBeforeUnmount(() => {
      removeBeforeHook?.();
      removeAfterHook?.();
      document.removeEventListener("visibilitychange", handleVisibilityChange);
      teardownRevealSystem();
    });

    return {
      handleRouteEntered,
      pageTransitionKey,
      pageTransitionName,
      showLogin,
      handleLoginSuccess,
    };
  },
};
</script>