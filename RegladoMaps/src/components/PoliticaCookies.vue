<template>
  <section class="legal-section">
    <div class="legal-card">
      <h1 class="h1">Política de Cookies</h1>
      <div class="legal-content">
        <p><strong>1. ¿QUÉ SON LAS COOKIES?</strong></p>
        <p>Una cookie es un fichero que se descarga en su ordenador al acceder a determinadas páginas web. Las cookies permiten a una página web, entre otras cosas, almacenar y recuperar información sobre los hábitos de navegación de un usuario o de su equipo y, dependiendo de la información que contengan y de la forma en que utilice su equipo, pueden utilizarse para reconocer al usuario.</p>
        
        <p><strong>2. TIPOS DE COOKIES QUE UTILIZA ESTA PÁGINA WEB</strong></p>
        <ul>
          <li><strong>Cookies técnicas:</strong> Son aquellas que permiten al usuario la navegación a través de una página web, plataforma o aplicación y la utilización de las diferentes opciones o servicios que en ella existan.</li>
          <li><strong>Cookies de personalización:</strong> Son aquellas que permiten al usuario acceder al servicio con algunas características de carácter general predefinidas en función de una serie de criterios en el terminal del usuario.</li>
          <li><strong>Cookies de análisis:</strong> Son aquellas que bien tratadas por nosotros o por terceros, nos permiten cuantificar el número de usuarios y así realizar la medición y análisis estadístico de la utilización que hacen los usuarios del servicio ofertado.</li>
        </ul>

        <p><strong>3. DESACTIVACIÓN Y ELIMINACIÓN DE COOKIES</strong></p>
        <p>Tiene la opción de permitir, bloquear o eliminar las cookies instaladas en su equipo mediante la configuración de las opciones del navegador instalado en su equipo. Al desactivar cookies, algunos de los servicios disponibles podrían dejar de estar operativos. La forma de deshabilitar las cookies es diferente para cada navegador, pero normalmente puede hacerse desde el menú Herramientas u Opciones.</p>
        
        <p>Para más información sobre cómo configurar las cookies según el navegador que estás utilizando, por favor visita los enlaces correspondientes según tu navegador (Chrome, Firefox, Safari, Edge, etc.).</p>

        <p><strong>4. ACEPTACIÓN DE LA POLÍTICA DE COOKIES</strong></p>
        <p>Reglado Maps (y sus plataformas como Reglado Maps) asumen que usted acepta el uso de cookies si continúa navegando. No obstante, muestra información sobre su Política de cookies en la parte inferior o superior de cualquier página del portal con cada inicio de sesión con el objeto de que usted sea consciente.</p>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted } from 'vue';

onMounted(() => {
  document.title = 'Política de Cookies | Reglado Maps';
});
</script>

<style scoped>
.legal-section {
  min-height: 100vh;
  width: 100vw;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  background: #0b0d10;
  color: #fff;
  font-family: 'Outfit', sans-serif;
  padding: 120px 20px 60px 20px;
  box-sizing: border-box;
}

.legal-card {
  max-width: 800px;
  width: 100%;
  background: rgba(255, 255, 255, 0.03);
  padding: 3rem 4rem;
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 10px 40px rgba(0,0,0,0.5);
  backdrop-filter: blur(8px);
  text-align: left;
}

.legal-card .h1 {
  font-size: 2.5rem;
  margin-bottom: 2rem;
  color: #00e5ff;
  font-weight: 700;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  padding-bottom: 1rem;
}

.legal-content {
  color: rgba(255, 255, 255, 0.85);
  line-height: 1.8;
  font-size: 1.05rem;
}

.legal-content p {
  margin-bottom: 1.5rem;
}

.legal-content ul {
  margin-bottom: 1.5rem;
  padding-left: 20px;
}

.legal-content ul li {
  margin-bottom: 0.5rem;
}

.legal-content strong {
  color: #fff;
  font-weight: 600;
  color: #00c47d;
}

@media (max-width: 600px) {
  .legal-card {
    padding: 2rem 1.5rem;
  }
}
</style>
