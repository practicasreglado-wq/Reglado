<template>
  <section class="legal-section">
    <div class="legal-card">
      <h1 class="h1">Política de Privacidad</h1>
      <div class="legal-content">
        <p><strong>1. ¿QUIÉN ES EL RESPONSABLE DEL TRATAMIENTO DE SUS DATOS?</strong></p>
        <p>El responsable del tratamiento de los datos personales es REGLADO GROUP S.L, con CIF B23982762 y domicilio en AVDA. ISLA GRACIOSA, 7 PISO 1º DESPACHOS 5-6 28703 SAN SEBASTIÁN DE LOS REYES (Madrid).</p>
        <p>Contacto: info@regladoconsultores.com</p>

        <p><strong>2. ¿CON QUÉ FINALIDAD TRATAMOS SUS DATOS PERSONALES?</strong></p>
        <p>En REGLADO GROUP S.L tratamos la información que nos facilitan las personas interesadas con las siguientes finalidades en el marco de Reglado Maps:</p>
        <ul>
          <li>Gestionar el acceso a la plataforma cartográfica y sus funcionalidades.</li>
          <li>Tramitar solicitudes de información técnica sobre activos energéticos.</li>
          <li>Mejorar la experiencia de usuario mediante el análisis de la interacción con el mapa.</li>
          <li>Remitir comunicaciones sobre actualizaciones de la plataforma cuando sea autorizado.</li>
        </ul>

        <p><strong>3. ¿POR CUÁNTO TIEMPO CONSERVAREMOS SUS DATOS?</strong></p>
        <p>Los datos personales proporcionados se conservarán mientras se mantenga la relación contractual/comercial o durante los años necesarios para cumplir con las obligaciones legales.</p>

        <p><strong>4. ¿CUÁL ES LA LEGITIMACIÓN PARA EL TRATAMIENTO DE SUS DATOS?</strong></p>
        <p>La base legal para el tratamiento de sus datos es el consentimiento del interesado al acceder a la plataforma o al realizar consultas específicas.</p>

        <p><strong>5. ¿A QUÉ DESTINATARIOS SE COMUNICARÁN SUS DATOS?</strong></p>
        <p>Los datos no se cederán a terceros salvo en los casos en que exista una obligación legal o sea necesario para la prestación del servicio geoespacial solicitado.</p>

        <p><strong>6. ¿CUÁLES SON SUS DERECHOS AL FACILITARNOS SUS DATOS?</strong></p>
        <p>Cualquier persona tiene derecho a obtener confirmación sobre si en REGLADO GROUP S.L estamos tratando datos personales que les conciernan, o no. Las personas interesadas tienen derecho a:</p>
        <ul>
          <li>Acceder a sus datos personales.</li>
          <li>Solicitar la rectificación de los datos inexactos.</li>
          <li>Solicitar su supresión cuando los datos ya no sean necesarios.</li>
          <li>Solicitar la limitación de su tratamiento.</li>
          <li>Oponerse al tratamiento de sus datos.</li>
          <li>Derecho a la portabilidad de los datos.</li>
        </ul>
        <p>Para ejercer estos derechos, puede dirigirse a info@regladoconsultores.com adjuntando copia de su DNI.</p>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted } from 'vue';

onMounted(() => {
  document.title = 'Política de Privacidad | Reglado Maps';
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
