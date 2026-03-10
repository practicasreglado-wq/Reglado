<!-- pagina Particulares en ruta /particulares -->
<template>
  <section class="section">
    <div class="container">
      <div class="grid grid-2">
        <div class="card soft glow" v-glow v-reveal="{ from: 'left', delay: 50 }">
          <div class="badge" v-reveal="{ from: 'up', delay: 80 }">Particulares</div>
          <h1 class="h1" v-reveal="{ from: 'up', delay: 120 }">Facturas elevadas, tarifas mal contratadas o sensación de que “te cobran de más”.</h1>
          <p class="p" v-reveal="{ from: 'right', delay: 170 }">
            Analizamos tu caso, optimizamos tu contrato y te explicamos con claridad qué estás pagando y por qué.
          </p>
          <div class="cta-row" v-reveal="{ from: 'up', delay: 220 }">
            <router-link to="/contacto" class="btn primary glow" v-glow v-reveal="{ from: 'left', delay: 250 }">Enviar factura</router-link>
            <router-link to="/recursos" class="btn glow" v-glow v-reveal="{ from: 'right', delay: 280 }">Ver recursos</router-link>
          </div>
        </div>

        <div class="card glow" v-glow v-reveal="{ from: 'right', delay: 70 }">
          <h2 class="h2new" v-reveal="{ from: 'up', delay: 100 }">Qué revisamos</h2>
          <ul class="bullets" v-reveal="{ from: 'up', delay: 140 }">
            <li><strong>Potencia contratada:</strong> si pagas por capacidad que no necesitas.</li>
            <li><strong>Tarifa y discriminación horaria:</strong> si encaja con tus hábitos.</li>
            <li><strong>Condiciones de contrato:</strong> permanencias, servicios añadidos, cambios de precio.</li>
            <li><strong>Conceptos de factura:</strong> detección de inconsistencias y sobrecostes.</li>
          </ul>

          <div class="hr" v-reveal="{ from: 'up', delay: 170 }"></div>

          <h2 class="h2new" v-reveal="{ from: 'up', delay: 200 }">Qué te llevas</h2>
          <ul class="bullets" v-reveal="{ from: 'up', delay: 240 }">
            <li>Explicación clara y comprensible de tu factura.</li>
            <li>Propuesta de optimización con ahorro estimado (sin letra pequeña).</li>
            <li>Gestión del cambio si procede y verificación de la primera factura.</li>
            <li>Revisión periódica opcional para mantener el ahorro en el tiempo.</li>
          </ul>
        </div>
      </div>

      <div class="section-tight">
        <div class="card soft glow" v-glow v-reveal="{ from: 'up', delay: 80 }">
          <h2 class="h2" v-reveal="{ from: 'up', delay: 120 }">Siguiente paso</h2>
          <p class="p2" v-reveal="{ from: 'up', delay: 160 }">Realizamos un análisis gratuito de facturas. Déjanos tus datos o sube tu factura en PDF y te llamamos con los resultados.</p>
          <div class="cta-center">
          <router-link to="/contacto" class="btn primary glow" v-glow v-reveal="{ from: 'up', delay: 200 }">Solicitar análisis gratuito</router-link>
          </div>
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
    title: "Particulares | Optimización de factura de luz | Reglado Energy",
    description: "Analizamos tu factura, optimizamos potencia y tarifa y explicamos en claro el ahorro estimado. Verificación en facturación.",
    canonical: "/#/particulares"
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
.cta-center {
  display: flex;
  justify-content: center;}
  .p2{ text-align: center;}

</style>
