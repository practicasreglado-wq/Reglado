<!-- pagina Administradores de fincas en ruta /administradores-fincas -->
<template>
  <section class="section">
    <div class="container">
      <div class="grid grid-2">
        <div class="card soft glow" v-glow v-reveal="{ from: 'left', delay: 50 }">
          <div class="badge" v-reveal="{ from: 'up', delay: 80 }">Administradores de fincas</div>
          <h1 class="h1" v-reveal="{ from: 'up', delay: 120 }">Centralizamos la gestión energética, optimizamos contratos y damos soporte continuo.</h1>
          <p class="p" v-reveal="{ from: 'right', delay: 170 }">
            Optimizamos carteras de inmuebles y comunidades con un enfoque de control: inventario, priorización por impacto y verificación de cambios.
          </p>
          <div class="cta-row" v-reveal="{ from: 'up', delay: 220 }">
            <router-link to="/contacto" class="btn primary glow" v-glow v-reveal="{ from: 'left', delay: 250 }">Solicitar análisis</router-link>
            <router-link to="/clientes" class="btn glow" v-glow v-reveal="{ from: 'right', delay: 280 }">Ver perfiles</router-link>
          </div>
        </div>

        <div class="card glow" v-glow v-reveal="{ from: 'right', delay: 70 }">
          <h2 class="h2new" v-reveal="{ from: 'up', delay: 100 }">Qué hacemos</h2>
          <ul class="bullets" v-reveal="{ from: 'up', delay: 140 }">
            <li>Inventario y revisión de condiciones por suministro.</li>
            <li>Priorización de acciones por impacto económico (quick wins).</li>
            <li>Gestión de cambios: potencias, titularidades, condiciones.</li>
            <li>Soporte continuo en incidencias y reclamaciones.</li>
          </ul>
<br>
          <div class="hr" v-reveal="{ from: 'up', delay: 170 }"></div>
<br>
          <h2 class="h2new" v-reveal="{ from: 'up', delay: 200 }">Qué aportamos a la comunidad</h2>
          <ul class="bullets" v-reveal="{ from: 'up', delay: 240 }">
            <li>Informes claros para juntas y propietarios.</li>
            <li>Control de facturación y verificación de cambios.</li>
            <li>Revisiones periódicas para sostener el ahorro.</li>
            <li>Un interlocutor técnico para reducir fricción y tiempos.</li>
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
    title: "Administradores de fincas | Gestión energética de comunidades | Reglado Energy",
    description: "Gestión centralizada de múltiples suministros: inventario, quick wins, incidencias y revisión periódica para sostener el ahorro.",
    canonical: "/#/administradores-fincas"
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
