<template>
  <div class="landing-page">
    <!-- Hero (Intacto) -->
    <HeroSection id="hero" />

    <!-- Qué hacemos (Nueva sección, estilo coherente) -->
    <section id="que-hacemos" class="services-section section-alt">
      <div class="container">
        <div class="reveal">
          <div class="label-wrapper">
            <span class="label-line" />
            <span class="label-text">Actividad</span>
          </div>
          <h2 class="section-title mb-6">
            Qué <em class="title-accent accent-gold">hacemos</em>
          </h2>
          <p class="section-intro">
            La actividad de Reglado Bienes Raíces se articula en torno a tres ejes fundamentales de gestión inmobiliaria, aplicando un enfoque técnico y una metodología de análisis rigurosa en cada fase operativa.
          </p>
          <div class="services-grid">
            <div class="service-item reveal">
              <div class="service-icon-wrapper">
                <svg class="service-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path pathLength="1" stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
              </div>
              <h3 class="service-name">Intermediación de activos</h3>
              <p class="service-text">Ejecución de procesos de compra y venta de bienes inmuebles en segmentos residenciales y terciarios.</p>
            </div>
            <div class="service-item reveal reveal-delay-2">
              <div class="service-icon-wrapper">
                <svg class="service-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path pathLength="1" stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
              </div>
              <h3 class="service-name">Análisis de mercado</h3>
              <p class="service-text">Evaluación técnica de activos, incluyendo estudios de viabilidad, proyecciones de rentabilidad y auditoría de mercado.</p>
            </div>
            <div class="service-item reveal reveal-delay-3">
              <div class="service-icon-wrapper">
                <svg class="service-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path pathLength="1" stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
              <h3 class="service-name">Gestión de operaciones</h3>
              <p class="service-text">Supervisión integral de los procedimientos administrativos y contractuales vinculados a la transacción de activos.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Sobre nosotros (Intacto) -->
    <div id="sobre-nosotros">
      <section class="about-header grain-overlay section-border-top">
        <div class="container">
          <div class="label-wrapper">
            <span class="label-line" />
            <span class="label-text">Trayectoria</span>
          </div>
          <h1 class="page-title">
            Sobre <em class="title-accent">nosotros</em>
          </h1>
          <p class="page-description">
            Reglado Bienes Raíces es una entidad constituida para la canalización de operaciones inmobiliarias mediante una consultoría técnica fundamentada en criterios de mercado objetivos.
          </p>
        </div>
      </section>

      <section class="about-content-section section-alt">
        <div class="container grid-layout">
          <div class="text-block reveal">
            <h2 class="section-title">Marco <em class="title-accent accent-gold">operativo</em></h2>
            <p class="content-text">
              La actividad de la firma se rige por el cumplimiento de estándares técnicos y la transparencia en la gestión de datos. El análisis de cada activo se aborda desde una perspectiva multidisciplinar, integrando variables financieras, jurídicas y urbanísticas.
            </p>
            <p class="content-text">
              La estructura organizativa permite la monitorización constante de las carteras de activos, asegurando una respuesta técnica adecuada a la complejidad de las operaciones patrimoniales contemporáneas.
            </p>
          </div>
          <div class="image-block reveal reveal-delay-2">
            <img src="https://images.unsplash.com/photo-1497366216548-37526070297c?w=1200&q=80" alt="Consultoría Técnica" class="about-img" />
          </div>
        </div>
      </section>

      <section class="values-section section-alt">
        <div class="container">
          <div class="values-grid">
            <div 
              v-for="(v, i) in values" 
              :key="v.title" 
              class="value-card reveal" 
              :class="`reveal-delay-${i + 1}`"
            >
              <div class="value-icon-container">
                <svg class="value-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path pathLength="1" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="v.icon" />
                </svg>
              </div>
              <h3 class="value-title">{{ v.title }}</h3>
              <p class="value-text">{{ v.desc }}</p>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { onMounted, nextTick } from 'vue'
import HeroSection from '@/components/HeroSection.vue'

