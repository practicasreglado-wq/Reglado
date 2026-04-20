<!-- pagina Sobre nosotros en ruta /sobre-nosotros -->
<template>
  <section class="section">
    <div class="container">
      <div class="card soft glow" v-glow v-reveal="{ from: 'up', delay: 50 }">
        <div class="badge" v-reveal="{ from: 'left', delay: 80 }">Sobre nosotros</div>
        <h1 class="h1" v-reveal="{ from: 'up', delay: 120 }">Consultoría energética independiente para particulares, empresas, administraciones públicas y gestores de múltiples suministros</h1>
        <p class="p" v-reveal="{ from: 'right', delay: 170 }">
          Aportamos criterio técnico, transparencia y control en un mercado energético complejo y cambiante. Frente a modelos basados en la venta de tarifas,
          apostamos por un enfoque profesional, analítico y orientado al largo plazo.
        </p>
        <p class="p" v-reveal="{ from: 'up', delay: 210 }">
          Analizamos, optimizamos y gestionamos los suministros con criterios técnicos y económicos, actuando de manera independiente frente a las comercializadoras.
          Acompañamos a nuestros clientes durante todo el ciclo de vida del suministro, desde el análisis inicial hasta el seguimiento periódico.
        </p>
        <div class="cta-row" v-reveal="{ from: 'up', delay: 250 }">
          <router-link to="/contacto" class="btn primary glow" v-glow v-reveal="{ from: 'left', delay: 280 }">Hablar con nosotros</router-link>
          <router-link to="/servicios" class="btn glow" v-glow v-reveal="{ from: 'right', delay: 310 }">Ver servicios</router-link>
        </div>
      </div>

      <div class="section-tight">
        <div class="grid grid-2">
          <div class="card glow" v-glow v-reveal="{ from: 'left', delay: 70 }">
            <h2 class="h2new" v-reveal="{ from: 'up', delay: 110 }">Vinculación con Reglado Consultores & Abogados</h2>
            <br>
            <p class="p" v-reveal="{ from: 'up', delay: 150 }">
              Reglado Energy forma parte del ecosistema profesional de Reglado Consultores & Abogados, aplicando el mismo rigor técnico, metodología de trabajo
              y compromiso con el cliente. Esta vinculación permite abordar la gestión energética con una visión integral, especialmente en entornos regulados,
              contratos complejos y sector público.
            </p>
          </div>

          <div class="card glow" v-glow v-reveal="{ from: 'right', delay: 90 }">
            <h2 class="h2new" v-reveal="{ from: 'up', delay: 120 }">Misión</h2>
            <p class="p" v-reveal="{ from: 'up', delay: 150 }">
              Ayudar a particulares, empresas y administraciones públicas a optimizar sus contratos de luz y gas para que paguen lo justo y mantengan el control del suministro.
            </p>

            <div class="hr" v-reveal="{ from: 'up', delay: 180 }"></div>

            <h2 class="h2new" v-reveal="{ from: 'up', delay: 210 }">Visión</h2>
            <p class="p" v-reveal="{ from: 'up', delay: 240 }">
              Consolidarnos como referente en consultoría y gestión energética independiente en España, reconocido por nuestro rigor técnico, metodología y especialización en contratos energéticos complejos.
            </p>
          </div>
        </div>
      </div>

      <div class="section-tight">
        <div class="card soft glow" v-glow v-reveal="{ from: 'up', delay: 80 }">
          <h2 class="h2" v-reveal="{ from: 'up', delay: 120 }">Valores</h2>
          <div class="grid grid-3" style="margin-top:12px;">
            <FeatureCard icon="src/assets/iconos/Independency.svg" title="Independencia" text="Defendemos los intereses del cliente frente al mercado." />
            <FeatureCard icon="src/assets/iconos/License.svg" title="Rigor técnico y económico" text="Decisiones basadas en datos y criterios objetivos." />
            <FeatureCard icon="src/assets/iconos/Transparency.svg" title="Transparencia y claridad" text="Explicamos cada paso y su impacto económico." />
            <FeatureCard icon="src/assets/iconos/Shake.svg" title="Acompañamiento" text="Soporte y seguimiento continuo durante toda la vida del contrato." />
            <FeatureCard icon="src/assets/iconos/EcoEnergi.svg" title="Eficiencia y sostenibilidad" text="Priorizamos soluciones sostenibles cuando son viables." />
            <FeatureCard icon="src/assets/iconos/Repeat.svg" title="Control" text="Revisión periódica para sostener ahorros y corregir desviaciones." />
          </div>
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
    title: "Sobre nosotros | Consultoría energética independiente | Reglado Energy",
    description: "Criterio técnico, transparencia y control en un mercado complejo. Consultoría independiente vinculada a Reglado Consultores & Abogados.",
    canonical: "/#/sobre-nosotros"
  });
});
</script>

<style scoped>
.badge{
  display:inline-flex; align-items:center; border-radius:999px; padding:10px 14px;
  border:1px solid rgba(242,197,61,.26); background: rgba(242,197,61,.08); width: fit-content;
  margin-bottom: 10px;
}
.hr{ height:1px; background: linear-gradient(90deg, transparent, var(--line), transparent); margin: 18px 0; }
.cta-row{ display:flex; gap:12px; flex-wrap:wrap; margin-top: 14px; }
.h2new{  font-size: clamp(1.5rem, 3.2vw, 2rem);}
</style>
