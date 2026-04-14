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

  // Inicialización del tema (Modo Oscuro/Claro) desde Cookies
  const savedTheme = auth.getCookie("reglado_theme");
  const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;

  if (savedTheme === "dark" || (!savedTheme && prefersDark)) {
    document.body.classList.add("dark-mode");
  } else if (savedTheme === "light") {
    document.body.classList.remove("dark-mode");
  }
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
