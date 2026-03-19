<template>
  <div id="app">
    <LoadingScreen :visible="showLoader" />
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
import LoadingScreen from "./components/LoadingScreen.vue";
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
    LoadingScreen,
  },

  setup() {
    const userStore = useUserStore();
    const router = useRouter();
    const route = useRoute();
    const isBootLoading = ref(true);
    const isRouteLoading = ref(false);
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

    const consumeSkipNextLoader = () => {
      if (typeof window === "undefined") {
        return false;
      }

      const shouldSkip = window.sessionStorage.getItem("skip-next-loader") === "true";

      if (shouldSkip) {
        window.sessionStorage.removeItem("skip-next-loader");
      }

      return shouldSkip;
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

    onMounted(async () => {
      initRevealSystem();

      removeBeforeHook = router.beforeEach((to, from, next) => {
        const skipLoader = consumeSkipNextLoader();
        skipNextTransition.value = consumeSkipNextTransition();

        if (
          to.fullPath !== from.fullPath &&
          !isProfileNavigation(to, from) &&
          !skipLoader
        ) {
          isRouteLoading.value = true;
        }

        next();
      });

      removeAfterHook = router.afterEach((to, from) => {
        if (userStore.isLoggedIn) {
          userStore.initializeSession().catch(err => console.error("Error refreshing session:", err));
        }

        nextTick(() => {
          refreshAnimations();
          skipNextTransition.value = false;

          if (!isProfileNavigation(to, from)) {
            window.setTimeout(() => {
              isRouteLoading.value = false;
            }, 180);
            return;
          }

          isRouteLoading.value = false;
        });
      });
      try {
        await userStore.initializeSession();
      } catch (err) {
        console.error("Error cargando sesion:", err);
      } finally {
        await nextTick();
        window.setTimeout(() => {
          isBootLoading.value = false;
          refreshAnimations();
        }, 260);
      }
    });

    onBeforeUnmount(() => {
      removeBeforeHook?.();
      removeAfterHook?.();
      teardownRevealSystem();
    });

    const showLoader = computed(() => {
      if (route.path.startsWith("/profile")) {
        return false;
      }

      return isBootLoading.value || isRouteLoading.value;
    });

    return {
      handleRouteEntered,
      pageTransitionKey,
      pageTransitionName,
      showLoader,
    };
  },
};
</script>