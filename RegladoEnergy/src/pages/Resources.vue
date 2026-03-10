<!-- pagina Recursos en ruta /recursos -->
<template>
  <section class="section">
    <div class="container">
      <div class="card soft glow" v-glow v-reveal="{ from: 'up', delay: 50 }">
        <div class="badge" v-reveal="{ from: 'left', delay: 80 }">Recursos</div>
        <h1 class="h1" v-reveal="{ from: 'up', delay: 120 }">Análisis, guías y contenidos claros sobre mercado eléctrico, cambios regulatorios y estrategias de ahorro.</h1>
        <p class="p" v-reveal="{ from: 'right', delay: 170 }">
          La factura eléctrica no se “lee”, se audita. Distinguir costes regulados de costes negociables es la base para detectar sobrecostes y optimizar la contratación.
        </p>
        <div class="cta-row" v-reveal="{ from: 'up', delay: 220 }">
          <router-link to="/contacto" class="btn primary glow" v-glow v-reveal="{ from: 'left', delay: 250 }">Quiero un análisis</router-link>
          <router-link to="/servicios" class="btn glow" v-glow v-reveal="{ from: 'right', delay: 280 }">Ver servicios</router-link>
        </div>
      </div>

      <div class="section-tight">
        <div class="grid grid-2">
          <div class="card glow" v-glow v-reveal="{ from: 'left', delay: 60 }">
            <h2 class="h2" v-reveal="{ from: 'up', delay: 100 }">Cómo entender la factura de la luz</h2>
            <p class="p" v-reveal="{ from: 'up', delay: 130 }">Desglose estructural</p>
            <ul class="bullets" v-reveal="{ from: 'up', delay: 160 }">
              <li><strong>Término de potencia (costes fijos):</strong> foco de sobrecoste si la potencia no se ajusta a la curva de carga.</li>
              <li><strong>Término de energía (costes variables):</strong> fijo = estabilidad con prima; indexado = mercado horario + fee con volatilidad.</li>
            </ul>
            <p class="p" style="margin-top:12px;" v-reveal="{ from: 'up', delay: 190 }">Conceptos regulatorios e impositivos</p>
            <ul class="bullets" v-reveal="{ from: 'up', delay: 220 }">
              <li>Impuesto Especial sobre la Electricidad (IEE) y posibles reducciones sectoriales.</li>
              <li>Alquiler de equipo de medida (si el contador es de la distribuidora).</li>
              <li>Financiación del bono social y cargos normativos.</li>
            </ul>
          </div>

          <div class="card glow" v-glow v-reveal="{ from: 'right', delay: 80 }">
            <h2 class="h2" v-reveal="{ from: 'up', delay: 110 }">Auditoría de sobrecostes</h2>
            <ul class="bullets" v-reveal="{ from: 'up', delay: 150 }">
              <li><strong>Excesos de potencia (maxímetro):</strong> ajustar potencia para evitar penalizaciones o capacidad ociosa.</li>
              <li><strong>Energía reactiva:</strong> recargos por factor de potencia; batería de condensadores cuando procede.</li>
              <li><strong>Revisión de precios y componentes:</strong> contratos antiguos o mal parametrizados pueden quedar fuera de mercado.</li>
            </ul>

            <div class="hr" v-reveal="{ from: 'up', delay: 180 }"></div>

            <h2 class="h2" v-reveal="{ from: 'up', delay: 210 }">Marco normativo de referencia</h2>
            <ul class="bullets" v-reveal="{ from: 'up', delay: 240 }">
              <li>Ley 24/2013, del Sector Eléctrico.</li>
              <li>Circular 3/2020, CNMC (peajes).</li>
              <li>Real Decreto-ley 148/2021 (cargos del sistema).</li>
              <li>Ley 38/1992, Impuestos Especiales.</li>
            </ul>
          </div>
        </div>
      </div>

      <div class="section-tight">
        <div class="grid grid-2">
          <div class="card soft glow" v-glow v-reveal="{ from: 'left', delay: 70 }">
            <h2 class="h2" v-reveal="{ from: 'up', delay: 100 }">Cambios regulatorios</h2>
            <p class="p" v-reveal="{ from: 'up', delay: 130 }">La regulación impacta en la estructura de costes, la contratación y las oportunidades de ahorro. Convertimos el seguimiento normativo en ventaja.</p>
            <ul class="bullets" v-reveal="{ from: 'up', delay: 160 }">
              <li><strong>Circular 3/2020 (CNMC):</strong> nueva estructura de peajes; 6 periodos de potencia en industriales.</li>
              <li><strong>RDL 148/2021:</strong> separación de peajes y cargos; revisiones periódicas con impacto en precio final.</li>
              <li><strong>Autoconsumo:</strong> oportunidades cuando es viable.</li>
              <li><strong>Certificados de Ahorro Energético (CAE):</strong> la eficiencia puede monetizarse (cuando aplica).</li>
            </ul>
          </div>

          <div class="card soft glow" v-glow v-reveal="{ from: 'right', delay: 90 }">
            <h2 class="h2" v-reveal="{ from: 'up', delay: 110 }">Estrategias de ahorro energético</h2>
            <p class="p" v-reveal="{ from: 'up', delay: 140 }">Reducir la factura no implica reducir la actividad: optimización contractual, medición/control y mejoras técnicas con retorno.</p>
            <ul class="bullets" v-reveal="{ from: 'up', delay: 170 }">
              <li><strong>Optimización de contratación:</strong> gestión dinámica de potencias y elección de modelo fijo/indexado según objetivo.</li>
              <li><strong>Medición y monitorización:</strong> detectar picos y consumo fantasma; monitorización cuartohoraria cuando procede.</li>
              <li><strong>Eficiencia técnica:</strong> climatización e iluminación con medidas de retorno claro.</li>
              <li><strong>Incentivos:</strong> evaluación de viabilidad técnica y económica de autoconsumo y eficiencia.</li>
            </ul>
          </div>
        </div>
      </div>

      <div class="section-tight">
        <div class="card glow" v-glow v-reveal="{ from: 'up', delay: 80 }">
          <h2 class="h2" v-reveal="{ from: 'up', delay: 110 }">Mercado eléctrico nacional</h2>
          <p class="p" v-reveal="{ from: 'up', delay: 140 }">
            Comprender formación de precios y qué componentes son regulados ayuda a decidir: fijo, indexado, coberturas, autoconsumo.
          </p>
          <div class="grid grid-2" style="margin-top:10px;">
            <ul class="bullets" v-reveal="{ from: 'left', delay: 170 }">
              <li><strong>OMIE:</strong> mercado diario y casación de ofertas.</li>
              <li><strong>REE (Redeia):</strong> operación del sistema y servicios de ajuste.</li>
              <li><strong>Modelo marginalista:</strong> el precio lo fija la última unidad necesaria para cubrir demanda.</li>
            </ul>
            <ul class="bullets" v-reveal="{ from: 'right', delay: 200 }">
              <li><strong>Precio final:</strong> energía + peajes + cargos + ajustes + impuestos.</li>
              <li><strong>Fijo vs indexado:</strong> depende de perfil y tolerancia al riesgo; se decide con escenarios y objetivos.</li>
              <li><strong>El análisis gratuito</strong> no tiene compromiso: primero se explica el impacto económico y el porqué.</li>
            </ul>
          </div>
        </div>
      </div>

      <div class="section-tight">
        <FAQ :items="faq" />
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted } from "vue";
import { setSeo } from "../seo.js";
import FAQ from "../components/FAQ.vue";

const faq = [
  { q: "Potencia contratada", a: "Capacidad máxima disponible; más de lo necesario = pagas de más; menos = penalizaciones." },
  { q: "Energía reactiva", a: "Asociada a cargas inductivas; puede generar recargos en ciertos suministros." },
  { q: "¿Fijo o indexado?", a: "Depende de tu perfil y tolerancia al riesgo; se decide con escenarios y objetivos." },
  { q: "¿El análisis gratuito tiene compromiso?", a: "No. Primero se explica el impacto económico y el porqué. Tú decides si ejecutar cambios." },
];

onMounted(() => {
  setSeo({
    title: "Recursos | Guías sobre factura eléctrica y ahorro | Reglado Energy",
    description: "Guías y análisis sobre factura eléctrica, normativa, mercado y estrategias de ahorro: potencia, reactiva, fijo vs indexado y más.",
    canonical: "/#/recursos"
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

</style>
