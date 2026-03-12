
<!-- 
  Sección que enumera los servicios ofrecidos por la agencia.
-->
<template>
  <section id="servicios" class="services">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title">Nuestros Servicios</h2>
        <div class="underline"></div>
        <p class="section-subtitle">Soluciones integrales para maximizar tu impacto digital.</p>
      </div>

      <div class="services-grid">
        <div class="service-card">
          <div class="icon-box">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#4689f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          </div>
          <h3>Captación Estratégica</h3>
          <p>Conectamos tu talento con las marcas líderes del mercado para crear colaboraciones de alto valor.</p>
        </div>

        <div class="service-card featured">
          <div class="icon-box">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#4689f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
          </div>
          <h3>Optimización de Producción</h3>
          <p>Elevamos la calidad técnica y creativa de tu contenido para destacar en un entorno competitivo.</p>
          <div class="silver-badge">Premium</div>
        </div>

        <div class="service-card">
          <div class="icon-box">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#4689f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"></line><line x1="18" y1="20" x2="18" y2="4"></line><line x1="6" y1="20" x2="6" y2="16"></line></svg>
          </div>
          <h3>Crecimiento de Audiencia</h3>
          <p>Implementamos estrategias de visibilidad que transforman visualizaciones en seguidores reales y leales.</p>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.services {
  background-color: var(--white);
}

.section-header {
  text-align: center;
  margin-bottom: 4rem;
}

.section-title {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.underline {
  width: 80px;
  height: 4px;
  background-color: var(--primary);
  margin: 0 auto 1.5rem;
  border-radius: 2px;
}

.section-subtitle {
  color: var(--text-muted);
  font-size: 1.2rem;
}

.services-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2.5rem;
}

.service-card {
  padding: 3rem 2rem;
  background: white;
  border-radius: 24px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.05);
  transition: all 0.4s ease;
  position: relative;
  overflow: hidden;
  border: 1px solid rgba(0,0,0,0.02);
}

.service-card:hover {
  transform: translateY(-10px);
  box-shadow: 0 20px 40px rgba(70, 137, 246, 0.15);
  border-color: var(--primary);
}

.featured {
  border: 2px solid var(--silver);
}

.icon-box {
  margin-bottom: 2rem;
  background: rgba(70, 137, 246, 0.05);
  width: 80px;
  height: 80px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 20px;
}

.service-card h3 {
  font-size: 1.5rem;
  margin-bottom: 1rem;
}

.service-card p {
  color: var(--text-muted);
}

.silver-badge {
  position: absolute;
  top: 20px;
  right: -30px;
  background: linear-gradient(135deg, #d1d1d1, #f0f0f0, #a8a8a8);
  color: #333;
  padding: 5px 40px;
  transform: rotate(45deg);
  font-size: 0.7rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 1px;
}
</style>
