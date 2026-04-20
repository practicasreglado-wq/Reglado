<template>
  <header ref="headerRef" class="topbar" :class="{ 'topbar-scrolled': isScrolled || isInternalRoute }">
    <RouterLink class="brand-link" to="/" aria-label="Ir al inicio">
      <img :src="logoSrc" alt="Reglado Energy" class="brand-logo" />
      <span class="brand">Grupo Reglado</span>
    </RouterLink>

    <nav class="menu desktop-menu">
      <a href="https://regladoconsultores.com/">Abogados</a>
      <a :href="energyUrl">Energía</a>
      <a :href="realstateUrl">Inmobiliaria</a>
      <a :href="mapasUrl">Mapas</a>
      <a href="#">Ingeniería</a>
      <a href="#">RBR</a>
    </nav>

    <div class="session-box desktop-session">
      <button class="theme-toggle" @click="toggleDarkMode" :aria-label="isDarkMode ? 'Activar modo claro' : 'Activar modo oscuro'" :title="isDarkMode ? 'Cambiar a Modo Claro' : 'Cambiar a Modo Oscuro'">
        <svg v-if="isDarkMode" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="theme-icon"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
        <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="theme-icon"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
      </button>

      <template v-if="user">
        <RouterLink v-if="isAdmin" class="admin-pill" to="/admin" aria-label="Panel de administración">
          <img :src="adminUserIcon" alt="" class="admin-icon" />
        </RouterLink>
        <div class="user-menu-wrap">
          <button class="user-pill user-menu-trigger" @click="toggleUserMenu" aria-haspopup="menu"
            :aria-expanded="userMenuOpen ? 'true' : 'false'" :title="displayUsername" aria-label="Menú de usuario">
            <span class="user-initial" aria-hidden="true">{{ userInitial }}</span>
          </button>

          <div v-if="userMenuOpen" class="user-menu" role="menu" aria-label="Menú de usuario">
            <div class="user-menu-info">
              <span class="user-menu-name">{{ user.name }}</span>
              <span class="user-menu-email">{{ user.email }}</span>
            </div>
            <div class="user-menu-divider"></div>
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
      <button class="mobile-menu-toggle" type="button" :aria-expanded="mobileMenuOpen ? 'true' : 'false'"
        aria-label="Abrir menú" @click="toggleMobileMenu">
        <img :src="menuIcon" alt="" class="mobile-menu-icon" />
      </button>
    </div>

    <div v-if="mobileMenuOpen" class="mobile-menu" role="menu" aria-label="Menú principal">
      <template v-if="user">
        <div class="mobile-user-profile">
          <div class="mobile-avatar">
            <span class="user-initial">{{ userInitial }}</span>
          </div>
          <div class="mobile-user-info">
            <span class="mobile-username">{{ displayUsername }}</span>
            <span v-if="isAdmin" class="mobile-role">Administrador</span>
          </div>
        </div>

        <div class="mobile-session">
          <button class="mobile-session-action" type="button" role="menuitem" @click="goToSettings(); closeMobileMenu()">
            Configuración
          </button>
          <button v-if="isAdmin" class="mobile-session-action" type="button" role="menuitem" @click="router.push('/admin'); closeMobileMenu()">
            Administración
          </button>
        </div>
        <div class="menu-divider" style="margin: 0.5rem 0;"></div>
      </template>

      <nav class="mobile-nav">
        <a href="https://regladoconsultores.com/" @click="closeMobileMenu">
          Abogados
        </a>
        <a :href="energyUrl" @click="closeMobileMenu">
          Energía
        </a>
        <a :href="realstateUrl" @click="closeMobileMenu">
          Inmobiliaria
        </a>
        <a :href="mapasUrl" @click="closeMobileMenu">
          Mapas
        </a>
        <a href="#" @click="closeMobileMenu">
          Ingeniería
        </a>
        <a href="#" @click="closeMobileMenu">
          RBR
        </a>
      </nav>

      <div class="mobile-session">
        <div class="menu-divider" style="margin: 0.5rem 0;"></div>
        <button class="mobile-session-action theme-mobile-item" type="button" @click="toggleDarkMode">
          <svg v-if="isDarkMode" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
          <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
          <span>{{ isDarkMode ? 'Modo Claro' : 'Modo Oscuro' }}</span>
        </button>

        <button v-if="user" class="mobile-session-action danger" type="button" role="menuitem" @click="logoutAndCloseMobile">
          Cerrar sesión
        </button>

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
import logoSrc from "../assets/reglado-logo.svg";
import { auth } from "../services/auth";

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
const rawMapasUrl = import.meta.env.VITE_REGLADO_MAPAS_URL || "https://teal-bat-675895.hostingersite.com/";
const mapasUrl = computed(() => buildExternalProductUrl(rawMapasUrl));
const rawEnergyUrl = import.meta.env.VITE_REGLADO_ENERGY_URL || "http://localhost:5174";
const energyUrl = computed(() => buildExternalProductUrl(rawEnergyUrl));

