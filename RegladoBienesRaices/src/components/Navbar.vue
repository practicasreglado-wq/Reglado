<template>
  <header
    class="navbar"
    :class="[
      scrolled ? 'navbar--scrolled' : 'navbar--transparent'
    ]"
  >
    <div class="nav-container">
      <!-- Logo -->
      <a href="#hero" class="logo-link">
        <div class="logo-image-wrapper">
          <div class="nav-logo-img"></div>
        </div>
        <div class="logo-text-group">
          <span
            class="logo-text"
            :class="{ 'logo-text--dark': scrolled, 'logo-text--light': !scrolled }"
          >
            Reglado
          </span>
          <span
            class="logo-subtext"
            :class="{ 'logo-subtext--accent': scrolled, 'logo-subtext--gold': !scrolled }"
          >
            Bienes Raíces
          </span>
        </div>
      </a>

      <!-- Desktop Nav -->
      <nav class="nav-links">
        <a href="#que-hacemos" class="nav-link" :class="scrolled ? 'nav-link--dark' : 'nav-link--light'">
          Qué hacemos
        </a>
        <a href="#sobre-nosotros" class="nav-link" :class="scrolled ? 'nav-link--dark' : 'nav-link--light'">
          Sobre nosotros
        </a>
      </nav>

      <!-- Mobile Toggle -->
      <div class="navbar-actions">
        <button
          @click="mobileOpen = !mobileOpen"
          class="mobile-toggle"
          aria-label="Toggle menu"
        >
          <span
            class="hamburger-line"
            :class="[
              mobileOpen ? 'hamburger-line--top-open' : '',
              scrolled ? 'hamburger-line--dark' : 'hamburger-line--light'
            ]"
          />
          <span
            class="hamburger-line"
            :class="[
              mobileOpen ? 'hamburger-line--middle-open' : '',
              scrolled ? 'hamburger-line--dark' : 'hamburger-line--light'
            ]"
          />
          <span
            class="hamburger-line"
            :class="[
              mobileOpen ? 'hamburger-line--bottom-open' : '',
              scrolled ? 'hamburger-line--dark' : 'hamburger-line--light'
            ]"
          />
        </button>
      </div>
    </div>

    <!-- Mobile Menu -->
    <Transition name="mobile-fade">
      <div
        v-if="mobileOpen"
        class="mobile-menu"
      >
        <nav class="mobile-nav">
          <a href="#hero" @click="mobileOpen = false" class="mobile-nav-link">Inicio</a>
          <a href="#que-hacemos" @click="mobileOpen = false" class="mobile-nav-link">Qué hacemos</a>
          <a href="#sobre-nosotros" @click="mobileOpen = false" class="mobile-nav-link">Sobre nosotros</a>
        </nav>
      </div>
    </Transition>
  </header>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const scrolled = ref(false)
const mobileOpen = ref(false)

let cachedTriggerPoint = null

const getTriggerTarget = () => {
  if (window.location.hash === '#aviso-legal') {
    return document.querySelector('.legal-content')
  }

  return document.getElementById('que-hacemos')
}

const recalculateTrigger = () => {
  const triggerTarget = getTriggerTarget()
  cachedTriggerPoint = triggerTarget ? triggerTarget.offsetTop - 120 : 60
}

const handleScroll = () => {
  if (cachedTriggerPoint === null) {
    recalculateTrigger()
  }
  scrolled.value = window.scrollY >= cachedTriggerPoint
}

const handleResize = () => {
  cachedTriggerPoint = null
  handleScroll()
}

const handleHashChange = () => {
  mobileOpen.value = false
  cachedTriggerPoint = null
  handleScroll()
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll, { passive: true })
  window.addEventListener('resize', handleResize, { passive: true })
  window.addEventListener('hashchange', handleHashChange)
  handleScroll()
})
onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
  window.removeEventListener('resize', handleResize)
  window.removeEventListener('hashchange', handleHashChange)
})
</script>

<style scoped>
.navbar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 50;
  transition: all 0.5s ease;
}

