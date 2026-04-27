<template>
  <main>
    <section class="section">
      <div class="container">
        <div class="card" style="max-width:560px; margin:0 auto; text-align:center; display:grid; gap:16px; padding:48px">
          <div style="font-size:2rem"><Icon name="wrench" /></div>
          <h1 class="h2">Panel de Administración</h1>
          <p class="text-muted">Esta sección está en construcción. Próximamente podrás gestionar consultas, usuarios y contenidos desde aquí.</p>
          <router-link to="/" class="btn outline" style="margin:0 auto">Volver al inicio</router-link>
        </div>
      </div>
    </section>
  </main>
</template>

<script setup>
import Icon from "@/components/Icon.vue";
</script>
