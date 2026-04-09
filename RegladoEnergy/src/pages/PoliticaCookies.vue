<template>
  <section class="section">
    <div class="container">
      <div class="card soft glow">
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
          <p>Reglado Energy asume que usted acepta el uso de cookies si continúa navegando. No obstante, muestra información sobre su Política de cookies en la parte inferior o superior de cualquier página del portal con cada inicio de sesión con el objeto de que usted sea consciente.</p>
        </div>
      </div>
    </div>
    <br><br>
  </section>
</template>

<script setup>
import { onMounted } from 'vue';
import { setSeo } from '../seo.js';

onMounted(() => {
  setSeo({
    title: 'Política de Cookies | Reglado Energy',
    description: 'Política de Cookies de Reglado Energy.',
    canonical: '/#/politica-cookies'
  });
});
</script>

<style scoped>
.legal-content {
  color: rgba(255, 255, 255, 0.85);
  line-height: 1.6;
}
.legal-content p {
  margin-bottom: 1.2rem;
}
.legal-content ul {
  margin-bottom: 1.2rem;
  padding-left: 20px;
}
.legal-content ul li {
  margin-bottom: 0.5rem;
}
.legal-content strong {
  color: #fff;
}
</style>
