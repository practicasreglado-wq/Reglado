<!--
  Módulo LPHeader (Cabecera Flotante Adaptativa)
  Garantiza la persistencia de los controles de navegación durante el scroll del usuario 
  sin bloquear el contenido visual inferior gracias a su diseño Glassmorphic translúcido.
  Gestiona la conmutación de menús (Hamburguesa en móvil) mediante estados reactivos 
  para acomodar la densidad de enlaces limpiamente en pantallas reducidas.
-->
<template>
  <header class="lp-header" :class="{ 'autohide': isAutohide }">
    <!-- Zona de colisión inferior para activar el hover cuando el header está escondido -->
    <div v-if="isAutohide" class="hover-trigger-zone"></div>
    <div class="header-brand" @click="goHome" style="cursor: pointer; display: flex; align-items: center;">
      <img src="/reglado-logo.svg" class="logo" alt="Reglado Maps Logo" />
      <h1>Reglado Maps</h1>
    </div>

    <!-- 🍔 Icono de Menú Hamburguesa -->
    <button class="hamburger-menu" @click="toggleMenu" :class="{ 'active': isMenuOpen }" aria-label="Menú">
      <span></span><span></span><span></span>
    </button>

    <!-- 🧭 Enlaces de Navegación (Sidebar) -->
    <nav class="nav-links" :class="{ 'menu-open': isMenuOpen }">
      <!-- 👑 Cabecera del Panel (Solo Móvil) -->
      <template v-if="isMenuOpen">
        <div class="menu-header">
          <span class="menu-title">Menú</span>
        </div>
        <div class="menu-divider" style="margin: 0.2rem 0 0.8rem 0;"></div>
      </template>
      <a href="https://regladogroup.com" class="nav-link-reglado" @click="closeMenu">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="icon-home" aria-hidden="true">
          <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z" />
        </svg>
        Reglado Group
      </a>
      
      <template v-if="user">
        <!-- Controles de Escritorio -->
        <div class="user-controls-row desktop-only">
          <router-link v-if="user.role === 'admin'" class="admin-pill" to="/admin" aria-label="Panel de administracion">
            <img :src="adminUserIcon" alt="Admin" class="admin-icon" />
          </router-link>
  
          <div class="user-menu-wrap">
            <button class="user-pill user-menu-trigger" @click="toggleUserMenu" :title="displayUsername">
              <span class="user-initial">{{ userInitial }}</span>
            </button>
          </div>
        </div>

        <!-- Lateral Sidebar del Usuario (Sustituye al antiguo dropdown) -->
        <transition name="fade">
          <div class="user-sidebar-overlay" v-if="userMenuOpen" @click.self="toggleUserMenu"></div>
        </transition>
        <transition name="slide-right">
          <div class="user-sidebar" v-if="userMenuOpen">
            <div class="user-sidebar-header">
              <div class="user-sidebar-avatar">{{ userInitial }}</div>
              <div class="user-sidebar-info">
                <h3>{{ displayUsername }}</h3>
                <p v-if="user && user.email" class="user-email-text">{{ user.email }}</p>
              </div>
              <button class="close-sidebar-btn" @click="toggleUserMenu">&times;</button>
            </div>
            
            <div class="user-sidebar-divider"></div>
            
            <div class="user-sidebar-body">
              <button class="sidebar-item-btn" type="button" @click="goToSettings">
                <img :src="settingsIcon" class="sidebar-icon-svg" alt="" /> Configuración
              </button>
              
              <button class="sidebar-item-btn danger-item" type="button" @click="handleLogout">
                <img :src="logoutIcon" class="sidebar-icon-svg" alt="" /> Cerrar sesión
              </button>
            </div>
          </div>
        </transition>

        <!-- Menú Expandido de Sesión (Solo Móvil) -->
        <div class="mobile-session-menu mobile-only">
          <div class="menu-divider"></div>
          <div class="menu-subtitulo">Tu Sesión</div>

          <div class="nav-link-anchor disabled-anchor">
            <span class="menu-emoji">👤</span> {{ displayUsername }}
          </div>
          
          <button class="nav-link-anchor" type="button" @click="goToSettings">
            <img :src="settingsIcon" class="menu-svg-icon" alt="" /> Configuración
          </button>
          
          <router-link v-if="user.role === 'admin'" to="/admin" class="nav-link-anchor" @click="closeMenu">
            <span class="menu-emoji">🛡️</span> Administrador
          </router-link>
          
          <button class="nav-link-anchor danger-anchor" type="button" @click="handleLogout">
            <img :src="logoutIcon" class="menu-svg-icon" alt="" /> Cerrar sesión
          </button>
        </div>
      </template>
      <template v-else>
        <a href="#" class="nav-link-login" @click.prevent="handleLogin">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="icon-login" aria-hidden="true">
            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
          </svg>
          Iniciar sesión
        </a>
      </template>

      <!-- 🛠️ Accesos Directos (Solo Móvil / Sidebar) -->
      <template v-if="isMenuOpen">
        <div class="menu-divider"></div>
        <div class="menu-subtitulo">Descubre el mapa</div>

        <div v-for="item in energyTypes" :key="item.id" class="nav-link-anchor" @click="scrollToSection(item.id)">
          <span class="menu-emoji" v-html="item.emoji"></span>
          {{ item.label }}
        </div>
      </template>
    </nav>

    <!-- ☁️ Capa traslúcida de fondo -->
    <div class="menu-backdrop" v-if="isMenuOpen" @click="closeMenu"></div>
  </header>
