<!-- pagina Sector publico en ruta /sector-publico -->
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
          <div class="badge" v-reveal="{ from: 'up', delay: 80 }">Sector público</div>
          <h1 class="h1" v-reveal="{ from: 'up', delay: 120 }">Gestión energética para administraciones en entornos regulados</h1>
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
    <br><br>
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
