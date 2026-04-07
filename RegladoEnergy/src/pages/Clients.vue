<!-- pagina Clientes en ruta /clientes -->
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
      <div class="card soft glow" v-glow v-reveal="{ from: 'up', delay: 50 }">
        <div class="badge" v-reveal="{ from: 'left', delay: 80 }">Clientes</div>
        <h1 class="h1" v-reveal="{ from: 'up', delay: 120 }">Cada cliente tiene un consumo distinto</h1>
        <p class="p" v-reveal="{ from: 'right', delay: 170 }">
          Realizamos estudios adaptados a tu perfil y necesidades reales para reducir costes, mejorar la contratación y mantener el control del suministro.
        </p>
        <div class="cta-row" v-reveal="{ from: 'up', delay: 220 }">
          <router-link to="/contacto" class="btn primary glow" v-glow v-reveal="{ from: 'left', delay: 250 }">Solicitar análisis gratuito</router-link>
          <router-link to="/servicios" class="btn glow" v-glow v-reveal="{ from: 'right', delay: 280 }">Ver servicios</router-link>
        </div>
      </div>

      <div class="section-tight">
        <div class="grid grid-3">
          <ProfileCard
            to="/particulares"
            label="Particulares"
            name="Hogares"
            problem="Facturas elevadas, tarifas mal contratadas y falta de claridad."
            solution="Analizamos tu factura, optimizamos tu tarifa y te explicamos con claridad qué estás pagando y cómo reducirlo."
          />
          <ProfileCard
            to="/empresas"
            label="PYMES"
            name="Negocios"
            problem="Sobrecostes, falta de control y poco tiempo para revisar contratos."
            solution="Optimizamos potencias, tarifas y condiciones contractuales para reducir costes sin afectar a la actividad."
          />
          <ProfileCard
            to="/empresas"
            label="Grandes empresas"
            name="Alto consumo"
            problem="Contratos complejos, alto consumo y riesgo económico."
            solution="Análisis técnico avanzado, comparativa entre comercializadoras y seguimiento continuo del contrato."
          />
        </div>

        <div class="grid grid-2" style="margin-top:18px;">
          <ProfileCard
            to="/administradores-fincas"
            label="Administradores de fincas"
            name="Comunidades"
            problem="Múltiples suministros, reclamaciones y falta de control."
            solution="Gestión energética centralizada, optimización de contratos y soporte continuo para comunidades."
          />
          <ProfileCard
            to="/sector-publico"
            label="Organismos públicos"
            name="Entornos regulados"
            problem="Rigidez contractual, cumplimiento normativo y control presupuestario."
            solution="Asesoramiento técnico en contratos energéticos y apoyo en licitación y revisión de suministros."
          />
        </div>
      </div>

      <div class="section-tight">
        <div class="card glow" v-glow v-reveal="{ from: 'up', delay: 70 }">
          <h2 class="h2" v-reveal="{ from: 'up', delay: 100 }">¿No encajas en una categoría?</h2>
          <p class="p p_centrado" v-reveal="{ from: 'up', delay: 140 }">
            Con una factura y tu objetivo (ahorro, estabilidad o control) te diremos el plan de acción.
          </p>
          <div class="clients-cta-center" v-reveal="{ from: 'up', delay: 180 }">
            <router-link to="/contacto" class="btn primary glow" v-glow v-reveal="{ from: 'up', delay: 210 }">Cuéntanos tu caso</router-link>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted } from "vue";
import { setSeo } from "../seo.js";
import ProfileCard from "../components/ProfileCard.vue";

onMounted(() => {
  setSeo({
    title: "Clientes | Soluciones por perfil de consumo | Reglado Energy",
    description: "Soluciones energéticas para particulares, PYMES, grandes empresas, administradores de fincas y sector público.",
    canonical: "/#/clientes"
  });
});
</script>

<style scoped>
.badge{
  display:inline-flex; align-items:center; border-radius:999px; padding:10px 14px;
  border:1px solid rgba(242,197,61,.26); background: rgba(242,197,61,.08); width: fit-content;
  margin-bottom: 10px;
}
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
