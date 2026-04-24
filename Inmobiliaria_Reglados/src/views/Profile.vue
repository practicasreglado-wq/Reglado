<template>
<section
  ref="profileRoot"
  class="profile"
  :class="{ 'profile--sidebar-pinned': isSidebarPinned && isDesktopViewport }"
>

<button
  class="sidebar-edge-trigger"
  type="button"
  aria-label="Abrir menu lateral"
  @mouseenter="handleDesktopEdgeEnter"
></button>

<button
  v-if="isDesktopViewport && !isSidebarPinned && !desktopSidebarVisible"
  class="sidebar-peek-indicator"
  type="button"
  aria-label="Mostrar menu lateral"
  @mouseenter="handleDesktopEdgeEnter"
  @click="handleDesktopEdgeEnter"
>
  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4">
    <path d="M8 5l8 7-8 7" stroke-linecap="round" stroke-linejoin="round"></path>
  </svg>
</button>

<button
  v-if="isProfileMenuOpen"
  class="sidebar-overlay"
  type="button"
  aria-label="Cerrar menu de perfil"
  @click="closeProfileMenu"
></button>

<div
  :key="sidebarRenderKey"
  class="sidebar"
  :class="{
    open: isProfileMenuOpen,
    'desktop-open': desktopSidebarVisible,
    'sidebar--pinned': isSidebarPinned && isDesktopViewport
  }"
  @mouseenter="handleDesktopSidebarEnter"
  @mouseleave="handleDesktopSidebarLeave"
>
<div class="sidebar-panel">
<div class="profile-hero">

<div class="hero-left">
<button
  v-if="isDesktopViewport"
  class="sidebar-pin-toggle"
  type="button"
  :aria-label="isSidebarPinned ? 'Ocultar menu' : 'Mostrar menu'"
  :title="isSidebarPinned ? 'Ocultar menu' : 'Mostrar menu'"
  @click="toggleSidebarPinned"
>
  <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8">
    <rect x="3" y="5" width="18" height="14" rx="2.5"></rect>
    <path d="M9 5v14"></path>
  </svg>
</button>
<span class="sidebar-kicker">Area privada</span>
<div v-if="user" class="sidebar-profile-chip">
<div class="sidebar-avatar" :class="avatarClass">{{ userInitials }}</div>
<div>
<h2>Hola {{ user.nombre_usuario }}</h2>
</div>
</div>
</div>
</div>
<h3>Menu de perfil</h3>

<ul class="sidebar-nav">
      <li>
        <router-link to="/profile/properties-for-sale" @click="closeProfileMenu">
          Inicio
        </router-link>
      </li>

      <li v-if="isReal">
        <router-link to="/profile/favorite-properties" @click="closeProfileMenu">
          Mis propiedades favoritas
        </router-link>
      </li>

      <li v-if="isReal">
        <router-link to="/profile/search-history" @click="closeProfileMenu">
          Historial de busquedas
        </router-link>
      </li>

      <li>
        <router-link to="/profile/my-properties-for-sale" @click="closeProfileMenu">
          Mis propiedades
        </router-link>
      </li>

      <li>
        <button class="sidebar-link" type="button" @click="goToSettings(); closeProfileMenu()">
          Configuracion
        </button>
      </li>
</ul>

<div v-if="user" class="logout-item">
<button class="logout-btn" @click="logout">
Cerrar sesion
</button>
</div>

</div>
</div>

<div class="profile-content">

<div v-if="user && isProfileHome" class="profile-home">

<section class="profile-home-hero">
<div class="hero-copy">
<div class="hero-title-row">
<div class="hero-avatar" :class="avatarClass">{{ userInitials }}</div>
<div>
<h1>Bienvenido, {{ user.nombre_usuario }}</h1>
</div>
</div>

</div>

<div class="hero-badges hero-badges--side">
<span class="hero-badge">{{ accessLabel }}</span>
</div>
</section>

<router-view
v-if="isProfileHome"
v-slot="{ Component, route }"
>
<transition
name="profile-page-transition"
mode="out-in"
@after-enter="handleProfileRouteEntered"
>
<div :key="route.fullPath" class="profile-route-shell profile-route-shell--top">
<component :is="Component"></component>
</div>
</transition>
</router-view>

      <div v-if="!isReal" class="restricted-notice">
        <div class="notice-icon">
          <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <div class="notice-content">
          <h4>Acceso Limitado</h4>
          <p>Tu cuenta actualmente tiene acceso limitado.</p>
          <p>Para acceder al catalogo completo de propiedades y a todas las herramientas de la plataforma necesitas una cuenta <strong>PREMIUM</strong>.</p>
          <p class="notice-footer">Ponte en contacto con nosotros para activar todos los servicios.</p>
          <button class="contact-upgrade-btn" @click="$router.push('/contacto')">Contactar ahora</button>
        </div>
      </div>

      <section v-if="isReal" class="quick-actions-panel">
        <div class="section-heading">
          <div class="profile-cityscape profile-cityscape--work" aria-hidden="true">
            <span class="profile-cityscape__sky"></span>
            <span class="profile-cityscape__moon"></span>
            <span class="profile-cityscape__stars"></span>
            <span class="profile-cityscape__layer profile-cityscape__layer--far"></span>
            <span class="profile-cityscape__layer profile-cityscape__layer--mid"></span>
            <span class="profile-cityscape__layer profile-cityscape__layer--front"></span>
          </div>
          <div>
            <span class="section-kicker">Accesos directos</span>
            <h3>Tu zona de trabajo</h3>
          </div>
        </div>

        <div class="dashboard-grid">

