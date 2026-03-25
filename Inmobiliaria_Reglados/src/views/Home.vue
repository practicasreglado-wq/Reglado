<template>
  <section class="hero grain-overlay">
    <!-- Background video with enhanced overlay -->
    <div class="hero-bg">
      <video class="hero-video" autoplay loop muted playsinline>
        <source src="@/assets/video_home.mp4" type="video/mp4">
      </video>
      <div class="hero-overlay-v"></div>
      <div class="hero-overlay-h"></div>
    </div>

    <!-- Structural Sidebar -->
    <div class="hero-sidebar">
      <div class="hero-sidebar-line"></div>
      <span class="hero-sidebar-text">Reglado . Real State</span>
      <div class="hero-sidebar-line"></div>
    </div>

    <!-- Hero Content Container -->
    <div class="hero-container">
      <div class="hero-content">
        <!-- Label -->
        <div class="hero-label-wrapper animate-in" style="animation-delay: 200ms">
          <span class="hero-label-line"></span>
          <span class="hero-label">La forma más fácil de comprar o vender</span>
        </div>

        <!-- Headline -->
        <div class="hero-title-wrapper animate-in" style="animation-delay: 400ms">
          <h1 class="hero-title">
            Reglado<br />
            <em class="hero-title-accent">Real State</em>
          </h1>
        </div>

        <!-- Subheadline -->
        <p class="hero-subtitle animate-in" style="animation-delay: 600ms">
          Encuentra el inmueble que se adapta a tus necesidades.
          Registra tu búsqueda, explora nuestras opciones y recibe alertas cuando lleguen nuevas oportunidades.
        </p>

        <!-- Premium Actions -->
        <div class="hero-actions animate-in" style="animation-delay: 800ms">
          <router-link v-if="userStore.isLoggedIn" to="/dashboard" class="hero-btn-primary">
            Explorar Inmuebles
          </router-link>
          <router-link v-else to="/login" class="hero-btn-primary">
            Iniciar sesión
          </router-link>
          
          <a href="#about-us" class="hero-btn-outline">
            Quiénes somos
            <svg class="cta-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8l4 4m0 0l-4 4m4-4H3" />
            </svg>
          </a>
        </div>
      </div>
    </div>

    <!-- Scroll Indicator -->
    <div class="hero-scroll animate-in" style="animation-delay: 1000ms">
      <span class="hero-scroll-text">Scroll</span>
      <div class="hero-scroll-line">
        <div class="hero-scroll-dot"></div>
      </div>
    </div>
  </section>

  <div class="somos">
    <AboutUs />
  </div>
</template>

<script>
import AboutUs from "../views/AboutUs.vue";
import { useUserStore } from "../stores/user";
export default {
  name: "Home",
  components: {
    AboutUs
  },
  setup() {
    const userStore = useUserStore();
    return { userStore };
  }
};
</script>

<style scoped>

.hero {
  position: relative;
  min-height: 100vh;
  display: flex;
  /* Removed align-items: center to let container padding handle verticality */
  overflow: hidden;
  background-color: #0b0c10;
}

/* Grain Overlay Effect - Hidden on mobile for cleaner look */
.hero::before {
  content: "";
  position: absolute;
  inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
  opacity: 0.15;
  mix-blend-mode: overlay;
  pointer-events: none;
  z-index: 1;
}

@media (max-width: 768px) {
  .hero::before {
    display: none;
  }
}

/* Background & Overlays */
.hero-bg {
  position: absolute;
  inset: 0;
  z-index: 0;
}

.hero-video {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.hero-overlay-v {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to bottom,
    rgba(11, 12, 16, 0.7) 0%,
    rgba(11, 12, 16, 0.4) 50%,
    rgba(11, 12, 16, 0.9) 100%
  );
}

.hero-overlay-h {
  position: absolute;
  inset: 0;
  background: linear-gradient(to right, rgba(11, 12, 16, 0.6), transparent);
}

/* Sidebar Branding */
.hero-sidebar {
  position: absolute;
  left: 2rem;
  top: 0;
  bottom: 0;
  display: none !important; /* Force hide on mobile/tablet */
  flex-direction: column;
  align-items: center;
  align-items: center;
  gap: 1.5rem;
  padding: 8vh 0; /* More room for vertical text */
  z-index: 5;
}

@media (min-width: 1200px) {
  .hero-sidebar {
    display: flex !important;
  }
}

.hero-sidebar-line {
  width: 1px;
  flex: 1;
  background-color: rgba(255, 255, 255, 0.15);
}

.hero-sidebar-text {
  font-size: 10px;
  font-weight: 500;
  letter-spacing: 0.4em;
  color: rgba(255, 255, 255, 0.3);
  writing-mode: vertical-rl;
  text-transform: uppercase;
  transform: rotate(180deg);
}

/* Main Container */
.hero-container {
  position: relative;
  z-index: 10;
  width: 100%;
  padding: 100px var(--spacing-md) 60px; /* Reduced top/bottom to handle short screens better */
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: flex-start; /* Forced left alignment */
  min-height: 100vh;
}

@media (min-width: 1024px) {
  .hero-container {
    padding-left: 10rem; /* Consistent grid with header and sidebar space */
    padding-right: 4rem;
  }
}

.hero-content {
  max-width: 850px;
}

/* Label & Typography */
.hero-label-wrapper {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  margin-bottom: 2.5rem;
}

.hero-label-line {
  display: block;
  width: 3rem;
  height: 1px;
  background-color: #bd9b2c;
}

