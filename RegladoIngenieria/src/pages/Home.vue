<template>
  <main>
    <!-- Hero -->
    <section class="hero section">
      <div class="container">
        <span class="badge">Ingeniería Industrial</span>
        <h1 class="h1 hero-title">Soluciones técnicas<br><span class="accent">para la industria</span></h1>
        <p class="lead hero-lead">Consultoría especializada en ingeniería industrial. Análisis técnicos precisos, proyectos a medida y soluciones para empresas del sector.</p>
        <div class="hero-cta">
          <router-link to="/servicios" class="btn outline">Ver servicios</router-link>
        </div>
      </div>
    </section>

    <!-- Servicios destacados -->
    <section class="section-sm bg-soft">
      <div class="container">
        <h2 class="h2 section-title">Nuestros servicios</h2>
        <div class="grid-3">
          <div class="card" v-for="s in services" :key="s.title">
            <div class="service-icon">{{ s.icon }}</div>
            <h3 class="h3">{{ s.title }}</h3>
            <p class="text-muted" style="margin-top:8px">{{ s.desc }}</p>
          </div>
        </div>
        <div style="margin-top:32px; text-align:center">
          <router-link to="/servicios" class="btn outline">Ver todos los servicios</router-link>
        </div>
      </div>
    </section>

    <!-- Por qué nosotros -->
    <section class="section">
      <div class="container">
        <div class="why-grid">
          <div class="why-text">
            <h2 class="h2">Rigor técnico en cada proyecto</h2>
            <p class="lead" style="margin-top:16px">Combinamos experiencia en ingeniería industrial con metodología estructurada para ofrecer resultados verificables y documentados.</p>
            <ul class="why-list">
              <li v-for="p in points" :key="p">{{ p }}</li>
            </ul>
            <router-link to="/nosotros" class="btn outline" style="margin-top:32px">Conocer el equipo</router-link>
          </div>
          <div class="why-stats">
            <div class="stat-card card" v-for="s in stats" :key="s.label">
              <div class="stat-value">{{ s.value }}</div>
              <div class="stat-label text-muted">{{ s.label }}</div>
            </div>
          </div>
        </div>
      </div>
    </section>

  </main>
</template>

<script setup>
const services = [
  { icon: "⚙", title: "Consultoría técnica", desc: "Asesoramiento especializado en procesos industriales y optimización de instalaciones." },
  { icon: "📋", title: "Análisis de parcelas", desc: "Estudios técnicos de parcelas industriales para instalaciones, usos y normativa vigente." },
  { icon: "📐", title: "Proyectos de ingeniería", desc: "Desarrollo de proyectos técnicos adaptados a los requisitos y normativa del cliente." },
];

const points = [
  "Informes técnicos con respaldo normativo",
  "Equipo con experiencia en industria real",
  "Plazos y entregables claros desde el inicio",
  "Comunicación directa con el técnico responsable",
];

const stats = [
  { value: "+50", label: "Proyectos realizados" },
  { value: "+10", label: "Años de experiencia" },
  { value: "100%", label: "Documentación técnica" },
];
</script>

<style scoped>
.hero { background: linear-gradient(135deg, #f8faff 0%, #ffffff 60%); }
.hero-title { margin-top: 16px; max-width: 700px; }
.hero-lead { max-width: 600px; margin-top: 20px; }
.hero-cta { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 32px; }
.service-icon { font-size: 1.75rem; margin-bottom: 12px; }
.why-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 64px; align-items: center; }
.why-list { margin-top: 24px; list-style: none; display: flex; flex-direction: column; gap: 10px; }
.why-list li { padding-left: 20px; position: relative; color: var(--text-muted); }
.why-list li::before { content: "—"; position: absolute; left: 0; color: var(--steel); }
.why-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.stat-card { text-align: center; padding: 24px; }
.stat-value { font-size: 2rem; font-weight: 700; color: var(--steel); }
.stat-label { margin-top: 4px; font-size: 0.875rem; }
.cta-box { text-align: center; max-width: 640px; margin: 0 auto; }

@media (max-width: 768px) {
  .why-grid { grid-template-columns: 1fr; gap: 40px; }
  .why-stats { grid-template-columns: repeat(3, 1fr); }
}
</style>
