<template>
  <section class="hero grain-overlay">
    <!-- Background video with overlay -->
    <div class="hero-bg">
      <video
        autoplay
        muted
        loop
        playsinline
        class="hero-video"
      >
        <source src="@/media/video_home_RBR.mp4" type="video/mp4" />
      </video>
      <div class="hero-overlay-v" />
      <div class="hero-overlay-h" />
    </div>

    <!-- Decorative vertical line -->
    <div class="hero-sidebar">
      <div class="hero-sidebar-line" />
      <span class="hero-sidebar-text">RBR. Capital · Madrid</span>
      <div class="hero-sidebar-line" />
    </div>

    <!-- Content -->
    <div class="hero-container">
      <div class="hero-content">
        <!-- Label -->
        <div class="hero-label-wrapper animate-fade-in">
          <span class="hero-label-line" />
          <span class="hero-label">
            División de activos inmobiliarios
          </span>
        </div>

        <!-- Headline -->
        <div class="hero-title-wrapper animate-fade-up">
          <img src="@/img/RBRaices_Logo.png" alt="RBR" class="hero-logo-img" />
          <h1 class="hero-title">
            Reglado<br />
            <em class="hero-title-accent">Bienes Raíces</em>
          </h1>
        </div>

        <!-- Subheadline -->
        <p class="hero-subtitle animate-fade-up" style="animation-delay:0.15s">
          Entidad dedicada a la compra y venta de activos inmobiliarios, con un enfoque metodológico en el análisis técnico y la gestión de operaciones patrimoniales.
        </p>

        <!-- Info Links -->
        <div class="hero-actions animate-fade-up" style="animation-delay:0.3s">
          <a href="#que-hacemos" class="btn-outline-white hero-btn-icon">
            Áreas de actividad
            <svg class="cta-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8l4 4m0 0l-4 4m4-4H3" />
            </svg>
          </a>
          <a href="#sobre-nosotros" class="hero-btn-primary">
            Estructura corporativa
          </a>
        </div>
      </div>
    </div>

    <!-- Scroll indicator -->
    <div class="hero-scroll animate-fade-in" style="animation-delay:0.6s">
      <span class="hero-scroll-text">Scroll</span>
      <div class="hero-scroll-line">
        <div class="hero-scroll-dot animate-bounce" />
      </div>
    </div>
  </section>
</template>

<script setup>
</script>

<style scoped>
.hero {
  position: relative;
  min-height: 100vh;
  display: flex;
  align-items: center; /* Centered instead of bottom-aligned */
  padding-top: 8rem; /* Avoid overlap with the absolute/fixed navbar */
  padding-bottom: 4rem;
  overflow: hidden; /* Avoid clipping text entirely, but hide background overflow */
}

@media (max-height: 800px) {
  .hero {
    /* If the screen is very short, ensure it becomes scrollable instead of overlapping */
    min-height: -webkit-fill-available;
  }
}

.hero-bg {
  position: absolute;
  inset: 0;
}

.hero-video {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.hero-overlay-v {
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, rgba(17, 16, 16, 0.7) 0%, rgba(17, 16, 16, 0.5) 50%, rgba(17, 16, 16, 0.9) 100%);
}

.hero-overlay-h {
  position: absolute;
  inset: 0;
  background: linear-gradient(to right, rgba(17, 16, 16, 0.4), transparent);
}

.hero-sidebar {
  position: absolute;
  left: 1.5rem;
  top: 0;
  bottom: 0;
  display: none;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 6rem 0;
}

@media (min-width: 1024px) {
  .hero-sidebar {
    left: 1.5rem; /* Moved much closer to the edge */
    display: flex;
  }
}

.hero-sidebar-line {
  width: 1px;
  flex: 1;
  background-color: rgba(255, 255, 255, 0.2);
}

.hero-sidebar-text {
  font-family: var(--font-mono);
  font-size: 10px;
  letter-spacing: var(--letter-spacing-widest3);
  color: rgba(255, 255, 255, 0.3);
  writing-mode: vertical-rl;
  transform: rotate(180deg);
}

