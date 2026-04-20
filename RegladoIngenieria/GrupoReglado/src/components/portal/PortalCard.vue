<template>
  <article class="company-card">
    <div class="company-media">
      <img class="company-image" :src="image" :alt="`Vista de ${name}`" loading="lazy" />
      <div class="company-brand">
        <img class="company-logo" :src="logo" alt="Logo corporativo" loading="lazy" />
        <span class="company-tag">{{ tag }}</span>
      </div>
    </div>

    <div class="company-body">
      <h3>{{ name }}</h3>
      <p>{{ description }}</p>
      <a class="company-btn" :href="href" target="_blank" rel="noreferrer">Entrar</a>
    </div>
  </article>
</template>

<script setup>
defineProps({
  name: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    required: true,
  },
  href: {
    type: String,
    required: true,
  },
  image: {
    type: String,
    required: true,
  },
  logo: {
    type: String,
    required: true,
  },
  tag: {
    type: String,
    default: "Reglado Group",
  },
});
</script>
