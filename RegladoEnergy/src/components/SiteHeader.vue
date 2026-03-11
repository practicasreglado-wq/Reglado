<template>
  <header ref="headerRef" class="header">
    <div class="container header-inner">
      <router-link class="brand" to="/">
        <img class="logo" :src="logo" alt="Reglado Energy" />
        <div class="brand-text">
          <div class="brand-name">REGLADO ENERGY</div>
          <div class="brand-sub">Consultoria energetica independiente</div>
        </div>
      </router-link>

      <nav class="nav">
        <router-link to="/" class="nav-link">Inicio</router-link>
        <router-link to="/servicios" class="nav-link">Servicios</router-link>
        <div class="nav-dropdown">
          <router-link to="/clientes" class="nav-link nav-drop-trigger" aria-haspopup="menu">
            Clientes
            <span class="caret" aria-hidden="true"></span>
          </router-link>
          <div class="dropdown-menu" role="menu" aria-label="Submenu clientes">
            <router-link to="/particulares" class="dropdown-link" role="menuitem">Particulares</router-link>
            <router-link to="/empresas" class="dropdown-link" role="menuitem">Empresas y Pymes</router-link>
            <router-link to="/administradores-fincas" class="dropdown-link" role="menuitem">Comunidades y Fincas</router-link>
            <router-link to="/sector-publico" class="dropdown-link" role="menuitem">Organismos publicos</router-link>
          </div>
        </div>
        <router-link to="/recursos" class="nav-link">Recursos</router-link>
        <router-link to="/sobre-nosotros" class="nav-link">Sobre nosotros</router-link>

        <div class="nav-actions">
          <router-link to="/contacto" class="btn primary glow header-action" v-glow>
            Solicitar analisis
          </router-link>

          <router-link
            v-if="isAdmin"
            to="/admin"
            class="admin-pill"
            title="Panel de administración"
            aria-label="Panel de administración"
          >
            <img :src="adminUserIcon" alt="" class="admin-icon" />
          </router-link>

          <template v-if="user">
            <div class="user-menu-wrap">
              <button
                class="user-pill user-menu-trigger"
                @click="toggleUserMenu"
                aria-haspopup="menu"
                :aria-expanded="userMenuOpen ? 'true' : 'false'"
                :title="displayUsername"
                aria-label="Menu de usuario"
              >
                <span class="user-initial" aria-hidden="true">{{ userInitial }}</span>
              </button>
              <div v-if="userMenuOpen" class="user-menu" role="menu" aria-label="Menu de usuario">
                <button class="user-menu-item" type="button" role="menuitem" @click="goToSettings">
                  Configuración
                </button>
                <button class="user-menu-item danger" type="button" role="menuitem" @click="handleLogout">
                  Cerrar sesión
                </button>
              </div>
            </div>
          </template>

          <template v-else>
            <button class="btn primary glow header-action" v-glow @click="goToLogin">
              Iniciar sesión
            </button>
          </template>
        </div>
      </nav>

      <div class="mobile-controls">
        <router-link
          v-if="isAdmin"
          to="/admin"
          class="admin-pill mobile-admin-pill"
          title="Panel de administracion"
          aria-label="Panel de administracion"
        >
          <img :src="adminUserIcon" alt="" class="admin-icon" />
        </router-link>

        <button
          v-if="user"
          class="user-pill mobile-user-trigger"
          :title="displayUsername"
          aria-label="Configuracion de usuario"
          @click="handleMobileSettings"
        >
          <span class="user-initial" aria-hidden="true">{{ userInitial }}</span>
        </button>

        <button class="burger" @click="toggleMobileMenu" aria-label="Abrir menu">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>

    <div v-if="open" class="mobile">
      <div class="container mobile-inner">
        <router-link @click="closeMobileMenu" to="/" class="m-link">Inicio</router-link>
        <router-link @click="closeMobileMenu" to="/servicios" class="m-link">Servicios</router-link>

        <div class="m-group">
          <router-link @click="closeMobileMenu" to="/clientes" class="m-link m-link-caret">
            Clientes
            <span
              class="m-caret m-caret-inline"
              :class="{ open: mobileClientsOpen }"
              @click.stop.prevent="mobileClientsOpen = !mobileClientsOpen"
              aria-hidden="true"
            >⌄</span>
          </router-link>

          <div v-show="mobileClientsOpen" class="m-submenu">
            <router-link @click="closeMobileMenu" to="/particulares" class="m-sublink">Particulares</router-link>
            <router-link @click="closeMobileMenu" to="/empresas" class="m-sublink">Empresas y Pymes</router-link>
            <router-link @click="closeMobileMenu" to="/administradores-fincas" class="m-sublink">Comunidades y Fincas</router-link>
            <router-link @click="closeMobileMenu" to="/sector-publico" class="m-sublink">Organismos publicos</router-link>
          </div>
        </div>

        <router-link @click="closeMobileMenu" to="/recursos" class="m-link">Recursos</router-link>
        <router-link @click="closeMobileMenu" to="/sobre-nosotros" class="m-link">Sobre nosotros</router-link>
        <router-link @click="closeMobileMenu" to="/contacto" class="btn primary glow mobile-action" v-glow>
          Solicitar analisis
        </router-link>        <template v-if="user">
          <button @click="handleMobileLogout" class="btn mobile-action">Salir</button>
        </template>

        <template v-else>
          <button @click="handleMobileLogin" class="btn primary glow mobile-action" v-glow>
            Iniciar sesión / registrarse
          </button>
        </template>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import logo from "../assets/reglado-energy-logo.svg";
