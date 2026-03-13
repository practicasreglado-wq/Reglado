<template>
  <header ref="headerRef" class="topbar" :class="{ 'topbar-scrolled': isScrolled || isInternalRoute }">
    <RouterLink class="brand-link" to="/" aria-label="Ir al inicio">
      <img :src="logoSrc" alt="Reglado Energy" class="brand-logo" />
      <span class="brand">Grupo Reglado</span>
    </RouterLink>

    <nav class="menu desktop-menu">
      <a href="https://regladoconsultores.com/">Abogados</a>
      <a :href="energyUrl">Energy</a>
      <a href="#">Ingeniería</a>
      <a href="#">Arquitectura</a>
      <a href="#">Mapas</a>
      <a :href="realstateUrl">Real Estate</a>
    </nav>

    <div class="session-box desktop-session">
      <template v-if="user">
        <RouterLink v-if="isAdmin" class="admin-pill" to="/admin" aria-label="Panel de administracion">
          <img :src="adminUserIcon" alt="" class="admin-icon" />
        </RouterLink>
        <div class="user-menu-wrap">
          <button class="user-pill user-menu-trigger" @click="toggleUserMenu" aria-haspopup="menu"
            :aria-expanded="userMenuOpen ? 'true' : 'false'" :title="displayUsername" aria-label="Menu de usuario">
            <span class="user-initial" aria-hidden="true">{{ userInitial }}</span>
          </button>

          <div v-if="userMenuOpen" class="user-menu" role="menu" aria-label="Menu de usuario">
            <button class="user-menu-item" type="button" role="menuitem" @click="goToSettings">
              Configuración
            </button>
            <button class="user-menu-item danger" type="button" role="menuitem" @click="logout">
              Cerrar sesión
            </button>
          </div>
        </div>
      </template>
      <button v-else class="login-btn" @click="$emit('open-login')">Iniciar sesión</button>
    </div>

    <div class="mobile-controls">
      <RouterLink v-if="user && isAdmin" class="admin-pill mobile-admin-pill" to="/admin"
        aria-label="Panel de administracion">
        <img :src="adminUserIcon" alt="" class="admin-icon" />
      </RouterLink>

      <RouterLink v-if="user" class="user-pill mobile-user-trigger" to="/configuracion" :title="displayUsername"
        aria-label="Configuración de usuario">
        <span class="user-initial" aria-hidden="true">{{ userInitial }}</span>
      </RouterLink>

      <button class="mobile-menu-toggle" type="button" :aria-expanded="mobileMenuOpen ? 'true' : 'false'"
        aria-label="Abrir menu" @click="toggleMobileMenu">
        <img :src="menuIcon" alt="" class="mobile-menu-icon" />
      </button>
    </div>

    <div v-if="mobileMenuOpen" class="mobile-menu" role="menu" aria-label="Menu principal">
      <nav class="mobile-nav">
        <a href="https://regladoconsultores.com/" @click="closeMobileMenu">Abogados</a>
        <a :href="energyUrl" @click="closeMobileMenu">Energy</a>
        <a href="#" @click="closeMobileMenu">Ingeniería</a>
        <a href="#" @click="closeMobileMenu">Arquitectura</a>
        <a href="#" @click="closeMobileMenu">Mapas</a>
        <a :href="realstateUrl" @click="closeMobileMenu">Real Estate</a>
      </nav>

      <div class="mobile-session">
        <template v-if="user">
          <button class="mobile-session-action" type="button" role="menuitem" @click="logoutAndCloseMobile">
            Cerrar sesión
          </button>
        </template>

        <button v-else class="btn-primary mobile-login-btn" type="button" @click="openLoginFromMobile">
          Iniciar sesión
        </button>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { RouterLink, useRouter, useRoute } from "vue-router";
import adminUserIcon from "../assets/admin-user-icon.svg";
import menuIcon from "../assets/menu.svg";
import logoSrc from "../assets/reglado-energy-logo.svg";

