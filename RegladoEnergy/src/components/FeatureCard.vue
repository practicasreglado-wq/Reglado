<!-- componente de Tarjeta de Característica en pag Home, Servicios, Empresas y Sobre nosotros -->
<template>
  <div class="card glow" v-glow :class="{ soft }" v-reveal="reveal">
    <div class="top">
      <div class="icon">
        <img v-if="icon?.includes('/')" :src="icon" :alt="title" class="svg-icon" />
        <span v-else>{{ icon }}</span>
      </div>
      <div class="title">{{ title }}</div>
    </div>
    <p class="p">{{ text }}</p>
  </div>
</template>

<script setup>
defineProps({
  icon: { type: String, default: "⚡" },
  title: { type: String, required: true },
  text: { type: String, required: true },
  soft: { type: Boolean, default: false },
  reveal: { type: Object, default: () => ({}) }
});
</script>

<style scoped>
.top{ display:flex; align-items:center; gap: 10px; margin-bottom: 10px; }
.icon{ width: 36px; height: 36px; display:grid; place-items:center; border-radius: 14px; background: linear-gradient(180deg, rgba(242,197,61,.16), rgba(242,197,61,.06)); border: 1px solid rgba(242,197,61,.26); box-shadow: 0 10px 26px rgba(0,0,0,.18); }
.svg-icon { width: 70%; height: 70%; object-fit: contain; filter: blur(0.4px); }
.title{ font-weight: 900; letter-spacing: .2px; }
</style>