.navbar--scrolled {
  background-color: rgba(255, 255, 255, 0.85); /* Slightly more opaque */
  -webkit-backdrop-filter: blur(8px);
  backdrop-filter: blur(8px); /* Reduced from 16px to stop GPU lag */
  border-bottom: 1px solid #f5f5f4;
  padding: 1rem 0;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.navbar--transparent {
  background-color: transparent;
  padding: 2.25rem 0;
}

@media (max-width: 768px) {
  .navbar--transparent {
    padding: 1.5rem 0;
  }
}

.nav-container {
  max-width: 80rem;
  margin: 0 auto;
  padding: 0 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

@media (min-width: 1024px) {
  .nav-container {
    padding: 0 3rem;
  }
}

.logo-link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  text-decoration: none;
}

.logo-image-wrapper {
  display: flex;
  align-items: center;
}

.nav-logo-img {
  height: 2.75rem;
  width: 3.5rem;
  background-color: var(--white);
  -webkit-mask-image: url("@/img/RBRaices_Logo.png");
  mask-image: url("@/img/RBRaices_Logo.png");
  -webkit-mask-size: contain;
  mask-size: contain;
  -webkit-mask-repeat: no-repeat;
  mask-repeat: no-repeat;
  -webkit-mask-position: center;
  mask-position: center;
  transform-origin: center;
  transition: transform 0.7s cubic-bezier(0.22, 1, 0.36, 1), background-color 0.4s ease, filter 0.4s ease;
}

.navbar--scrolled .nav-logo-img {
  background-color: var(--gold-800); /* Precise dark gold without rainbow issues */
}

.logo-link:hover .nav-logo-img {
  transform: rotate(180deg) scale(1.05);
  filter: drop-shadow(0 0.35rem 0.8rem rgba(201, 152, 26, 0.18));
}

@media (prefers-reduced-motion: reduce) {
  .nav-logo-img {
    transition: background-color 0.4s ease;
  }

  .logo-link:hover .nav-logo-img {
    transform: none;
    filter: none;
  }
}

.logo-text-group {
  display: flex;
  flex-direction: column;
}

.logo-text {
  font-family: var(--font-display);
  font-size: 1.5rem;
  font-weight: 600;
  letter-spacing: 0.025em;
  transition: color 0.3s ease;
  line-height: 1;
}

@media (max-width: 480px) {
  .logo-text {
    font-size: 1.25rem;
  }
}

.logo-text--dark {
  color: var(--ink-default);
}

.logo-text--light {
  color: var(--white);
}

.logo-subtext {
  font-family: var(--font-mono);
  font-size: 11px;
  letter-spacing: var(--letter-spacing-widest3);
  text-transform: uppercase;
  transition: color 0.3s ease;
}

@media (max-width: 480px) {
  .logo-subtext {
    font-size: 9px;
  }
}

.logo-subtext--accent {
  color: var(--gold-800); /* Much more visible on white */
}

.logo-subtext--gold {
  color: var(--gold-300);
}

.nav-links {
  display: none;
  align-items: center;
  gap: 2.5rem;
}

@media (min-width: 768px) {
  .nav-links {
    display: flex;
  }
}

.nav-link {
  font-family: var(--font-body);
  font-size: 0.875rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  transition: color 0.3s ease;
  position: relative;
  text-decoration: none;
}

.nav-link::after {
  content: '';
  position: absolute;
  left: 0;
  bottom: -0.25rem;
  height: 1px;
  width: 0;
  background-color: var(--gold-500);
  transition: all 0.3s ease;
}

.nav-link:hover::after {
  width: 100%;
}

.nav-link--dark {
  color: rgba(17, 16, 16, 0.7);
}

.nav-link--dark:hover {
  color: var(--ink-default);
}

.nav-link--light {
  color: rgba(255, 255, 255, 0.75);
}

.nav-link--light:hover {
  color: var(--white);
}

.navbar-actions {
  display: flex;
  align-items: center;
}

.mobile-toggle {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
  padding: 0.25rem;
  background: none;
  border: none;
  cursor: pointer;
}

@media (min-width: 768px) {
  .mobile-toggle {
    display: none;
  }
}

.hamburger-line {
  display: block;
  width: 1.5rem;
  height: 1px;
  transition: all 0.3s ease;
}

.hamburger-line--dark {
  background-color: var(--ink-default);
}

.hamburger-line--light {
  background-color: var(--white);
}

.hamburger-line--top-open {
  transform: translateY(0.5rem) rotate(45deg);
}

.hamburger-line--middle-open {
  opacity: 0;
}

.hamburger-line--bottom-open {
  transform: translateY(-0.5rem) rotate(-45deg);
}

.mobile-menu {
  background-color: var(--white);
  border-top: 1px solid #f5f5f4;
  padding: 2rem 1.5rem;
}

@media (min-width: 768px) {
  .mobile-menu {
    display: none;
  }
}

.mobile-nav {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.mobile-nav-link {
  font-family: var(--font-body);
  font-size: 0.875rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: rgba(17, 16, 16, 0.7);
  text-decoration: none;
  transition: color 0.3s ease;
}

.mobile-nav-link:hover {
  color: var(--gold-600);
}

/* Transitions */
.mobile-fade-enter-active {
  transition: all 0.3s ease;
}

.mobile-fade-enter-from {
  opacity: 0;
  transform: translateY(-0.5rem);
}

.mobile-fade-leave-active {
  transition: all 0.2s ease;
}

.mobile-fade-leave-to {
  opacity: 0;
}
</style>
