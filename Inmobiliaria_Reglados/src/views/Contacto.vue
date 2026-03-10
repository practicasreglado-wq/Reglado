<template>
  <div class="contacto">
    <div class="overlay"></div>
    <!-- Lado izquierdo -->
    <div class="contacto-texto">

      <!-- Si NO está logueado -->
      <template v-if="!user || Object.keys(user).length === 0">
        <h1>
          <span class="highlight">Crea tu cuenta</span>
        </h1>

        <p>
          Obtén acceso a listados premium y recomendaciones personalizadas.
        </p>
      </template>

      <!-- Si está logeado -->
      <template v-else>
        <h1>
          <span class="highlight">Bienvenido {{ user.nombre }}</span>
        </h1>

        <p>
          Estamos encantados de tenerte de nuevo. Nuestro equipo responderá tu consulta lo antes posible.
        </p>
      </template>

    </div>

    <!-- Tarjeta derecha -->
    <div class="contacto-card">
      <h2>Contáctanos</h2>

      <form class="formulario">
        <div class="row">
          <input type="text" placeholder="Nombre" required />
          <input type="text" placeholder="Apellido" required />
        </div>

        <textarea placeholder="Mensaje" rows="4" required></textarea>

        <button type="submit" class="enviar-btn">Enviar</button>
      </form>

      <div class="info">
        <div class="item">
          <span class="dot"></span> Teléfono
        </div>
        <div class="item">
          <span class="dot"></span> Correo
        </div>
        <div class="item">
          <span class="dot"></span> Ubicación
        </div>
      </div>
    </div>
    <div class="login-link" v-if="!user || Object.keys(user).length === 0">
        ¿Ya tienes una cuenta?
        <span @click="$router.push('/login')">Login</span>
      </div>
  </div>
</template>

<script>
import { useUserStore } from "../stores/user";

export default {
  name: "Contacto",

  setup() {
  const userStore = useUserStore();
  return {
    user: userStore.user
  };
}
};
</script>

<style scoped>

.contacto {
  position: relative;
  min-height:100vh;
  background-color: var(--gris-claro);
  background-image: url('@/assets/fondito.png');
  background-size: cover;
  background-position: center;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 100px 0 80px;
  gap: 60px;
}

/* Overlay oscuro */

.overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.363);
  z-index: 0;
}

/* Contenido por encima */

.contacto-texto,
.contacto-card {
  position: relative;
  z-index: 1;
}

/* -------- TEXTO -------- */

.contacto-texto {
  color: white;
  max-width: 500px;
  transform: translateY(-70px);
}

.contacto-texto h1 {
  font-size: 4rem;
  margin-bottom: 20px;
}

.highlight {
  color: #eabe2f;
}

.contacto-texto p {
  font-size: 2rem;
  margin-bottom: 30px;
}

.login-link {
  position: absolute;
  bottom: 30px;
  left: 230px;
  color: white;
  transform: translateX(-50%);
  font-size: 1.5rem;
  z-index: 2;
  text-align: center;
}

.login-link span {
  color: #eabe2f;
  cursor: pointer;
  font-weight: bold;
  margin-left: 5px;
}

.login-link span:hover {
  text-decoration: underline;
}

/* -------- CARD -------- */

.contacto-card {
  width: 600px;
  background: rgba(255, 255, 255, 0.95);
  padding: 40px;
  border-radius: 20px;
  box-shadow: 0 15px 40px rgba(0,0,0,0.2);
}

h2 {
  margin-bottom: 25px;
  font-size: 2.5rem;
  color: #111;
}

.row {
  display: flex;
  gap: 15px;
  margin-bottom: 15px;
}

input, textarea {
  width: 100%;
  padding: 10px;
  border: 2px solid var(--azul-principal);
  border-radius: 6px;
  font-size: 1rem;
  outline: none;
}

textarea {
  resize: none;
  margin-bottom: 20px;
}

.enviar-btn {
  font-size: 1em;
  background-color: var(--azul-principal);
  color: white;
  padding: 8px 25px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: 0.3s ease;
}

.enviar-btn:hover {
  background-color: var(--azul-secundario);
}

.info {
  margin-top: 25px;
}

.item {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
  font-size: 1rem;
}

.dot {
  width: 12px;
  height: 12px;
  background-color: var(--azul-principal);
  border-radius: 50%;
}

/* ======================
TABLET
====================== */

@media (max-width:1024px){

  h2 {
  font-size: 2.25rem;
  }

  .contacto{
    padding:0 60px;
    gap:40px;
  }

  .contacto-texto h1{
    font-size:3rem;
  }

  .contacto-texto p{
    font-size:1.5rem;
  }

  .contacto-card{
    width:480px;
    padding:30px;
  }

  .contacto-texto{
    width: 400px;
  }

}

/* ======================
TABLET PEQUEÑA
====================== */

@media (max-width:768px){

  h2 {
  font-size: 2rem;
  }

  .contacto{
    flex-direction:column;
    justify-content:center;
    text-align:center;
    padding:80px 40px;
    gap:60px;
  }

  .contacto-texto{
    transform:none;
  }

  .login-link{
    position:relative;
    left:auto;
    bottom:auto;
    transform:none;
    margin-top:20px;
  }

  .contacto-card{
    width:100%;
    max-width:500px;
  }

}

/* ======================
MÓVIL
====================== */

@media (max-width:480px){

  h2 {
  font-size: 1.5rem;
  }

  .contacto-texto{
    width: 100%;
  }

  .contacto{
    padding:80px 20px;
  }

  .contacto-texto h1{
    font-size:2.3rem;
  }

  .contacto-texto p{
    font-size:1.2rem;
  }

  .row{
    flex-direction:column;
    gap:10px;
  }

  .contacto-card{
    padding:10px;
  }

  .enviar-btn{
    width:100%;
  }

}

</style>