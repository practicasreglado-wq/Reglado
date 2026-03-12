<script setup>
import Navbar from './components/Navbar.vue'
import HeroSection from './components/HeroSection.vue'
import ServicesSection from './components/ServicesSection.vue'
import AboutSection from './components/AboutSection.vue'
import StatsSection from './components/StatsSection.vue'
import ContactSection from './components/ContactSection.vue'
import FooterSection from './components/FooterSection.vue'
import WhatsAppButton from './components/WhatsAppButton.vue'
</script>

<template>
  <main>
    <Navbar />
    <HeroSection />
    <ServicesSection />
    <AboutSection />
    <StatsSection />
    <ContactSection />
    <FooterSection />
    <WhatsAppButton />
  </main>
</template>

<style scoped>
main {
  width: 100%;
}
</style>
