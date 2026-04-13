<template>
  <section class="section">
    <div class="container">
      <div class="card soft glow">
        <h1 class="h1">Política de Privacidad</h1>
        <div class="legal-content">
          <p><strong>1. ¿QUIÉN ES EL RESPONSABLE DEL TRATAMIENTO DE SUS DATOS?</strong></p>
          <p>El responsable del tratamiento de los datos personales es REGLADO ENERGY S.L, con CIF B24887754 y domicilio en AVDA. ISLA GRACIOSA, 7 PISO 1º DESPACHOS 5-6 28703 SAN SEBASTIÁN DE LOS REYES (Madrid).</p>
          <p>Contacto: info@regladoenergy.com</p>

          <p><strong>2. ¿CON QUÉ FINALIDAD TRATAMOS SUS DATOS PERSONALES?</strong></p>
          <p>En REGLADO ENERGY S.L tratamos la información que nos facilitan las personas interesadas con las siguientes finalidades:</p>
          <ul>
            <li>Gestionar el registro de usuarios y el acceso a los servicios del portal empresarial.</li>
            <li>Tramitar el envío de la información que nos soliciten a través de los diversos formularios de contacto.</li>
            <li>Prestar los servicios contratados por el usuario (energía, inmobiliaria, consultoría).</li>
            <li>Remitir comunicaciones comerciales sobre nuestros servicios cuando el usuario lo haya autorizado.</li>
          </ul>

          <p><strong>3. ¿POR CUÁNTO TIEMPO CONSERVAREMOS SUS DATOS?</strong></p>
          <p>Los datos personales proporcionados se conservarán mientras se mantenga la relación comercial o durante los años necesarios para cumplir con las obligaciones legales.</p>

          <p><strong>4. ¿CUÁL ES LA LEGITIMACIÓN PARA EL TRATAMIENTO DE SUS DATOS?</strong></p>
          <p>La base legal para el tratamiento de sus datos es el consentimiento del interesado al registrarse en el portal o al enviar una consulta a través de nuestros formularios.</p>

          <p><strong>5. ¿A QUÉ DESTINATARIOS SE COMUNICARÁN SUS DATOS?</strong></p>
          <p>Los datos no se cederán a terceros salvo en los casos en que exista una obligación legal o sea necesario para la prestación del servicio solicitado (ej. comunicación a empresas del grupo para servicios específicos de energía o inmuebles).</p>

          <p><strong>6. ¿CUÁLES SON SUS DERECHOS AL FACILITARNOS SUS DATOS?</strong></p>
          <p>Cualquier persona tiene derecho a obtener confirmación sobre si en REGLADO ENERGY S.L estamos tratando datos personales que les conciernan, o no. Las personas interesadas tienen derecho a:</p>
          <ul>
            <li>Acceder a sus datos personales.</li>
            <li>Solicitar la rectificación de los datos inexactos.</li>
            <li>Solicitar su supresión cuando, entre otros motivos, los datos ya no sean necesarios para los fines que fueron recogidos.</li>
            <li>Solicitar la limitación de su tratamiento.</li>
            <li>Oponerse al tratamiento de sus datos.</li>
            <li>Derecho a la portabilidad de los datos.</li>
          </ul>
          <p>Para ejercer estos derechos, puede dirigirse a info@regladoconsultores.com adjuntando copia de su DNI.</p>

          <p><strong>7. PROCEDENCIA DE LOS DATOS</strong></p>
          <p>Los datos personales que tratamos en REGLADO ENERGY S.L han sido facilitados directamente por el interesado a través de los formularios de registro y contacto del sitio web.</p>
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
    title: 'Política de Privacidad | Reglado Energy',
    description: 'Política de Privacidad de Reglado Energy.',
    canonical: '/#/politica-privacidad'
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
