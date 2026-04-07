<!-- pagina Inicio en ruta / -->
<template>
  <section class="section">
    <div class="container">
      <div class="hero card soft glow" v-glow v-reveal="{ from: 'up' }">
        <div class="badge" v-reveal="{ from: 'left', delay: 40 }">REGLADO ENERGY</div>
        <h1 class="h1" v-reveal="{ from: 'up', delay: 80 }">Optimizamos contratos de luz y gas, reducimos costes y te acompañamos en toda la gestión energética</h1>
        <p class="p" v-reveal="{ from: 'right', delay: 140 }">
          Tomamos decisiones con criterio técnico y económico, explicando cada paso con transparencia y manteniendo el control del
          suministro con revisiones periódicas.
        </p>

        <div class="cta-row" v-reveal="{ from: 'up', delay: 180 }">
          <router-link to="/contacto" class="btn primary glow" v-glow>Solicitar análisis gratuito</router-link>
          <router-link to="/servicios" class="btn glow" v-glow>Ver servicios</router-link>
        </div>
      </div>
    </div>
  </section>

  <section class="section-tight">
    <div class="container">
        <h2 class="h2" v-reveal="{ from: 'up' }">A quién ayudamos</h2>
        <p class="p" v-reveal="{ from: 'up', delay: 70 }">
        Cada tipo de cliente tiene problemas energéticos distintos. Por eso adaptamos nuestros análisis, herramientas y recomendaciones
        a la realidad de cada consumo.
      </p>

      <div class="grid grid-3" style="margin-top:18px;">
        <ProfileCard
          to="/particulares"
          label="Particulares"
          name="Hogares"
          problem="Facturas elevadas, tarifas mal contratadas, falta de claridad."
          solution="Optimización y explicación sencilla y comprensible."
          :reveal="{ from: 'left', delay: 60 }"
        />
        <ProfileCard
          to="/empresas"
          label="PYMES"
          name="Negocios"
          problem="Sobrecostes y falta de tiempo para revisar contratos."
          solution="Optimización de potencias, tarifas y condiciones sin afectar a la actividad."
          :reveal="{ from: 'up', delay: 100 }"
        />
        <ProfileCard
          to="/empresas"
          label="Grandes empresas"
          name="Alto consumo"
          problem="Contratos complejos, alto consumo y riesgo económico."
          solution="Análisis técnico avanzado, comparativa objetiva y seguimiento continuo."
          :reveal="{ from: 'right', delay: 140 }"
        />
      </div>

      <div class="grid grid-2" style="margin-top:18px;">
        <ProfileCard
          to="/sector-publico"
          label="Organismos públicos"
          name="Entornos regulados"
          problem="Rigidez contractual, cumplimiento normativo y control presupuestario."
          solution="Asesoramiento técnico y apoyo en licitación y revisión de suministros."
          :reveal="{ from: 'left', delay: 80 }"
        />
        <ProfileCard
          to="/administradores-fincas"
          label="Administradores de fincas"
          name="Comunidades"
          problem="Múltiples suministros, incidencias y reclamaciones."
          solution="Gestión centralizada, optimización de contratos y soporte continuo para comunidades."
          :reveal="{ from: 'right', delay: 120 }"
        />
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
        <h2 class="h2" v-reveal="{ from: 'up' }">Beneficios clave</h2>
        <p class="p2" v-reveal="{ from: 'up', delay: 70 }">
        Buscamos ahorro sostenible y control real del suministro: decisiones explicadas, trazables y mantenidas en el tiempo.
      </p>

      <div class="grid grid-3" style="margin-top:18px;">
        <FeatureCard icon="src/assets/iconos/PrecioBajo.svg" title="Ahorro económico"
          text="Optimización técnica de contratos, potencias y condiciones (no cambios indiscriminados de comercializadora)."
          :reveal="{ from: 'left', delay: 60 }"
        />
        <FeatureCard icon="src/assets/iconos/Transparency.svg" title="Transparencia"
          text="Impacto económico claro antes de ejecutar cambios, sin letra pequeña."
          :reveal="{ from: 'up', delay: 90 }"
        />
        <FeatureCard icon="src/assets/iconos/EcoEnergi.svg" title="Energías renovables"
          text="Priorizamos soluciones sostenibles cuando son técnica y económicamente viables."
          :reveal="{ from: 'right', delay: 120 }"
        />
        <FeatureCard icon="src/assets/iconos/Financiacion.svg" title="Seguimiento de objetivos"
          text="Revisiones periódicas para sostener ahorros y corregir desviaciones."
          :reveal="{ from: 'left', delay: 150 }"
        />
        <FeatureCard icon="src/assets/iconos/Shake.svg" title="Acompañamiento continuo"
          text="Soporte durante toda la vida del contrato."
          :reveal="{ from: 'up', delay: 180 }"
        />
        <FeatureCard icon="src/assets/iconos/Independency.svg" title="Independencia"
          text="Actuamos como asesores, defendiendo los intereses del cliente frente al mercado."
          :reveal="{ from: 'right', delay: 210 }"
        />
      </div>
    </div>
  </section>

  <section class="section-tight">
    <div class="container">
        <h2 class="h2" v-reveal="{ from: 'up' }">Metodología de trabajo</h2>
        <p class="p2" v-reveal="{ from: 'up', delay: 70 }">
        Aplicamos una metodología estructurada que garantiza decisiones correctas hoy y revisiones periódicas en el futuro.
      </p>

      <div class="grid grid-3" style="margin-top:18px;">
        <FeatureCard icon="src/assets/iconos/DocumentSearch.svg" title="Análisis de facturas y consumo"
          text="Detección de ineficiencias, sobrecostes y riesgos contractuales."
          :reveal="{ from: 'left', delay: 60 }"
        />
        <FeatureCard icon="src/assets/iconos/Optimizacion.svg" title="Optimización de potencias y tarifas"
          text="Adecuación a consumo real para evitar pagar de más o incurrir en penalizaciones."
          :reveal="{ from: 'up', delay: 90 }"
        />
        <FeatureCard icon="src/assets/iconos/Compare.svg" title="Comparativa objetiva de opciones"
          text="Evaluación sin conflicto de interés y con criterios técnico‑económicos."
          :reveal="{ from: 'right', delay: 120 }"
        />
        <FeatureCard icon="src/assets/iconos/Contract.svg" title="Contratación y seguimiento"
          text="Acompañamiento en la ejecución y verificación de la correcta aplicación en facturación."
          :reveal="{ from: 'left', delay: 150 }"
        />
        <FeatureCard icon="src/assets/iconos/CustomerService.svg" title="Soporte y revisión periódica"
          text="Mantenimiento del control del suministro y adaptación a cambios del mercado."
          :reveal="{ from: 'up', delay: 180 }"
        />
        <div class="card glow" v-glow v-reveal="{ from: 'right', delay: 210 }">
          <div class="h2new" style="margin-bottom:10px;">¿Quieres saber si estás pagando de más?</div>
          <p class="p">
            Realizamos un análisis gratuito de facturas. Déjanos tus datos o sube tu factura en PDF y te llamamos con los resultados.
          </p>
          <div class="card-cta-center">
            <router-link to="/contacto" class="btn primary glow" v-glow>Empezar ahora</router-link>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="card soft glow" v-glow v-reveal="{ from: 'up' }">
        <h2 class="h2" v-reveal="{ from: 'up', delay: 40 }">Quiénes somos</h2>
        <p class="p" v-reveal="{ from: 'right', delay: 90 }">
          Reglado Energy es una consultoría energética independiente especializada en la optimización y gestión de contratos de luz y gas
          para particulares, empresas y sector público.
        </p>
        <div class="about-cta-center" v-reveal="{ from: 'up', delay: 130 }">
          <router-link to="/sobre-nosotros" class="btn primary glow" v-glow>Conocer más</router-link>
        </div>
      </div>
      <br><br>
    </div>
  </section>
</template>

<script setup>
import { onMounted } from "vue";
import { setSeo } from "../seo.js";
import FeatureCard from "../components/FeatureCard.vue";
import ProfileCard from "../components/ProfileCard.vue";

onMounted(() => {
  setSeo({
    title: "Reglado Energy | Consultoría energética independiente",
    description: "Optimizamos contratos de luz y gas con criterio técnico y económico. Análisis gratuito de facturas y seguimiento periódico.",
    canonical: "/#/"
  });
});
</script>

<style scoped>
.hero{ padding: 34px; }
.badge{
  display:inline-flex; align-items:center; border-radius:999px; padding:10px 14px;
  border:1px solid rgba(242,197,61,.26); background: rgba(242,197,61,.08); width: fit-content;
  margin-bottom: 10px;
}
.cta-row{ display:flex; gap:12px; flex-wrap:wrap; margin-top: 16px; }
.about-cta-center{ display:flex; justify-content:center; margin-top: 14px; }
.card-cta-center{ display:flex; justify-content:center; margin-top: 14px; }
.p2{ text-align: center;}
</style>
