<script setup>
import { computed } from 'vue';

const props = defineProps({
  name: { type: String, required: true },
  size: { type: String, default: '1em' }
});

const icons = import.meta.glob('../assets/icons/*.svg', {
  query: '?raw',
  import: 'default',
  eager: true
});

const svg = computed(() => icons[`../assets/icons/${props.name}.svg`] || '');
</script>

<template>
  <span class="icon" :style="{ width: size, height: size }" v-html="svg" aria-hidden="true" />
</template>

<style scoped>
.icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
}
.icon :deep(svg) {
  width: 100%;
  height: 100%;
  display: block;
}
</style>