</template>

<script>
import { auth } from '../services/auth';
import adminUserIcon from '../assets/admin-user-icon.svg';
import settingsIcon from '../assets/setings.svg';
import logoutIcon from '../assets/logout.svg';

export default {
  name: 'LPHeader',
  data() {
    return {
      adminUserIcon,
      settingsIcon,
      logoutIcon,
      isMenuOpen: false,
      energyTypes: [
        { id: 'eolica', emoji: '<svg viewBox="0 0 24 24" width="1.2em" height="1.2em" fill="none" class="svg-eolica" style="vertical-align: middle;"><defs><linearGradient id="towerGrad" x1="0%" y1="0%" x2="100%" y2="0%"><stop offset="0%" stop-color="#CBD5E1"/><stop offset="40%" stop-color="#FFFFFF"/><stop offset="100%" stop-color="#E2E8F0"/></linearGradient><linearGradient id="bladeGrad" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#FFFFFF"/><stop offset="100%" stop-color="#CBD5E1"/></linearGradient></defs><path d="M8 22h8" stroke="#E2E8F0" stroke-width="1.5" stroke-linecap="round" opacity="0.4"/><path d="M11.3 22 L11.7 10 L12.3 10 L12.7 22 Z" fill="url(#towerGrad)" /><rect x="11" y="9.2" width="2" height="1.6" rx="0.4" fill="#FFFFFF" stroke="#E2E8F0" stroke-width="0.3" /><g class="molino-aspas"><path d="M12 10 L12.4 4 A 0.4 0.4 0 0 0 11.6 4 L12 10 Z" fill="url(#bladeGrad)" /><path d="M12 10 L12.4 4 A 0.4 0.4 0 0 0 11.6 4 L12 10 Z" fill="url(#bladeGrad)" transform="rotate(120, 12, 10)" /><path d="M12 10 L12.4 4 A 0.4 0.4 0 0 0 11.6 4 L12 10 Z" fill="url(#bladeGrad)" transform="rotate(240, 12, 10)" /></g><circle cx="12" cy="10" r="1.2" fill="#FFFFFF" stroke="#E2E8F0" stroke-width="0.5" /></svg>', label: 'Eólica' },
        { id: 'solar', emoji: '☀️', label: 'Solar' },
        { id: 'hidrogeno', emoji: '<svg viewBox="0 0 24 24" width="1.2em" height="1.2em" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" class="svg-hidrogeno" style="vertical-align: middle;"><path d="M7 4v16M17 4v16M7 12h10" /></svg>', label: 'Hidrógeno' },
        { id: 'biometano', emoji: '🌿', label: 'Biometano' },
        { id: 'biodiesel', emoji: '⛽', label: 'Biodiésel' },
        { id: 'hidraulica', emoji: '💧', label: 'Hidráulica' }
      ],
      userMenuOpen: false
    }
  },
  mounted() {
    document.addEventListener("pointerdown", this.handlePointerDown);
  },
  beforeUnmount() {
    document.removeEventListener("pointerdown", this.handlePointerDown);
  },
  computed: {
    isMapView() { return this.$route.path === '/mapa'; },
    isAutohide() { return this.isMapView && !this.isMenuOpen && !this.userMenuOpen; },
    user() { return auth.state.user; },
    displayUsername() {
      const username = this.user?.username;
      if (typeof username === "string" && username.trim() !== "") {
        return username.trim();
      }
      return this.user?.name || "Usuario";
    },
    userInitial() {
      if (!this.user) return '';
      const name = this.user.username || this.user.name || 'U';
      return name.charAt(0).toUpperCase();
    }
  },
  methods: {
    goHome() {
      if (this.$route.path !== '/') {
        this.$router.push('/');
      } else {
        this.$emit('scrollToTop');
      }
    },
    scrollToTop() { this.$emit('scrollToTop'); },
    toggleMenu() { this.isMenuOpen = !this.isMenuOpen; },
    closeMenu() { this.isMenuOpen = false; },
    scrollToSection(id) {
      this.$emit('scrollTo', id);
      this.closeMenu();
    },
    toggleUserMenu() {
      this.userMenuOpen = !this.userMenuOpen;
    },
    handlePointerDown(event) {
      if (this.userMenuOpen) {
        const menuTrigger = event.target.closest?.(".user-menu-wrap");
        const sidebarWrap = event.target.closest?.(".user-sidebar");
        if (!menuTrigger && !sidebarWrap && !event.target.classList.contains("user-sidebar-overlay")) {
          this.userMenuOpen = false;
        }
      }
    },
    goToSettings() {
      this.userMenuOpen = false;
      this.closeMenu();
      const base = import.meta.env.VITE_GRUPO_REGLADO_BASE_URL || 'http://localhost:5173';
      const settingsPath = import.meta.env.VITE_GRUPO_REGLADO_SETTINGS_PATH || '/configuracion';
      window.location.href = new URL(settingsPath, base).toString();
    },
    handleLogin() {
      this.closeMenu();
      const loginPath = import.meta.env.VITE_GRUPO_REGLADO_LOGIN_PATH || '/login';
      const base = import.meta.env.VITE_GRUPO_REGLADO_BASE_URL || 'http://localhost:5173';
      const returnUrl = `${window.location.origin}/auth/callback`;
      window.location.href = `${base}${loginPath}?returnTo=${encodeURIComponent(returnUrl)}`;
    },
    async handleLogout() {
      this.closeMenu();
      await auth.logout();
      window.location.reload();
    }
  }
}
</script>

