<template>
  <header ref="headerRef" class="header">
    <div class="header-inner">
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
          <!-- SUBMENU CLIENTES DESHABILITADO.
          <div class="dropdown-menu" role="menu" aria-label="Submenu clientes">
            <router-link to="/particulares" class="dropdown-link" role="menuitem">Particulares</router-link>
            <router-link to="/empresas" class="dropdown-link" role="menuitem">Empresas y Pymes</router-link>
            <router-link to="/administradores-fincas" class="dropdown-link" role="menuitem">Comunidades y Fincas</router-link>
            <router-link to="/sector-publico" class="dropdown-link" role="menuitem">Organismos públicos</router-link>
          </div> -->
        </div> 

        <router-link to="/recursos" class="nav-link">Recursos</router-link>
                <router-link to="/sobre-nosotros" class="nav-link">Sobre nosotros</router-link>
      </nav>

      <div class="nav-actions">
        <a
          href="https://regladogroup.com/"
          class="group-link"
          target="_blank"
          rel="noopener noreferrer"
        >
          Reglado Group
        </a>
        <router-link to="/contacto" class="btn primary glow header-action" v-glow>
          Solicitar análisis
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
              <div class="user-menu-info">
                <span class="user-menu-name">{{ user.name }}</span>
                <span class="user-menu-email">{{ user.email }}</span>
              </div>
              <div class="user-menu-divider"></div>
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
        <a
          href="https://regladogroup.com/"
          class="m-link m-link-group-home"
          target="_blank"
          rel="noopener noreferrer"
        >
          <span class="m-link-group-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false">
              <path
                d="M12 3.2 4.5 9.4a1 1 0 0 0-.37.77V20a1 1 0 0 0 1 1h4.7a1 1 0 0 0 1-1v-4.6h2.34V20a1 1 0 0 0 1 1h4.7a1 1 0 0 0 1-1v-9.83a1 1 0 0 0-.36-.77L12 3.2Z"
              />
            </svg>
          </span>
          <span class="m-link-group-text">
            <strong>Reglado Group</strong>
            <small>Volver al grupo</small>
          </span>
        </a>
        <div class="mobile-divider" aria-hidden="true"></div>
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
            <router-link @click="closeMobileMenu" to="/sector-publico" class="m-sublink">Organismos públicos</router-link>
          </div>
        </div>

        <router-link @click="closeMobileMenu" to="/recursos" class="m-link">Recursos</router-link>
        <router-link @click="closeMobileMenu" to="/sobre-nosotros" class="m-link">Sobre nosotros</router-link>
        <div class="mobile-divider" aria-hidden="true"></div>
        <router-link @click="closeMobileMenu" to="/contacto" class="btn primary glow mobile-action" v-glow>
          Solicitar análisis
        </router-link>
        
        <template v-if="user">
          <div class="m-user-profile">
            <div class="m-user-avatar">
              <span class="user-initial">{{ userInitial }}</span>
            </div>
            <div class="m-user-info">
              <span class="m-user-name">{{ user.name }}</span>
              <span class="m-user-email">{{ user.email }}</span>
            </div>
          </div>
          <div class="m-user-actions">
            <button @click="handleMobileSettings" class="m-action-btn">Configuración</button>
            <button @click="handleMobileLogout" class="m-action-btn danger">Cerrar sesión</button>
          </div>
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
import logo from "../assets/reglado-logo.svg";
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
  const base = `${window.location.origin}${window.location.pathname}`;
  const sep = base.endsWith("/") ? "" : "/";
  return `${base}${sep}auth/callback`;
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
.header-inner{ display:flex; align-items:center; justify-content:space-between; padding: 14px 20px; gap: 14px; position: relative; width: 100%; box-sizing: border-box; }
.brand{ display:flex; align-items:center; gap: 12px; position: relative; z-index: 10; }
.logo{ 
  width: 44px; 
  height: 44px; 
  object-fit: contain; 
  transition: transform 0.7s cubic-bezier(0.22, 1, 0.36, 1), filter 0.4s ease;
}

