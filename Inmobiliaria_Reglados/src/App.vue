<template>
  <div id="app">
    <Header />
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
  </div>
</template>

<script>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import Header from "./components/Header.vue";
import Footer from "./components/Footer.vue";
import ScrollToTop from "./components/ScrollToTop.vue";
import { useUserStore } from "./stores/user";
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
  },

  setup() {
    const userStore = useUserStore();
    const router = useRouter();
    const route = useRoute();
    const skipNextTransition = ref(false);
    let removeBeforeHook;
    let removeAfterHook;

    const refreshAnimations = () => {
      refreshRevealElements(document.querySelector(".main-content") || document);
    };

    const isProfileRoute = (route) => route.path.startsWith("/profile");

    const handleRouteEntered = () => {
      refreshAnimations();
    };

    const isProfileNavigation = (to, from) =>
      to.path.startsWith("/profile") && from.path.startsWith("/profile");

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

    onMounted(async () => {
      initRevealSystem();

      removeBeforeHook = router.beforeEach((to, from, next) => {
        skipNextTransition.value = consumeSkipNextTransition();
        next();
      });

      removeAfterHook = router.afterEach((to, from) => {
        if (userStore.isLoggedIn) {
          userStore.initializeSession().catch(err => console.error("Error refreshing session:", err));
        }

        nextTick(() => {
          refreshAnimations();
          skipNextTransition.value = false;
        });
      });

      try {
        await userStore.initializeSession();
      } catch (err) {
        console.error("Error cargando sesion:", err);
      } finally {
        refreshAnimations();
      }
    });

    onBeforeUnmount(() => {
      removeBeforeHook?.();
      removeAfterHook?.();
      teardownRevealSystem();
    });

    return {
      handleRouteEntered,
      pageTransitionKey,
      pageTransitionName,
    };
  },
};
</script>
