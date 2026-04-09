<!-- pagina Empresas en ruta /empresas -->
<template>

<!-- botones link a 4 páginas de seccion clientes -->
 <section class="section clients-subnav-section">
   <div class="container" v-reveal="{ from: 'up', delay: 120 }">
        <nav class="clients-subnav" aria-label="Submenu de clientes">
          <router-link to="/particulares" class="clients-subnav-link">Particulares</router-link>
          <router-link to="/empresas" class="clients-subnav-link">Empresas y PYMES</router-link>
          <router-link to="/administradores-fincas" class="clients-subnav-link">Comunidades y fincas</router-link>
          <router-link to="/sector-publico" class="clients-subnav-link">Organismos públicos</router-link>
        </nav>
      </div>
    </section>

  <section class="section clients-content-section">
    <div class="container">
      <div class="grid grid-2">
        <div class="card soft glow" v-glow v-reveal="{ from: 'left', delay: 50 }">
          <div class="badge" v-reveal="{ from: 'up', delay: 80 }">Empresas y PYMES</div>
          <h1 class="h1" v-reveal="{ from: 'up', delay: 120 }">Reducción de costes, mejora contractual y control continuo del suministro</h1>
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
     <br><br>
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
/* submenú clientes */
.cta-row{ display:flex; gap:12px; flex-wrap:wrap; margin-top: 14px; }
.clients-subnav-wrap{ margin-top: 20px; }
.clients-subnav{
  display:grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}
.clients-subnav-link{
  display:flex;
  align-items:center;
  justify-content:center;
  min-height: 58px;
  padding: 14px 18px;
  border-radius: 18px;
  border: 1px solid rgba(242,197,61,.26);
  background:
    linear-gradient(180deg, rgba(242,197,61,.14), rgba(242,197,61,.06)),
    rgba(255,255,255,.03);
  color: rgba(242, 197, 61, .9);
  font-weight: 800;
  text-align: center;
  text-decoration: none;
  box-shadow: 0 14px 34px rgba(0,0,0,.18);
  transition:
    transform .16s ease,
    border-color .16s ease,
    box-shadow .16s ease,
    background .16s ease;
}
.clients-subnav-link:hover{
  transform: translateY(-1px);
  border-color: rgba(242,197,61,.4);
  background:
    linear-gradient(180deg, rgba(242,197,61,.2), rgba(242,197,61,.09)),
    rgba(255,255,255,.04);
  color: #ffffff;
  box-shadow: 0 18px 40px rgba(242,197,61,.12);
}
.clients-subnav-link.router-link-active{
  color: #0b0d10;
  background: linear-gradient(180deg, rgba(242,197,61,.96), rgba(242,197,61,.72));
  border-color: rgba(242,197,61,.42);
  box-shadow: 0 18px 44px rgba(242,197,61,.2);
}
.clients-cta-center{ display:flex; justify-content:center; margin-top: 14px; }
.p_centrado{ text-align: center; }

@media (max-width: 980px){
  .clients-subnav{ grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 640px){
  .clients-subnav{
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
  }
  .clients-subnav-link{
    min-height: 58px;
    padding: 12px 10px;
    font-size: 14px;
  }
  .clients-subnav-section{
    padding-bottom: 32px;
  }
  .clients-content-section{
    padding-top: 32px;
  }
}
</style>
