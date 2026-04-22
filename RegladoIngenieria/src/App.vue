<template>
  <div id="layout">
    <Header />
    <RouterView />
    <Footer />
    <CookieBanner />
  </div>
</template>

<script setup>
import { onMounted } from "vue";
import { auth } from "@/services/auth.js";
import Header from "@/components/Header.vue";
import Footer from "@/components/Footer.vue";
import CookieBanner from "@/components/CookieBanner.vue";

onMounted(() => {
  auth.initialize();
});
</script>

<style>
#layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}
</style>
