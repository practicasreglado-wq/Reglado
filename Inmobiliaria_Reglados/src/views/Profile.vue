<template>
<section class="profile">

<button class="menu-toggle" @click="menuOpen = !menuOpen">
☰
</button>

<!-- SIDEBAR -->
<div class="sidebar" :class="{ open: menuOpen }">

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
  <router-link to="/profile/messages" @click="menuOpen = false">
    Mensajes
  </router-link>
</li>

<li>
  <router-link to="/profile/my-properties-for-sale" @click="menuOpen = false">
    Mis propiedades
  </router-link>
</li>

<li>
  <router-link to="/profile/settings" @click="menuOpen = false">
    Ajustes
  </router-link>
</li>
</ul>

<div v-if="user" class="logout-item">
<button class="logout-btn" @click="logout">
Cerrar sesión
</button>
</div>

</div>


<!-- CONTENIDO -->
<div class="profile-content">

<div v-if="user && isProfileHome">

<!-- HERO PERFIL -->

<div class="profile-hero">

<div class="hero-left">
<h2>Hola {{ user.nombre_usuario }}</h2>
<p>Bienvenido a tu panel de perfil</p>
</div>

<div class="category-highlight">

  <span class="category-label">Categoria actual:</span>

  <div class="category-badge">
    {{ category }}
  </div>

</div>

</div>


<!-- ACCIONES -->

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

<router-link to="/profile/messages" class="dashboard-card">
<div class="card-icon">
<svg viewBox="0 0 24 24" width="28" height="28" fill="none">
<path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"
stroke="currentColor"
stroke-width="1.8"
stroke-linejoin="round"/>
</svg>
</div>
<h4>Mensajes</h4>
<p>Contactos con propietarios</p>
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


<!-- PREFERENCIAS -->

<div v-if="preferences && hasPreferences" class="preferences">

<h3>Tus preferencias</h3>

<template v-for="(group, key) in preferences" :key="key">

<div v-if="Array.isArray(group) && group.length" class="pref-group">

<h4>{{ formatLabel(key) }}</h4>

<ul>
<li v-for="(item,index) in group" :key="index">
{{ item }}
</li>
</ul>

</div>

</template>

</div>

<div v-else class="no-pref">
No tienes preferencias guardadas todavía
</div>

</div>


<router-view></router-view>

</div>

</section>
</template>

<script>
import { useUserStore } from "../stores/user";
import { storeToRefs } from "pinia";
import { ref, computed, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";

export default {

setup(){
const menuOpen = ref(false)
const userStore = useUserStore()
const router = useRouter()
const route = useRoute()

const { user, selectedCategory: category, preferences } = storeToRefs(userStore)


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


const hasPreferences = computed(()=>{

if(!preferences.value) return false

return Object.values(preferences.value).some(
arr => Array.isArray(arr) && arr.length
)

})


const isProfileHome = computed(()=>{
return route.path === "/profile/properties-for-sale"
})


const formatLabel = (key)=>{

const labels = {
estrellas:"Estrellas",
servicios:"Servicios",
ubicacion:"Ubicación",
tipo:"Tipo",
caracteristicas:"Características",
zona:"Zona",
uso:"Uso"
}

return labels[key] || key
}


return{
user,
category,
preferences,
hasPreferences,
formatLabel,
logout,
isProfileHome,
menuOpen
}

}

}
</script>

<style scoped>

/* LAYOUT GENERAL */

.profile{
display:flex;
min-height:100vh;
background:#d8dbe1;
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

/* SIDEBAR */

.sidebar{
margin-top: 90px;
width:300px;
background:linear-gradient(to bottom,#101d41,#2c4692);
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


/* CONTENIDO */

.profile-content{
flex:1;
margin-top: 90px;
padding:40px;
}


/* HERO PERFIL */

.profile-hero{
display:flex;
justify-content:space-between;
align-items:center;

background:linear-gradient(135deg,#172a5d,#3654ae);
color:white;

padding:30px;
border-radius:15px;
margin-bottom:30px;
}

.hero-left h2{
margin:0;
font-size:2.3rem;
}

.hero-left p{
font-size:1.1rem;
opacity:0.9;
}

/* CATEGORY HIGHLIGHT */

.category-highlight{
display:flex;
flex-direction:column;
align-items:center;
justify-content:center;
}

.category-label{
font-size:1rem;
opacity:0.8;
margin-bottom:6px;
}

.category-badge{

background:linear-gradient(135deg,#d2b454,#f0c14b);
color:#172a5d;

font-size:1.3rem;
font-weight:700;

padding:10px 25px;
border-radius:30px;

box-shadow:0 4px 15px rgba(0,0,0,0.2);
letter-spacing:0.5px;

}


/* DASHBOARD CARDS */

.dashboard-grid{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
gap:20px;
margin-bottom:40px;
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


/* PREFERENCIAS */

.preferences{
background:white;
padding:25px;
border-radius:12px;
box-shadow:0 6px 20px rgba(0,0,0,0.1);
}

.preferences h3{
margin-bottom:15px;
font-size:1.75rem;
color:#172a5d;
text-shadow: 0 1px 2px rgba(0, 0, 0, 0.4);
}

.pref-group{
margin-bottom:15px;
}

.pref-group h4{
margin-bottom:8px;
font-size:1.3rem;
}

.pref-group ul{
display:flex;
flex-wrap:wrap;
gap:8px;
padding:0;
list-style:none;
}

.pref-group li{
background:#d6dced;
border-radius:20px;
padding:6px 14px;
font-size:0.9rem;
}


/* LOGOUT */

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

.pref-group li:hover{
background:#f0c14b;
color:#172a5d;
cursor:pointer;
}


/* MENU RESPONSIVE */

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

/* ===== MOVIL MUY PEQUEÑO ===== */

@media (max-width: 400px){

.profile-content{
  margin-top: 70px;
padding:15px;
}

/* boton menu */
.menu-toggle{
  font-size: 1rem;
  padding: 8px 12px;
}

/* menu lateral */
.sidebar{
  width: 200px;
  padding: 15px;
}

/* titulo del menu */
.sidebar h3{
  margin-top: 50px;
  font-size: 1.3rem;
  margin-bottom: 10px;
}

/* lista */
.sidebar ul{
  gap: 6px;
}
/* links */
.sidebar a{
  font-size: 0.9rem;
  padding: 6px;
}

/* contenido principal */
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
