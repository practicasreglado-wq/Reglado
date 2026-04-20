<!-- componente de Preguntas Frecuentes en pag Recursos -->
<template>
  <div class="card glow" v-glow v-reveal="{ from: 'up', delay: 70 }">
    <div class="h2" style="margin-bottom:10px;" v-reveal="{ from: 'up', delay: 110 }">Preguntas frecuentes</div>
    <div class="items">
      <details v-for="(it, i) in items" :key="i" class="item" v-reveal="{ from: 'up', delay: 140 + i * 40 }">
        <summary>{{ it.q }}</summary>
        <div class="ans">{{ it.a }}</div>
      </details>
    </div>
  </div>
</template>

<script setup>
defineProps({ items: { type: Array, required: true }});
</script>

<style scoped>
.items{ display:flex; flex-direction:column; gap: 10px; }
.item{ border: 1px solid rgba(255,255,255,.12); border-radius: 18px; background: rgba(255,255,255,.035); padding: 12px 14px; box-shadow: 0 14px 34px rgba(0,0,0,.22); }
summary{ cursor:pointer; font-weight: 900; list-style: none; } summary::-webkit-details-marker{ display:none; }
.ans{ color: rgba(233,238,246,.78); margin-top: 8px; line-height: 1.6; }

summary{ position:relative; padding-right: 26px; }
summary::after{
  content:"⌄";
  position:absolute; right: 0; top: 0;
  width: 24px; height: 24px;
  display:grid; place-items:center;
  border-radius: 999px;
  border: 0px solid rgba(242,197,61,.22);
  background: none;
  color: rgba(242,197,61,.95);
  transform: rotate(0deg);
  transition: transform .16s ease;
}
details[open] summary::after{ transform: rotate(180deg); }
</style>
