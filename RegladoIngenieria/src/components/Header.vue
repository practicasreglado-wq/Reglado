<template>
  <header class="site-header">
    <div class="container header-inner">
      <router-link to="/" class="logo">
        <span class="logo-text">Reglado <strong>Ingeniería</strong></span>
      </router-link>

      <nav class="nav-desktop" aria-label="Navegación principal">
        <router-link to="/servicios">Servicios</router-link>
        <router-link to="/proyectos">Proyectos</router-link>
        <router-link to="/nosotros">Nosotros</router-link>
        <router-link to="/contacto">Contacto</router-link>
      </nav>

      <div class="header-actions">
        <template v-if="auth.state.user">
          <router-link to="/area-clientes" class="btn outline btn-sm">Área Clientes</router-link>
          <button class="btn ghost btn-sm" @click="auth.logout()">Salir</button>
        </template>
        <template v-else>
          <router-link to="/area-clientes" class="btn primary btn-sm">Acceder</router-link>
        </template>
      </div>

      <button
        class="nav-toggle"
        :class="{ open: mobileOpen }"
        @click="mobileOpen = !mobileOpen"
        aria-label="Menú"
      >
        <span></span><span></span><span></span>
      </button>
    </div>

    <nav class="nav-mobile" :class="{ open: mobileOpen }" aria-label="Navegación móvil">
      <router-link to="/servicios" @click="mobileOpen = false">Servicios</router-link>
      <router-link to="/proyectos" @click="mobileOpen = false">Proyectos</router-link>
      <router-link to="/nosotros" @click="mobileOpen = false">Nosotros</router-link>
      <router-link to="/contacto" @click="mobileOpen = false">Contacto</router-link>
      <template v-if="auth.state.user">
        <router-link to="/area-clientes" @click="mobileOpen = false">Área Clientes</router-link>
        <button @click="auth.logout()">Salir</button>
      </template>
      <template v-else>
        <router-link to="/area-clientes" @click="mobileOpen = false">Acceder</router-link>
      </template>
    </nav>
  </header>
</template>

<script setup>
import { ref } from "vue";
import { auth } from "@/services/auth.js";
const mobileOpen = ref(false);
</script>

<style scoped>
.site-header {
  position: sticky;
  top: 0;
  z-index: 100;
  background: rgba(255,255,255,.95);
  backdrop-filter: blur(8px);
  border-bottom: 1px solid var(--border);
}
.header-inner {
  display: flex;
  align-items: center;
  gap: 32px;
  height: 64px;
}
.logo { display: flex; align-items: center; }
.logo-text { font-size: 1.125rem; color: var(--text); }
.logo-text strong { color: var(--steel); }
.nav-desktop { display: flex; gap: 28px; margin-right: auto; }
.nav-desktop a {
  font-size: 0.9375rem;
  font-weight: 500;
  color: var(--text-muted);
  transition: color var(--transition);
}
.nav-desktop a:hover,
.nav-desktop a.router-link-active { color: var(--steel); }
.header-actions { display: flex; gap: 8px; align-items: center; }
.btn-sm { padding: 8px 16px; font-size: 0.875rem; }
.nav-toggle {
  display: none;
  flex-direction: column;
  gap: 5px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
}
.nav-toggle span {
  display: block;
  width: 22px;
  height: 2px;
  background: var(--text);
  border-radius: 2px;
  transition: all var(--transition);
}
.nav-mobile {
  display: none;
  flex-direction: column;
  padding: 16px 24px 20px;
  border-top: 1px solid var(--border);
  gap: 4px;
}
.nav-mobile a,
.nav-mobile button {
  padding: 10px 0;
  font-size: 1rem;
  font-weight: 500;
  color: var(--text-muted);
  background: none;
  border: none;
  cursor: pointer;
  text-align: left;
  transition: color var(--transition);
}
.nav-mobile a:hover,
.nav-mobile button:hover,
.nav-mobile a.router-link-active { color: var(--steel); }

@media (max-width: 768px) {
  .nav-desktop,
  .header-actions { display: none; }
  .nav-toggle { display: flex; margin-left: auto; }
  .nav-mobile.open { display: flex; }
}
</style>
