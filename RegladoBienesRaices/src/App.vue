<template>
  <div class="app-wrapper">
    <Navbar />
    <main>
      <LegalNotice v-if="currentHash === '#aviso-legal'" />
      <LandingPage v-else />
    </main>
    <Footer />

    <!-- Botón elegante Volver Arriba -->
    <Transition name="fade">
      <button 
        v-if="showScrollTop" 
        ref="scrollTopButton"
        @click="scrollToTop" 
        class="scroll-to-top"
        :class="{ 'scroll-to-top--light': isOverFooter }"
        aria-label="Volver arriba"
      >
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
        </svg>
      </button>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import Navbar from './components/Navbar.vue'
import LandingPage from './views/LandingPage.vue'
import LegalNotice from './views/LegalNotice.vue'
import Footer from './components/Footer.vue'

const currentHash = ref(window.location.hash)
const showScrollTop = ref(false)
const isOverFooter = ref(false)
const scrollTopButton = ref(null)

const updateHash = async () => {
  currentHash.value = window.location.hash
  
  // Wait for Vue to physically swap out the views (e.g., from LegalNotice to LandingPage)
  await nextTick()

  if (!currentHash.value || currentHash.value === '#') {
    window.scrollTo(0, 0)
    return
  }

  try {
    const targetElement = document.querySelector(currentHash.value)
    if (targetElement) {
      targetElement.scrollIntoView()
    } else {
      window.scrollTo(0, 0) // Default fallback (e.g. #aviso-legal doesn't have an ID, so scrolls to top)
    }
  } catch (e) {
    window.scrollTo(0, 0)
  }
}

const updateScrollTopFooterState = () => {
  const footerEl = document.querySelector('.footer')
  const buttonEl = scrollTopButton.value

  if (!footerEl || !buttonEl || !showScrollTop.value) {
    isOverFooter.value = false
    return
  }

  const footerRect = footerEl.getBoundingClientRect()
  const buttonRect = buttonEl.getBoundingClientRect()
  const buttonMidpoint = buttonRect.top + buttonRect.height / 2

  isOverFooter.value = footerRect.top <= buttonMidpoint
}

const checkScroll = async () => {
  showScrollTop.value = window.scrollY > window.innerHeight * 0.5
  await nextTick()
  updateScrollTopFooterState()
}

const scrollToTop = () => {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

onMounted(async () => {
  window.addEventListener('hashchange', updateHash)
  window.addEventListener('scroll', checkScroll, { passive: true })
  window.addEventListener('resize', updateScrollTopFooterState, { passive: true })
  await checkScroll()
})

onUnmounted(() => {
  window.removeEventListener('hashchange', updateHash)
  window.removeEventListener('scroll', checkScroll)
  window.removeEventListener('resize', updateScrollTopFooterState)
})
</script>

<style>
.app-wrapper {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

main {
  flex-grow: 1;
}

/* Scroll To Top Button */
.scroll-to-top {
  position: fixed;
  bottom: 2.5rem;
  right: 2.5rem;
  z-index: 90;
  width: 3.5rem;
  height: 3.5rem;
  background-color: rgba(17, 16, 16, 0.78);
  color: var(--gold-500);
  border: 1px solid rgba(201, 163, 74, 0.3); /* Subtle gold border */
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5); /* Strong dark shadow to float above light sections */
  transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
  padding: 0.875rem;
  backdrop-filter: blur(6px);
}

.scroll-to-top:hover {
  transform: translateY(-6px);
  background-color: #000000;
  color: var(--white);
  border-color: #c9a34a;
  box-shadow: 0 15px 30px -5px rgba(201, 163, 74, 0.3); /* Premium gold glow when hovered */
}

/* Dynamic Light Mode when hovering over Dark Footer */
.scroll-to-top--light {
  background-color: rgba(255, 255, 255, 0.68);
  color: var(--ink-default);
  border-color: var(--gold-500);
  box-shadow: 0 10px 25px -5px rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(6px);
}

.scroll-to-top--light:hover {
  background-color: rgba(255, 255, 255, 0.9);
  color: var(--ink-default);
  border-color: var(--gold-500);
  box-shadow: 0 12px 28px -5px rgba(255, 255, 255, 0.3);
}

.scroll-to-top svg {
  width: 100%;
  height: 100%;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s ease, transform 0.5s cubic-bezier(0.165, 0.84, 0.44, 1);
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(1.5rem) scale(0.9);
}

@media (max-width: 768px) {
  .scroll-to-top {
    bottom: 1.5rem;
    right: 1.5rem;
    width: 3rem;
    height: 3rem;
    padding: 0.75rem;
  }
}
</style>