.hero-container {
  position: relative;
  padding: 0 1.5rem;
  width: 100%;
}

@media (min-width: 1024px) {
  .hero-container {
    padding-left: 10.5rem; /* Exactly (Sidebar 1.5) + (Gap 1.5) + (Logo ~7.5) */
    padding-right: 3rem;
  }
}

@media (min-width: 1280px) {
  .hero-container {
    padding-left: 13.5rem; /* Exactly (Sidebar 1.5) + (Gap 2) + (Logo ~10) */
  }
}

.hero-content {
  max-width: 48rem;
}

.hero-label-wrapper {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 2rem;
}

.hero-label-line {
  display: block;
  width: 3rem;
  height: 1px;
  background-color: var(--gold-400);
}

.hero-label {
  font-family: var(--font-mono);
  font-size: 10px;
  letter-spacing: var(--letter-spacing-widest3);
  text-transform: uppercase;
  color: var(--gold-400);
}

.hero-title-wrapper {
  position: relative;
  margin-bottom: 2rem;
}

.hero-logo-img {
  position: relative;
  display: block;
  height: 4rem;
  margin-bottom: 2rem;
  filter: brightness(0) invert(1);
  transition: all 0.3s ease;
}

@media (min-width: 768px) {
  .hero-logo-img {
    height: 5.5rem;
  }
}

@media (min-width: 1024px) {
  .hero-logo-img {
    position: absolute;
    right: 100%;
    margin-right: 1.25rem; /* Reduced gap between the logo and "Reglado..." */
    top: 0;
    margin-bottom: 0;
  }
}

@media (min-width: 1280px) {
  .hero-logo-img {
    height: 7rem;
    margin-right: 1.75rem; /* Reduced gap between the logo and "Reglado..." */
  }
}


.hero-title {
  font-family: var(--font-display);
  font-size: clamp(2.5rem, 12vw, 6rem); /* Fluid sizing */
  font-weight: 300;
  color: var(--white);
  line-height: 1.1;
}

@media (max-width: 768px) {
  .hero-title {
    line-height: 1.2;
  }
}
/* Substituted the fixed media queries with the clamp() above, but keeping explicit overrides for extreme cases if needed */
@media (min-width: 1280px) {
  .hero-title {
    font-size: 6rem;
  }
}

.hero-title-accent {
  font-style: normal;
  color: var(--gold-300);
}

.hero-subtitle {
  font-family: var(--font-body);
  font-size: 1rem;
  color: rgba(255, 255, 255, 0.65);
  max-width: 36rem;
  line-height: 1.6;
  margin-bottom: 3rem;
}

@media (min-width: 768px) {
  .hero-subtitle {
    font-size: 1.125rem;
  }
}

.hero-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 1.5rem;
}

@media (max-width: 480px) {
  .hero-actions {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .hero-actions a {
    width: 100%;
    justify-content: center;
  }
}

.hero-btn-icon {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.cta-icon {
  width: 1rem;
  height: 1rem;
}

.hero-btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background-color: var(--gold-600);
  color: var(--white);
  font-family: var(--font-body);
  font-weight: 500;
  font-size: 0.875rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  padding: 1rem 2rem;
  transition: all 0.3s ease;
  text-decoration: none;
  cursor: pointer;
  border: none;
}

.hero-btn-primary:hover {
  background-color: var(--gold-500);
}


.hero-scroll {
  position: absolute;
  bottom: 2rem;
  right: 3rem;
  display: none;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
}

@media (min-width: 1024px) {
  .hero-scroll {
    display: flex;
  }
}

.hero-scroll-text {
  font-family: var(--font-mono);
  font-size: 10px;
  letter-spacing: var(--letter-spacing-widest3);
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.4);
  writing-mode: vertical-rl;
}

.hero-scroll-line {
  width: 1px;
  height: 3rem;
  background-color: rgba(255, 255, 255, 0.2);
  position: relative;
  overflow: hidden;
}

.hero-scroll-dot {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 50%;
  background-color: var(--gold-400);
}
</style>
