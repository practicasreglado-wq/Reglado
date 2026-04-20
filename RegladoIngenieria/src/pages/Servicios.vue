<template>
  <main>
    <section class="section">
      <div class="container">
        <span class="badge">Lo que hacemos</span>
        <h1 class="h1" style="margin-top:16px; max-width:640px">Servicios de ingeniería industrial</h1>
        <p class="lead" style="margin-top:16px; max-width:580px">Ofrecemos soluciones técnicas integrales para empresas del sector industrial. Cada servicio se entrega con documentación completa y respaldo normativo.</p>
      </div>
    </section>

    <section class="section-sm bg-soft">
      <div class="container">
        <div class="grid-2">
          <div class="card service-card" v-for="s in services" :key="s.title">
            <div class="service-tag">{{ s.tag }}</div>
            <h2 class="h3" style="margin-top:12px">{{ s.title }}</h2>
            <p class="text-muted" style="margin-top:10px">{{ s.desc }}</p>
            <ul class="service-list">
              <li v-for="item in s.items" :key="item">{{ item }}</li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <section class="section-sm">
      <div class="container" style="text-align:center">
        <h2 class="h2">¿Necesitas un servicio personalizado?</h2>
        <p class="lead" style="margin-top:12px">Contacta con nosotros y analizamos tu caso concreto.</p>
        <router-link to="/contacto" class="btn primary" style="margin-top:24px">Solicitar información</router-link>
      </div>
    </section>
  </main>
</template>

<script setup>
const services = [
  {
    tag: "Consultoría",
    title: "Consultoría técnica industrial",
    desc: "Asesoramiento especializado en el análisis, diseño y optimización de procesos e instalaciones industriales.",
    items: ["Diagnóstico de instalaciones", "Optimización de procesos productivos", "Cumplimiento normativo", "Informes técnicos"],
  },
  {
    tag: "Parcelas",
    title: "Análisis de parcelas industriales",
    desc: "Estudios técnicos de parcelas para determinar viabilidad de uso industrial, requisitos de instalación y adecuación normativa.",
    items: ["Análisis urbanístico y de usos", "Viabilidad de instalaciones", "Estudio de normativa sectorial", "Informe de adecuación"],
  },
  {
    tag: "Proyectos",
    title: "Proyectos de ingeniería",
    desc: "Desarrollo completo de proyectos técnicos desde la definición hasta la documentación final lista para tramitación.",
    items: ["Memoria técnica y planos", "Cálculos justificativos", "Documentación para licencias", "Seguimiento de ejecución"],
  },
  {
    tag: "Informes",
    title: "Informes y peritaciones",
    desc: "Elaboración de informes técnicos y peritaciones industriales con respaldo normativo y firma de técnico competente.",
    items: ["Peritaciones industriales", "Informes de daños", "Certificados técnicos", "Valoraciones"],
  },
];
</script>

<style scoped>
.service-tag {
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
.service-list {
  margin-top: 16px;
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.service-list li {
  padding-left: 16px;
  position: relative;
  font-size: 0.9375rem;
  color: var(--text-muted);
}
.service-list li::before {
  content: "·";
  position: absolute;
  left: 4px;
  color: var(--steel);
  font-weight: 700;
}
</style>
