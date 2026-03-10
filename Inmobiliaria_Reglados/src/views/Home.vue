<template>
  <section class="hero">
    <div class="overlay"></div>

    <div class="hero-content">
      <router-link 
        v-if="!userStore.isLoggedIn"
        to="/login" 
        class="btn-login">
        Login
      </router-link>
      <h1>
        <span class="highlight">Reglado</span> Real State
      </h1>
      <p class="description">
        Encuentra el inmueble que se adapta a tus necesidades.
        Registra tu búsqueda, explora nuestras opciones y recibe alertas
        cuando lleguen nuevas oportunidades.
      </p>

      <p class="subtext">
        La forma más fácil de comprar o vender
      </p>
      
    </div>
  </section>
    <div id="about-us" class="somos">
      <AboutUs/>
    </div>
</template>

<script>
import AboutUs from "../views/AboutUs.vue";
import { useUserStore } from "../stores/user";
export default {
  name: "Home",
  components: {
    AboutUs
  },
  setup() {
    const userStore = useUserStore();
    return { userStore };
  }
};
</script>

<style scoped>

.hero {
  position: relative;
  min-height:100vh;
  background-image: url('/src/assets/img_Home.png');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  display: flex;
  align-items: center;
  justify-content: center;
  padding-left: 20px;
  padding-right: 33%;
}

.somos{
  min-height:100vh;
  background-image: url('/src/assets/img_Home2.png');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  align-items: center;
  justify-content: center;
  gap: 80px;
}

/* Overlay */

.overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.075);
}

/* CONTENIDO HERO */

.hero-content {
  position: relative;
  z-index: 2;
  width: 90%;
  max-width: 100%;
  text-align: start;
  color: white;

  display: flex;
  flex-direction: column;
  align-items: end;
}

.hero-content h1,
.hero-content p {
  align-self: flex-start;
}

.hero h1 {
  font-size: 7rem;
  text-shadow: 0 2px 3px rgba(0, 0, 0, 0.605);
  margin-bottom: 20px;
}

.highlight {
  color: #eabe2f;
}

.description {
  font-size: 2.75rem;
  text-shadow: 0 2px 3px rgba(0, 0, 0, 0.605);
  line-height: 1.6;
  margin-bottom: 20px;
}

.subtext {
  font-size: 1.5rem;
  text-shadow: 0 2px 3px rgba(0, 0, 0, 0.605);
  opacity: 0.85;
}

/* BOTÓN */

.btn-login{
  margin-top: 120px;
  margin-right: 14%;
  font-size: 2.3rem;
  background-color: var(--azul-principal);
  box-shadow: 0px 5px 20px rgba(0, 0, 0, 0.443);
  text-shadow: 0 2px 3px rgba(0, 200, 255, 0.274);
  color: white;
  padding: 13px 35px;
  border: none;
  border-radius: 6px;
  transition: 0.3s ease;
  text-decoration: none;
}

.btn-login:hover{
  background-color: var(--azul-secundario);
  color: white;
}

/* =========================
TABLETS
========================= */

@media (max-width:1024px){

  .hero{
    padding-right:10%;
    padding-left:10%;
  }

  .hero-content{
    align-items:center;
    text-align:center;
  }

  .hero h1{
    font-size:5rem;
  }

  .description{
    font-size:2rem;
  }

  .subtext{
    font-size:1.3rem;
  }

  .btn-login{
    font-size:1.8rem;
    margin: 0;
  }

}

/* =========================
TABLETS PEQUEÑAS
========================= */

@media (max-width:768px){

  .hero{
    padding-right:10%;
    padding-left:10%;
  }

  .hero-content{
    align-items:center;
    text-align:center;
  }

  .hero h1{
    font-size:4rem;
  }

  .description{
    font-size:1.7rem;
  }

  .subtext{
    font-size:1.2rem;
  }

  .btn-login{
    font-size:1.6rem;
    margin: 0;
  }

}

/* =========================
MOVIL
========================= */

@media (max-width:480px){

  .hero{
    padding:40px 20px;
    justify-content:center;
  }

  .hero-content{
    align-items:center;
    text-align:center;
  }

  .hero-content h1,
  .hero-content p{
    align-self:center;
  }

  .hero h1{
    font-size:2.7rem;
  }

  .description{
    font-size:1.3rem;
  }

  .subtext{
    font-size:1rem;
  }

  .btn-login{
    font-size:1.2rem;
    padding:10px 25px;
    margin: 0;
  }

}

</style>