const values = [
  { 
    title: 'Neutralidad', 
    desc: 'Los informes y análisis emitidos se basan estrictamente en datos de mercado contrastables y criterios técnicos.',
    icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
  },
  { 
    title: 'Rigor Técnico', 
    desc: 'Aplicación de metodologías analíticas avanzadas en la valoración y gestión de cada activo inmobiliario.',
    icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'
  },
  { 
    title: 'Estructura', 
    desc: 'Organización operativa diseñada para la gestión eficiente de transacciones de alta complejidad jurídica.',
    icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2'
  }
]

onMounted(async () => {
  await nextTick()
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        // Only trigger the animation ONCE to permanently solve reverse-scroll lag
        if (entry.isIntersecting) {
          entry.target.classList.add('visible')
          observer.unobserve(entry.target) // Disconnect observer immediately to save CPU
        }
      })
    },
    { threshold: 0.1, rootMargin: '0px 0px -40px 0px' }
  )

  document.querySelectorAll('.reveal').forEach((el) => observer.observe(el))
})
</script>

<style scoped>
/* Estilos para la nueva sección Qué Hacemos */
.services-section {
  padding: 7rem 1.5rem;
}

@media (max-width: 768px) {
  .services-section {
    padding: 4rem 1.5rem;
  }
}

.container {
  max-width: 80rem;
  margin: 0 auto;
}

.services-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 3rem;
  margin-top: 3rem;
}

@media (min-width: 640px) {
  .services-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 1024px) {
  .services-grid {
    grid-template-columns: repeat(3, 1fr);
    gap: 4rem;
  }
}

.service-icon-wrapper {
  margin-bottom: 2rem;
  display: flex;
}

.service-item {
  background-color: #ffffff;
  padding: 2.5rem;
  border: 1px solid #eaeaea;
  box-shadow: 5px 6px 15px -2px rgba(0, 0, 0, 0.06); /* Shadow exactly targeted to bottom and right */
  transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
  position: relative;
  overflow: hidden;
}

/* Optional hover interaction for the service cards */
.service-item:hover {
  transform: translate(-3px, -4px); /* Moves slightly up and left to amplify the bottom-right shadow */
  box-shadow: 10px 12px 25px -4px rgba(0, 0, 0, 0.08);
  border-color: #c9a34a;
}

