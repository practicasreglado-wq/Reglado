<template>
<div class="forgot">
<div class="overlay"></div>
<div class="vent-forgot">

<h2>Recuperar contraseña</h2>

<form @submit.prevent="send">

<input
type="email"
v-model="email"
placeholder="Correo electrónico"
required
/>

<input
type="text"
v-model="username"
placeholder="Nombre de usuario"
required
/>

<button type="submit">
Enviar correo de recuperación
</button>

</form>

<p v-if="message">{{message}}</p>

</div> 
</div>
</template>

<script>
import {ref} from "vue"

export default{
name: "ForgotPassword",
setup(){

const email=ref("")
const username=ref("")
const message=ref("")

const send = async () => {

try{

const response = await fetch(
"http://localhost/inmobiliaria/backend/forgot_password.php",
{
method:"POST",
headers:{ "Content-Type":"application/json" },

body:JSON.stringify({
email:email.value,
username:username.value
})
}
)

const text = await response.text()

console.log("RESPUESTA BACKEND:", text)

const data = JSON.parse(text)

message.value = data.message

}catch(err){

console.error(err)
message.value="Error conectando con el servidor"

}

}

return{
email,
username,
message,
send
}

}

}
</script>
<style scoped>

.forgot{
  min-height: 100vh;
  background-image: url('@/assets/fondito.png');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding:5px;
}

.overlay{
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.363);
  z-index: 0;
}

.vent-forgot{
  background: rgba(255,255,255,0.9);
  padding: 40px;
  border-radius: 10px;
  box-shadow: 0 6px 20px rgba(0,0,0,0.1);
  z-index: 1;
}

/* formulario */

form{
  display: flex;
  flex-direction: column;
  gap: 15px;
  width: 320px;
  padding: 30px;
  border-radius: 10px;
}

input{
  padding: 12px;
  border-radius: 6px;
  border:1px solid #ccc;
}

button{
  padding: 12px;
  border:none;
  border-radius:6px;
  background: var(--azul-principal);
  color:white;
  font-weight:bold;
  cursor:pointer;
}

button:hover{
  background: var(--azul-secundario);
}

h2{
  font-size:2.2rem;
  margin-bottom:20px;
  text-align:center;
}

/* ---------- 1024px ---------- */

@media (max-width:1024px){

  .vent-forgot{
    padding:30px;
  }

  form{
    width:280px;
    padding:25px;
  }

  h2{
    font-size:2rem;
  }

}

/* ---------- 768px ---------- */

@media (max-width:768px){

  .vent-forgot{
    width:90%;
    padding:25px;
  }

  form{
    width:100%;
    padding:20px;
  }

  h2{
    font-size:1.8rem;
  }

}

/* ---------- 480px ---------- */

@media (max-width:480px){

  .vent-forgot{
    padding:5px;
  }

  form{
    width:100%;
    padding:15px;
    gap:12px;
  }

  input{
    padding:10px;
  }

  button{
    padding:10px;
  }

  h2{
    font-size:1.3rem;
  }

}
</style>