<router-link to="/profile/favorite-properties" class="dashboard-card">
<div class="card-icon">
<svg viewBox="0 0 24 24" width="28" height="28" fill="none">
<path d="M12 17.3L5.8 21l1.6-7L2 9.5l7.2-.6L12 2l2.8 6.9 7.2.6-5.4 4.5 1.6 7z"
stroke="currentColor"
stroke-width="1.8"
stroke-linejoin="round"/>
</svg>
</div>
<div class="dashboard-card-info">
<h4>Favoritos</h4>
<p>Propiedades que has guardado</p>
</div>
</router-link>

<router-link to="/profile/my-properties-for-sale" class="dashboard-card">
<div class="card-icon">
<svg viewBox="0 0 24 24" width="28" height="28" fill="none">
<path d="M3 11L12 4L21 11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
<path d="M5 10V20H19V10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
</svg>
</div>
<div class="dashboard-card-info">
<h4>Mis propiedades</h4>
<p>Gestiona tus anuncios</p>
</div>
</router-link>

<router-link to="/profile/search-history" class="dashboard-card">
<div class="card-icon">
<svg viewBox="0 0 24 24" width="28" height="28" fill="none">
<circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8" />
<path d="M20 20l-4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
<path d="M11 8v3l2.5 2.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
</svg>
</div>
<div class="dashboard-card-info">
<h4>Historial de busquedas</h4>
<p>Revisa tus busquedas anteriores</p>
</div>
</router-link>

      </div>
      </section>

      <div v-if="isReal && preferenceEntries.length" class="preferences">
<div class="section-heading">
<div class="profile-cityscape profile-cityscape--investment" aria-hidden="true">
<span class="profile-cityscape__sky"></span>
<span class="profile-cityscape__moon"></span>
<span class="profile-cityscape__stars"></span>
<span class="profile-cityscape__layer profile-cityscape__layer--far"></span>
<span class="profile-cityscape__layer profile-cityscape__layer--mid"></span>
<span class="profile-cityscape__layer profile-cityscape__layer--front"></span>
</div>
<div>
<span class="section-kicker">Preferencias guardadas</span>
<h3>Resumen de inversion</h3>
</div>
</div>
<PreferencePanel :category="category" :entries="preferenceEntries" />

<div class="preferences-actions">
<button
  class="save-search-btn"
  type="button"
  :disabled="savingSearch"
  @click="saveSearch"
>
  {{ savingSearch ? "Guardando..." : "Guardar busqueda" }}
</button>

<p v-if="saveSearchMessage" class="save-search-message">
  {{ saveSearchMessage }}
</p>
</div>
</div>

<div v-else-if="isReal" class="no-pref">
No tienes preferencias guardadas todavia
</div>

</div>

<router-view
v-if="!isProfileHome"
v-slot="{ Component, route }"
>
<transition
name="profile-page-transition"
mode="out-in"
@after-enter="handleProfileRouteEntered"
>
<div :key="route.fullPath" class="profile-route-shell">
<component :is="Component"></component>
</div>
</transition>
</router-view>

</div>

</section>
</template>

<script>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { storeToRefs } from "pinia";
import { useRouter, useRoute } from "vue-router";
import { useUserStore } from "../stores/user";
import { useProfileMenuStore } from "../stores/profileMenu";
import PreferencePanel from "../components/PreferencePanel.vue";
import { buildPreferenceEntries } from "../data/preferenceSchemas";
import { backendJson } from "../services/backend";

const CATEGORY_LABELS = {
  activos: "Activos",
  edificios: "Edificios",
  fincas: "Fincas",
  hoteles: "Hoteles",
  parking: "Parking",
};
const SIDEBAR_PIN_STORAGE_KEY = "profile-sidebar-pinned";

