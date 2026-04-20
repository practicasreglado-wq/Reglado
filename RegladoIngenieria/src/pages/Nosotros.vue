<template>
  <main>
    <section class="section">
      <div class="container">
        <span class="badge">El equipo</span>
        <h1 class="h1" style="margin-top:16px; max-width:600px">Ingeniería con experiencia industrial real</h1>
        <p class="lead" style="margin-top:16px; max-width:580px">Somos un equipo de ingenieros especializados en el sector industrial. Aportamos rigor técnico y experiencia práctica en cada proyecto que abordamos.</p>
      </div>
    </section>

    <section class="section-sm bg-soft">
      <div class="container">
        <div class="about-grid">
          <div>
            <h2 class="h2">Nuestra misión</h2>
            <p class="text-muted" style="margin-top:16px; line-height:1.8">Ofrecer consultoría de ingeniería industrial con el mismo nivel de rigor que exige la industria: documentación completa, cálculos justificados y cumplimiento normativo. Sin atajos.</p>
            <p class="text-muted" style="margin-top:12px; line-height:1.8">Trabajamos directamente con el técnico responsable de cada proyecto, sin intermediarios, para garantizar la calidad del resultado.</p>
          </div>
          <div>
            <h2 class="h2">Especialidades</h2>
            <div class="spec-list" style="margin-top:20px">
              <div class="spec-item" v-for="s in specialties" :key="s">
                <span class="spec-dot"></span>
                <span>{{ s }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="section-sm">
      <div class="container" style="text-align:center">
        <h2 class="h2">¿Trabajamos juntos?</h2>
        <p class="lead" style="margin-top:12px">Cuéntanos tu proyecto y te damos una respuesta técnica en 48 horas.</p>
        <router-link to="/contacto" class="btn primary" style="margin-top:24px">Contactar</router-link>
      </div>
    </section>
  </main>
</template>

<script setup>
const specialties = [
  "Ingeniería de instalaciones industriales",
  "Análisis y viabilidad de parcelas industriales",
  "Proyectos técnicos y documentación para licencias",
  "Protección contra incendios en industria",
  "Certificación y eficiencia energética",
  "Peritaciones y valoraciones industriales",
];
</script>

<style scoped>
.about-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 64px; }
.spec-list { display: flex; flex-direction: column; gap: 12px; }
.spec-item { display: flex; align-items: flex-start; gap: 12px; font-size: .9375rem; color: var(--text-muted); }
.spec-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--steel); flex-shrink: 0; margin-top: 6px; }

@media (max-width: 768px) {
  .about-grid { grid-template-columns: 1fr; gap: 40px; }
}
</style>
