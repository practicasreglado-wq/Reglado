<template>
  <section class="about">

   <!-- Lado izquierdo (imágenes reales) -->
<div class="about-images">
  <div class="img-large">
    <img src="@/assets/aboutimg2.png" alt="Descripción imagen pequeña">
  </div>
</div>

    <!-- Lado derecho (texto) -->
    <div class="about-content">
      <h2>Quiénes somos</h2>
      <div class="about-text">
      <p>
        En RS Inmobiliaria ayudamos a nuestros clientes a encontrar el hogar
        ideal y las mejores oportunidades de inversión. Nuestro equipo combina
        experiencia, cercanía y profesionalidad para ofrecer un servicio
        inmobiliario transparente y personalizado.
      </p>

      <p>
        Creemos en la confianza, la calidad y el compromiso como pilares
        fundamentales de nuestro trabajo.
      </p>
    </div>
    </div>

  </section>
</template>

<script>
export default {
  name: "AboutUs"
};
</script>

<style scoped>

.about {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 120px;
  min-height: 100vh;
  background: rgba(0, 0, 0, 0.269);
  padding: 60px 40px;
}

/* ----- IMÁGENES ----- */

.about-images {
  position: relative;
  display: flex;
  align-items: flex-start;
}

.img-large {
  width: 420px;
  height: 500px;
  overflow: hidden;
  border-radius: 5%;
  margin-top: 120px;
   mask-image: radial-gradient(circle, rgba(0,0,0,1) 60%, rgba(0,0,0,0) 100%);
  -webkit-mask-image: radial-gradient(circle, rgba(0,0,0,1) 80%, rgba(0,0,0,0) 100%);
}

.about-images img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  
}

/* ----- CONTENIDO ----- */

.about-content {
  max-width: 700px;
}

.about-content h2 {
  font-size: 5rem;
  margin-bottom: 25px;
  background: linear-gradient(
    135deg,
    #5f4b08 0%,
    #bd9b2c 20%,
    #f9d766 45%,
    #c6aa46 55%,
    #a48424 75%,
    #6e560c 100%
  );
  background-clip: text;
  -webkit-text-fill-color: transparent;
  text-shadow: 0 2px 3px rgba(253, 203, 2, 0.313);
}

.about-content p {
  font-size: 1.75rem;
  text-shadow: 0 2px 3px rgba(0, 0, 0, 0.605);
  line-height: 1.7;
  margin-bottom: 20px;
  color: #ffffff;
}

.about-text {
  background: rgba(0, 0, 0, 0.226);
  padding: 25px;
  border-radius: 12px;
  box-shadow: 0px 10px 25px rgba(0, 0, 0, 0.475);
}

/* =========================
TABLET
========================= */

@media (max-width:1024px){

  .about{
    gap:40px;
  }

  .img-large{
    width:200px;
    height:260px;
    margin-top:60px;
  }

  .about-content h2{
    font-size:3.5rem;
  }

  .about-content p{
    font-size:1.4rem;
  }

}

/* =========================
TABLET PEQUEÑA (CAMBIO DE LAYOUT)
========================= */

@media (max-width:768px){

  .about{
    flex-direction:column;
    text-align:center;
    gap:50px;
  }

  .about-images{
    align-items:center;
    gap:20px;
  }

  .img-large{
    width:200px;
    height:260px;
    margin-top:60px;
  }

  .about-content{
    max-width:600px;
  }

  .about-content h2{
    font-size:2.8rem;
  }

  .about-content p{
    font-size:1.25rem;
  }

}

/* =========================
MÓVIL
========================= */

@media (max-width:480px){

  .img-large{
    width:150px;
    height:210px;
    margin-top:60px;
  }

  .about{
    padding:60px 20px;
  }

  .about-content h2{
    font-size:2.2rem;
  }

  .about-content p{
    font-size:1.1rem;
    line-height:1.6;
  }

}

</style>