<template>
  <section id="contacto" class="contact-section">
    <div class="contact-heading">
      <h2>{{ title }}</h2>
      <p>{{ description }}</p>
    </div>

    <div class="contact-grid">
      <a class="contact-item" :href="`mailto:${email}`">
        <span class="contact-label">Email</span>
        <strong>{{ email }}</strong>
      </a>

      <a class="contact-item" :href="`tel:${sanitizedPhone}`">
        <span class="contact-label">Telefono</span>
        <strong>{{ phone }}</strong>
      </a>

      <a class="btn-primary whatsapp-btn" :href="whatsappUrl" target="_blank" rel="noreferrer">
        {{ whatsappLabel }}
      </a>
    </div>
  </section>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    required: true,
  },
  email: {
    type: String,
    required: true,
  },
  phone: {
    type: String,
    required: true,
  },
  whatsappUrl: {
    type: String,
    required: true,
  },
  whatsappLabel: {
    type: String,
    default: "Contactar por WhatsApp",
  },
});

const sanitizedPhone = computed(() => props.phone.replace(/\s+/g, ""));
</script>
