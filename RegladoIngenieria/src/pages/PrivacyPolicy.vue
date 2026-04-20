<template>
  <div class="legal-page">
    <div class="container">
      <div class="legal-card">
        <h1>Política de Privacidad</h1>
        <div class="legal-content">
          <p><strong>1. ¿QUIÉN ES EL RESPONSABLE DEL TRATAMIENTO DE SUS DATOS?</strong></p>
          <p>El responsable del tratamiento de los datos personales es REGLADO GROUP S.L, con CIF B23982762 y domicilio en AVDA. ISLA GRACIOSA, 7 PISO 1º DESPACHOS 5-6 28703 SAN SEBASTIÁN DE LOS REYES (Madrid).</p>
          <p>Contacto: info@regladoingenieria.com</p>

          <p><strong>2. ¿CON QUÉ FINALIDAD TRATAMOS SUS DATOS PERSONALES?</strong></p>
          <p>En REGLADO GROUP S.L tratamos la información que nos facilitan las personas interesadas con las siguientes finalidades en el marco de REGLADO INGENIERÍA:</p>
          <ul>
            <li>Gestionar las solicitudes de información y presupuestos técnicos.</li>
            <li>Tramitar el envío de la información que nos soliciten a través de los diversos formularios de contacto.</li>
            <li>Prestar los servicios de ingeniería y consultoría industrial contratados por el usuario.</li>
            <li>Remitir comunicaciones comerciales sobre nuevos servicios técnicos cuando el usuario lo haya autorizado.</li>
          </ul>

          <p><strong>3. ¿POR CUÁNTO TIEMPO CONSERVAREMOS SUS DATOS?</strong></p>
          <p>Los datos personales proporcionados se conservarán mientras se mantenga la relación comercial o durante los años necesarios para cumplir con las obligaciones legales y contractuales derivadas de los proyectos técnicos.</p>

          <p><strong>4. ¿CUÁL ES LA LEGITIMACIÓN PARA EL TRATAMIENTO DE SUS DATOS?</strong></p>
          <p>La base legal para el tratamiento de sus datos es el consentimiento del interesado al enviar una consulta a través de nuestros formularios o la ejecución de un contrato de prestación de servicios técnicos.</p>

          <p><strong>5. ¿A QUÉ DESTINATARIOS SE COMUNICARÁN SUS DATOS?</strong></p>
          <p>Los datos no se cederán a terceros salvo en los casos en que exista una obligación legal o sea necesario para la prestación del servicio solicitado (ej. organismos oficiales para la tramitación de licencias y proyectos de ingeniería).</p>

          <p><strong>6. ¿CUÁLES SON SUS DERECHOS AL FACILITARNOS SUS DATOS?</strong></p>
          <p>Cualquier persona tiene derecho a obtener confirmación sobre si en REGLADO GROUP S.L estamos tratando datos personales que les conciernan, o no. Las personas interesadas tienen derecho a:</p>
          <ul>
            <li>Acceder a sus datos personales.</li>
            <li>Solicitar la rectificación de los datos inexactos.</li>
            <li>Solicitar su supresión cuando, entre otros motivos, los datos ya no sean necesarios para los fines que fueron recogidos.</li>
            <li>Solicitar la limitación de su tratamiento.</li>
            <li>Oponerse al tratamiento de sus datos.</li>
            <li>Derecho a la portabilidad de los datos.</li>
          </ul>
          <p>Para ejercer estos derechos, puede dirigirse a info@regladoingenieria.com adjuntando copia de su DNI.</p>

          <p><strong>7. PROCEDENCIA DE LOS DATOS</strong></p>
          <p>Los datos personales que tratamos en REGLADO INGENIERÍA han sido facilitados directamente por el interesado a través de los formularios de contacto y solicitud de servicios del sitio web.</p>
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
