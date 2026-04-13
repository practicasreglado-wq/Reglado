<template>
  <section class="section">
    <div class="container">
      <div class="card soft glow">
        <h1 class="h1">Aviso Legal</h1>
        <div class="legal-content">
          <p><strong>1. TITULARIDAD DEL PORTAL.</strong></p>
          <p>REGLADO ENERGY, es la titular y responsable de este sitio web, poniendo a disposición de los usuarios el presente documento, con el que pretende dar cumplimiento a las obligaciones dispuestas en la Ley 34/2002, de 11 de julio, de Servicios de la Sociedad de la Información y del Comercio Electrónico (LSSICE), así como informar a todos los usuarios del sitio web respecto a cuáles son las condiciones de uso.</p>
          <p>REGLADO ENERGY se reserva el derecho de modificar cualquier tipo de información que pudiera aparecer en el sitio web, sin que exista obligación de preavisar o poner en conocimiento de los usuarios dichas obligaciones.</p>

          <p><strong>2. DATOS IDENTIFICATIVOS.</strong></p>
          <ul>
            <li>Denominación social: REGLADO ENERGY SL</li>
            <li>Nombre Comercial: REGLADO ENERGY</li>
            <li>CIF: B24887754</li>
            <li>Domicilio: AVDA. ISLA GRACIOSA, 7 PISO 1º DESPACHOS 5-6 28703 SAN SEBASTIÁN DE LOS REYES.</li>
            <li>Teléfono: +34 911462674 / 615-641-081</li>
            <li>Dirección electrónica: info@regladoenergy.com</li>
          </ul>

          <p><strong>3. OBJETO.</strong></p>
          <p>El presente Aviso Legal recoge las condiciones de uso que regulan el acceso, navegación y uso del Portal, así como las responsabilidades derivadas de la utilización, prestación y/o contratación de los productos o servicios que, en su caso, puedan ser ofrecidos, así como de los contenidos que lo integran.</p>

          <h2><strong>4. ACEPTACIÓN.</strong></h2>
          <p>El acceso y utilización de este portal de Internet como usuario implica la aceptación de las disposiciones contenidas en este Aviso legal, el cual puede cambiar en cualquier momento.</p>

          <p><strong>5. ACCESO Y USO DEL PORTAL.</strong></p>
          <p>El acceso al Portal por parte de los Usuarios tiene carácter libre y gratuito. En todo caso, el acceso, navegación y uso del Portal se hace bajo la única y exclusiva responsabilidad del Usuario.</p>

          <p><strong>6. CONDICIONES DE USO.</strong></p>
          <p>El portal y sus servicios son, en general, de libre acceso. El Usuario se compromete a utilizar este sitio web sin incurrir en actividades que puedan ser consideradas ilícitas o ilegales, que infrinjan los derechos de terceros o que puedan atentar contra las presentes Condiciones de uso, la legislación vigente y los usos generalmente aceptados.</p>

          <p><strong>7. PRIVACIDAD Y TRATAMIENTO DE DATOS.</strong></p>
          <p>Cuando para el acceso a determinados contenidos o servicio sea necesario facilitar datos de carácter personal, los Usuarios garantizarán su veracidad, exactitud, autenticidad y vigencia. La empresa dará a dichos datos el tratamiento automatizado que corresponda en función de su naturaleza o finalidad (cumplimiento del RGPD, LOPDGDD y LSSI).</p>

          <p><strong>8. RESPONSABILIDADES Y LIMITACIONES.</strong></p>
          <p>La Empresa no puede garantizar la fiabilidad, utilidad o veracidad de la información que se preste a través del Portal, y no garantiza ni se hace responsable de daños por virus, falta de disponibilidad, invulnerabilidad del Portal, u otros daños causados por el uso del mismo.</p>

          <p><strong>9. DERECHOS DE PROPIEDAD INTELECTUAL E INDUSTRIAL.</strong></p>
          <p>Todos aquellos activos intangibles puestos a disposición del público por la presente página web están protegidos. Quedan reservados todos los derechos de propiedad intelectual e industrial sobre los Contenidos y, en particular, queda prohibido modificar, copiar, reproducir, comunicar públicamente, transformar o distribuir de cualquier forma la totalidad o parte de los contenidos incluidos en el Portal.</p>

          <p><strong>10. HIPERVÍNCULOS Y ENLACES A OTRAS PÁGINAS WEB</strong></p>
          <p>En el caso de que se dispusiesen enlaces o hipervínculos hacía otros sitios de Internet, REGLADO ENERGY no ejercerá ningún tipo de control sobre dichos sitios y contenidos, ni asumirá responsabilidad alguna por sus contenidos.</p>

          <p><strong>11. COOKIES.</strong></p>
          <p>La empresa se reserva el derecho de utilizar la tecnología "cookie" en el espacio Web, a fin de reconocerlo como Usuario frecuente y personalizar el uso que realice del espacio Web.</p>

          <p><strong>12. DURACIÓN Y MODIFICACIÓN.</strong></p>
          <p>REGLADO ENERGY se reserva el derecho de modificar el presente Aviso Legal y/o las condiciones particulares.</p>

          <p><strong>13. COMUNICACIONES.</strong></p>
          <p>Cualquier comunicación entre REGLADO ENERGY y el Usuario, deberá dirigirse a la Empresa a la dirección electrónica: info@regladoenergy.com</p>

          <p><strong>14. LEGISLACIÓN APLICABLE Y TRIBUNALES COMPETENTES.</strong></p>
          <p>El presente Aviso Legal y las relaciones establecidas entre la Empresa y el Usuario se regirán y resolverán de conformidad con lo establecido en la normativa española. Para los casos en los que la normativa prevea la posibilidad a las partes de someterse a un fuero, la Empresa y el Usuario, someterán cualesquiera controversias a los Juzgados y Tribunales competentes.</p>
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
    title: 'Aviso Legal | Reglado Energy',
    description: 'Aviso Legal y Condiciones de Uso de Reglado Energy.',
    canonical: '/#/aviso-legal'
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
