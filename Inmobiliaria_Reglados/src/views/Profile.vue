<template>
<section class="profile">

<button
  v-if="isProfileMenuOpen"
  class="sidebar-overlay"
  type="button"
  aria-label="Cerrar menu de perfil"
  @click="closeProfileMenu"
></button>

<div class="sidebar" :class="{ open: isProfileMenuOpen }">
<div class="sidebar-panel">
<div class="profile-hero">

<div class="hero-left">
<h2>Hola {{ user.nombre_usuario }}</h2>
</div>
</div>
<h3>Menu de perfil</h3>

<ul>
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

<div v-if="user && isProfileHome">

<router-view
v-if="isProfileHome"
v-slot="{ Component, route }"
>
<transition name="profile-page-transition" mode="out-in">
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
          <p>Para acceder al catalogo completo de propiedades y a todas las herramientas de la plataforma necesitas una cuenta <strong>REAL</strong>.</p>
          <p class="notice-footer">Ponte en contacto con nosotros para activar todos los servicios.</p>
          <button class="contact-upgrade-btn" @click="$router.push('/contacto')">Contactar ahora</button>
        </div>
      </div>

      <div v-if="isReal" class="dashboard-grid">

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

      <div v-if="isReal && preferenceEntries.length" class="preferences">
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
<transition name="profile-page-transition" mode="out-in">
<div :key="route.fullPath" class="profile-route-shell">
<component :is="Component"></component>
</div>
</transition>
</router-view>

</div>

</section>
</template>

<script>
import { computed, onMounted, ref, watch } from "vue";
import { storeToRefs } from "pinia";
import { useRouter, useRoute } from "vue-router";
import { useUserStore } from "../stores/user";
import { useProfileMenuStore } from "../stores/profileMenu";
import PreferencePanel from "../components/PreferencePanel.vue";
import { buildPreferenceEntries } from "../data/preferenceSchemas";
import { backendJson } from "../services/backend";

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

    function goToSettings() {
      const base = import.meta.env.VITE_GRUPO_REGLADO_BASE_URL || "http://localhost:5173";
      const settingsPath = import.meta.env.VITE_GRUPO_REGLADO_SETTINGS_PATH || "/configuracion";
      window.location.href = new URL(settingsPath, base).toString();
    }

    onMounted(() => {
      const savedCategory = localStorage.getItem("selectedCategory");
      if (savedCategory && userStore.selectedCategory !== savedCategory) {
        userStore.setCategory(savedCategory);
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
    };
  },
};
</script>

<style scoped>
.profile{
display:flex;
align-items:stretch;
min-height:100vh;
background: linear-gradient(180deg, #b6c6d6, #eef2f7);
}

.sidebar-overlay{
display:none;
}

.sidebar{
position:relative;
width:clamp(220px, 24vw, 300px);
margin-top:90px;
min-height:calc(100vh - 90px);
background:linear-gradient(to bottom,#2f3e69,#0a315e);
box-shadow:3px 0 10px rgba(0,0,0,0.2);
z-index:995;
}

.sidebar-panel{
position:sticky;
top:90px;
height:100%;
padding:clamp(16px, 1.2vw, 25px);
display:flex;
flex-direction:column;
overflow-y:auto;
min-height:calc(100vh - 90px);
}

.sidebar h3{
color:goldenrod;
font-size:clamp(1.4rem, 1.1rem + 0.9vw, 2rem);
text-align:center;
margin-bottom:clamp(12px, 1vw, 20px);
}

.sidebar ul{
list-style:none;
padding:0;
}

.sidebar li{
margin:clamp(6px, 0.7vw, 10px) 0;
}

.sidebar a{
display:block;
padding:clamp(7px, 0.7vw, 10px);
border-radius:6px;
text-decoration:none;
color:white;
font-size:clamp(0.98rem, 0.88rem + 0.35vw, 1.2rem);
}

.sidebar a:hover{
background:#f0c14bd7;
font-weight:600;
}

.router-link-exact-active{
background:#d6ab3e;
}

.sidebar-link{
font-family: inherit;
display:block;
width:100%;
padding:clamp(7px, 0.7vw, 10px);
border-radius:6px;
text-decoration:none;
color:white;
font-size:clamp(0.98rem, 0.88rem + 0.35vw, 1.2rem);
background:none;
border:none;
text-align:left;
cursor:pointer;
}

.sidebar-link:hover{
background:#f0c14bd7;
font-weight:600;
}

.profile-content{
flex:1;
min-width:0;
margin-top: 90px;
padding:40px;
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
align-items:center;
color:white;
padding:30px 30px 0px;
}

.hero-left h2{
margin:0;
font-size:2rem;
}

.hero-left p{
font-size:1rem;
opacity:0.9;
}

.dashboard-grid{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
gap:20px;
margin-bottom:40px;
margin-top:36px;
}

.dashboard-card{
background:white;
padding:25px;
border-radius:12px;
box-shadow:0 6px 20px rgba(0,0,0,0.1);
text-decoration:none;
color:#333;
transition:0.25s;
}

.dashboard-card:hover{
transform:translateY(-5px);
box-shadow:0 10px 30px rgba(0,0,0,0.15);
}

.card-icon{
display:flex;
align-items:center;
justify-content:center;
width:40px;
height:40px;
margin-bottom:10px;
color:#3654ae;
}

.dashboard-card h4{
margin:5px 0;
font-size:1.4rem;
}

.dashboard-card p{
font-size:0.9rem;
color:#666;
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
margin-top: 10px;
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
background:white;
padding:25px;
border-radius:12px;
box-shadow:0 6px 20px rgba(0,0,0,0.1);
color:#51627f;
}

.logout-item{
margin-top:clamp(36px, 4vw, 80px);
padding-top:clamp(12px, 1.2vw, 20px);
border-top:1px solid rgba(255,255,255,0.2);
display:flex;
justify-content:center;
}

.logout-btn{
width:80%;
padding:clamp(8px, 0.7vw, 10px);
border-radius:6px;
border:1px solid rgba(255,255,255,0.3);
background:transparent;
color:white;
cursor:pointer;
transition:0.2s;
font-size:clamp(0.92rem, 0.84rem + 0.2vw, 1rem);
}

.logout-btn:hover{
background:rgba(239, 68, 68, 0.747);
}

@media(max-width:768px){
.profile{
flex-direction:column;
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
}

.sidebar.open{
transform:translateY(0) scale(1);
opacity:1;
pointer-events:auto;
}

.sidebar-panel{
top:0;
height:auto;
min-height:0;
max-height:calc(100dvh - 106px);
padding:18px;
}

.profile-content{
margin-left:0;
padding: 40px;
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
padding:15px;
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
padding: 6px;
}

.profile-hero{
flex-direction:column;
text-align:center;
gap: 15px;
}

.dashboard-grid {
grid-template-columns: 1fr;
gap: 10px;
margin-bottom: 24px;
margin-top: 24px;
}

.dashboard-card {
padding: 16px;
display: flex;
align-items: center;
gap: 14px;
}

.card-icon {
width: 30px;
height: 30px;
margin-bottom: 0;
flex-shrink: 0;
}

.card-icon svg {
width: 22px;
height: 22px;
}

.dashboard-card h4 {
font-size: 1.05rem;
margin: 0;
}

.dashboard-card p {
font-size: 0.8rem;
margin: 2px 0 0;
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