.service-icon {
  width: 2.5rem;
  height: 2.5rem;
  color: #c9a34a; /* Updated to true accent gold */
  opacity: 0;
  transform: translateY(10px);
  transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

.service-item.visible .service-icon {
  opacity: 1;
  transform: translateY(0);
}

.service-icon path {
  stroke-dasharray: 1;
  stroke-dashoffset: 1;
  transition: stroke-dashoffset 2s cubic-bezier(0.65, 0, 0.35, 1);
}

.service-item.visible .service-icon path {
  stroke-dashoffset: 0;
}

/* Delay staggered drawing */
.service-item.reveal-delay-2.visible .service-icon path {
  transition-delay: 0.3s;
}

.service-item.reveal-delay-3.visible .service-icon path {
  transition-delay: 0.6s;
}

.service-name {
  font-family: var(--font-display);
  font-size: 1.5rem;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #e7e5e4;
  color: var(--ink-default);
}

.service-text {
  font-family: var(--font-body);
  font-size: 0.9375rem;
  color: #78716c;
  line-height: 1.6;
}

.section-intro {
  font-family: var(--font-body);
  font-size: 1.125rem;
  color: #57534e;
  max-width: 36rem;
  line-height: 1.6;
  margin-bottom: 2.5rem;
}

@media (max-width: 768px) {
  .section-intro {
    font-size: 1rem;
  }
}

.title-accent {
  font-style: normal;
  color: var(--gold-600);
}

/* Estilos Sobre Nosotros (Copiados exactamente) */
.about-header {
  padding: 8rem 1.5rem 6rem;
  background-color: var(--ink-default);
  color: var(--white);
}

@media (max-width: 768px) {
  .about-header {
    padding: 5rem 1.5rem 4rem;
  }
}

.section-border-top {
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.label-wrapper {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 2rem;
}

.label-line {
  display: block;
  width: 2rem;
  height: 1px;
  background-color: var(--gold-500);
}

.label-text {
  font-family: var(--font-mono);
  font-size: 10px;
  letter-spacing: var(--letter-spacing-widest3);
  text-transform: uppercase;
  color: var(--gold-400);
}

.page-title {
  font-family: var(--font-display);
  font-size: 3rem;
  font-weight: 300;
  margin-bottom: 1.5rem;
  line-height: 1.2;
}

.page-description {
  font-family: var(--font-body);
  font-size: 1.125rem;
  color: rgba(255, 255, 255, 0.6);
  max-width: 36rem;
  line-height: 1.6;
}

.about-content-section {
  padding: 7rem 1.5rem;
}

@media (max-width: 768px) {
  .about-content-section {
    padding: 4rem 1.5rem;
  }
}

.grid-layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: 4rem;
  align-items: center;
}

@media (min-width: 1024px) {
  .grid-layout {
    grid-template-columns: 1fr 1fr;
  }
}

.section-title {
  font-family: var(--font-display);
  font-size: 2.25rem;
  font-weight: 300;
  color: var(--ink-default);
  margin-bottom: 2.5rem;
}

@media (min-width: 768px) {
  .section-title {
    font-size: 3rem;
  }
}

.content-text {
  font-family: var(--font-body);
  font-size: 1rem;
  color: #57534e;
  line-height: 1.7;
  margin-bottom: 1.5rem;
}

.about-img {
  width: 100%;
  aspect-ratio: 4 / 3;
  object-fit: cover;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.values-section {
  padding: 8rem 1.5rem;
}

@media (max-width: 768px) {
  .values-section {
    padding: 4rem 1.5rem;
  }
}

.values-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;
}

@media (min-width: 640px) {
  .values-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 1024px) {
  .values-grid {
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
  }
}

.value-card {
  background-color: #1c1b1a; /* Soft black, slightly lighter than section-dark #111111 */
  padding: 3.5rem 2.5rem;
  border: 1px solid rgba(255, 255, 255, 0.05); /* Premium faint border */
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4); /* Deep dark shadow */
  transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
  display: flex;
  flex-direction: column;
  position: relative;
  overflow: hidden;
}

.value-card::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 6px; /* Increased from 2px for maximum visibility */
  background-color: #c9a34a; /* Accent gold line */
  box-shadow: 0 4px 15px rgba(201, 163, 74, 0.6); /* Gold glow to "make it more noticeable" */
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1); /* Elegant, fast snap */
}

.value-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 20px 40px -15px rgba(201, 163, 74, 0.15); /* Classy gold glow */
}

.value-card:hover::after {
  transform: scaleX(1);
}

.value-icon-container {
  margin-bottom: 2rem;
  color: #c9a34a; /* Designer requested accent gold */
  transition: transform 0.4s ease;
}

.value-card:hover .value-icon-container {
  transform: scale(1.1);
}

.value-icon {
  width: 2.5rem;
  height: 2.5rem;
  opacity: 0;
  transform: translateY(10px);
  transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

.value-card.visible .value-icon {
  opacity: 1;
  transform: translateY(0);
}

.value-icon path {
  stroke-dasharray: 1;
  stroke-dashoffset: 1;
  transition: stroke-dashoffset 2s cubic-bezier(0.65, 0, 0.35, 1);
}

.value-card.visible .value-icon path {
  stroke-dashoffset: 0;
}

/* Staggered drawing for Value Cards */
.reveal-delay-1.visible .value-icon path { transition-delay: 0.1s; }
.reveal-delay-2.visible .value-icon path { transition-delay: 0.4s; }
.reveal-delay-3.visible .value-icon path { transition-delay: 0.7s; }

.value-title {
  font-family: var(--font-display);
  font-size: 1.5rem;
  font-weight: 500;
  margin-bottom: 1.25rem;
  color: #ffffff; /* Strict white targeting for dark section */
}

.value-text {
  font-family: var(--font-body);
  font-size: 0.9375rem;
  color: rgba(255, 255, 255, 0.7); /* Light grey against dark bg */
  line-height: 1.7;
}
</style>
