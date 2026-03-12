<!-- 
  Barra de navegación principal. Incluye logo, enlaces de navegación, 
-->
<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const isScrolled = ref(false)
const isMobileMenuOpen = ref(false)

const handleScroll = () => {
  isScrolled.value = window.scrollY > 50
  if (isMobileMenuOpen.value) isMobileMenuOpen.value = false
}

const toggleMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}

const closeMenu = () => {
  isMobileMenuOpen.value = false
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll)
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})
</script>

<template>
  <nav :class="['navbar', { 'navbar-scrolled': isScrolled, 'nav-open': isMobileMenuOpen }]">
    <div class="container nav-container">
      <div class="logo-container">
        <img src="../assets/logo.png" alt="Agencia Logo" class="nav-logo">
        <span class="logo-text">Agencia de <span>Publicidad</span></span>
      </div>
      
      <button class="menu-toggle" @click="toggleMenu" aria-label="Abrir menú">
        <span class="bar"></span>
        <span class="bar"></span>
        <span class="bar"></span>
      </button>
      
      <ul :class="['nav-links', { 'mobile-active': isMobileMenuOpen }]">
        <li><a href="#" @click="closeMenu">Inicio</a></li>
        <li><a href="#servicios" @click="closeMenu">Servicios</a></li>
        <li><a href="#sobre-nosotros" @click="closeMenu">Nosotros</a></li>
        <li><a href="#contacto" class="btn-nav" @click="closeMenu">Contacto</a></li>
      </ul>
    </div>
  </nav>
</template>

<style scoped>
.navbar {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  padding: 1.1rem 0;
  z-index: 1000;
  transition: all 0.4s ease;
  background: transparent;
}

.navbar-scrolled {
 
  width: 100%;
  border-radius: 10px;
  border: 2px solid var(--silver);
  padding: 0.8rem 0;
  background: linear-gradient(135deg, rgba(220, 235, 255, 0.9) 0%, rgba(109, 166, 215, 0.9) 100%);
  backdrop-filter: blur(15px);
  box-shadow: 0 10px 30px rgba(70, 137, 246, 0.1),
              inset 0 0 15px rgba(255, 255, 255, 0.5); /* Efecto de brillo interior */
}

.nav-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.logo-container {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.nav-logo {
  height: 40px;
  width: auto;
  border-radius: 8px;
}

.logo-text {
  font-weight: 800;
  font-size: 1.2rem;
  color: var(--white);
  transition: color 0.3s ease;
}

.navbar-scrolled .logo-text {
  color: var(--black);
}

.logo-text span {
  color: var(--primary);
}

.nav-links {
  display: flex;
  align-items: center;
  gap: 2.5rem;
}

.nav-links a {
  font-weight: 600;
  color: var(--white);
  font-size: 1rem;
}

.navbar-scrolled .nav-links a {
  color: var(--text-main);
}

.nav-links a:hover {
  color: var(--primary);
}

.btn-nav {
  background: var(--primary);
  color: var(--white) !important;
  padding: 0.7rem 1.5rem;
  border-radius: 50px;
  border: 2px solid transparent;
  transition: all 0.3s ease;
}

.btn-nav:hover {
  transform: translateY(-2px);
  background-color: var(--dark-blue);
  border-color: var(--primary);
  box-shadow: 0 5px 15px rgba(29, 53, 87, 0.3);
}

.menu-toggle {
  display: none;
  flex-direction: column;
  gap: 6px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 5px;
  z-index: 1001;
}

.bar {
  width: 30px;
  height: 3px;
  background-color: var(--white);
  border-radius: 2px;
  transition: all 0.3s ease;
}

.navbar-scrolled .bar {
  background-color: var(--primary);
}

/* Animación Hamburguesa -> X */
.nav-open .bar:nth-child(1) {
  transform: translateY(9px) rotate(45deg);
}
.nav-open .bar:nth-child(2) {
  opacity: 0;
}
.nav-open .bar:nth-child(3) {
  transform: translateY(-9px) rotate(-45deg);
}

@media (max-width: 768px) {
  .menu-toggle {
    display: flex;
  }

  .navbar-scrolled {
    border-width: 1px;
  }

  .nav-links {
    position: absolute;
    top: 100%;
    left: 0;
    width: 100%;
    height: auto;
    max-height: 0;
    background: rgba(10, 15, 25, 0.98); /* Fondo oscuro profundo */
    flex-direction: column;
    justify-content: flex-start;
    gap: 12px;
    padding: 0 1.5rem;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    z-index: 1000;
  }

  .nav-links.mobile-active {
    max-height: 100vh;
    padding: 1.5rem;
  }

  .nav-links li {
    width: 100%;
  }

  .nav-links a {
    display: block;
    width: 100%;
    padding: 12px 20px;
    border-radius: 14px;
    border: 1px solid rgba(70, 137, 246, 0.6); /* Borde azul de la agencia */
    background: rgba(70, 137, 246, 0.05);
    color: var(--white) !important;
    font-size: 1.1rem;
    text-align: left;
    transition: all 0.2s ease;
  }

  .nav-links a:hover {
    background: rgba(70, 137, 246, 0.15);
    border-color: var(--primary);
  }

  .btn-nav {
    margin-top: 10px;
    background: var(--primary) !important;
    color: var(--white) !important;
    text-align: center !important;
    font-weight: 700 !important;
    border: none !important;
  }
}
</style>
