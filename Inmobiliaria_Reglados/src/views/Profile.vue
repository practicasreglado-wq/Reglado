<template>
<section class="profile">

<button class="menu-toggle" @click="menuOpen = !menuOpen">
☰
</button>

<div class="sidebar" :class="{ open: menuOpen }">
<div class="profile-hero">

<div class="hero-left">
<h2>Hola {{ user.nombre_usuario }}</h2>
<p>Bienvenido a tu panel de perfil</p>
</div>
</div>
<h3>Menú de perfil</h3>

<ul>
<li>
  <router-link to="/profile/properties-for-sale" @click="menuOpen = false">
    Inicio
  </router-link>
</li>

<li>
  <router-link to="/profile/favorite-properties" @click="menuOpen = false">
    Mis propiedades favoritas
  </router-link>
</li>

<li>
  <router-link to="/profile/search-history" @click="menuOpen = false">
    Historial de búsquedas
  </router-link>
</li>

<li>
  <router-link to="/profile/my-properties-for-sale" @click="menuOpen = false">
    Mis propiedades
  </router-link>
</li>

<li>
  <button class="sidebar-link" type="button" @click="goToSettings(); menuOpen = false">
    Configuración
  </button>
</li>
</ul>

<div v-if="user" class="logout-item">
<button class="logout-btn" @click="logout">
Cerrar sesión
</button>
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
<h4>Favoritos</h4>
<p>Propiedades que has guardado</p>
</router-link>

<router-link to="/profile/my-properties-for-sale" class="dashboard-card">
<div class="card-icon">
<svg viewBox="0 0 24 24" width="28" height="28" fill="none">
<path d="M3 11L12 4L21 11" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
<path d="M5 10V20H19V10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
</svg>
</div>
<h4>Mis propiedades</h4>
<p>Gestiona tus anuncios</p>
</router-link>

</div>

<div v-if="preferenceEntries.length" class="preferences">
<PreferencePanel :category="category" :entries="preferenceEntries" />

<div class="preferences-actions">
<button
  class="save-search-btn"
  type="button"
  :disabled="savingSearch"
  @click="saveSearch"
>
  {{ savingSearch ? "Guardando..." : "Guardar búsqueda" }}
</button>

<p v-if="saveSearchMessage" class="save-search-message">
  {{ saveSearchMessage }}
</p>
</div>
</div>

<div v-else class="no-pref">
No tienes preferencias guardadas todavía
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
import { useUserStore } from "../stores/user";
import { storeToRefs } from "pinia";
import { ref, computed, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import PreferencePanel from "../components/PreferencePanel.vue";
import { buildPreferenceEntries } from "../data/preferenceSchemas";
import { backendJson } from "../services/backend";

export default {
components: {
PreferencePanel,
},

setup(){

const menuOpen = ref(false)
const userStore = useUserStore()
const router = useRouter()
const route = useRoute()

const { user, selectedCategory: category, preferences } = storeToRefs(userStore)
const savingSearch = ref(false)
const saveSearchMessage = ref("")

function goToSettings() {
  const base = import.meta.env.VITE_GRUPO_REGLADO_BASE_URL || "http://localhost:5173"
  const settingsPath = import.meta.env.VITE_GRUPO_REGLADO_SETTINGS_PATH || "/configuracion"

  window.location.href = new URL(settingsPath, base).toString()
}

onMounted(()=>{

const savedCategory = localStorage.getItem("selectedCategory")

if(savedCategory && userStore.selectedCategory !== savedCategory){
userStore.setCategory(savedCategory)
}

})

const logout = async ()=>{
await userStore.logout()
router.push("/")
}

const preferenceEntries = computed(() =>
buildPreferenceEntries(category.value, preferences.value)
)

const saveSearch = async () => {
  if (!user.value?.iduser || !category.value || !preferenceEntries.value.length) {
    saveSearchMessage.value = "No hay una búsqueda completa para guardar."
    return
  }

  savingSearch.value = true
  saveSearchMessage.value = ""

  try {
    const payload = await backendJson("api/save_search.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        user_id: user.value.iduser,
        category: category.value,
        preferences: preferences.value,
      }),
    })

    saveSearchMessage.value = payload.message || "Búsqueda guardada correctamente."
  } catch (err) {
    saveSearchMessage.value = err.message || "No se pudo guardar la búsqueda."
  } finally {
    savingSearch.value = false
  }
}

const isProfileHome = computed(()=>{
return route.path === "/profile/properties-for-sale"
})

return{
user,
category,
preferenceEntries,
logout,
isProfileHome,
menuOpen,
goToSettings,
saveSearch,
savingSearch,
saveSearchMessage
}

}

}
</script>

<style scoped>
.profile{
display:flex;
min-height:100vh;
background: linear-gradient(180deg, #b6c6d6, #eef2f7);
}

.menu-toggle{
display:none;
position:fixed;
top:70px;
background:#172a5d;
color:white;
border:none;
border-radius:8px;
padding:10px 12px;
font-size:1rem;
cursor:pointer;
z-index:1000;
}

.sidebar{
margin-top: 90px;
width:300px;
background:linear-gradient(to bottom,#2f3e69,#0a315e);
padding:25px;
display:flex;
flex-direction:column;
box-shadow:3px 0 10px rgba(0,0,0,0.2);
}

.sidebar h3{
color:goldenrod;
font-size:2rem;
text-align:center;
margin-bottom:20px;
}

.sidebar ul{
list-style:none;
padding:0;
}

.sidebar li{
margin:10px 0;
}

.sidebar a{
display:block;
padding:10px;
border-radius:6px;
text-decoration:none;
color:white;
font-size:1.2rem;
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
padding:10px;
border-radius:6px;
text-decoration:none;
color:white;
font-size:1.2rem;
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
padding:30px;
border-radius:15px;
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
margin-top:80px;
padding-top:20px;
border-top:1px solid rgba(255,255,255,0.2);
display:flex;
justify-content:center;
}

.logout-btn{
width:80%;
padding:10px;
border-radius:6px;
border:1px solid rgba(255,255,255,0.3);
background:transparent;
color:white;
cursor:pointer;
transition:0.2s;
}

.logout-btn:hover{
background:rgba(239, 68, 68, 0.747);
}

@media(max-width:768px){

.menu-toggle{
display:block;
}

.sidebar{
position:fixed;
top:0;
margin-top: 70px;
left:-260px;
height:100vh;
width:260px;
transition:0.3s;
z-index:999;
}

.sidebar.open{
left:0;
}

.profile{
flex-direction:column;
}

.profile-content{
padding-top:80px;
}

}

@media (max-width: 400px){

.profile-content{
margin-top: 70px;
padding:15px;
}

.menu-toggle{
font-size: 1rem;
padding: 8px 12px;
}

.sidebar{
width: 200px;
padding: 15px;
}

.sidebar h3{
margin-top: 50px;
font-size: 1.3rem;
margin-bottom: 10px;
}

.sidebar ul{
gap: 6px;
}

.sidebar a{
font-size: 0.9rem;
padding: 6px;
}

.profile-content{
padding: 15px;
}

.profile-hero{
flex-direction:column;
text-align:center;
gap: 15px;
}
}
</style>