<style>
.lp-header {
  background-color: rgba(10, 11, 14, 0.33);
  padding: 1.25rem 1.25rem 1.25rem 2rem;
  text-align: left;
  color: white;
  width: 100%;
  position: fixed;
  top: 0;
  left: 0;
  z-index: 2005; /* Elevado para siempre vencer a cualquier componente de vista interior (como los paneles del mapa) */
  display: flex;
  align-items: center;
  backdrop-filter: blur(12px);
  transition: transform 0.4s cubic-bezier(0.25, 1, 0.5, 1), background-color 0.3s;
}

/* Modificadores Autohide */
.lp-header.autohide {
  transform: translateY(-100%);
}

.lp-header.autohide:hover {
  transform: translateY(0);
}

.hover-trigger-zone {
  position: absolute;
  top: 100%;
  left: 0;
  width: 100%;
  height: 35px; /* Crea una zona muerta de 35px bajo el menu escodido que captura el hover */
  background: transparent;
  cursor: default;
}

.header-brand {
  display: flex;
  align-items: center;
}

.lp-header h1 {
  font-size: 1.5rem;
  margin-left: 0.75rem;
  cursor: pointer;
  transition: font-size 0.3s ease;
}

@media (max-width: 380px) {
  .lp-header h1 {
    font-size: 1.1rem !important;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 55vw;
  }
}

.logo {
  height: 2.5rem;
  width: auto;
}

.nav-links {
  display: flex;
  align-items: center;
  margin-left: auto;
}

.nav-link-reglado {
  color: white;
  text-decoration: none;
  margin-right: 1.5rem;
  font-weight: 500;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  position: relative;
}

.nav-link-reglado::after {
  content: '';
  position: absolute;
  bottom: -5px;
  left: 50%;
  width: 0%;
  height: 2px;
  background-color: #00c47d;
  transition: all 0.3s ease;
  transform: translateX(-50%);
}

.nav-link-reglado:hover {
  color: #00c47d;
}

.nav-link-reglado:hover::after {
  width: 100%;
}

.icon-home {
  width: 1.2rem;
  height: 1.2rem;
  margin-right: 0.4rem;
  fill: currentColor;
  display: none;
}

.nav-link-login {
  color: white;
  text-decoration: none;
  margin-right: 2rem;
  padding: 0.5rem 1.25rem;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.4);
  border-radius: 2rem;
  font-weight: 600;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
}

