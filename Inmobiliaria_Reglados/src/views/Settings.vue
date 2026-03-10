<template>

<div class="settings">

<h2>Ajustes de Usuario</h2>

<div v-if="!user">
Cargando usuario...
</div>

<div v-else>

<div class="profile-picture">
  <div class="avatar-wrapper" @click="openFilePicker">

<img
v-if="preview || user.profile_picture"
:src="preview ? preview : 'http://localhost/inmobiliaria/backend/' + user.profile_picture + '?t=' + Date.now()"
alt="Foto de perfil"
class="avatar"
/>

<div v-else class="profile-initial avatar">
{{ userInitials }}
</div>

<!-- OVERLAY CAMARA -->
        <div class="avatar-overlay">

  <svg
    class="camera-icon"
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="white"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
  >

    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V7a2 
    2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>

    <circle cx="12" cy="13" r="4"></circle>

  </svg>

</div>
  </div>

<input type="file" ref="fileInput" class="hidden-input" accept="image/*" @change="handleFileChange">

<br>

<button type="button" @click="updateProfile">
Guardar foto
</button>

<br>

<button v-if="user.profile_picture" @click="deletePhoto">
Eliminar foto
</button>

</div>

<form @submit.prevent="updateProfile">

<div class="form-group">
<label>Nombre</label>
<input type="text" v-model="user.nombre" required>
</div>

<div class="form-group">
<label>Apellidos</label>
<input type="text" v-model="user.apellidos" required>
</div>

<div class="form-group">
<label>Email</label>
<input type="email" v-model="user.email" required>
</div>

<div class="form-group">
<label>Teléfono</label>
<input type="text" v-model="user.telefono" required>
</div>

<div class="form-group">
<label>Nombre de Usuario</label>
<input type="text" v-model="user.nombre_usuario" required>
</div>

<div class="form-group">
<label>Fecha de nacimiento</label>
<input type="date" v-model="user.fecha_nacimiento" readonly>
</div>

<div class="form-group">
<label>Contraseña actual</label>
<input type="password" v-model="currentPassword">
</div>

<div class="form-group">
<label>Nueva contraseña</label>
<input type="password" v-model="newPassword">
</div>

<button type="submit">
Guardar cambios
</button>

</form>

</div>

</div>

</template>

<script>

import { useUserStore } from "../stores/user"
import { onMounted, computed, ref } from "vue"
import { storeToRefs } from "pinia"

export default {

  setup() {
    const fileInput = ref(null);
    const preview = ref(null);
    const selectedFile = ref(null);

const currentPassword = ref("")
const newPassword = ref("")

const userStore = useUserStore()
const { user } = storeToRefs(userStore)

const userInitials = computed(()=>{

if(!user.value) return "U"

return user.value.nombre
? user.value.nombre.charAt(0).toUpperCase()
: "U"

})

    const openFilePicker = () => {
      fileInput.value.click();
    };

    onMounted(() => {

loadUserData()

})

const loadUserData = async ()=>{

try{

const response = await fetch(
"http://localhost/inmobiliaria/backend/get_user_data.php",
{
credentials:"include"
})

const data = await response.json()

if(data.success){

userStore.setUser(data.user)

}else{

alert("Error cargando usuario")

}

}catch(error){

console.error(error)

}

}

const handleFileChange = (event)=>{

const file = event.target.files[0]

if(!file) return

selectedFile.value = file
preview.value = URL.createObjectURL(file)

}

const updateProfile = async ()=>{

// ✅ VALIDACIÓN DE CONTRASEÑA
if ((currentPassword.value && !newPassword.value) || (!currentPassword.value && newPassword.value)) {
  alert("Para cambiar la contraseña debes introducir la contraseña actual y la nueva.")
  return
}

try{

const formData = new FormData()

formData.append("nombre",user.value.nombre)
formData.append("apellidos",user.value.apellidos)
formData.append("email",user.value.email)
formData.append("telefono",user.value.telefono)
formData.append("nombre_usuario",user.value.nombre_usuario)

if(currentPassword.value && newPassword.value){

formData.append("current_password",currentPassword.value)
formData.append("new_password",newPassword.value)

}

if(selectedFile.value){

formData.append("profile_picture",selectedFile.value)

}

const response = await fetch(
"http://localhost/inmobiliaria/backend/update_profile.php",
{
method:"POST",
body:formData,
credentials:"include"
})

const data = await response.json()

if(data.success){

alert("Perfil actualizado")

currentPassword.value=""
newPassword.value=""
preview.value=null
selectedFile.value=null

loadUserData()

}else{

alert(data.message)

}

}catch(error){

console.error(error)

}

}

const deletePhoto = async ()=>{

const response = await fetch(
"http://localhost/inmobiliaria/backend/delete_profile_picture.php",
{
method:"POST",
credentials:"include"
})

const data = await response.json()

if(data.success){

preview.value=null
loadUserData()

}

}

return{
user,
preview,
handleFileChange,
updateProfile,
deletePhoto,
userInitials,
currentPassword,
newPassword,
fileInput,
      openFilePicker
}

}

}

</script>

<style scoped>
/* contenedor avatar */

.avatar-wrapper{
  position:relative;
  width:150px;
  height:150px;
  cursor:pointer;
}

/* avatar */

.avatar{
  width:150px;
  height:150px;
  border-radius:50%;
  object-fit:cover;
}

/* inicial */

.profile-initial{
  display:flex;
  align-items:center;
  justify-content:center;
  background:#ccc;
  font-size:2rem;
  color:white;
}

/* overlay camara */

.avatar-overlay{
  position:absolute;
  top:0;
  left:0;

  width:100%;
  height:100%;

  border-radius:50%;

  display:flex;
  align-items:center;
  justify-content:center;

  background:rgba(0,0,0,0.45);

  font-size:2.2rem;
  color:white;

  opacity:0;
  transition:opacity .25s ease;
}

.camera-icon{
  width:40px;
  height:40px;
  opacity:.65;
}

/* aparece al hover */

.avatar-wrapper:hover .avatar-overlay{
  opacity:1;
}

/* input oculto */

.hidden-input{
  display:none;
}

.settings {
  max-width: 600px;
  margin: 0 auto;
}

.hidden-input{
  display:none;
}

.clickable-avatar{
  cursor:pointer;
  transition:transform .2s;
}

.clickable-avatar:hover{
  transform:scale(1.05);
}

.profile-picture {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 20px;
}

.profile-picture img{
width:150px;
height:150px;
border-radius:50%;
object-fit:cover;
}

.profile-initial{
width:150px;
height:150px;
border-radius:50%;
display:flex;
align-items:center;
justify-content:center;
background:#ccc;
font-size:2rem;
color:white;
}

.form-group{
margin-bottom:15px;
}

.form-group label{
display:block;
font-weight:bold;

}

.form-group input{
width:100%;
padding:10px;
border-radius:5px;
border:1px solid #ccc;
text-shadow: 0 1px 2px rgba(0, 0, 0, 0.518);
}

button {
  padding: 10px 20px;
  background-color: #d6ab3e;
  color: white;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.605);
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-size: 16px;
}

button:hover {
  background-color: #f0c14bc9;
}

@media (max-width:1024px) {
  .avatar-overlay{
    opacity: 1;
  }

}
</style>