export default {
  components: {
    PreferencePanel,
  },

  setup() {
    const userStore = useUserStore();
    const profileMenuStore = useProfileMenuStore();
    const router = useRouter();
    const route = useRoute();

    const { user, selectedCategory: category, preferences, isAdmin, isReal } = storeToRefs(userStore);
    const { isOpen: isProfileMenuOpen } = storeToRefs(profileMenuStore);
    const savingSearch = ref(false);
    const saveSearchMessage = ref("");
    const desktopSidebarVisible = ref(true);
    const isDesktopViewport = ref(false);
    const isSidebarPinned = ref(true);
    const sidebarRenderKey = ref(0);
    const profileRoot = ref(null);
    let desktopSidebarCloseTimer = null;
    let desktopEdgeHandler = null;

    function goToSettings() {
      const base = import.meta.env.VITE_GRUPO_REGLADO_BASE_URL || "http://localhost:5173";
      const settingsPath = import.meta.env.VITE_GRUPO_REGLADO_SETTINGS_PATH || "/configuracion";
      window.location.href = new URL(settingsPath, base).toString();
    }

    const getSavedPinnedPreference = () => {
      const savedPreference = window.localStorage.getItem(SIDEBAR_PIN_STORAGE_KEY);
      if (savedPreference === "true") {
        return true;
      }

      if (savedPreference === "false") {
        return false;
      }

      return true;
    };

    const syncViewportMode = () => {
      const nextDesktop = window.innerWidth > 768;
      const savedPinnedPreference = getSavedPinnedPreference();
      isDesktopViewport.value = nextDesktop;

      if (!nextDesktop) {
        desktopSidebarVisible.value = false;
        isSidebarPinned.value = false;
      } else {
        isSidebarPinned.value = savedPinnedPreference;
        desktopSidebarVisible.value = savedPinnedPreference;
      }
    };

    const resetDesktopSidebarState = () => {
      clearDesktopSidebarTimer();
      if (!isDesktopViewport.value) {
        isSidebarPinned.value = false;
        desktopSidebarVisible.value = false;
        return;
      }

      const savedPinnedPreference = getSavedPinnedPreference();
      isSidebarPinned.value = savedPinnedPreference;
      desktopSidebarVisible.value = savedPinnedPreference;
    };

    const clearDesktopSidebarTimer = () => {
      if (desktopSidebarCloseTimer) {
        clearTimeout(desktopSidebarCloseTimer);
        desktopSidebarCloseTimer = null;
      }
    };

    const openDesktopSidebar = () => {
      if (!isDesktopViewport.value || isSidebarPinned.value) {
        return;
      }
      clearDesktopSidebarTimer();
      desktopSidebarVisible.value = true;
    };

    const scheduleDesktopSidebarClose = () => {
      if (!isDesktopViewport.value || isSidebarPinned.value) {
        return;
      }

      clearDesktopSidebarTimer();
      desktopSidebarCloseTimer = setTimeout(() => {
        desktopSidebarVisible.value = false;
      }, 170);
    };

    const handleDesktopEdgeEnter = () => {
      openDesktopSidebar();
    };

    const handleDesktopSidebarEnter = () => {
      openDesktopSidebar();
    };

    const handleDesktopSidebarLeave = () => {
      scheduleDesktopSidebarClose();
    };

    const toggleSidebarPinned = () => {
      if (!isDesktopViewport.value) {
        return;
      }

      isSidebarPinned.value = !isSidebarPinned.value;
      desktopSidebarVisible.value = isSidebarPinned.value;
      window.localStorage.setItem(
        SIDEBAR_PIN_STORAGE_KEY,
        String(isSidebarPinned.value)
      );
    };

    const stripRevealClassesFromFixedLayout = () => {
      const root = profileRoot.value;
      if (!root) {
        return;
      }

      root.classList.remove("reveal-hidden", "reveal-visible");
      delete root.dataset.revealBound;
      root.style.removeProperty("--reveal-delay");
    };

    const refreshSidebarAfterNavigation = async () => {
      syncViewportMode();
      await nextTick();
      stripRevealClassesFromFixedLayout();
      sidebarRenderKey.value += 1;
    };

    const handleProfileRouteEntered = async () => {
      await refreshSidebarAfterNavigation();
    };

    onMounted(() => {
      const savedCategory = localStorage.getItem("selectedCategory");
      if (savedCategory && userStore.selectedCategory !== savedCategory) {
        userStore.setCategory(savedCategory);
      }

      resetDesktopSidebarState();
      syncViewportMode();
      stripRevealClassesFromFixedLayout();
      window.addEventListener("resize", syncViewportMode);

      desktopEdgeHandler = (event) => {
        if (!isDesktopViewport.value) {
          return;
        }

        if (isSidebarPinned.value) {
          return;
        }

        if (event.clientX <= 26) {
          openDesktopSidebar();
        } else if (desktopSidebarVisible.value && event.clientX > 340) {
          scheduleDesktopSidebarClose();
        }
      };

      window.addEventListener("mousemove", desktopEdgeHandler);
    });

    onBeforeUnmount(() => {
      resetDesktopSidebarState();
      clearDesktopSidebarTimer();
      window.removeEventListener("resize", syncViewportMode);

      if (desktopEdgeHandler) {
        window.removeEventListener("mousemove", desktopEdgeHandler);
      }
    });

    const logout = async () => {
      profileMenuStore.close();
      await userStore.logout();
      router.push("/");
    };

    const closeProfileMenu = () => {
      profileMenuStore.close();
    };

    const preferenceEntries = computed(() =>
      buildPreferenceEntries(category.value, preferences.value)
    );

    const userInitials = computed(() => {
      const source = user.value?.nombre_usuario || user.value?.nombre || "";
      return source
        .split(" ")
        .filter(Boolean)
        .slice(0, 2)
        .map((chunk) => chunk[0]?.toUpperCase() || "")
        .join("") || "RG";
    });

    const accessLabel = computed(() => (isReal.value ? "Cuenta verificada" : "Acceso limitado"));
    const saveSearch = async () => {
      if (!user.value?.iduser || !category.value || !preferenceEntries.value.length) {
        saveSearchMessage.value = "No hay una busqueda completa para guardar.";
        return;
      }

      savingSearch.value = true;
      saveSearchMessage.value = "";

      try {
        const payload = await backendJson("api/save_search.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            user_id: user.value.iduser,
            category: category.value,
            preferences: preferences.value,
          }),
        });
        saveSearchMessage.value = payload.message || "Busqueda guardada correctamente.";
      } catch (err) {
        saveSearchMessage.value = err.message || "No se pudo guardar la busqueda.";
      } finally {
        savingSearch.value = false;
      }
    };

    const isProfileHome = computed(() => {
      return route.path === "/profile" || route.path === "/profile/properties-for-sale";
    });

    watch(
      () => route.fullPath,
      () => {
        profileMenuStore.close();
      }
    );

    return {
      user,
      category,
      preferenceEntries,
      userInitials,
      accessLabel,
      desktopSidebarVisible,
      isDesktopViewport,
      isSidebarPinned,
      sidebarRenderKey,
      profileRoot,
      handleDesktopEdgeEnter,
      handleDesktopSidebarEnter,
      handleDesktopSidebarLeave,
      handleProfileRouteEntered,
      toggleSidebarPinned,
      logout,
      isProfileHome,
      isProfileMenuOpen,
      closeProfileMenu,
      goToSettings,
      saveSearch,
      savingSearch,
      saveSearchMessage,
      isAdmin,
      isReal,
      avatarClass: computed(() => {
        if (isAdmin.value) return 'avatar--gold';
        if (isReal.value) return 'avatar--silver';
        return 'avatar--bronze';
      }),
    };
  },
};
</script>

