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
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { RouterView } from "vue-router";
import LoginModal from "./components/LoginModal.vue";
import SiteFooter from "./components/SiteFooter.vue";
import SiteHeader from "./components/SiteHeader.vue";
import { auth } from "./services/auth";

const showLogin = ref(false);

onMounted(() => {
  auth.initialize();
});

function handleLogout() {
  auth.logout();
}
</script>