.brand:hover .logo {
  transform: rotate(180deg) scale(1.05);
  filter: drop-shadow(0 0.35rem 0.8rem rgba(255, 255, 255, 0.15));
}
.brand-name{ font-weight: 800; letter-spacing: .8px; }
.brand-sub{ font-size: 12px; color: rgba(233,238,246,.70); }
.nav{ display:flex; align-items:center; justify-content: center; gap: 6px; flex: 1; z-index: 5; pointer-events: none; }
.nav-actions{ display: flex; align-items: center; justify-content: flex-end; gap: 10px; position: relative; z-index: 10; pointer-events: auto; }
.group-link{
  position: relative;
  margin-right: 20px;
  color: rgba(255,255,255,.9);
  font-size: 14px;
  font-weight: 700;
  text-decoration: none;
  transition: color .18s ease;
}
.group-link::after{
  content: "";
  position: absolute;
  left: 0;
  right: 0;
  bottom: -4px;
  height: 1px;
  background: rgba(242,197,61,.95);
  transform: scaleX(0);
  transform-origin: left center;
  transition: transform .18s ease;
}
.group-link:hover{
  color: rgba(242,197,61,.95);
  text-shadow: 0 0 10px rgba(242,197,61,.2);
}
.group-link:hover::after{
  transform: scaleX(1);
}
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
  color: #ffffff;
  background: rgba(255,255,255,.03);
  display: grid;
  place-items: center;
  transition: transform .2s ease, box-shadow .2s ease;
}
.user-pill:hover {
  transform: scale(1.05);
  background: rgba(255,255,255,.08);
  border-color: rgba(242,197,61,.35);
}
.user-initial{
  color: inherit;
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
.user-menu-info {
  padding: 10px 12px;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.user-menu-name {
  font-weight: 700;
  font-size: 14px;
  color: rgba(233,238,246,.95);
  line-height: 1.2;
}
.user-menu-email {
  font-size: 11px;
  color: rgba(233,238,246,.55);
  word-break: break-all;
}
.user-menu-divider {
  height: 1px;
  background: rgba(255,255,255,.08);
  margin: 4px 8px;
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
.nav-link{ color: rgba(233,238,246,.82); font-size: 14px; padding: 10px 10px; border-radius: 12px; border: 1px solid transparent; pointer-events: auto; }
.nav-link:hover{ border-color: rgba(242,197,61,.25); background: rgba(255,255,255,.03); }
.nav-dropdown{ position: relative; pointer-events: auto; }
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
.m-link-group-home{
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border-color: rgba(27,92,142,.72);
  background:
    linear-gradient(135deg, rgba(27,92,142,.24), rgba(242,197,61,.18)),
    rgba(255,255,255,.03);
  box-shadow: 0 16px 30px rgba(0,0,0,.22);
}
.m-link-group-home:hover{
  border-color: rgba(242,197,61,.86);
  background:
    linear-gradient(135deg, rgba(27,92,142,.32), rgba(242,197,61,.24)),
    rgba(255,255,255,.05);
}
.m-link-group-icon{
  flex: 0 0 40px;
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: grid;
  place-items: center;
  background: rgba(11,13,16,.32);
  border: 1px solid rgba(255,255,255,.14);
  color: rgba(242,197,61,.98);
}
.m-link-group-icon svg{
  width: 18px;
  height: 18px;
  fill: currentColor;
}
.m-link-group-text{
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}
.m-link-group-text strong{
  font-size: 14px;
  line-height: 1.1;
  color: rgba(255,255,255,.96);
}
.m-link-group-text small{
  font-size: 11px;
  line-height: 1.2;
  color: rgba(233,238,246,.72);
}
.mobile-divider{
  height: 1px;
  width: 100%;
  background: linear-gradient(90deg, rgba(27,92,142,0), rgba(242,197,61,.55), rgba(27,92,142,0));
  opacity: .9;
  margin: 2px 0 4px;
}
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
  .header-inner{ padding: 12px 14px; gap: 8px; }
  .brand{ gap: 8px; }
  .nav, .nav-actions{ display:none; }
  .brand-sub{ display:none; }
  .mobile-controls{ display:flex; gap: 6px; }
  .burger{ display:block; }
  .mobile-admin-pill,
  .mobile-user-trigger{ display:grid; }
  
  /* Mobile User Profile */
  .m-user-profile {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    background: rgba(255,255,255,0.04);
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,0.08);
    margin-top: 8px;
  }
  .m-user-avatar {
    width: 44px;
    height: 44px;
    background: rgba(255,255,255,.03);
    border: 1px solid rgba(255,255,255,.18);
    color: #ffffff;
    border-radius: 50%;
    display: grid;
    place-items: center;
    flex-shrink: 0;
  }
  .m-user-avatar .user-initial {
    font-size: 1.1rem;
  }
  .m-user-info {
    display: flex;
    flex-direction: column;
    gap: 1px;
    min-width: 0;
  }
  .m-user-name {
    font-weight: 700;
    font-size: 14px;
    color: white;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .m-user-email {
    font-size: 11px;
    color: rgba(233,238,246,0.6);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .m-user-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-top: 4px;
  }
  .m-action-btn {
    padding: 10px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 10px;
    color: white;
    font-size: 13px;
    cursor: pointer;
  }
  .m-action-btn.danger {
    color: #ffb7b7;
    border-color: rgba(255, 183, 183, 0.2);
  }
}
</style>
