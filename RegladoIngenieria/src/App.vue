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
  if (document.visibilityState === "visible") {
    auth.syncWithCookie();
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

onMounted(() => {
  auth.initialize();
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