import adminUserIcon from "../assets/admin-user-icon.svg";
import { auth } from "../services/auth";

const props = defineProps({
  user: {
    type: Object,
    default: null,
  },
});

const open = ref(false);
const mobileClientsOpen = ref(false);
const userMenuOpen = ref(false);
const headerRef = ref(null);
const mobileMediaQuery = "(max-width: 980px)";
let mediaQueryList;
const isAdmin = computed(() => props.user?.role === "admin");
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

function closeMobileMenu() {
  open.value = false;
  mobileClientsOpen.value = false;
}

function toggleMobileMenu() {
  open.value = !open.value;
  if (!open.value) mobileClientsOpen.value = false;
}

function toggleUserMenu() {
  userMenuOpen.value = !userMenuOpen.value;
}

function handlePointerDown(event) {
  if (userMenuOpen.value) {
    const menuTrigger = event.target.closest?.(".user-menu-wrap");
    if (!menuTrigger) {
      userMenuOpen.value = false;
    }
  }

  if (!open.value) return;

  const headerEl = headerRef.value;
  if (headerEl && !headerEl.contains(event.target)) {
    closeMobileMenu();
  }
}

function handleMediaChange(event) {
  if (!event.matches && open.value) {
    closeMobileMenu();
  }
}

function getCallbackUrl() {
  return `${window.location.origin}${window.location.pathname}#/auth/callback`;
}

function buildExternalAuthUrl(path) {
  const base = import.meta.env.VITE_GRUPO_REGLADO_BASE_URL || "http://localhost:5173";
  const url = new URL(path, base);
  url.searchParams.set("returnTo", getCallbackUrl());
  return url.toString();
}

function goToLogin() {
  const loginPath = import.meta.env.VITE_GRUPO_REGLADO_LOGIN_PATH || "/login";
  window.location.href = buildExternalAuthUrl(loginPath);
}

function goToSettings() {
  userMenuOpen.value = false;
  const base = import.meta.env.VITE_GRUPO_REGLADO_BASE_URL || "http://localhost:5173";
  const settingsPath = import.meta.env.VITE_GRUPO_REGLADO_SETTINGS_PATH || "/configuracion";
  window.location.href = new URL(settingsPath, base).toString();
}

async function handleLogout() {
  userMenuOpen.value = false;
  await auth.logout();
  window.location.reload();
}

function handleMobileLogin() {
  closeMobileMenu();
  goToLogin();
}