<style scoped>
.profile{
position:relative;
display:flex;
align-items:stretch;
min-height:100vh;
background: linear-gradient(180deg, #b6c6d6, #eef2f7);
}

.sidebar-edge-trigger{
position:fixed;
top:90px;
left:0;
width:10px;
height:calc(100vh - 90px);
border:none;
padding:0;
background:transparent;
z-index:993;
}

.profile--sidebar-pinned .sidebar-edge-trigger{
display:none;
}

.sidebar-peek-indicator{
position:fixed;
top:50%;
left:0;
transform:translateY(-50%);
width:34px;
height:96px;
display:grid;
place-items:center;
border:none;
padding:0;
border-top-right-radius:16px;
border-bottom-right-radius:16px;
background:
radial-gradient(circle at top, rgba(244, 208, 120, 0.18), transparent 34%),
linear-gradient(180deg,#142856 0%, #12244d 46%, #0d1a38 100%);
color:#f4d078;
box-shadow:14px 0 30px rgba(8,18,42,0.22);
z-index:994;
cursor:pointer;
transition:transform 0.22s ease, width 0.22s ease, box-shadow 0.22s ease;
}

.sidebar-peek-indicator:hover{
width:40px;
box-shadow:18px 0 34px rgba(8,18,42,0.26);
}

.sidebar-peek-indicator svg{
transform:scaleX(1.05) scaleY(1.45);
}

.sidebar-overlay{
display:none;
}

.sidebar{
position:fixed;
top:90px;
left:0;
width:clamp(220px, 24vw, 300px);
height:calc(100vh - 90px);
min-height:calc(100vh - 90px);
background:
radial-gradient(circle at top, rgba(244, 208, 120, 0.22), transparent 28%),
linear-gradient(180deg,#142856 0%, #12244d 46%, #0d1a38 100%);
box-shadow:14px 0 40px rgba(8,18,42,0.2);
z-index:995;
border-top-right-radius:28px;
border-bottom-right-radius:28px;
transform:translateX(calc(-100% + 10px));
opacity:0.98;
transition:transform 0.28s ease, box-shadow 0.28s ease;
}

.sidebar.desktop-open{
transform:translateX(0);
box-shadow:22px 0 52px rgba(8,18,42,0.26);
}

.sidebar.sidebar--pinned{
position:fixed;
top:90px;
left:0;
transform:none;
width:clamp(220px, 24vw, 300px);
height:calc(100vh - 90px);
min-height:calc(100vh - 90px);
box-shadow:22px 0 52px rgba(8,18,42,0.26);
border-top-right-radius:0;
border-bottom-right-radius:0;
}

.sidebar-panel{
position:relative;
top:0;
padding:clamp(16px, 1.2vw, 25px);
display:flex;
flex-direction:column;
overflow-y:auto;
height:100%;
min-height:0;
box-sizing:border-box;
scrollbar-width:none;
-ms-overflow-style:none;
}

.sidebar-panel::-webkit-scrollbar{
display:none;
}

.sidebar h3{
color:#f1d17d;
font-size:clamp(1.2rem, 1.02rem + 0.6vw, 1.6rem);
text-align:center;
margin-bottom:clamp(6px, 0.5vw, 10px);
}

.sidebar-nav{
list-style:none;
padding:0;
display:grid;
gap:7px;
flex:1 1 auto;
align-content:start;
}

.sidebar li{
margin:0;
}

.sidebar a{
display:block;
padding:10px 12px;
border-radius:14px;
text-decoration:none;
color:white;
font-size:clamp(0.9rem, 0.84rem + 0.2vw, 1rem);
background:rgba(255,255,255,0.06);
border:1px solid rgba(255,255,255,0.08);
backdrop-filter:blur(8px);
transition:transform 0.22s ease, background 0.22s ease, border-color 0.22s ease, box-shadow 0.22s ease;
}

.sidebar a:hover{
background:rgba(255,255,255,0.12);
border-color:rgba(244, 208, 120, 0.36);
font-weight:600;
transform:translateX(4px);
box-shadow:0 12px 24px rgba(0,0,0,0.16);
}

.router-link-exact-active{
background:linear-gradient(135deg, rgba(20, 40, 86, 0.96), rgba(29, 58, 120, 0.9));
color:#f4d078 !important;
border-color:rgba(244, 208, 120, 0.46) !important;
box-shadow:0 12px 28px rgba(8,18,42,0.24);
}

.sidebar-link{
font-family: inherit;
display:block;
width:100%;
padding:10px 12px;
border-radius:14px;
text-decoration:none;
color:white;
font-size:clamp(0.9rem, 0.84rem + 0.2vw, 1rem);
background:rgba(255,255,255,0.06);
border:1px solid rgba(255,255,255,0.08);
text-align:left;
cursor:pointer;
backdrop-filter:blur(8px);
transition:transform 0.22s ease, background 0.22s ease, border-color 0.22s ease, box-shadow 0.22s ease;
}

.sidebar-link:hover{
background:rgba(255,255,255,0.12);
border-color:rgba(244, 208, 120, 0.36);
font-weight:600;
transform:translateX(4px);
box-shadow:0 12px 24px rgba(0,0,0,0.16);
}

.profile-content{
flex:1;
min-width:0;
margin-top: 90px;
padding:32px 36px 40px 52px;
}

.profile--sidebar-pinned .profile-content{
padding-left:calc(clamp(220px, 24vw, 300px) + 36px);
}

.profile-route-shell{
min-height:1px;
}

.profile-page-transition-enter-active,
.profile-page-transition-leave-active{
transition:
opacity 0.2s ease,
transform 0.2s ease;
}

.profile-page-transition-enter-from,
.profile-page-transition-leave-to{
opacity:0;
transform:translateY(8px);
}

.profile-hero{
display:flex;
justify-content:space-between;
align-items:flex-start;
color:white;
padding:12px 8px 2px;
}

.sidebar-pin-toggle{
margin-left:auto;
margin-bottom:10px;
width:38px;
height:38px;
padding:0;
display:grid;
place-items:center;
border-radius:10px;
border:1px solid rgba(244, 208, 120, 0.28);
background:rgba(255,255,255,0.08);
color:#f4d078;
cursor:pointer;
transition:transform 0.2s ease, background 0.2s ease, border-color 0.2s ease;
}

.sidebar-pin-toggle svg{
transition:color 0.2s ease;
}

.sidebar-pin-toggle:hover{
transform:translateY(-1px);
background:rgba(255,255,255,0.12);
border-color:rgba(244, 208, 120, 0.46);
}

.hero-left h2{
margin:0;
font-size:1.35rem;
line-height:1.15;
}

.hero-left p{
margin:0;
font-size:0.95rem;
opacity:0.8;
}

.sidebar-kicker{
display:inline-flex;
padding:5px 10px;
border-radius:999px;
background:rgba(255,255,255,0.08);
border:1px solid rgba(255,255,255,0.1);
font-size:0.66rem;
text-transform:uppercase;
letter-spacing:0.08em;
font-weight:700;
margin-bottom:10px;
}

.sidebar-profile-chip{
display:flex;
align-items:center;
gap:10px;
padding:10px 12px;
border-radius:18px;
background:rgba(255,255,255,0.08);
border:1px solid rgba(255,255,255,0.1);
box-shadow:0 14px 24px rgba(0,0,0,0.12);
}

.sidebar-avatar{
display:grid;
place-items:center;
width:44px;
height:44px;
border-radius:14px;
color:#142856;
font-weight:800;
font-size:1rem;
flex-shrink:0;
box-shadow: 0 4px 10px rgba(0,0,0,0.15);
}

.avatar--gold {
  background: linear-gradient(135deg, #f4d078, #bd9b2c);
  color: #172a5d;
}

.avatar--silver {
  background: linear-gradient(135deg, #e2e8f0, #94a3b8);
  color: #1e293b;
}

.avatar--bronze {
  background: linear-gradient(135deg, #d9a066, #8c5a2b);
  color: #3b240e;
}

.profile-home{
display:grid;
gap:24px;
}

.profile-home-hero{
position:relative;
overflow:hidden;
display:grid;
grid-template-columns:minmax(0, 1.4fr) minmax(260px, 0.8fr);
gap:22px;
padding:30px;
border-radius:28px;
background:
radial-gradient(circle at top right, rgba(255, 215, 126, 0.28), transparent 34%),
linear-gradient(135deg, #12244d 0%, #20386b 55%, #3a5ca9 100%);
box-shadow:0 22px 48px rgba(18, 36, 77, 0.24);
color:#fff;
}

.profile-home-hero::before,
.profile-home-hero::after{
content:"";
position:absolute;
border-radius:999px;
pointer-events:none;
opacity:0.72;
transition:opacity 0.28s ease;
}

.profile-home-hero::before{
width:240px;
height:240px;
right:-80px;
top:-100px;
background:rgba(255,255,255,0.08);
}

.profile-home-hero::after{
width:180px;
height:180px;
left:-70px;
bottom:-100px;
background:rgba(255,204,84,0.14);
}

.profile-home-hero > *{
position:relative;
z-index:2;
}

.hero-copy{
display:flex;
flex-direction:column;
justify-content:space-between;
gap:22px;
}

.hero-eyebrow,
.section-kicker{
display:inline-flex;
align-items:center;
width:max-content;
padding:7px 12px;
border-radius:999px;
background:rgba(255,255,255,0.12);
border:1px solid rgba(255,255,255,0.14);
font-size:0.78rem;
font-weight:700;
letter-spacing:0.08em;
text-transform:uppercase;
}

.section-kicker{
color:#ffffff;
}

.hero-title-row{
display:flex;
align-items:center;
gap:18px;
}

.hero-avatar{
display:grid;
animation: initialDraw 1.1s ease-out forwards;
transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease;
place-items:center;
width:76px;
height:76px;
border-radius:24px;
font-size:1.5rem;
font-weight:800;
box-shadow: 0 16px 30px rgba(0,0,0,0.22);
flex-shrink:0;
}

.hero-copy h1{
margin:0 0 10px;
font-size:clamp(2rem, 1.5rem + 1.5vw, 3rem);
line-height:1.05;
}

.hero-copy p{
margin:0;
max-width:62ch;
color:rgba(255,255,255,0.82);
line-height:1.6;
}

.hero-badges{
display:flex;
flex-wrap:wrap;
gap:10px;
}

.hero-badges--side{
justify-content:flex-end;
align-content:center;
align-items:center;
}

.hero-badge{
display:inline-flex;
align-items:center;
padding:9px 14px;
border-radius:999px;
background:rgba(255,255,255,0.14);
backdrop-filter:blur(10px);
font-weight:600;
}

.hero-badge--soft{
background:rgba(255, 214, 104, 0.16);
color:#ffe59e;
}

.hero-actions{
display:flex;
flex-wrap:wrap;
gap:12px;
}

.hero-action{
display:inline-flex;
align-items:center;
justify-content:center;
min-height:46px;
padding:0 18px;
border-radius:14px;
text-decoration:none;
font-weight:700;
transition:transform 0.22s ease, box-shadow 0.22s ease, background 0.22s ease, border-color 0.22s ease;
cursor:pointer;
}

.hero-action:hover{
transform:translateY(-2px);
}

.hero-action--primary{
background:#f4d078;
color:#172a5d;
box-shadow:0 14px 28px rgba(244, 208, 120, 0.22);
}

.hero-action--secondary{
border:1px solid rgba(255,255,255,0.2);
background:rgba(255,255,255,0.08);
color:#fff;
}

.hero-stats{
display:grid;
gap:14px;
align-content:center;
}

.stat-card{
padding:18px 20px;
border-radius:20px;
background:rgba(255,255,255,0.1);
border:1px solid rgba(255,255,255,0.14);
backdrop-filter:blur(12px);
box-shadow:0 10px 24px rgba(7,16,36,0.14);
transition:transform 0.22s ease, background 0.22s ease;
}

.stat-card:hover{
transform:translateY(-3px);
background:rgba(255,255,255,0.14);
}

.stat-label{
margin:0 0 8px;
font-size:0.82rem;
letter-spacing:0.08em;
text-transform:uppercase;
color:rgba(255,255,255,0.65);
}

.stat-value{
display:block;
font-size:1.55rem;
line-height:1.1;
margin-bottom:6px;
}

.stat-help{
display:block;
color:rgba(255,255,255,0.76);
line-height:1.45;
font-size:0.94rem;
}

.quick-actions-panel,
.preferences{
display:grid;
gap:18px;
}

.section-heading{
position:relative;
overflow:hidden;
display:flex;
justify-content:flex-start;
align-items:flex-start;
gap:16px;
padding:28px 30px;
border-radius:28px;
background:
radial-gradient(circle at top right, rgba(244, 208, 120, 0.24), transparent 30%),
linear-gradient(135deg, #12244d 0%, #20386b 55%, #3a5ca9 100%);
box-shadow:0 22px 48px rgba(18, 36, 77, 0.22);
color:#fff;
}

.section-heading::before,
.section-heading::after{
content:"";
position:absolute;
border-radius:999px;
pointer-events:none;
opacity:0.72;
transition:opacity 0.28s ease;
}

.section-heading::before{
width:220px;
height:220px;
right:-70px;
top:-90px;
background:rgba(255,255,255,0.08);
}

.section-heading::after{
width:160px;
height:160px;
left:-50px;
bottom:-90px;
background:rgba(255,204,84,0.14);
}

.section-heading > *{
position:relative;
z-index:2;
}

.section-heading h3{
margin:8px 0 0;
font-size:1.6rem;
color:#fff;
text-align:left;
}

.section-heading p{
margin:0;
max-width:38ch;
color:rgba(255,255,255,0.82);
line-height:1.5;
text-align:left;
}

.dashboard-grid{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
gap:20px;
margin-bottom:40px;
margin-top:0;
}

.dashboard-card{
position:relative;
overflow:hidden;
background:linear-gradient(160deg, #fff8dc 0%, #f1d17d 16%, #ffffff 16.5%, #f7faff 100%);
padding:26px;
border-radius:22px;
box-shadow:0 14px 28px rgba(23,42,93,0.12);
text-decoration:none;
color:#333;
transition:transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
min-height:190px;
display:flex;
flex-direction:column;
justify-content:space-between;
}

.dashboard-card::before{
content:"";
position:absolute;
inset:auto -40px -50px auto;
width:140px;
height:140px;
border-radius:999px;
background:radial-gradient(circle, rgba(54, 84, 174, 0.16), transparent 70%);
pointer-events:none;
}

.dashboard-card::after{
content:"";
position:absolute;
inset:0;
background:linear-gradient(135deg, rgba(255,255,255,0), rgba(255, 209, 100, 0.16));
opacity:0;
transition:opacity 0.25s ease;
pointer-events:none;
}

.dashboard-card:hover{
transform:translateY(-5px);
box-shadow:0 22px 40px rgba(31, 62, 128, 0.18);
border-color:rgba(219, 182, 72, 0.6);
}

.dashboard-card:hover::after{
opacity:1;
}

.card-icon{
display:flex;
align-items:center;
justify-content:center;
width:60px;
height:60px;
margin-bottom:16px;
color:#fff;
border-radius:18px;
background:linear-gradient(135deg, #172a5d, #3654ae);
box-shadow:0 14px 24px rgba(23,42,93,0.2);
}

.dashboard-card h4{
margin:5px 0;
font-size:1.4rem;
color:#142856;
}

.dashboard-card p{
font-size:0.95rem;
color:#42567b;
}

.dashboard-card-info{
display:grid;
gap:8px;
}

.restricted-notice {
  background: white;
  border-radius: 12px;
  padding: 30px;
  display: flex;
  gap: 25px;
  align-items: center;
  margin-bottom: 30px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.05);
  border-left: 5px solid #bd9b2c;
  text-align: left;
}

.notice-icon {
  color: #bd9b2c;
  flex-shrink: 0;
}

.notice-content h4 {
  margin: 0 0 10px;
  font-size: 1.4rem;
  color: #172a5d;
}

.notice-content p {
  margin: 5px 0;
  color: #51627f;
  line-height: 1.5;
}

.notice-footer {
  margin-top: 10px !important;
  font-weight: 500;
  color: #172a5d !important;
}

.contact-upgrade-btn {
  margin-top: 15px;
  padding: 10px 25px;
  background: #bd9b2c;
  color: white;
  border: none;
  border-radius: 30px;
  font-weight: 600;
  cursor: pointer;
  transition: 0.3s;
}

.contact-upgrade-btn:hover {
  background: #a48424;
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(189, 155, 44, 0.2);
}

.preferences{
margin-top: 6px;
}

.preferences :deep(.preference-panel){
border-radius:24px;
box-shadow:0 14px 32px rgba(23, 42, 93, 0.08);
}

.preferences-actions{
display:flex;
flex-direction:column;
align-items:flex-start;
gap:12px;
margin-top:18px;
}

.save-search-btn{
padding:12px 20px;
border:none;
border-radius:999px;
background:linear-gradient(135deg,#172a5d,#3654ae);
color:white;
font-weight:700;
cursor:pointer;
box-shadow:0 10px 22px rgba(23,42,93,0.18);
transition:transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
}

.save-search-btn:hover:not(:disabled){
transform:translateY(-2px);
box-shadow:0 14px 28px rgba(23,42,93,0.24);
}

.save-search-btn:disabled{
opacity:0.7;
cursor:not-allowed;
}

.save-search-message{
margin:0;
color:#51627f;
font-weight:600;
}

.no-pref{
background:linear-gradient(180deg, #ffffff, #f8fafc);
padding:25px;
border-radius:20px;
border:1px solid #dfe6f2;
box-shadow:0 10px 24px rgba(0,0,0,0.07);
color:#51627f;
}

.logout-item{
margin-top:auto;
padding-top:clamp(10px, 0.9vw, 14px);
border-top:1px solid rgba(255,255,255,0.2);
display:flex;
justify-content:center;
flex-shrink:0;
}

.logout-btn{
width:80%;
padding:8px 10px;
border-radius:6px;
border:1px solid rgba(255,255,255,0.3);
background:transparent;
color:white;
cursor:pointer;
transition:0.2s;
font-size:0.9rem;
}

.logout-btn:hover{
background:rgba(239, 68, 68, 0.747);
}

.card-icon svg path,
.card-icon svg circle {
  stroke-dasharray: 100;
  stroke-dashoffset: 0;
  transition: stroke-dashoffset 0.5s ease;
}

/* Initially draw icons on load */
.card-icon svg path,
.card-icon svg circle {
  animation: initialDraw 1.1s ease-out forwards;
}

@keyframes initialDraw {
  from { stroke-dashoffset: 100; }
  to { stroke-dashoffset: 0; }
}

.card-icon svg path:nth-child(2) { animation-delay: 0.15s; }
.card-icon svg path:nth-child(3) { animation-delay: 0.3s; }

@media (max-width: 1280px){
.sidebar-panel{
padding:12px;
}

.profile-hero{
padding:10px 6px 2px;
}

.sidebar-pin-toggle{
width:38px;
height:38px;
margin-bottom:10px;
}

.sidebar-kicker{
padding:6px 10px;
font-size:0.68rem;
margin-bottom:10px;
}

.sidebar-profile-chip{
gap:8px;
padding:9px 10px;
border-radius:16px;
}

.sidebar-avatar{
width:40px;
height:40px;
border-radius:12px;
font-size:0.92rem;
}

.hero-left h2{
font-size:1.15rem;
}

.hero-left p{
font-size:0.86rem;
}

.sidebar h3{
font-size:1.05rem;
margin-bottom:8px;
}

.sidebar-nav{
gap:6px;
}

.sidebar a,
.sidebar-link{
padding:8px 10px;
border-radius:12px;
font-size:0.86rem;
}

.logout-item{
margin-top:auto;
padding-top:14px;
}

.logout-btn{
width:100%;
padding:7px 9px;
font-size:0.86rem;
}
}

@media (max-width: 980px){
.sidebar{
width:clamp(210px, 23vw, 250px);
}

.profile--sidebar-pinned .profile-content{
padding-left:calc(clamp(210px, 23vw, 250px) + 28px);
}

.sidebar-panel{
padding:12px;
}

.sidebar-profile-chip{
gap:8px;
padding:10px;
}

.sidebar-avatar{
width:42px;
height:42px;
font-size:0.95rem;
}

.hero-left h2{
font-size:1.28rem;
}

.hero-left p{
font-size:0.8rem;
}

.sidebar a,
.sidebar-link{
padding:10px 11px;
font-size:0.86rem;
}

.sidebar-pin-toggle{
width:34px;
height:34px;
}

.logout-btn{
font-size:0.86rem;
padding:8px;
}
}

@media(max-width:768px){
.profile{
flex-direction:column;
}

.sidebar-edge-trigger{
display:none;
}

.sidebar-peek-indicator{
display:none;
}

.sidebar-hint{
display:none;
}

.sidebar-pin-toggle{
display:none;
}

.sidebar-overlay{
display:block;
position:fixed;
inset:90px 0 0 0;
border:none;
padding:0;
margin:0;
background:rgba(12,23,52,0.18);
z-index:994;
}

.sidebar{
position:fixed;
top:90px;
right:16px;
left:auto;
width:min(320px, calc(100vw - 32px));
max-height:calc(100dvh - 106px);
height:auto;
min-height:0;
margin-top:0;
border-radius:22px;
box-shadow:0 18px 40px rgba(12,23,52,0.24);
overflow:hidden;
transform:translateY(-12px) scale(0.98);
opacity:0;
pointer-events:none;
transition:transform 0.22s ease, opacity 0.22s ease;
z-index:995;
background:linear-gradient(180deg,#243864,#102447);
margin-top:0;
border-top-right-radius:22px;
border-bottom-right-radius:22px;
}

.sidebar.open{
transform:translateY(0) scale(1);
opacity:1;
pointer-events:auto;
}

.sidebar.sidebar--pinned{
position:fixed;
left:auto;
flex:none;
border-top-right-radius:18px;
border-bottom-right-radius:18px;
}

.sidebar-panel{
top:0;
height:auto;
min-height:0;
max-height:calc(100dvh - 106px);
padding:18px;
transform:none !important;
}

.profile-content{
margin-left:0;
padding: 28px 24px 40px;
}

.profile-home-hero{
grid-template-columns:1fr;
padding:24px;
}

.hero-badges--side{
justify-content:flex-start;
}

.section-heading{
align-items:flex-start;
flex-direction:column;
padding:20px;
}

.section-heading p{
text-align:left;
}

.dashboard-grid {
grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
gap: 16px;
}

.dashboard-card {
padding: 20px;
}

.dashboard-card h4 {
font-size: 1.2rem;
}

.save-search-btn {
padding: 10px 18px;
font-size: 0.95rem;
}

.no-pref {
padding: 20px;
}

}

@media (max-width: 480px){

.profile-content{
margin-top: 65px;
margin-left:0;
padding:12px;
}

.profile-home{
gap:12px;
}

.sidebar{
top:65px;
right:12px;
width:calc(100vw - 24px);
max-height:calc(100dvh - 81px);
border-radius:18px;
}

.sidebar-overlay{
inset:65px 0 0 0;
}

.sidebar-panel{
max-height:calc(100dvh - 81px);
padding:14px;
transform:none !important;
}

.sidebar h3{
font-size:1.2rem;
margin-bottom:10px;
}

.sidebar ul{
gap: 6px;
}

.sidebar a{
font-size: 0.9rem;
padding: 10px 12px;
}

.profile-hero{
flex-direction:column;
text-align:center;
gap: 10px;
padding:8px 4px 0;
}

.hero-title-row{
align-items:flex-start;
gap:12px;
}

.hero-avatar{
width:52px;
height:52px;
border-radius:16px;
font-size:1rem;
}

.hero-copy h1{
font-size:1.35rem;
margin-bottom:6px;
line-height:1.1;
}

.hero-copy p{
font-size:0.84rem;
line-height:1.45;
}

.profile-home-hero{
gap:14px;
padding:16px;
border-radius:20px;
}

.hero-badge,
.hero-eyebrow,
.section-kicker{
display:inline-flex;
width:fit-content;
padding:5px 8px;
font-size:0.66rem;
max-width:100%;
white-space:normal;
line-height:1.2;
text-wrap:balance;
}

.section-heading{
gap:10px;
padding:16px;
border-radius:20px;
}

.section-heading > div:last-child{
min-width:0;
width:100%;
}

.section-kicker{
display:flex;
width:100%;
justify-content:center;
text-align:center;
border-radius:12px;
}

.section-heading h3{
margin:4px 0 0;
font-size:1.15rem;
}

.section-heading p{
font-size:0.84rem;
line-height:1.4;
}

.hero-actions{
flex-direction:column;
}

.hero-action{
width:100%;
}

.hero-stats{
grid-template-columns:1fr;
}

.dashboard-grid {
grid-template-columns: 1fr;
gap: 8px;
margin-bottom: 18px;
margin-top: 12px;
}

.dashboard-card {
padding: 12px;
display: flex;
align-items: center;
gap: 10px;
min-height:0;
border-radius:16px;
}

.card-icon {
width: 28px;
height: 28px;
margin-bottom: 0;
flex-shrink: 0;
border-radius:10px;
}

.card-icon svg {
width: 18px;
height: 18px;
}

.dashboard-card h4 {
font-size: 0.95rem;
margin: 0;
}

.dashboard-card p {
font-size: 0.74rem;
margin: 2px 0 0;
line-height:1.35;
}

.dashboard-card-info {
display: flex;
flex-direction: column;
}

.save-search-btn {
padding: 9px 16px;
font-size: 0.9rem;
width: 100%;
text-align: center;
}

.save-search-message {
font-size: 0.85rem;
}

.no-pref {
padding: 15px;
font-size: 0.9rem;
}

}
</style>
