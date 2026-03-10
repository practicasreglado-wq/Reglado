<!-- pagina Empresas en ruta /empresas -->
<template>
  <section class="section">
    <div class="container">
      <div class="grid grid-2">
        <div class="card soft glow" v-glow v-reveal="{ from: 'left', delay: 50 }">
          <div class="badge" v-reveal="{ from: 'up', delay: 80 }">Empresas y PYMES</div>
          <h1 class="h1" v-reveal="{ from: 'up', delay: 120 }">Reducción de costes, mejora contractual y control continuo del suministro.</h1>
          <p class="p" v-reveal="{ from: 'right', delay: 170 }">
            Analizamos contratos, facturas y consumo para detectar ineficiencias y riesgos que suelen pasar desapercibidos.
          </p>
<br>
          <h2 class="h2new" style="margin-top:14px;" v-reveal="{ from: 'up', delay: 210 }">Enfoque</h2>
          <p class="p" v-reveal="{ from: 'up', delay: 250 }">
            No nos limitamos a recomendar un cambio de tarifa o comercializadora. Entendemos cómo consumes, cómo estás contratado y qué impacto real
            tiene cada decisión en el corto, medio y largo plazo.
          </p>

          <div class="cta-row" v-reveal="{ from: 'up', delay: 290 }">
            <router-link to="/contacto" class="btn primary glow" v-glow v-reveal="{ from: 'left', delay: 320 }">Pedir diagnóstico</router-link>
            <router-link to="/servicios" class="btn glow" v-glow v-reveal="{ from: 'right', delay: 350 }">Ver servicios</router-link>
          </div>
        </div>

        <div class="card glow" v-glow v-reveal="{ from: 'right', delay: 70 }">
          <h2 class="h2new" v-reveal="{ from: 'up', delay: 110 }">Qué hacemos</h2>
          <div class="grid grid-2" style="margin-top:12px;">
            <FeatureCard icon="src/assets/iconos/Auditorias.svg" title="Auditoría de facturas"
              text="Detección de sobrecostes, errores y riesgos contractuales."
            />
            <FeatureCard icon="src/assets/iconos/Optimizacion.svg" title="Optimización de potencia"
              text="Ajuste por periodos para evitar capacidad ociosa o penalizaciones."
            />
            <FeatureCard icon="src/assets/iconos/Analytic.svg" title="Control y seguimiento"
              text="Revisión periódica para mantener el ahorro y anticipar desviaciones."
            />
            <FeatureCard icon="src/assets/iconos/Brain.svg" title="Decisiones justificadas"
              text="Medidas priorizadas con impacto económico estimado."
            />
          </div>

          <div class="hr" v-reveal="{ from: 'up', delay: 170 }"></div>

          <h2 class="h2new" v-reveal="{ from: 'up', delay: 200 }">Metodología</h2>
          <ol class="bullets" v-reveal="{ from: 'up', delay: 230 }">
            <li>Facturas, contrato, datos de consumo y objetivos.</li>
            <li>Identificación de costes, riesgos, penalizaciones y oportunidades de optimización.</li>
            <li>Medidas priorizadas con impacto económico estimado.</li>
            <li>Gestión de cambios y comprobación de facturación.</li>
            <li>Seguimiento para mantener objetivos y adaptarse al mercado.</li>
          </ol>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted } from "vue";
import { setSeo } from "../seo.js";
import FeatureCard from "../components/FeatureCard.vue";

onMounted(() => {
  setSeo({
    title: "Empresas y PYMES | Reducción de costes energéticos | Reglado Energy",
    description: "Auditoría de facturas, optimización contractual y seguimiento continuo del suministro para reducir costes sin afectar a la actividad.",
    canonical: "/#/empresas"
  });
});
</script>

<style scoped>
.badge{
  display:inline-flex; align-items:center; border-radius:999px; padding:10px 14px;
  border:1px solid rgba(242,197,61,.26); background: rgba(242,197,61,.08); width: fit-content;
  margin-bottom: 10px;
}
.bullets{ margin:0; padding-left:18px; color: var(--muted); line-height:1.9; }
.hr{ height:1px; background: linear-gradient(90deg, transparent, var(--line), transparent); margin: 18px 0; }
.cta-row{ display:flex; gap:12px; flex-wrap:wrap; margin-top: 14px; }
.h2new{  font-size: clamp(1.5rem, 3.2vw, 2rem);}
</style>
