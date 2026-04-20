<template>
  <section id="empresas" class="companies-section">
    <div class="section-heading">
      <p class="section-kicker">Ecosistema empresarial</p>
      <h2>{{ title }}</h2>
      <p>{{ subtitle }}</p>
    </div>

    <div class="companies-grid">
      <PortalCard
        v-for="portal in portals"
        :key="portal.name"
        :name="portal.name"
        :description="portal.description"
        :href="portal.href"
        :image="portal.image"
        :logo="portal.logo"
        :tag="portal.tag"
      />
    </div>
  </section>
</template>

<script setup>
import PortalCard from "./PortalCard.vue";

defineProps({
  title: {
    type: String,
    required: true,
  },
  subtitle: {
    type: String,
    required: true,
  },
  portals: {
    type: Array,
    required: true,
  },
});
</script>
