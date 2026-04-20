<template>

<div class="reset-container">

<div class="overlay"></div>

<div class="reset">

<h2>Cambiar contraseña</h2>

<form @submit.prevent="changePassword">

<input
type="password"
v-model="password"
placeholder="Nueva contraseña"
required
/>

<input
type="password"
v-model="confirmPassword"
placeholder="Confirmar contraseña"
required
/>

<button type="submit" :disabled="loading">
{{ loading ? "Cambiando..." : "Cambiar contraseña" }}
</button>

<p v-if="showRules" class="rules">
La contraseña debe tener mínimo 8 caracteres, una mayúscula y un número
</p>

</form>

<p v-if="message" :class="success ? 'success' : 'error'">
{{message}}
</p>

</div>

</div>

</template>

<script>
import { ref } from "vue"
import { useRoute, useRouter } from "vue-router"

export default {

setup(){

const password = ref("")
const confirmPassword = ref("")
const message = ref("")
const showRules = ref(false)
const success = ref(false)
const loading = ref(false)

const route = useRoute()
const router = useRouter()

const token = route.query.token?.trim()
console.log("TOKEN:", token)

const changePassword = async()=>{

const regex = /^(?=.*[A-Z])(?=.*\d).{8,}$/

// validar reglas
if(!regex.test(password.value)){
showRules.value = true
message.value = ""
return
}else{
showRules.value = false
}

// comprobar que coinciden
if(password.value !== confirmPassword.value){
message.value = "Las contraseñas no coinciden"
success.value = false
return
}

loading.value = true

try{

const response = await fetch(
"http://localhost/inmobiliaria/backend/reset_password.php",
{
method:"POST",
headers:{ "Content-Type":"application/json" },
body:JSON.stringify({
token:token,
password:password.value
})
}
)

const text = await response.text()

console.log("RESPUESTA BACKEND:", text)

const data = JSON.parse(text)

message.value = data.message
success.value = data.message.includes("correctamente")

}catch(err){

console.error(err)
message.value = "Error conectando con el servidor"

}

loading.value = false

}

return{
password,
confirmPassword,
message,
showRules,
success,
loading,
changePassword
}

}

}
</script>

<style scoped>

.reset-container{
  min-height: 100vh;
  background-image: url('@/assets/fondito.png');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  display:flex;
  justify-content:center;
  align-items:center;
  padding:20px;
}

.overlay{
  position:absolute;
  inset:0;
  background:rgba(0,0,0,0.363);
  z-index:0;
}

.reset{
  background:white;
  padding:40px;
  border-radius:12px;
  box-shadow:0 10px 25px rgba(0,0,0,0.1);
  width:360px;
  text-align:center;
  z-index: 1;
}

.reset h2{
  margin-bottom:25px;
  font-size:1.8rem;
}

.reset input{
  width:100%;
  padding:12px;
  margin-bottom:15px;
  border:1px solid #ddd;
  border-radius:6px;
  font-size:14px;
  transition:0.2s;
}

.reset input:focus{
  border-color:#2c3e50;
  outline:none;
}

.reset button{
  width:100%;
  padding:12px;
  background-color: var(--azul-principal);
  color:white;
  border:none;
  border-radius:6px;
  cursor:pointer;
  font-weight:bold;
  transition:0.2s;
}

.reset button:hover{
  background-color: var(--azul-secundario);
}

.reset button:disabled{
  opacity:0.6;
  cursor:not-allowed;
}

.rules{
  margin-top:10px;
  font-size:13px;
  color:#e67e22;
}

.success{
  margin-top:20px;
  color:#cba21a;
  font-weight:bold;
}

.error{
  margin-top:20px;
  color:#e74c3c;
  font-weight:bold;
}

/* ----------- 1024px ----------- */

@media (max-width:1024px){

  .reset{
    width:320px;
    padding:35px;
  }

  .reset h2{
    font-size:1.6rem;
  }

}

/* ----------- 768px ----------- */

@media (max-width:768px){

  .reset{
    width:90%;
    padding:30px;
  }

  .reset h2{
    font-size:1.5rem;
  }

}

/* ----------- 480px ----------- */

@media (max-width:480px){

  .reset{
    width:100%;
    padding:25px;
  }

  .reset h2{
    font-size:1.2rem;
  }

  .reset input{
    padding:10px;
    font-size:13px;
  }

  .reset button{
    padding:10px;
    font-size:14px;
  }

  .rules{
    font-size:12px;
  }

}

</style>