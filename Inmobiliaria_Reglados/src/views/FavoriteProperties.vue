<template>

<div>

<h2>Mis propiedades favoritas</h2>

<div v-if="loading">
Cargando favoritos...
</div>

<div v-else-if="favorites.length === 0">
No tienes propiedades favoritas.
</div>

<div v-else class="properties">

<div
v-for="property in favorites"
:key="property.id"
class="property-card"
>

<h3>{{ property.nombre }}</h3>

<p>{{ property.ubicacion }}</p>

<p><strong>Tipo:</strong> {{ property.tipo }}</p>

<p><strong>Precio:</strong> {{ property.precio }} €</p>

</div>

</div>

</div>

</template>

<script>

import axios from "axios"

export default{

name:"FavoriteProperties",

data(){
return{
favorites:[],
loading:true
}
},

mounted(){
this.getFavorites()
},

methods:{

async getFavorites(){

try{

const res = await axios.get(
"http://localhost/inmobiliaria/backend/api/get_favorite_properties.php",
{withCredentials:true}
)

// asegurar array
if(Array.isArray(res.data)){
this.favorites = res.data
}else{
this.favorites = []
}

}catch(error){

console.error(error)
this.favorites = []

}finally{

this.loading = false

}

}

}

}

</script>

<style scoped>

.properties{
display:grid;
grid-template-columns:repeat(auto-fill,minmax(250px,1fr));
gap:16px;
}

.property-card{
background:white;
padding:15px;
border-radius:8px;
box-shadow:0 2px 5px rgba(0,0,0,0.1);
}

</style>