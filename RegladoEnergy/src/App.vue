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

const isCTAClosed = ref(false);
const showLogin = ref(false);

function handleVisibilityChange() {
  if (document.visibilityState === "visible") {
    auth.syncWithCookie();
  }
}

onMounted(() => {
  auth.initialize();
  document.addEventListener("visibilitychange", handleVisibilityChange);
});

onBeforeUnmount(() => {
  document.removeEventListener("visibilitychange", handleVisibilityChange);
});
</script>