.nav-link-login:hover {
  background: rgba(255, 255, 255, 0.2);
  border-color: white;
  transform: translateY(-1px);
}

.icon-login {
  width: 1.2rem;
  height: 1.2rem;
  margin-right: 0.4rem;
  fill: currentColor;
  display: none;
}

.desktop-only { display: flex; }
.mobile-only { display: none; }

.user-controls-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-right: 2rem;
}

.disabled-anchor {
  cursor: default !important;
  background: rgba(0, 196, 125, 0.1) !important;
  border-color: rgba(0, 196, 125, 0.25) !important;
  color: #fff !important;
}

.danger-anchor {
  color: #ff8a8a !important;
}
.danger-anchor:hover {
  background: rgba(255, 94, 94, 0.12) !important;
  border-color: rgba(255, 94, 94, 0.3) !important;
}
.user-menu-wrap { position: relative; }
.admin-pill {
  border: 1px solid rgba(255,255,255,.26);
  border-radius: 999px;
  width: 38px;
  height: 38px;
  background: rgba(255,255,255,.08);
  display: grid;
  place-items: center;
  text-decoration: none;
  transition: all 0.2s ease;
}
.admin-pill:hover {
  background: rgba(255,255,255,.16);
  transform: translateY(-1px);
}
.admin-icon {
  width: 22px;
  height: 22px;
  opacity: 0.95;
}
.user-menu-trigger { cursor: pointer; }
.user-pill {
  border: 1px solid rgba(255,255,255,.26);
  border-radius: 999px;
  width: 38px;
  height: 38px;
  padding: 0;
  font-size: 15px;
  color: rgba(233,238,246,.9);
  background: rgba(255,255,255,.08);
  display: flex;
  justify-content: center;
  align-items: center;
  transition: all 0.18s ease;
}
.user-pill:hover {
  background: rgba(0, 196, 125, 0.3);
  border-color: rgba(0, 196, 125, 0.6);
  transform: translateY(-1px);
}
.user-initial { 
  color: rgba(233,238,246,.95); 
  font-size: 1.1rem; 
  font-weight: 800; 
  line-height: normal;
  display: inline-flex;
  margin-top: -1px; /* Micro-ajuste para letras mayúsculas */
}
/* ===================================================
   USER SIDEBAR / OFFCANVAS
   =================================================== */
.user-sidebar-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  z-index: 2000;
}

.user-sidebar {
  position: fixed;
  top: 0;
  right: 0;
  width: 290px;
  height: 100vh;
  background: rgba(10, 11, 14, 0.33); /* Mismo color translúcido del Header principal */
  border-left: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: -10px 0 30px rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(16px);
  z-index: 2001;
  display: flex;
  flex-direction: column;
  padding: 2.2rem 1.6rem;
  box-sizing: border-box;
}

.user-sidebar-header {
  display: flex;
  align-items: center;
  gap: 15px;
  position: relative;
  margin-bottom: 20px;
}

.user-sidebar-avatar {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: linear-gradient(135deg, #00c47d, #007bbd);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.6rem;
  font-weight: 800;
  color: white;
  border: 2px solid rgba(255, 255, 255, 0.2);
}

.user-sidebar-info {
  display: flex;
  flex-direction: column;
  flex: 1;
}

.user-sidebar-info h3 {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0;
  color: white;
}

.user-email-text {
  font-size: 0.85rem;
  color: rgba(255, 255, 255, 0.5);
  margin: 0;
  margin-top: 4px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 190px;
}

.close-sidebar-btn {
  position: absolute;
  top: -28px;
  right: -5px;
  background: transparent;
  border: none;
  color: rgba(255, 255, 255, 0.4);
  font-size: 2.2rem;
  cursor: pointer;
  transition: color 0.2s, transform 0.2s;
  line-height: 1;
}

.close-sidebar-btn:hover {
  color: white;
  transform: scale(1.1);
}

.user-sidebar-divider {
  width: 100%;
  height: 1px;
  background: rgba(255, 255, 255, 0.1);
  margin: 15px 0 25px 0;
}

.user-sidebar-body {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.sidebar-item-btn {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 12px;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: rgba(255, 255, 255, 0.9);
  padding: 12px 18px;
  border-radius: 2rem; /* Elipse estilo header nativo */
  font-size: 0.95rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  text-decoration: none;
  box-sizing: border-box;
}

.sidebar-item-btn:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.2);
  transform: translateX(4px);
}

.danger-item {
  color: #ff5e5e !important;
}

