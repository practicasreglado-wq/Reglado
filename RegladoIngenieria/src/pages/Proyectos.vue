<template>
  <main>
    <section class="section">
      <div class="container">
        <span class="badge">Portfolio</span>
        <h1 class="h1" style="margin-top:16px; max-width:600px">Proyectos realizados</h1>
        <p class="lead" style="margin-top:16px; max-width:560px">Selección de proyectos de ingeniería industrial desarrollados para clientes del sector.</p>
      </div>
    </section>

    <section class="section-sm">
      <div class="container">
        <div class="grid-3">
          <div class="card project-card" v-for="p in projects" :key="p.title">
            <div class="project-type">{{ p.type }}</div>
            <h2 class="h3" style="margin-top:10px">{{ p.title }}</h2>
            <p class="text-muted" style="margin-top:8px; font-size:.9375rem">{{ p.desc }}</p>
            <div class="project-meta">
              <span>{{ p.sector }}</span>
              <span>{{ p.year }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>
</template>

<script setup>
const projects = [
  { type: "Consultoría", title: "Análisis de instalación matadero industrial", desc: "Estudio técnico de parcela y adecuación normativa para instalación de industria cárnica.", sector: "Industria alimentaria", year: "2024" },
  { type: "Proyecto", title: "Proyecto de instalación eléctrica BT", desc: "Diseño y documentación de instalación eléctrica en nave industrial de 3.000 m².", sector: "Industria general", year: "2024" },
  { type: "Informe", title: "Peritación daños estructura metálica", desc: "Informe pericial de daños estructurales en nave industrial afectada por siniestro.", sector: "Seguros industriales", year: "2023" },
  { type: "Consultoría", title: "Viabilidad uso industrial parcela logística", desc: "Análisis de viabilidad urbanística y técnica para implantación de almacén logístico.", sector: "Logística", year: "2023" },
  { type: "Proyecto", title: "Proyecto contra incendios nave almacén", desc: "Documentación técnica de protección contra incendios para nave de 5.000 m².", sector: "Industria general", year: "2023" },
  { type: "Informe", title: "Certificación eficiencia energética industrial", desc: "Certificado de eficiencia energética de instalaciones en planta de producción.", sector: "Energía", year: "2022" },
];
</script>

<style scoped>
.project-type {
  display: inline-block;
  padding: 3px 10px;
  background: var(--steel-light);
  color: var(--steel-dark);
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .04em;
}
.project-meta {
  display: flex;
  gap: 12px;
  margin-top: 16px;
  font-size: 0.8125rem;
  color: var(--text-muted);
  border-top: 1px solid var(--border);
  padding-top: 12px;
}
</style>