const userMenuOpen = ref(false);
const mobileMenuOpen = ref(false);
const headerRef = ref(null);
const isScrolled = ref(false);
const isDarkMode = ref(false);
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

function toggleDarkMode() {
  isDarkMode.value = !isDarkMode.value;
  if (isDarkMode.value) {
    document.body.classList.add("dark-mode");
    if (auth.hasConsentCategory('preferences')) {
      auth.setCookie("reglado_theme", "dark", 60 * 60 * 24 * 365, "Lax");
    }
  } else {
    document.body.classList.remove("dark-mode");
    if (auth.hasConsentCategory('preferences')) {
      auth.setCookie("reglado_theme", "light", 60 * 60 * 24 * 365, "Lax");
    }
  }
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

  const savedTheme = auth.getCookie("reglado_theme");
  const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;

  if (savedTheme === "dark" || (!savedTheme && prefersDark)) {
    isDarkMode.value = true;
    document.body.classList.add("dark-mode");
  }
});

onBeforeUnmount(() => {
  document.removeEventListener("pointerdown", handlePointerDown);
  window.removeEventListener("scroll", handleScroll);
});

function buildExternalProductUrl(baseUrl) {
  if (!baseUrl || baseUrl === "#") {
    return "#";
  }

  const cleanBase = String(baseUrl).replace(/\/+$/, "");
  const token = auth.state.token;

  if (!token) {
    return cleanBase;
  }

  return `${cleanBase}/auth/callback?token=${encodeURIComponent(token)}`;
}
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
  transition: transform 0.7s cubic-bezier(0.22, 1, 0.36, 1), filter 0.4s ease;
}

.brand-link:hover .brand-logo {
  transform: rotate(180deg) scale(1.05);
  filter: drop-shadow(0 0.35rem 0.8rem rgba(255, 255, 255, 0.15));
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

.theme-toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 39px;
  height: 39px;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.26);
  background: rgba(255, 255, 255, 0.08);
  color: #fff;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  padding: 0;
  outline: none;
}

.theme-toggle:hover {
  background: rgba(255, 255, 255, 0.16);
  transform: rotate(15deg) scale(1.05);
  box-shadow: 0 0 15px rgba(255, 255, 255, 0.1);
}

.theme-icon {
  display: block;
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
  border: 1px solid rgba(255, 255, 255, 0.26);
  border-radius: 999px;
  width: 39px;
  height: 39px;
  padding: 0;
  background: rgba(255, 255, 255, 0.08);
  display: grid;
  place-items: center;
  text-decoration: none;
  -webkit-tap-highlight-color: transparent;
  transition: transform .2s ease, background-color .2s ease;
}
.user-pill:hover {
  transform: translateY(-1px);
  background: rgba(255, 255, 255, 0.16);
}
.user-initial {
  color: #fff;
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
  border: 1px solid var(--line);
  border-radius: 12px;
  background: var(--surface);
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
  padding: 0.35rem;
  display: grid;
  gap: 0.25rem;
  z-index: 60;
  transition: background-color 0.4s ease;
}
.user-menu-info {
  padding: 10px 12px;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.user-menu-name {
  font-weight: 700;
  font-size: 14px;
  color: var(--text);
  line-height: 1.2;
}
.user-menu-email {
  font-size: 11px;
  color: var(--text-muted, #888);
  word-break: break-all;
}
.user-menu-divider {
  height: 1px;
  background: var(--line);
  margin: 4px 8px;
}

.user-menu-item {
  width: 100%;
  border: none;
  border-radius: 9px;
  background: transparent;
  color: var(--text);
  text-align: left;
  padding: 0.54rem 0.62rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.user-menu-item:hover {
  background: var(--surface-soft);
  color: var(--primary);
}

.user-menu-item.danger {
  color: var(--danger);
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
    border: 1px solid var(--line);
    border-radius: 16px;
    background: var(--surface);
    box-shadow: 0 18px 34px rgba(0, 0, 0, 0.28);
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
    color: var(--text);
    background: var(--surface-soft);
    display: flex;
    align-items: center;
    gap: 0.7rem;
    padding: 0.75rem 0.9rem;
    border: 1px solid var(--line);
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

  .menu-divider {
    height: 1px;
    background: var(--line);
    margin: 0.4rem 0.6rem;
  }

  .mobile-session-action.danger {
    color: #ef4444;
  }

  .mobile-user-profile {
    display: flex;
    align-items: center;
    gap: 0.9rem;
    padding: 0.2rem 0.6rem 0.5rem;
  }

  .mobile-avatar {
    width: 46px;
    height: 46px;
    border-radius: 999px;
    background: var(--surface-soft);
    border: 1px solid var(--line);
    display: grid;
    place-items: center;
  }

  .mobile-avatar .user-initial {
    font-size: 1.2rem;
    color: var(--text);
  }

  .mobile-user-info {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
  }

  .mobile-username {
    font-family: "Manrope", "Trebuchet MS", sans-serif;
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--text);
    line-height: 1.2;
  }

  .mobile-role {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--primary, #0ea5e9);
    letter-spacing: 0.02em;
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