.hero-label {
  font-size: 11px;
  letter-spacing: 0.3em;
  text-transform: uppercase;
  color: #bd9b2c;
  font-weight: 700;
}

.hero-title {
  font-size: clamp(2.8rem, 10vw, 6rem);
  font-weight: 300;
  color: white;
  line-height: 1.05;
  margin: 0 0 2.5rem 0;
}

.hero-title-accent {
  font-style: italic;
  font-family: inherit;
  color: #bd9b2c;
  display: block;
  margin-top: 0.2em;
}

.hero-subtitle {
  font-size: clamp(1rem, 1.2vw, 1.25rem);
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.6);
  max-width: 38rem;
  margin: 0 0 3.5rem 0;
}

/* Reduced spacing for short screens */
@media (max-height: 650px) {
  .hero-container {
    padding-top: 90px;
    padding-bottom: 30px;
  }
  .hero-label-wrapper {
    margin-bottom: 0.8rem;
  }
  .hero-title {
    margin: 0 0 1rem 0;
    font-size: clamp(2rem, 8vw, 4rem); /* Smaller title on short screens */
  }
  .hero-subtitle {
    margin-bottom: 1rem;
    font-size: 0.95rem;
  }
  .hero-actions {
    gap: 1rem;
  }
}

@media (max-height: 450px) {
  .hero-container {
    padding-top: 80px;
    padding-bottom: 20px;
  }
  .hero-subtitle {
    display: none; /* Hide subtitle on ultra-short screens to keep buttons visible */
  }
}

/* Actions & Buttons */
.hero-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 1.8rem;
}

.hero-btn-primary {
  display: inline-flex;
  align-items: center;
  background: linear-gradient(135deg, #bd9b2c, #9b7e1e);
  color: white;
  padding: 1.1rem 2.8rem;
  border-radius: 4px;
  font-weight: 700;
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  text-decoration: none;
  box-shadow: 0 10px 25px rgba(189, 155, 44, 0.2);
  transition: all 0.4s var(--motion-ease-premium);
}

.hero-btn-primary:hover {
  transform: translateY(-4px);
  box-shadow: 0 15px 35px rgba(189, 155, 44, 0.3);
  filter: brightness(1.1);
}

.hero-btn-outline {
  display: inline-flex;
  align-items: center;
  gap: 0.8rem;
  color: white;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.2);
  padding: 1.1rem 2.5rem;
  border-radius: 4px;
  font-weight: 700;
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  text-decoration: none;
  backdrop-filter: blur(8px);
  transition: all 0.4s var(--motion-ease-premium);
}

.hero-btn-outline:hover {
  background: white;
  color: #1a2545;
  transform: translateY(-4px);
}

.cta-icon {
  width: 1.1rem;
  height: 1.1rem;
  transition: transform 0.3s ease;
}

.hero-btn-outline:hover .cta-icon {
  transform: translateX(5px);
}

/* Scroll Indicator */
.hero-scroll {
  position: absolute;
  bottom: 3rem;
  right: 4rem;
  display: none;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  z-index: 5;
}

@media (min-width: 1024px) {
  .hero-scroll {
    display: flex;
  }
}

.hero-scroll-text {
  font-size: 10px;
  letter-spacing: 0.4em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.4);
  writing-mode: vertical-rl;
}

.hero-scroll-line {
  width: 1px;
  height: 4rem;
  background-color: rgba(255, 255, 255, 0.1);
  position: relative;
  overflow: hidden;
}

.hero-scroll-dot {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 25%;
  background-color: #bd9b2c;
  animation: scrollAnim 2s infinite cubic-bezier(0.77, 0, 0.175, 1);
}

@keyframes scrollAnim {
  0% { transform: translateY(-100%); }
  50% { transform: translateY(100%); }
  100% { transform: translateY(400%); }
}

/* Somos background sync */
.somos {
  background: #e9e9e9;
  padding: 0;
}

/* Responsive Overrides - Compacted for smaller devices */
@media (max-width: 768px) {
  .hero {
    padding-top: 60px; /* Less top space */
  }

  .hero-title {
    font-size: 2.2rem; /* Default mobile size */
    line-height: 1.1;
    margin-bottom: 1.5rem;
  }

  .hero-subtitle {
    font-size: 0.95rem;
    line-height: 1.5;
    margin-bottom: 2rem;
    max-width: 90%;
  }
  
  .hero-actions {
    display: flex;
    flex-direction: column;
    align-items: stretch; /* Full width for very small devices */
    gap: 1.2rem;
  }
  
  /* Incremental scaling for medium mobile/tablets */
  @media (min-width: 481px) {
    .hero-title {
      font-size: 2.8rem; /* Slightly larger */
    }

    .hero-actions {
      flex-direction: row; /* Side by side */
      align-items: center;
      flex-wrap: wrap; /* In case they are too wide */
    }

    .hero-btn-primary, .hero-btn-outline {
      width: auto; /* Not full width */
      flex: 0 1 auto;
    }
  }

  .hero-btn-primary, .hero-btn-outline {
    padding: 0.9rem 1.5rem; /* Compact padding */
    font-size: 0.85rem;
    text-align: center;
    justify-content: center;
  }

  .hero-label-wrapper {
    display: none;
  }
}

/* New Direct Animations */
.animate-in {
  animation: heroReveal 1.2s cubic-bezier(0.22, 1, 0.36, 1) forwards;
  opacity: 0;
  will-change: transform, opacity;
}

@keyframes heroReveal {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

</style>
