<template>
  <section class="hero-shell" :style="heroStyle">
    <div class="hero-overlay"></div>
    <div class="hero-glow hero-glow-top"></div>
    <div class="hero-glow hero-glow-bottom"></div>

    <div class="hero-content">
      <p class="hero-kicker">{{ kicker }}</p>
      <h1>{{ title }}</h1>

      <p class="hero-subtitle" :aria-label="subtitle">
        <span
          v-for="(char, index) in subtitleChars"
          :key="`subtitle-${index}-${char}`"
          class="hero-char"
          :style="{ animationDelay: `${index * 0.018}s` }"
        >
          {{ char === " " ? "\u00A0" : char }}
        </span>
      </p>

      <a class="btn-primary hero-cta" :href="ctaTarget">{{ ctaText }}</a>
    </div>

    <div class="hero-watermark">{{ watermark }}</div>
  </section>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  subtitle: {
    type: String,
    required: true,
  },
  kicker: {
    type: String,
    default: "Portal corporativo",
  },
  watermark: {
    type: String,
    default: "Reglado Group",
  },
  ctaText: {
    type: String,
    default: "Explorar empresas",
  },
  ctaTarget: {
    type: String,
    default: "#empresas",
  },
  backgroundImage: {
    type: String,
    default: "",
  },
});

const subtitleChars = computed(() => Array.from(props.subtitle));

const heroStyle = computed(() => {
  if (!props.backgroundImage) {
    return {};
  }

  return {
    backgroundImage: `linear-gradient(110deg, rgba(18, 35, 60, 0.82), rgba(39, 61, 92, 0.55)), url('${props.backgroundImage}')`,
  };
});
</script>
