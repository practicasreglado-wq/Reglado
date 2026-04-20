<template>
  <div class="legal-page">
    <div class="container">
      <div class="legal-card">
        <h1>Política de Cookies</h1>
        <div class="legal-content">
          <p><strong>1. ¿QUÉ SON LAS COOKIES?</strong></p>
          <p>Una cookie es un fichero que se descarga en su ordenador al acceder a determinadas páginas web. Las cookies permiten a una página web, entre otras cosas, almacenar y recuperar información sobre los hábitos de navegación de un usuario o de su equipo y, dependiendo de la información que contengan y de la forma en que utilice su equipo, pueden utilizarse para reconocer al usuario.</p>
          
          <p><strong>2. TIPOS DE COOKIES QUE UTILIZA ESTA PÁGINA WEB</strong></p>
          <ul>
            <li><strong>Cookies técnicas:</strong> Son aquellas que permiten al usuario la navegación a través de una página web, plataforma o aplicación y la utilización de las diferentes opciones o servicios que en ella existan (como el control de tráfico y la comunicación de datos).</li>
            <li><strong>Cookies de personalización:</strong> Son aquellas que permiten al usuario acceder al servicio con algunas características de carácter general predefinidas en función de una serie de criterios en el terminal del usuario (como el idioma o el tipo de navegador).</li>
            <li><strong>Cookies de análisis:</strong> Son aquellas que bien tratadas por nosotros o por terceros, nos permiten cuantificar el número de usuarios y así realizar la medición y análisis estadístico de la utilización que hacen los usuarios del servicio ofertado para mejorar la oferta de servicios técnicos.</li>
          </ul>

          <p><strong>3. DESACTIVACIÓN Y ELIMINACIÓN DE COOKIES</strong></p>
          <p>Tiene la opción de permitir, bloquear o eliminar las cookies instaladas en su equipo mediante la configuración de las opciones del navegador instalado en su equipo. Al desactivar cookies, algunos de los servicios disponibles podrían dejar de estar operativos. La forma de deshabilitar las cookies es diferente para cada navegador, pero normalmente puede hacerse desde el menú Herramientas u Opciones.</p>
          
          <p>Para más información sobre cómo configurar las cookies según el navegador que estás utilizando, por favor visita los enlaces correspondientes según tu navegador (Chrome, Firefox, Safari, Edge, etc.).</p>

          <p><strong>4. ACEPTACIÓN DE LA POLÍTICA DE COOKIES</strong></p>
          <p>REGLADO INGENIERÍA asume que usted acepta el uso de cookies si continúa navegando. No obstante, muestra información sobre su Política de cookies en la parte inferior o superior de cualquier página con cada inicio de sesión o visita inicial con el objeto de que usted sea consciente.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
</script>

<style scoped>
.legal-page {
  padding: 120px 0 80px;
  min-height: calc(100vh - 200px);
  background-color: #f8fafc;
}
.container {
  max-width: 900px;
  margin: 0 auto;
  padding: 0 20px;
}
.legal-card {
  background: #fff;
  border-radius: 20px;
  padding: 50px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
  border: 1px solid #e2e8f0;
}
.legal-card h1 {
  margin-top: 0;
  margin-bottom: 40px;
  color: #0f172a;
  font-size: 2.5rem;
  font-weight: 800;
  text-align: center;
}
.legal-content {
  color: #475569;
  line-height: 1.8;
  font-size: 1.05rem;
}
.legal-content p {
  margin-bottom: 1.5rem;
}
.legal-content ul {
  margin-bottom: 1.5rem;
  padding-left: 25px;
  list-style-type: disc;
}
.legal-content ul li {
  margin-bottom: 0.75rem;
}
.legal-content strong {
  color: #1e293b;
  font-weight: 700;
}

@media (max-width: 768px) {
  .legal-page {
    padding: 100px 0 50px;
  }
  .legal-card {
    padding: 30px 20px;
  }
  .legal-card h1 {
    font-size: 2rem;
    margin-bottom: 30px;
  }
}
</style>
