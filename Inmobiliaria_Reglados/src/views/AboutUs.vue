<template>
  <section class="about">

   <!-- Lado izquierdo (imágenes reales) -->
<div class="about-images">
  <div class="img-large">
    <img src="@/assets/aboutimg2.png" alt="Descripción imagen pequeña">
  </div>
  <div class="img-small">
    <img src="@/assets/aboutimg.jpg" alt="Descripción imagen grande">
  </div>
</div>

    <!-- Lado derecho (texto) -->
    <div class="about-content">
      <h2>Quiénes somos</h2>

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
  gap: 60px;
  min-height: 100vh;
  background: rgba(0, 0, 0, 0.263);
  padding: 60px 40px;
}

/* ----- IMÁGENES ----- */

.about-images {
  position: relative;
  display: flex;
  align-items: flex-start;
  gap: 30px;
}

.img-large {
  width: 380px;
  height: 420px;
  overflow: hidden;
  margin-top: 120px;
  box-shadow: 0px 10px 25px rgba(0, 0, 0, 0.733);
}

.img-small {
  width: 270px;
  height: 460px;
  overflow: hidden;
  box-shadow: 0px 10px 25px rgba(0, 0, 0, 0.733);
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
  color: #eabe2f;
  text-shadow: 0 2px 2px rgba(0, 0, 0, 0.619);
}

.about-content p {
  font-size: 1.75rem;
  text-shadow: 0 2px 3px rgba(0, 0, 0, 0.605);
  line-height: 1.7;
  margin-bottom: 20px;
  color: #ffffff;
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

  .img-small{
    width:150px;
    height:280px;
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

  .img-small{
    width:150px;
    height:280px;
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

  .img-small{
    width:100px;
    height:230px;
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