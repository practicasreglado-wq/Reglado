<template>
  <main>
    <section class="section-sm">
      <div class="container">
        <div class="area-header">
          <div>
            <span class="badge">Área privada</span>
            <h1 class="h2" style="margin-top:12px">Bienvenido, {{ auth.state.user?.name || "cliente" }}</h1>
          </div>
          <button class="btn ghost" @click="auth.logout()">Cerrar sesión</button>
        </div>
      </div>
    </section>

    <section class="section-sm bg-soft">
      <div class="container">
        <ParcelasContainer :token="auth.state.token" />
      </div>
    </section>
  </main>
</template>

<script setup>
import { auth } from "@/services/auth.js";
import ParcelasContainer from "@/components/ParcelasContainer.vue";
</script>

<style scoped>
.area-header { display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 16px; }
</style>
