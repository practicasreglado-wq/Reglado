<!-- pagina Sector publico en ruta /sector-publico -->
<template>
  <section class="section">
    <div class="container">
      <div class="grid grid-2">
        <div class="card soft glow" v-glow v-reveal="{ from: 'left', delay: 50 }">
          <div class="badge" v-reveal="{ from: 'up', delay: 80 }">Sector público</div>
          <h1 class="h1" v-reveal="{ from: 'up', delay: 120 }">Gestión energética para administraciones en entornos regulados.</h1>
          <p class="p" v-reveal="{ from: 'right', delay: 170 }">
            Servicio orientado a la correcta gestión energética de las administraciones, combinando criterios técnicos, económicos y de cumplimiento normativo.
          </p>

          <div class="cta-row" v-reveal="{ from: 'up', delay: 220 }">
            <router-link to="/contacto" class="btn primary glow" v-glow v-reveal="{ from: 'left', delay: 250 }">Solicitar propuesta</router-link>
            <router-link to="/servicios" class="btn glow" v-glow v-reveal="{ from: 'right', delay: 280 }">Ver servicios</router-link>
          </div>
        </div>

        <div class="card glow" v-glow v-reveal="{ from: 'right', delay: 70 }">
          <h2 class="h2new" v-reveal="{ from: 'up', delay: 100 }">Qué hacemos</h2>
          <ul class="bullets" v-reveal="{ from: 'up', delay: 140 }">
            <li><strong>Análisis técnico de contratos:</strong> potencias, precios, penalizaciones y condiciones contractuales.</li>
            <li><strong>Apoyo en licitación:</strong> comparativas objetivas y criterios de decisión técnico‑económicos.</li>
            <li><strong>Revisión económica y técnica:</strong> supervisión de la correcta aplicación de condiciones adjudicadas.</li>
            <li><strong>Seguimiento y revisiones periódicas:</strong> evitar desviaciones presupuestarias y mejorar eficiencia.</li>
          </ul>

          <div class="hr" v-reveal="{ from: 'up', delay: 170 }"></div>

          <h2 class="h2new" v-reveal="{ from: 'up', delay: 200 }">Qué necesitas para iniciar</h2>
          <ul class="bullets" v-reveal="{ from: 'up', delay: 240 }">
            <li>Pliegos / contrato vigente o documentación del suministro.</li>
            <li>Facturas recientes y datos de consumo (si están disponibles).</li>
            <li>Objetivos: ahorro, estabilidad presupuestaria, cumplimiento, eficiencia.</li>
          </ul>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted } from "vue";
import { setSeo } from "../seo.js";

onMounted(() => {
  setSeo({
    title: "Sector público | Gestión energética y licitación | Reglado Energy",
    description: "Gestión energética para administraciones: análisis de contratos, apoyo en licitación, verificación de condiciones y control presupuestario.",
    canonical: "/#/sector-publico"
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
.h2new{ font-size: clamp(1.5rem, 3.2vw, 2rem); }
</style>