const props = defineProps({
  user: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["open-login", "logout"]);
const router = useRouter();
const route = useRoute();
const realstateUrl = import.meta.env.VITE_REGLADO_REALSTATE_URL || "#";
const energyUrl = import.meta.env.VITE_REGLADO_ENERGY_URL || "http://localhost:5174";

const userMenuOpen = ref(false);
const mobileMenuOpen = ref(false);
const headerRef = ref(null);
const isScrolled = ref(false);
const isAdmin = computed(() => props.user?.role === "admin");
const isInternalRoute = computed(() => route.path !== '/');

const displayUsername = computed(() => {
  const username = props.user?.username;
  if (typeof username === "string" && username.trim() !== "") {
    return username.trim();
  }
  return props.user?.name || "Usuario";
});

const userInitial = computed(() => {
  const source = displayUsername.value.trim();
  if (source === "") {
    return "U";
  }

  return source.charAt(0).toUpperCase();
});

function toggleUserMenu() {
  userMenuOpen.value = !userMenuOpen.value;
}

function toggleMobileMenu() {
  mobileMenuOpen.value = !mobileMenuOpen.value;
  userMenuOpen.value = false;
}

function closeMobileMenu() {
  mobileMenuOpen.value = false;
}

function logout() {
  userMenuOpen.value = false;
  emit("logout");
}

function logoutAndCloseMobile() {
  closeMobileMenu();
  logout();
}

function goToSettings() {
  userMenuOpen.value = false;
  router.push("/configuracion");
}

function openLoginFromMobile() {
  closeMobileMenu();
  emit("open-login");
}

function handlePointerDown(event) {
  if (!userMenuOpen.value && !mobileMenuOpen.value) {
    return;
  }

  const headerEl = headerRef.value;
  if (headerEl && !headerEl.contains(event.target)) {
    userMenuOpen.value = false;
    mobileMenuOpen.value = false;
  }
}

function handleScroll() {
  isScrolled.value = window.scrollY > 60;
}

onMounted(() => {
  document.addEventListener("pointerdown", handlePointerDown);
  handleScroll();
  window.addEventListener("scroll", handleScroll, { passive: true });
});

onBeforeUnmount(() => {
  document.removeEventListener("pointerdown", handlePointerDown);
  window.removeEventListener("scroll", handleScroll);
});
</script>

<style scoped>
.topbar {
  position: fixed;
  top: 0;
  left: 0;
  min-height: var(--topbar-height);
  width: 100%;
  z-index: 40;
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  gap: 1rem;
  padding: 0.9rem 1.35rem;
  border-radius: 0;
  background: transparent;
  border: none;
  border-bottom: 1px solid transparent;
  box-shadow: none;
  overflow: visible;
  transition: border-color 0.6s ease, box-shadow 0.6s ease;
}

.topbar::before {
  content: "";
  position: absolute;
  inset: 0;
  z-index: -1;
  border-radius: inherit;
  background: linear-gradient(135deg, rgba(23, 39, 61, 0.95), rgba(39, 61, 92, 0.88));
  backdrop-filter: blur(8px);
  opacity: 0;
  transition: opacity 0.6s ease;
  pointer-events: none;
}

.topbar-scrolled::before {
  opacity: 1;
}

.topbar-scrolled {
  border-bottom: 1px solid rgba(255, 255, 255, 0.16);
  box-shadow: 0 12px 25px rgba(16, 28, 47, 0.22);
}

.brand-link {
  display: inline-flex;
  align-items: center;
  gap: 0.62rem;
  text-decoration: none;
  justify-self: start;
}

.brand-logo {
  width: 36px;
  height: 36px;
  object-fit: contain;
}

.brand {
  font-family: "Manrope", "Trebuchet MS", sans-serif;
  font-weight: 700;
  color: #f6f8fc;
  letter-spacing: 0.02em;
}

.menu {
  display: flex;
  gap: 0.38rem;
  justify-self: center;
  align-items: center;
  padding: 0.22rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.08);
}

.menu a {
  text-decoration: none;
  color: rgba(241, 246, 255, 0.9);
  font-size: 0.9rem;
  font-weight: 500;
  padding: 0.38rem 0.68rem;
  border-radius: 999px;
  transition: background 0.2s ease, color 0.2s ease;
}

.menu a:hover {
  background: rgba(255, 255, 255, 0.17);
  color: #fff;
}

.login-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  color: rgba(241, 246, 255, 0.95);
  font-size: 0.9rem;
  font-weight: 500;
  font-family: inherit;
  padding: 0.45rem 1rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  cursor: pointer;
  transition: background 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}

.login-btn:hover {
  background: rgba(255, 255, 255, 0.18);
  box-shadow: 0 4px 12px rgba(10, 20, 35, 0.15);
  transform: translateY(-1px);
}

