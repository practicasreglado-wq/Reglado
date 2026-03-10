<template>

<div class="create-container">

<h2>Crear nueva propiedad</h2>

<div class="form">

<input v-model="property.nombre" placeholder="Nombre">

<input v-model="property.ubicacion" placeholder="Ubicación">

<input v-model="property.precio" type="number" placeholder="Precio">

<input v-model="property.tipo" placeholder="Tipo">

<button @click="createProperty">
Guardar propiedad
</button>

</div>

</div>

</template>

<script>

import axios from "axios"

export default {

data(){
return{

property:{
nombre:"",
ubicacion:"",
precio:"",
tipo:""
}

}
},

methods:{

async createProperty(){

try{

await axios.post(
"http://localhost/inmobiliaria/api/createProperty.php",
this.property,
{withCredentials:true}
)

this.$router.push("/profile/my-properties-for-sale")

}catch(error){

console.error(error)

}

}

}

}

</script>

<style scoped>

.create-container{
max-width:400px;
}

.form{
display:flex;
flex-direction:column;
gap:10px;
}

input{
padding:8px;
}

button{
padding:10px;
background:#f0c14b;
border:none;
cursor:pointer;
}

</style>