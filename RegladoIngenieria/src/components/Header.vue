<template>
  <header class="site-header">
    <div class="container header-inner">
      <router-link to="/" class="logo">
        <img src="@/assets/reglado-logo.svg" alt="Reglado Logo" class="logo-img" />
        <span class="logo-text">Reglado <strong>Ingeniería</strong></span>
      </router-link>

      <nav class="nav-desktop" aria-label="Navegación principal">
        <router-link to="/servicios">Servicios</router-link>
        <router-link to="/proyectos">Proyectos</router-link>
        <router-link to="/nosotros">Nosotros</router-link>
      </nav>

      <div class="header-actions">
        <!-- Dashboard / Admin Button -->
        <router-link
          v-if="isAdmin"
          to="/admin"
          class="admin-pill"
          title="Panel de administración"
        >
          <img src="@/assets/admin-user-icon.svg" alt="" class="admin-icon" />
        </router-link>

        <template v-if="auth.state.user">
          <!-- User Profile Dropdown -->
          <div class="user-menu-wrap" ref="userMenuWrap">
            <button
              class="user-pill"
              @click="toggleUserMenu"
              :aria-expanded="userMenuOpen"
              :title="auth.state.user.name"
            >
              <span class="user-initial">{{ userInitial }}</span>
            </button>
            <div v-if="userMenuOpen" class="user-menu" role="menu">
              <div class="user-menu-info">
                <span class="user-menu-name">{{ auth.state.user.name }}</span>
                <span class="user-menu-email">{{ auth.state.user.email }}</span>
              </div>
              <div class="user-menu-divider"></div>
              <button @click="goToSettings" class="user-menu-item">
                Configuración
              </button>
              <button @click="auth.logout()" class="user-menu-item danger">
                Cerrar sesión
              </button>
            </div>
          </div>
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
      <template v-if="auth.state.user">
        <router-link v-if="isAdmin" to="/admin" @click="mobileOpen = false">Administración</router-link>
        <router-link to="/area-clientes" @click="mobileOpen = false">Área Clientes</router-link>
        <button @click="goToSettings">Configuración</button>
        <button @click="auth.logout()" class="danger-text">Salir</button>
      </template>
      <template v-else>
        <router-link to="/area-clientes" @click="mobileOpen = false">Acceder</router-link>
      </template>
    </nav>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { auth } from "@/services/auth.js";

const mobileOpen = ref(false);
const userMenuOpen = ref(false);
const userMenuWrap = ref(null);

const isAdmin = computed(() => auth.state.user?.role === "admin");
const userInitial = computed(() => {
  const name = auth.state.user?.name || "U";
  return name.charAt(0).toUpperCase();
});

const toggleUserMenu = () => {
  userMenuOpen.value = !userMenuOpen.value;
};

const goToSettings = () => {
  userMenuOpen.value = false;
  mobileOpen.value = false;
  const baseUrl = import.meta.env.VITE_AUTH_FRONTEND_URL || "https://gruporeglado.com";
  window.location.href = `${baseUrl}/configuracion`;
};

const handleOutsideClick = (event) => {
  if (userMenuOpen.value && userMenuWrap.value && !userMenuWrap.value.contains(event.target)) {
    userMenuOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener("click", handleOutsideClick);
});

onUnmounted(() => {
  document.removeEventListener("click", handleOutsideClick);
});
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
.logo { display: flex; align-items: center; gap: 4px; }
.logo-img { 
  height: 40px; 
  width: auto; 
  transition: transform 0.7s cubic-bezier(0.22, 1, 0.36, 1), filter 0.4s ease;
}
.logo:hover .logo-img {
  transform: rotate(180deg) scale(1.05);
  filter: drop-shadow(0 0.35rem 0.8rem rgba(0, 0, 0, 0.15));
}
.logo-text { font-size: 1.25rem; color: var(--text); }
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
.header-actions { display: flex; gap: 16px; align-items: center; }
.btn-sm { padding: 8px 16px; font-size: 0.875rem; }

/* Admin and User buttons */
.admin-pill {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: #f1f5f9;
  border: 1px solid var(--border);
  color: #000000;
  transition: all var(--transition);
}
.admin-pill:hover {
  background: var(--steel);
  border-color: var(--steel);
}
.admin-pill:hover .admin-icon {
  filter: brightness(0) invert(1);
}
.admin-icon {
  width: 20px;
  height: 20px;
  opacity: 0.8;
}

.user-menu-wrap {
  position: relative;
}
.user-pill {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #f1f5f9;
  border: 1px solid var(--border);
  color: #000000;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  cursor: pointer;
  transition: all var(--transition);
}
.user-pill:hover {
  transform: scale(1.05);
  box-shadow: 0 0 12px rgba(74, 158, 255, 0.3);
}
.user-menu {
  position: absolute;
  top: calc(100% + 12px);
  right: 0;
  min-width: 220px;
  background: white;
  border: 1px solid var(--border);
  border-radius: 12px;
  box-shadow: var(--shadow-lg);
  padding: 8px;
  z-index: 1000;
}
.user-menu-info {
  padding: 12px;
  display: flex;
  flex-direction: column;
}
.user-menu-name {
  font-weight: 600;
  font-size: 0.9375rem;
  color: var(--text);
}
.user-menu-email {
  font-size: 0.8125rem;
  color: var(--text-muted);
}
.user-menu-divider {
  height: 1px;
  background: var(--border);
  margin: 8px 0;
}
.user-menu-item {
  width: 100%;
  padding: 10px 12px;
  border: none;
  background: none;
  border-radius: 8px;
  text-align: left;
  font-size: 0.875rem;
  color: var(--text);
  cursor: pointer;
  transition: all var(--transition);
}
.user-menu-item:hover {
  background: var(--bg-soft);
  color: var(--steel);
}
.user-menu-item.danger {
  color: #ef4444;
}
.user-menu-item.danger:hover {
  background: #fef2f2;
}
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
.nav-mobile .danger-text { color: #ef4444; }

@media (max-width: 768px) {
  .nav-desktop,
  .header-actions { display: none; }
  .nav-toggle { display: flex; margin-left: auto; }
  .nav-mobile.open { display: flex; }
}
</style>