.session-box {
  justify-self: end;
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
}

.admin-pill,
.mobile-admin-link {
  width: 39px;
  height: 39px;
  border: 1px solid rgba(255, 255, 255, 0.26);
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.08);
  color: #f6f8fc;
  text-decoration: none;
  display: grid;
  place-items: center;
  -webkit-tap-highlight-color: transparent;
}

.admin-pill:hover {
  background: rgba(255, 255, 255, 0.16);
}

.admin-icon {
  width: 19px;
  height: 19px;
  display: block;
}

.user-menu-wrap {
  position: relative;
}

.user-pill {
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 999px;
  width: 39px;
  height: 39px;
  padding: 0;
  background: rgba(255, 255, 255, 0.08);
  display: grid;
  place-items: center;
  text-decoration: none;
  -webkit-tap-highlight-color: transparent;
}

.user-menu-trigger {
  cursor: pointer;
  outline: none;
}

.user-pill:hover {
  background: rgba(255, 255, 255, 0.16);
}

.user-initial {
  color: #f6f8fc;
  font-family: "Manrope", "Trebuchet MS", sans-serif;
  font-size: 0.98rem;
  font-weight: 800;
  line-height: 1;
}

.user-menu {
  position: absolute;
  top: calc(100% + 9px);
  right: 0;
  min-width: 180px;
  border: 1px solid rgba(39, 61, 92, 0.2);
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.18);
  padding: 0.35rem;
  display: grid;
  gap: 0.25rem;
  z-index: 60;
}

.user-menu-item {
  width: 100%;
  border: 1px solid transparent;
  border-radius: 9px;
  background: #fff;
  color: #273d5c;
  text-align: left;
  padding: 0.54rem 0.62rem;
  cursor: pointer;
}

.user-menu-item:hover {
  background: #f1f5fb;
}

.user-menu-item.danger {
  color: #b42318;
}

.mobile-controls,
.mobile-menu-toggle,
.mobile-menu {
  display: none;
}

.mobile-menu-toggle {
  justify-self: end;
  width: 39px;
  height: 39px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.08);
  padding: 0;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
  outline: none;
}

.mobile-menu-icon {
  width: 18px;
  height: 18px;
  display: block;
  margin: 0 auto;
}

@media (max-width: 900px) {
  .topbar {
    grid-template-columns: 1fr auto;
    row-gap: 0;
  }

  .desktop-menu,
  .desktop-session {
    display: none;
  }

  .mobile-controls {
    display: inline-flex;
    justify-self: end;
    align-items: center;
    gap: 0.55rem;
  }

  .mobile-menu-toggle {
    display: block;
  }

  .mobile-admin-pill,
  .mobile-user-trigger {
    display: grid;
  }

  .mobile-menu {
    display: grid;
    position: absolute;
    top: calc(100% + 0.55rem);
    left: 0;
    right: 0;
    gap: 0.9rem;
    margin: 0 0.6rem;
    padding: 0.9rem;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 16px;
    background: linear-gradient(135deg, rgba(23, 39, 61, 0.98), rgba(39, 61, 92, 0.95));
    box-shadow: 0 18px 34px rgba(16, 28, 47, 0.28);
    backdrop-filter: blur(12px);
    z-index: 70;
  }

  .mobile-nav,
  .mobile-session {
    display: grid;
    gap: 0.45rem;
  }

  .mobile-nav a,
  .mobile-session-action {
    width: 100%;
    min-height: 44px;
    border-radius: 12px;
    text-decoration: none;
    color: rgba(241, 246, 255, 0.94);
    background: rgba(255, 255, 255, 0.08);
    display: flex;
    align-items: center;
    gap: 0.7rem;
    padding: 0.75rem 0.9rem;
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-sizing: border-box;
    -webkit-tap-highlight-color: transparent;
  }

  .mobile-session-action {
    font: inherit;
    cursor: pointer;
    text-align: left;
    outline: none;
  }

  .mobile-login-btn {
    width: 100%;
  }
}

@media (max-width: 640px) {
  .topbar {
    margin-top: 0;
    margin-bottom: 0;
    border-radius: 0;
    padding: 0.75rem 0.9rem;
  }

  .topbar-scrolled {
    margin-top: 0;
    margin-bottom: 0;
    border-radius: 0;
  }

  .brand {
    font-size: 0.94rem;
  }
}
</style>
