<template>
  <div class="parcelas-wrap">
    <div class="card placeholder-card">
      <div class="placeholder-icon"><Icon name="gear" /></div>
      <h2 class="h3">Consulta de Parcelas Industriales</h2>
      <p class="text-muted" style="margin-top:8px">Esta funcionalidad está siendo integrada y estará disponible próximamente.</p>
      <p class="hint">Cuando esté lista, se montará aquí automáticamente usando el token de sesión.</p>
    </div>
  </div>
</template>

<script setup>
import Icon from "@/components/Icon.vue";

defineProps({ token: { type: String, required: true } });
</script>

<style scoped>
.parcelas-wrap { width: 100%; min-height: 400px; display: flex; align-items: center; justify-content: center; }
.placeholder-card { text-align: center; max-width: 480px; display: grid; gap: 8px; }
.placeholder-icon { font-size: 2.5rem; margin-bottom: 8px; }
.hint { font-size: 0.8125rem; color: var(--text-muted); margin-top: 8px; border-top: 1px solid var(--border); padding-top: 12px; }
</style>