.danger-item:hover {
  background: rgba(255, 94, 94, 0.1) !important;
  border-color: rgba(255, 94, 94, 0.2) !important;
}

.sidebar-icon {
  font-size: 1.25rem;
}

.sidebar-icon-svg {
  width: 1.25rem;
  height: 1.25rem;
  opacity: 0.85;
  transition: opacity 0.2s ease, transform 0.2s ease;
  flex-shrink: 0;
}

/* Transiciones Fluidas de Vue */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.slide-right-enter-active, .slide-right-leave-active {
  transition: transform 0.4s cubic-bezier(0.25, 1, 0.5, 1);
}
.slide-right-enter-from, .slide-right-leave-to {
  transform: translateX(100%);
}

/* 🍔 Menú Hamburguesa */
.hamburger-menu {
  display: none;
  flex-direction: column;
  gap: 4px;
  background: transparent;
  border: none;
  cursor: pointer;
  z-index: 1001;
  transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.hamburger-menu span {
  width: 22px;
  height: 3px;
  background: white;
  border-radius: 2px;
  transition: all 0.3s ease;
}

.hamburger-menu:hover {
  transform: scale(0.88);
}

.hamburger-menu:active {
  transform: scale(0.82);
}

.hamburger-menu.active span:nth-child(1) {
  transform: translateY(7px) rotate(45deg);
}

.hamburger-menu.active span:nth-child(2) {
  opacity: 0;
}

.hamburger-menu.active span:nth-child(3) {
  transform: translateY(-7px) rotate(-45deg);
}

@media (max-width: 600px) {
  .hamburger-menu {
    display: flex;
    margin-left: auto;
    margin-right: 1rem;
  }

  .nav-links {
    position: fixed;
    top: 0;
    right: -100%;
    width: 240px;
    height: 100vh;
    background: rgba(10, 11, 16, 0.98);
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    padding: 2.2rem 1.5rem;
    gap: 1rem;
    transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 1000;
    box-shadow: -5px 0 15px rgba(0, 0, 0, 0.5);
  }

  .nav-links.menu-open {
    right: 0;
  }

  .desktop-only { display: none !important; }
  .mobile-only { 
    display: flex; 
    flex-direction: column; 
    width: 100%; 
    gap: 0px; 
  }
  
  .mobile-session-menu {
    width: 100%;
    margin-bottom: 0.5rem;
    display: flex;
    flex-direction: column;
    gap: 10px;
  }

  .nav-link-reglado,
  .nav-link-login {
    margin: 0 !important;
    width: 100% !important;
    max-width: 14rem;
    text-align: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.08) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    border-radius: 2rem;
    padding: 0.75rem 1.25rem !important;
  }

  .icon-home,
  .icon-login {
    display: inline-block !important;
  }

  .nav-link-reglado:hover {
    color: white !important;
    background: rgba(255, 255, 255, 0.15) !important;
  }

  .nav-link-reglado::after {
    display: none !important;
  }
}

.menu-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(3px);
  z-index: 998;
}

/* 🛠️ Estilos para Accesos Directos del Menú */
.menu-divider {
  width: 100%;
  height: 1px;
  background: rgba(255, 255, 255, 0.1);
  margin: 0.75rem 0;
}

.menu-subtitulo {
  color: rgba(255, 255, 255, 0.4);
  font-size: 0.72rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04rem;
  margin-bottom: 0.5rem;
  width: 100%;
  text-align: left;
  padding-left: 0.25rem;
}

.nav-link-anchor {
  color: rgba(255, 255, 255, 0.85);
  text-decoration: none;
  width: 100%;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.65rem 1rem;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.11) !important;
  border-radius: 1rem;
  font-size: 0.95rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  box-sizing: border-box;
}

.nav-link-anchor:hover {
  background: rgba(255, 255, 255, 0.12) !important;
  transform: translateX(3px);
  border-color: rgba(255, 255, 255, 0.25) !important;
}

.menu-emoji {
  font-size: 1.1rem;
}

.menu-svg-icon {
  width: 1.15rem;
  height: 1.15rem;
  opacity: 0.85;
  flex-shrink: 0;
}

/* 👑 Cabecera del Panel */
.menu-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
  padding-left: 0.4rem;
  padding-bottom: 0px;
}

.menu-logo {
  height: 1.5rem;
  width: auto;
  filter: drop-shadow(0 0 4px rgba(255, 255, 255, 0.1));
}

.menu-title {
  color: #ffffff;
  font-size: 0.9rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.04rem;
  opacity: 0.85;
}
</style>
