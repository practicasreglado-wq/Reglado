<template>
  <div class="app-shell">
    <SiteHeader
      :user="auth.state.user"
      @open-login="showLogin = true"
      @logout="handleLogout"
    />

    <main class="content">
      <RouterView />
    </main>

    <SiteFooter />

    <LoginModal
      :open="showLogin"
      @close="showLogin = false"
      @success="showLogin = false"
    />
    <CookieBanner />
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { RouterView, useRouter } from "vue-router";
import LoginModal from "./components/LoginModal.vue";
import SiteFooter from "./components/SiteFooter.vue";
import SiteHeader from "./components/SiteHeader.vue";
import CookieBanner from "./components/CookieBanner.vue";
import { auth } from "./services/auth";

const showLogin = ref(false);
const router = useRouter();

onMounted(() => {
  auth.initialize();
});

async function handleLogout() {
  try {
    await auth.logout();
    router.push("/");
  } catch (error) {
    console.error("Error during logout:", error);
    window.location.href = "/";
  }
}
</script>