function handleMobileSettings() {
  closeMobileMenu();
  goToSettings();
}

async function handleMobileLogout() {
  closeMobileMenu();
  userMenuOpen.value = false;
  await auth.logout();
  window.location.reload();
}

onMounted(() => {
  document.addEventListener("pointerdown", handlePointerDown);

  mediaQueryList = window.matchMedia(mobileMediaQuery);
  mediaQueryList.addEventListener("change", handleMediaChange);
});

onBeforeUnmount(() => {
  document.removeEventListener("pointerdown", handlePointerDown);

  if (mediaQueryList) {
    mediaQueryList.removeEventListener("change", handleMediaChange);
  }
});
</script>

<style scoped>
.header{ position: sticky; top:0; z-index: 50; backdrop-filter: blur(12px); background: rgba(11,13,16,.65); border-bottom: 1px solid rgba(255,255,255,.08); }
.header-inner{ display:flex; align-items:center; justify-content:space-between; padding: 14px 0; gap: 14px; }
.brand{ display:flex; align-items:center; gap: 12px; }
.logo{ width: 44px; height: 44px; object-fit: contain; }
.brand-name{ font-weight: 800; letter-spacing: .8px; }
.brand-sub{ font-size: 12px; color: rgba(233,238,246,.70); }
.nav{ display:flex; align-items:center; gap: 6px; }
.nav-actions{ display: flex; align-items: center; gap: 10px; margin-left: 40px; }
.header-action{ min-width: 124px; min-height: 36px; padding: 0 12px; font-size: 12px; line-height: 1; white-space: nowrap; }
.admin-pill{
  width: 38px;
  height: 38px;
  border: 1px solid rgba(255,255,255,.18);
  border-radius: 999px;
  background: rgba(255,255,255,.03);
  display: grid;
  place-items: center;
  transition: background 0.18s ease, border-color 0.18s ease, transform 0.18s ease;
}
.admin-pill:hover{
  background: rgba(255,255,255,.08);
  border-color: rgba(242,197,61,.35);
  transform: translateY(-1px);
}
.admin-icon{ width: 20px; height: 20px; display: block; }
.user-pill{
  border: 1px solid rgba(255,255,255,.18);
  border-radius: 999px;
  width: 38px;
  height: 38px;
  padding: 0;
  font-size: 12px;
  color: rgba(233,238,246,.9);
  background: rgba(255,255,255,.03);
  display: grid;
  place-items: center;
}
.user-initial{
  color: rgba(233,238,246,.95);
  font-size: 0.98rem;
  font-weight: 800;
  line-height: 1;
}
.user-menu-wrap{
  position: relative;
}
.user-menu-trigger{
  cursor: pointer;
}
.user-menu{
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  min-width: 180px;
  background: #0f1318;
  border: 1px solid rgba(242,197,61,.24);
  border-radius: 12px;
  box-shadow: 0 18px 40px rgba(0,0,0,.45);
  padding: 6px;
  display: grid;
  gap: 4px;
  z-index: 90;
}
.user-menu-item{
  width: 100%;
  text-align: left;
  border: 1px solid transparent;
  background: transparent;
  color: rgba(233,238,246,.9);
  border-radius: 9px;
  padding: 8px 10px;
  cursor: pointer;
  font-size: 13px;
}
.user-menu-item:hover{
  border-color: rgba(242,197,61,.25);
  background: rgba(255,255,255,.06);
}
.user-menu-item.danger{
  color: #ffb7b7;
}
.nav-link{ color: rgba(233,238,246,.82); font-size: 14px; padding: 10px 10px; border-radius: 12px; border: 1px solid transparent; }
.nav-link:hover{ border-color: rgba(242,197,61,.25); background: rgba(255,255,255,.03); }
.nav-dropdown{ position: relative; }
.nav-drop-trigger{ display: inline-flex; align-items: center; gap: 7px; cursor: pointer; font-family: inherit; }
.caret{ font-size: 12px; color: rgba(242,197,61,.85); transition: transform .18s ease; }
.dropdown-menu{ position: absolute; top: 100%; left: 0; min-width: 235px; margin-top: 0; padding: 8px; border-radius: 14px; border: 1px solid rgba(242,197,61,.24); background: #0f1318; box-shadow: 0 20px 46px rgba(0,0,0,.42); opacity: 0; transform: translateY(8px) scale(.985); pointer-events: none; transition: opacity .16s ease, transform .16s ease; z-index: 60; }
.dropdown-link{ display: block; padding: 10px 12px; border-radius: 10px; border: 1px solid transparent; color: rgba(233,238,246,.86); font-size: 14px; }
.dropdown-link:hover{ border-color: rgba(242,197,61,.25); background: rgba(255,255,255,.05); color: rgba(255,255,255,.96); }
.dropdown-link.router-link-active{ border-color: rgba(242,197,61,.38); background: rgba(242,197,61,.12); color: rgba(255,255,255,.98); }
.nav-dropdown:hover .dropdown-menu,
.nav-dropdown:focus-within .dropdown-menu{ opacity: 1; transform: translateY(0) scale(1); pointer-events: auto; }
.nav-dropdown:hover .caret,
.nav-dropdown:focus-within .caret{ transform: rotate(180deg); }
.burger{ display:none; background: transparent; border:none; cursor:pointer; width: 44px; height: 44px; border-radius: 14px; -webkit-tap-highlight-color: transparent; outline: none; }
.burger span{ display:block; height:2px; margin:6px 10px; background: rgba(233,238,246,.85); }
.burger:focus-visible{ outline: 2px solid rgba(242,197,61,.7); outline-offset: 2px; }
.mobile-controls{ display:none; align-items:center; gap:10px; }
.mobile{ position: absolute; top: 100%; left: 0; right: 0; z-index: 70; border-top: 1px solid rgba(255,255,255,.08); background: rgba(15, 16, 11, 0.95); box-shadow: 0 20px 40px rgba(0,0,0,.35); }
.mobile-inner{ padding: 14px 0 18px; display:flex; flex-direction:column; gap: 10px; }
.m-group{ display:flex; flex-direction:column; gap: 8px; }
.m-caret{ display:inline-block; transition: transform .18s ease; }
.m-caret.open{ transform: rotate(180deg); }
.m-link-caret{ position: relative; padding-right: 40px; -webkit-tap-highlight-color: transparent; }
.m-caret-inline{ position: absolute; right: 12px; top: 50%; transform: translateY(-50%); display: inline-block; color: rgba(242,197,61,.95); -webkit-tap-highlight-color: transparent; }
.m-caret-inline.open{ transform: translateY(-50%) rotate(180deg); }
.m-submenu{ display:flex; flex-direction:column; gap: 8px; padding-left: 12px; }
.m-sublink{ padding: 10px 12px; border-radius: 12px; border: 1px solid rgba(242,197,61,.52); border-bottom: 1px solid rgba(242,197,61,.52); background: transparent; color: rgba(233,238,246,.9); font-size: 14px; }
.m-sublink.router-link-active{ color: rgba(233,238,246,.9); border-color: rgba(242,197,61,.52); border-bottom-color: rgba(242,197,61,.52); background: rgba(242,197,61,.16); }
.m-link{ padding: 12px 12px; border-radius: 14px; border: 1px solid rgba(242,197,61,.84); border-bottom: 1px solid rgba(242,197,61,.84); background: transparent; }
.m-link.router-link-active{ color: rgba(233,238,246,.9); border-color: rgba(242,197,61,.84); border-bottom-color: rgba(242,197,61,.84); background: rgba(242,197,61,.16); }
.mobile-action{ width: 100%; min-height: 38px; padding: 8px 12px; font-size: 13px; border-radius: 12px; }
@media (max-width: 980px){
  .nav{ display:none; }
  .mobile-controls{ display:flex; }
  .burger{ display:block; }
  .mobile-admin-pill,
  .mobile-user-trigger{ display:grid; }
}
</style>

