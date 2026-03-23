<template>
  <section class="hero">
    <video class="hero-video" autoplay loop muted playsinline>
      <source src="@/assets/video_home.mp4" type="video/mp4">
    </video>
    <div class="overlay"></div>

    <div class="hero-content">
      <div class="t_hero">
        <h1>
          <span class="highlight">Reglado</span> Real State
        </h1>
      </div>
      <p class="description">
        Encuentra el inmueble que se adapta a tus necesidades.
        Registra tu búsqueda, explora nuestras opciones y recibe alertas
        cuando lleguen nuevas oportunidades.
      </p>

      <p class="subtext">
        La forma más fácil de comprar o vender
      </p>
      <router-link
        v-if="!userStore.isLoggedIn"
        to="/login"
        class="btn-login">
        Login
      </router-link>
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
  min-height: 100vh;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: flex-start; /* Alineado a la izquierda */
  padding-left: 1.5%;
  padding-right: 5%;
  padding-top: 90px; /* Compensa el header fijo */
}

.hero-video {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  z-index: 0;
}

.somos{
  min-height:100vh;
  background:
    linear-gradient(180deg, #ffffff 0%, #e9e9e9 100%);
  align-items: center;
  justify-content: center;
  gap: 80px;
}

/* Overlay */

.overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.256);
  z-index: 1;
}

/* CONTENIDO HERO */

.hero-content {
  position: relative; /* Para el botón de login absoluto */
  z-index: 2;
  width: 100%; /* Ocupa todo el ancho disponible */
  max-width: 1400px; /* Limitador opcional para pantallas ultra anchas */
  margin: 0;
  text-align: start;
  color: white;

  display: flex;
  flex-direction: column;
  align-items: flex-start; /* Texto a la izquierda por defecto */
  gap: 0;
}

.hero-content h1,
.hero-content p {
  align-self: flex-start;
}

.t_hero {
  position: relative;
  isolation: isolate;
  display: inline-block;
  align-self: flex-start;
  padding: 15px 35px;
  background: rgba(255, 255, 255, 0.04);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border-radius: 24px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2);
  margin-bottom: 30px;
  margin-top: 80px; /* Asegura espacio para el botón de login si existe */
}

.hero h1{
  font-size: clamp(2.5rem, 7vw, 7rem);
  background: linear-gradient(
    140deg,
    #785f0b 0%,
    #bd9b2c 20%,
    #d3ae33 45%,
    #f2d46b 55%,
    #e8c14c 75%,
    #cba430 100%
  );
  background-clip:text;
  -webkit-text-fill-color:transparent;
  margin:0;
  text-shadow: 0 4px 15px rgba(186, 154, 40, 0.6);
}

.highlight {

  background: linear-gradient(
    135deg,
    #e1dede 0%,
    #d9d8d8 20%,
    #cac9c9 40%,
    #d2d0d0 50%,
    #d2d0d0 70%,
    #e1e0e0 100%
  );
  background-clip: text;
  -webkit-text-fill-color: transparent;
  text-shadow: 0 4px 10px rgba(255, 255, 255, 0.215);
}

.description {
  font-size: clamp(0.9rem, 2.5vw, 2.75rem);
  text-shadow: 0 2px 3px rgba(0, 0, 0, 0.605);
  line-height: 1.6;
  margin-bottom: 20px;
}

.subtext {
  font-size: clamp(0.8rem, 1.5vw, 1.5rem);
  text-shadow: 0 2px 3px rgba(0, 0, 0, 0.605);
  opacity: 0.85;
}

/* BOTÓN */

.btn-login{
  position: relative;
  align-self: center;
  font-size: clamp(0.95rem, 2vw, 2.3rem);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  box-shadow: 0 10px 28px rgba(0, 0, 0, 0.2);
  padding: 13px 35px;
  border: none;
  border-radius: 999px;
  transition: transform 0.25s ease, box-shadow 0.25s ease, background 0.25s ease;
  text-decoration: none;
  font-weight: 700;
  border: 1px solid rgb(255, 255, 255);
  margin-top: 28px;
  text-shadow: 0 4px 10px rgb(206, 164, 13);
  background-image:
    linear-gradient(rgba(255, 255, 255, 0.247), rgba(255, 255, 255, 0.178)),
    linear-gradient(
      140deg,
      #9b7e1e 0%,
      #caa52b 20%,
      #c8ab38 45%,
      #c4a42e 55%,
      #ac8f27 75%,
      #b29720 100%
    );
  background-origin: border-box;
  background-clip: padding-box, text;
  -webkit-background-clip: padding-box, text;
  color: transparent;
  -webkit-text-fill-color: transparent;
}

.btn-login:hover{
  transform: translateY(-2px) scale(1.02);
  box-shadow:
    0 0 0 1px rgba(255, 255, 255, 0.22),
    0 0 28px rgba(255, 255, 255, 0.38),
    0 14px 34px rgba(0, 0, 0, 0.24);
}

/* =========================
TABLETS
========================= */

@media (max-width:1024px){

  .hero{
    padding-right: 5%;
    padding-left: 1.5%;
  }

  .hero-content{
    align-items: flex-start;
    text-align: left;
  }

  .hero h1{
    font-size: clamp(2.2rem, 6vw, 5rem);
  }

  .description{
    font-size:2rem;
  }

  .subtext{
    font-size:1.3rem;
  }

  .btn-login{
    font-size:1.8rem;
    margin-top: 28px;
    margin-bottom: 20px;
  }

  .t_hero {
  margin-top: 0;
  }

}

/* =========================
TABLETS PEQUEÑAS
========================= */

@media (max-width:768px){

  .hero{
    padding-right: 5%;
    padding-left: 1.5%;
  }

  .hero-content{
    align-items: flex-start;
    text-align: left;
  }

  .hero h1{
    font-size: clamp(2rem, 5vw, 3.5rem);
  }

  .description{
    font-size:1.5rem;
  }

  .subtext{
    font-size:1.1rem;
  }

  .btn-login{
    font-size:1.4rem;
    margin-top: 24px;
    margin-bottom: 20px;
  }

  .t_hero {
    margin-top: 0;
  }

}

/* =========================
MOVIL
========================= */

@media (max-width:480px){

  .hero{
    padding: 90px 1.5% 40px;
    justify-content: flex-start;
  }

  .t_hero{
    align-self: flex-start;
    margin-top: 0;
  }

  .hero-content{
    align-items: flex-start;
    text-align: left;
  }

  .hero-content h1,
  .hero-content p{
    align-self: flex-start;
  }

  .hero h1{
    font-size: clamp(1.6rem, 8vw, 2.2rem);
  }

  .description{
    font-size:0.9rem;
  }

  .subtext{
    font-size:0.8rem;
  }

  .btn-login{
    font-size:0.95rem;
    padding:6px 18px;
    margin-top: 18px;
    margin-bottom: 20px;
  }

  .t_hero {
    margin-top: 0;
  }

}

</style>
