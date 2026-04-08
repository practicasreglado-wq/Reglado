<!--
  Modulo Principal (App.vue)
  Orquestador centralizado de la aplicación.
  Consolida el Landing completo en una única vista para permitir transiciones de scroll 
  suaves entre secciones y gestionar animaciones de entrada controlando el ciclo de vida 
  del IntersectionObserver en un solo nodo central.
  Dispone capas alternándose condicionalmente mediante v-show para evitar cargas 
  de navegación tradicionales (SPA), garantizando velocidad de respuesta inmediata.
-->
<template>
  <div id="app">
    <LPHeader @scrollToTop="scrollToTop" @scrollTo="scrollToSection" />

    <nav class="side-nav" :class="{ 'side-nav-visible': showBackToTop && !isFooterVisible }" v-show="$route.path === '/'">
      <button v-for="energia in energyTypes" :key="energia.id" 
              class="side-nav-btn" 
              :class="['side-nav-' + energia.id, { 'active': activeSection === energia.id }]" 
              @click="scrollToSection(energia.id)" :aria-label="energia.label">
        <span class="side-emoji" v-html="energia.emoji"></span>
        <span class="side-label">{{ energia.label }}</span>
      </button>
    </nav>

    <div class="pantalla-inicio" v-show="$route.path === '/'">
      <div class="video-container">
        <video ref="video" autoplay muted id="background-video" @ended="restartVideo">
          <source src="/video/video.mp4" type="video/mp4">
        </video>
        <div class="video-overlay"></div>
        <LandingPage msg="El mapa energético de España" @scrollTo="scrollToSection" />
      </div>

      <div class="seccion eolica" ref="eolica">
        <!-- Aerogeneradores de Fondo -->
        <div class="eolica-fondo">
          <svg class="turbina t1" viewBox="0 0 24 24" fill="none">
            <path d="M11.6 24 L11.8 8 L12.2 8 L12.4 24 Z" fill="rgba(255,255,255,0.25)" />
            <circle cx="12" cy="8" r="0.4" fill="rgba(255,255,255,0.3)" />
            <g class="aspas">
              <path d="M12 8 L12.3 2.5 A 0.3 0.3 0 0 0 11.7 2.5 L12 8 Z" fill="rgba(255,255,255,0.22)" />
              <path d="M12 8 L12.3 2.5 A 0.3 0.3 0 0 0 11.7 2.5 L12 8 Z" fill="rgba(255,255,255,0.22)"
                transform="rotate(120, 12, 8)" />
              <path d="M12 8 L12.3 2.5 A 0.3 0.3 0 0 0 11.7 2.5 L12 8 Z" fill="rgba(255,255,255,0.22)"
                transform="rotate(240, 12, 8)" />
            </g>
          </svg>
          <svg class="turbina t2" viewBox="0 0 24 24" fill="none">
            <path d="M11.6 24 L11.8 8 L12.2 8 L12.4 24 Z" fill="rgba(255,255,255,0.16)" />
            <circle cx="12" cy="8" r="0.4" fill="rgba(255,255,255,0.2)" />
            <g class="aspas">
              <path d="M12 8 L12.3 2.5 A 0.3 0.3 0 0 0 11.7 2.5 L12 8 Z" fill="rgba(255,255,255,0.14)" />
              <path d="M12 8 L12.3 2.5 A 0.3 0.3 0 0 0 11.7 2.5 L12 8 Z" fill="rgba(255,255,255,0.14)"
                transform="rotate(120, 12, 8)" />
              <path d="M12 8 L12.3 2.5 A 0.3 0.3 0 0 0 11.7 2.5 L12 8 Z" fill="rgba(255,255,255,0.14)"
                transform="rotate(240, 12, 8)" />
            </g>
          </svg>
          <!-- Tercera Turbina (Gigante Derecha) -->
          <svg class="turbina t3" viewBox="0 0 24 24" fill="none">
            <path d="M11.6 24 L11.8 8 L12.2 8 L12.4 24 Z" fill="rgba(255,255,255,0.16)" />
            <circle cx="12" cy="8" r="0.4" fill="rgba(255,255,255,0.22)" />
            <g class="aspas">
              <path d="M12 8 L12.3 2.5 A 0.3 0.3 0 0 0 11.7 2.5 L12 8 Z" fill="rgba(255,255,255,0.14)" />
              <path d="M12 8 L12.3 2.5 A 0.3 0.3 0 0 0 11.7 2.5 L12 8 Z" fill="rgba(255,255,255,0.14)"
                transform="rotate(120, 12, 8)" />
              <path d="M12 8 L12.3 2.5 A 0.3 0.3 0 0 0 11.7 2.5 L12 8 Z" fill="rgba(255,255,255,0.14)"
                transform="rotate(240, 12, 8)" />
            </g>
          </svg>
        </div>

        <div class="seccion-texto-eolica fade-left">
          <h2 class="titulo-eolica">Parques Eólicos</h2>
          <p class="texto-eolica">La energía eólica transforma la energía cinética del viento en energía eléctrica
            mediante aerogeneradores. Explora el mapa para ver la distribución de todos los parques eólicos operativos
            en el país.</p>
          <button class="btn-ver-mapa btn-ver-eolica" @click="goToMap('eolica')">Ver en el mapa <span
              class="arrow">→</span></button>
        </div>
        <img src="@/assets/eolica.jpg" alt="Energía Eólica" class="imagen-eolica fade-right" />
      </div>

      <div class="seccion solar" ref="solar" @mousemove="handleMouseMove" @mouseenter="handleMouseEnter"
        @mouseleave="handleMouseLeave">
        <img src="@/assets/solar.jpg" alt="Energía Solar" class="imagen-solar fade-left" />
        <div class="seccion-texto-solar fade-right">
          <h2 class="titulo-solar">Instalaciones Fotovoltaicas</h2>
          <p class="texto-solar">La energía solar fotovoltaica y termosolar aprovecha la radiación del sol para generar
            electricidad. Filtra en el mapa interactivo para localizar todas las instalaciones instaladas a lo largo del
            territorio español.</p>
          <button class="btn-ver-mapa btn-ver-solar" @click="goToMap('solar')">Ver en el mapa <span
              class="arrow">→</span></button>
        </div>
      </div>

      <div class="seccion hidrogeno" ref="hidrogeno">
        <!-- Flujo de Gas de Fondo -->
        <svg class="seccion-flujo" viewBox="0 0 120 120" preserveAspectRatio="none">
          <path d="M-20,25 Q30,10 60,25 T140,25" stroke="rgba(0, 229, 255, 0.15)" stroke-width="1.2" fill="none" />
          <path d="M-20,45 Q40,60 80,45 T140,45" stroke="rgba(0, 229, 255, 0.18)" stroke-width="1.3" fill="none" />
          <path d="M-20,65 Q30,50 65,65 T140,65" stroke="rgba(0, 229, 255, 0.12)" stroke-width="1.1" fill="none" />
          <path d="M-20,85 Q35,100 70,85 T140,85" stroke="rgba(0, 229, 255, 0.14)" stroke-width="1.2" fill="none" />
          <path d="M-20,105 Q30,90 60,105 T140,105" stroke="rgba(0, 229, 255, 0.13)" stroke-width="1.1" fill="none" />
        </svg>

        <div class="seccion-texto-hidrogeno fade-left">
          <h2 class="titulo-hidrogeno">Estaciones de Hidrógeno</h2>
          <p class="texto-hidrogeno">Las hidrogeneras utilizan electricidad para extraer el hidrógeno del agua y
            producir este combustible sin emisiones. Encuentra en el mapa todas las estaciones y puntos de suministro
            disponibles a nivel nacional.</p>
          <button class="btn-ver-mapa btn-ver-hidrogeno" @click="goToMap('hidrogeno')">Ver en el mapa <span
              class="arrow">→</span></button>
        </div>
        <img src="@/assets/hidrogeno.jpg" alt="Hidrógeno" class="imagen-hidrogeno fade-right" />
      </div>

      <div class="seccion biometano" ref="biometano" @mousemove="handleMouseMove" @mouseenter="handleMouseEnter"
        @mouseleave="handleMouseLeave">
        <div class="biometano-interactivo"></div>
        <img src="@/assets/biometano.jpg" alt="Biometano" class="imagen-biometano fade-left" />
        <div class="seccion-texto-biometano fade-right">
          <h2 class="titulo-biometano">Centrales de Biometano</h2>
          <p class="texto-biometano">El biometano es un gas renovable producido a partir de la descomposición natural de
            residuos orgánicos. Utiliza el mapa para consultar la ubicación exacta de las centrales de producción
            activas en España.</p>
          <button class="btn-ver-mapa btn-ver-biometano" @click="goToMap('biometano')">Ver en el mapa <span
              class="arrow">→</span></button>
        </div>
      </div>

      <div class="seccion biodiesel" ref="biodiesel">
        <div class="seccion-texto-biodiesel fade-left">
          <h2 class="titulo-biodiesel">Plantas de Biodiésel</h2>
          <p class="texto-biodiesel">El biodiésel es un biocarburante líquido que se obtiene a partir de aceites
            vegetales o grasas animales. Localiza en el mapa la distribución geográfica de cada planta refinadora en
            España.</p>
          <button class="btn-ver-mapa btn-ver-biodiesel" @click="goToMap('biodiesel')">Ver en el mapa <span
              class="arrow">→</span></button>
        </div>
        <img src="@/assets/biodiesel.jpg" alt="Biodiesel" class="imagen-biodiesel fade-right" />
      </div>

      <div class="seccion hidraulica" ref="hidraulica">
        <div class="wave-overlay"></div>
        <div class="glass-texture"></div>

        <!-- Olas Gigantes de Fondo -->
        <svg class="seccion-olas" viewBox="0 0 100 100" preserveAspectRatio="none">
          <defs>
            <linearGradient id="secAgua1" x1="0%" y1="0%" x2="0%" y2="100%">
              <stop offset="0%" stop-color="#ffffff" stop-opacity="0.18" />
              <stop offset="12%" stop-color="#00E5FF" stop-opacity="0.25" />
              <stop offset="100%" stop-color="#002b5e" stop-opacity="0.75" />
            </linearGradient>
            <linearGradient id="secAgua2" x1="0%" y1="0%" x2="0%" y2="100%">
              <stop offset="0%" stop-color="#ffffff" stop-opacity="0.08" />
              <stop offset="15%" stop-color="#00B0FF" stop-opacity="0.18" />
              <stop offset="100%" stop-color="#001a35" stop-opacity="0.65" />
            </linearGradient>
          </defs>
          <path d="M 0,50 Q 25,43 50,50 T 100,50 T 150,50 T 200,50 L 200,100 L 0,100 Z" class="ola-sec s1"
            fill="url(#secAgua1)" />
          <path d="M 0,53 Q 25,59 50,53 T 100,53 T 150,53 T 200,53 L 200,100 L 0,100 Z" class="ola-sec s2"
            fill="url(#secAgua2)" />
        </svg>

        <img src="@/assets/hidraulica.jpg" alt="Energía Hidráulica" class="imagen-hidraulica fade-left" />
        <div class="seccion-texto-hidraulica fade-right">
          <h2 class="titulo-hidraulica">Centrales Hidroeléctricas</h2>
          <p class="texto-hidraulica">La energía hidráulica aprovecha la fuerza y el movimiento natural del agua.
            Selecciona en el mapa para ver la ubicación de la extensa red de centrales hidroeléctricas españolas.</p>
          <button class="btn-ver-mapa btn-ver-hidraulica" @click="goToMap('hidraulica')">Ver en el mapa <span
              class="arrow">→</span></button>
        </div>
      </div>
    </div>

    <router-view v-show="$route.path !== '/'" />
    <LPFooter ref="footer" @scrollTo="scrollToSection" />

    <button class="btn-volver-arriba" :class="{ 'btn-visible': showBackToTop, 'btn-light': isWhiteGlass }"
      @click="scrollToTop" aria-label="Volver arriba">
      <svg viewBox="0 0 24 24" width="24" height="24">
        <path d="M7.41 15.41L12 10.83l4.59 4.58L18 14l-6-6-6 6z" fill="currentColor" />
      </svg>
    </button>
    <CookieBanner />
  </div>
</template>

<script>
/**
 * Componente Raíz de RegladoMaps (App.vue)
 * 
 * Orquesta la estructura principal y la Landing Page interactiva del visor
 * de mapas. Controla las animaciones al hacer scroll mediante IntersectionObserver,
 * la navegación lateral y gatilla la inicialización global del servicio de autenticación.
 */
import LPHeader from './components/LPHeader.vue'
import LandingPage from './components/LandingPage.vue'
import LPFooter from './components/LPFooter.vue'
import CookieBanner from './components/CookieBanner.vue'
import { auth } from './services/auth'

export default {
  name: 'App',
  components: { LPHeader, LandingPage, LPFooter, CookieBanner },
  data() {
    return {
      showBackToTop: false, 
      isWhiteGlass: false,
      isFooterVisible: false,
      activeSection: null,
      energyTypes: [
        { id: 'eolica', emoji: '<svg viewBox="0 0 24 24" width="1.2em" height="1.2em" fill="none" class="svg-eolica" style="vertical-align: middle;"><defs><linearGradient id="towerGrad" x1="0%" y1="0%" x2="100%" y2="0%"><stop offset="0%" stop-color="#CBD5E1"/><stop offset="40%" stop-color="#FFFFFF"/><stop offset="100%" stop-color="#E2E8F0"/></linearGradient><linearGradient id="bladeGrad" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#FFFFFF"/><stop offset="100%" stop-color="#CBD5E1"/></linearGradient></defs><path d="M8 22h8" stroke="#E2E8F0" stroke-width="1.5" stroke-linecap="round" opacity="0.4"/><path d="M11.3 22 L11.7 10 L12.3 10 L12.7 22 Z" fill="url(#towerGrad)" /><rect x="11" y="9.2" width="2" height="1.6" rx="0.4" fill="#FFFFFF" stroke="#E2E8F0" stroke-width="0.3" /><g class="molino-aspas"><path d="M12 10 L12.4 4 A 0.4 0.4 0 0 0 11.6 4 L12 10 Z" fill="url(#bladeGrad)" /><path d="M12 10 L12.4 4 A 0.4 0.4 0 0 0 11.6 4 L12 10 Z" fill="url(#bladeGrad)" transform="rotate(120, 12, 10)" /><path d="M12 10 L12.4 4 A 0.4 0.4 0 0 0 11.6 4 L12 10 Z" fill="url(#bladeGrad)" transform="rotate(240, 12, 10)" /></g><circle cx="12" cy="10" r="1.2" fill="#FFFFFF" stroke="#E2E8F0" stroke-width="0.5" /></svg>', label: 'Eólica' },
        { id: 'solar', emoji: '☀️', label: 'Solar' },
        { id: 'hidrogeno', emoji: '<svg viewBox="0 0 24 24" width="1.2em" height="1.2em" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" class="svg-hidrogeno" style="vertical-align: middle;"><path d="M7 4v16M17 4v16M7 12h10" /></svg>', label: 'Hidrógeno' },
        { id: 'biometano', emoji: '🌿', label: 'Biometano' },
        { id: 'biodiesel', emoji: '⛽', label: 'Biodiésel' },
        { id: 'hidraulica', emoji: '💧', label: 'Hidráulica' }
      ]
    }
  },
  methods: {
    scrollToTop() { window.scrollTo({ top: 0, behavior: 'smooth' }) },
    /*
      Control de contraste para el botón Volver Arriba y Panel Lateral.
      Se realiza un test de colisión de coordenadas (BoundingBox) en lugar de medir scroll manual.
    */
    handleScroll() {
      this.showBackToTop = window.scrollY > 400;
      
      const viewportCenter = window.innerHeight / 2;
      let currentActive = null;
      let anyBrightHovered = false;
      const bY = window.innerHeight - 60; // Altura aproximada del botón Volver Arriba

      this.energyTypes.forEach(en => {
        const el = this.$refs[en.id];
        if (el) {
          const rect = el.getBoundingClientRect();
          // Detectar si el centro del Viewport está dentro de los límites de la sección
          if (rect.top <= viewportCenter && rect.bottom >= viewportCenter) {
            currentActive = en.id;
          }
          // Detectar colisión con el botón "back to top" solo para las zonas de alto brillo (Eolica/Solar)
          if (en.id === 'eolica' || en.id === 'solar') {
             if (bY >= rect.top && bY <= rect.bottom) anyBrightHovered = true;
          }
        }
      });
      
      if (currentActive) {
        this.activeSection = currentActive;
      } else if (window.scrollY < window.innerHeight * 0.3) {
        this.activeSection = null; // Reiniciar si estamos en Landing
      }

      this.isWhiteGlass = !anyBrightHovered;
    },
    /*
      Transición de bucle para evitar el salto de fotograma brusco del vídeo de fondo.
      Aplica un desenfoque temporal mientras se rebobina la reproducción para suavizar el ciclo.
    */
    restartVideo() {
      const video = this.$refs.video;
      if (!video) return;
      video.classList.add("blur-transition");
      setTimeout(() => { if (video) { video.currentTime = 0; video.classList.remove("blur-transition"); video.play(); } }, 500);
    },
    scrollToSection(sectionRef) {
      const el = this.$refs[sectionRef];
      if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    },
    /*
      Alimentación de coordenadas para sombras y luces reactivas (Efecto Lupa/Glow).
      Calcula el offset relativo al contenedor para pasárselo a las variables CSS de alto rendimiento.
    */
    handleMouseMove(e) {
      const rect = e.currentTarget.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;
      e.currentTarget.style.setProperty('--mouse-x', `${x}px`);
      e.currentTarget.style.setProperty('--mouse-y', `${y}px`);
    },
    handleMouseEnter(e) { e.currentTarget.style.setProperty('--mouse-opacity', '1') },
    handleMouseLeave(e) { e.currentTarget.style.setProperty('--mouse-opacity', '0') },
    /**
     * Navega a la vista de mapa filtrando opcionalmente por un tipo de energía.
     * @param {string} [energia] Tipo de energía (eolica, solar, etc.)
     */
    goToMap(energia) { 
      if(energia) {
        this.$router.push({ path: '/mapa', query: { energia } });
      } else {
        this.$router.push('/mapa');
      }
    }
  },
  /*
    Animación de Entrada en Cascada (Fade-in-up) por Sección.
    Utiliza IntersectionObserver centralizado para retrasar la aparición de los elementos 
    hijos y ahorrar cálculo de scroll manual.
  */
  mounted() {
    // Inicialización del estado de autenticación centralizado.
    // Intenta recuperar el token de la cookie compartida 'reglado_auth_token'.
    auth.initialize().catch(err => console.warn('Auth INIT Error:', err));

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.querySelectorAll('.fade-left, .fade-right').forEach((el, i) => {
            setTimeout(() => { el.classList.add(el.classList.contains('fade-left') ? 'fade-left-visible' : 'fade-right-visible') }, i * 200);
          });
        } else {
          entry.target.querySelectorAll('.fade-left, .fade-right').forEach(el => { el.classList.remove('fade-left-visible', 'fade-right-visible') });
        }
      });
    }, { threshold: 0.2 });

    [this.$refs.eolica, this.$refs.solar, this.$refs.hidrogeno, this.$refs.biometano, this.$refs.biodiesel, this.$refs.hidraulica].forEach(el => { if (el) observer.observe(el); });

    // Observer para el footer para ocultar side-nav
    const footerObserver = new IntersectionObserver((entries) => {
      this.isFooterVisible = entries[0].isIntersecting;
    }, { threshold: 0.1 });
    if (this.$refs.footer && this.$refs.footer.$el) {
      footerObserver.observe(this.$refs.footer.$el);
    }

    window.addEventListener('scroll', this.handleScroll);
  },
  beforeUnmount() { window.removeEventListener('scroll', this.handleScroll) }
}
</script>

<style>
/* =========================================
   SIDEBAR FLOTANTE (Navegación Rápida)
   ========================================= */
.side-nav {
  position: fixed;
  left: 0;
  top: 50%;
  transform: translateY(-50%) translateX(-100%);
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  z-index: 100;
  transition: transform 0.4s cubic-bezier(0.25, 0.8, 0.25, 1); /* Ajustado para no sobrepasar ni separarse del borde */
}

.side-nav.side-nav-visible {
  transform: translateY(-50%) translateX(0);
}

.side-nav-btn {
  display: flex;
  align-items: center;
  background: rgba(10, 11, 16, 0.65);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-left: none;
  border-radius: 0 16px 16px 0;
  padding: 0; /* Sin padding general para controlar la cuadratura */
  height: 48px;
  width: 48px; /* Oculta todo, deja un cuadrado en el lateral */
  color: white;
  cursor: pointer;
  transition: width 0.3s ease-in-out, background 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
  overflow: hidden;
  white-space: nowrap;
  box-shadow: 2px 4px 12px rgba(0,0,0,0.15);
}

.side-nav-btn:hover {
  background: rgba(255, 255, 255, 0.15);
  border-color: rgba(255, 255, 255, 0.45);
  width: 175px; /* Expande al hacer hover para revelar texto */
  box-shadow: 4px 6px 16px rgba(0,0,0,0.3);
}

.side-emoji {
  width: 48px;
  height: 48px;
  display: flex;
  justify-content: center;
  align-items: center;
  flex-shrink: 0;
  font-size: 1.25rem;
}

.side-label {
  opacity: 0;
  font-size: 0.95rem;
  font-weight: 500;
  transition: opacity 0.2s ease;
  padding-left: 0.5rem;
}

.side-nav-btn:hover .side-label {
  opacity: 1;
  transition-delay: 0.1s; /* Retrasa la opacidad para que no desborde mientras crece */
}

/* Colores de Fondo Activos (Indicador de Posición) */
.side-nav-eolica.active { background: rgba(98, 205, 255, 0.35); border-color: rgba(98, 205, 255, 0.8); }
.side-nav-solar.active { background: rgba(253, 184, 19, 0.35); border-color: rgba(253, 184, 19, 0.8); }
.side-nav-hidrogeno.active { background: rgba(0, 150, 220, 0.35); border-color: rgba(0, 150, 220, 0.8); }
.side-nav-biometano.active { background: rgba(0, 180, 100, 0.35); border-color: rgba(0, 180, 100, 0.8); }
.side-nav-biodiesel.active { background: rgba(139, 195, 74, 0.35); border-color: rgba(139, 195, 74, 0.8); }
.side-nav-hidraulica.active { background: rgba(0, 170, 255, 0.35); border-color: rgba(0, 170, 255, 0.8); }

@media (max-width: 768px) {
  /* En móviles ocultamos el menú lateral para no invadir el espacio crítico de lectura */
  .side-nav {
    display: none !important;
  }
}

/* 
  Arquitectura REM (Responsive)
  Todo el proyecto utiliza dimensiones basadas en 'rem' (coeficientes del font-size de la raíz). 
  Modificar dinámicamente este base mediante Media Queries escala el diseño completo (márgenes, 
  fuentes, imágenes) de forma elástica y armónica, actuando como un zoom físico controlado.
*/
html {
  font-size: 100%;
  overflow-x: hidden;
}

@media (min-width: 1200px) {
  html {
    font-size: 112.5%;
  }
}

@media (min-width: 1440px) {
  html {
    font-size: 125%;
  }
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

button {
  font-family: inherit;
}

body {
  width: 100%;
  overflow-x: hidden;
}

#app {
  font-family: 'Outfit', sans-serif;
  text-align: center;
  color: #ffffff;
}

.video-container {
  position: relative;
  width: 100%;
  min-height: 100vh;
  height: auto;
  overflow: hidden;
}

#background-video {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  z-index: -2;
  transition: opacity 0.25s ease, filter 0.25s ease;
}

.blur-transition {
  opacity: 0.1;
  filter: blur(0.5rem);
}

.video-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.4);
  z-index: -1;
}

/* 
  Estructura base para las secciones de información.
  `scroll-margin-top` reserva espacio superior para que la navegación por anclas 
  no colisione ni quede oculta detrás de la cabecera fija Glassmorphic.
*/
.seccion {
  min-height: 100vh;
  width: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: center;
  padding: 3.75rem 2.5rem;
  box-sizing: border-box;
  color: white;
  box-shadow: inset 0px 0.9375rem 1.875rem -0.9375rem rgba(0, 0, 0, 0.5), inset 0px -0.9375rem 1.875rem -0.9375rem rgba(0, 0, 0, 0.5);
  position: relative;
  scroll-margin-top: 5.0rem;
}

/* Estilos de secciones con exactitud original */
.hidrogeno {
  background: radial-gradient(ellipse at bottom left, rgba(0, 150, 220, 0.4) 0%, transparent 60%), radial-gradient(ellipse at top right, rgba(0, 90, 160, 0.5) 0%, transparent 70%), linear-gradient(135deg, rgba(0, 70, 120, 0.95) 0%, rgba(0, 40, 80, 0.95) 100%);
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
  gap: 3.75rem;
  padding: 3.75rem 5rem;
}

.imagen-hidrogeno {
  width: 45%;
  max-width: 31.25rem;
  height: 25.625rem;
  object-fit: cover;
  border-radius: 0.75rem;
  box-shadow: 0 0.9375rem 2.1875rem rgba(0, 0, 0, 0.4);
}

.seccion-texto-hidrogeno {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  max-width: 31.25rem;
}

.titulo-hidrogeno {
  color: #ffffff;
  text-align: left;
  font-size: 2.25rem;
  margin-bottom: 1.25rem;
}

.texto-hidrogeno {
  color: rgba(255, 255, 255, 0.9);
  text-align: left;
  font-size: 1.125rem;
  line-height: 1.6;
}

.biodiesel {
  background: repeating-linear-gradient(to bottom, #1A3E11 0px, #265418 150px, #33691E 300px, #447A26 450px, #558B2F 600px, #447A26 750px, #33691E 900px, #265418 1050px, #1A3E11 1200px);
  background-size: 100% 1200px;
  animation: fluir-seccion 20s linear infinite;
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
  gap: 3.75rem;
  padding: 3.75rem 5rem;
}

.imagen-biodiesel {
  width: 45%;
  max-width: 31.25rem;
  height: 25.625rem;
  object-fit: cover;
  border-radius: 0.75rem;
  box-shadow: 0 0.9375rem 2.1875rem rgba(0, 0, 0, 0.4);
}

.seccion-texto-biodiesel {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  max-width: 31.25rem;
}

.titulo-biodiesel {
  color: #ffffff;
  text-align: left;
  font-size: 2.25rem;
  margin-bottom: 1.25rem;
}

.texto-biodiesel {
  color: rgba(255, 255, 255, 0.9);
  text-align: left;
  font-size: 1.125rem;
  line-height: 1.6;
}

.hidraulica {
  background: linear-gradient(135deg, #02386a, #005682, #002e5c, #004a7c, #007bbd);
  background-size: 400% 400%;
  animation: water-glow 12s ease infinite;
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
  gap: 3.75rem;
  padding: 3.75rem 5rem;
}

@keyframes water-glow {
  0% {
    background-position: 0% 50%;
  }

  50% {
    background-position: 100% 50%;
  }

  100% {
    background-position: 0% 50%;
  }
}

.hidraulica .wave-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: radial-gradient(circle at 50% 50%, rgba(0, 170, 255, 0.15) 0%, transparent 80%);
  mix-blend-mode: screen;
  animation: shimmer 8s ease-in-out infinite alternate;
  z-index: 1;
}

.hidraulica .glass-texture {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-image: url('data:image/svg+xml,%3Csvg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"%3E%3Cfilter id="noiseFilter"%3E%3CfeTurbulence type="fractalNoise" baseFrequency="0.015" numOctaves="2" stitchTiles="stitch"/%3E%3C/filter%3E%3Crect width="100%" height="100%" filter="url(%23noiseFilter)"/%3E%3C/svg%3E');
  opacity: 0.08;
  mix-blend-mode: overlay;
  pointer-events: none;
  z-index: 1;
}

@keyframes wave-move {
  0% {
    transform: translateX(0) scaleY(1);
  }

  50% {
    transform: translateX(-25%) scaleY(0.8);
  }

  100% {
    transform: translateX(-50%) scaleY(1);
  }
}

@keyframes shimmer {
  0% {
    opacity: 0.3;
    transform: scale(1);
  }

  100% {
    opacity: 0.6;
    transform: scale(1.1);
  }
}

.imagen-hidraulica {
  width: 45%;
  max-width: 31.25rem;
  height: 25.625rem;
  object-fit: cover;
  border-radius: 0.75rem;
  box-shadow: 0 0.9375rem 2.1875rem rgba(0, 0, 0, 0.4);
  z-index: 1;
}

.seccion-texto-hidraulica {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  max-width: 31.25rem;
  z-index: 1;
}

.titulo-hidraulica {
  color: #ffffff;
  text-align: right;
  font-size: 2.25rem;
  margin-bottom: 1.25rem;
}

.texto-hidraulica {
  color: rgba(255, 255, 255, 0.9);
  text-align: right;
  font-size: 1.125rem;
  line-height: 1.6;
}

.biometano {
  background: radial-gradient(circle at top left, rgba(0, 180, 100, 0.2) 0%, transparent 50%), radial-gradient(circle at bottom right, rgba(0, 150, 70, 0.15) 0%, transparent 50%), linear-gradient(rgba(0, 100, 60, 0.95), rgba(0, 80, 40, 0.95));
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
  gap: 3.75rem;
  padding: 3.75rem 5rem;
  overflow: hidden;
  position: relative;
}

.biometano::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-image: radial-gradient(rgba(255, 255, 255, 0.7) 1.25px, transparent 1px);
  background-size: 1.15rem 1.15rem;
  opacity: 0.12;
  pointer-events: none;
  z-index: 0;
}

.biometano-interactivo {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 1;
  background-image: radial-gradient(rgba(255, 255, 255, 0.75) 2.3px, transparent 1px);
  background-size: 1.32rem 1.32rem;
  /* Dilatación del 14.78% */
  background-position: calc(var(--mouse-x, 0) * -0.1478) calc(var(--mouse-y, 0) * -0.1478);
  opacity: var(--mouse-opacity, 0);
  -webkit-mask-image: radial-gradient(90px circle at var(--mouse-x, 50%) var(--mouse-y, 50%), black 0%, transparent 100%);
  mask-image: radial-gradient(90px circle at var(--mouse-x, 50%) var(--mouse-y, 50%), black 0%, transparent 100%);
  transition: opacity 0.3s ease;
}

/* 🟢 Desactivar brillo interactivo cuando el ratón está sobre el botón para evitar que se trasluzca */
.biometano:has(.btn-ver-mapa:hover) .biometano-interactivo {
  opacity: 0 !important;
}

.imagen-biometano {
  width: 45%;
  max-width: 31.25rem;
  height: 25.625rem;
  object-fit: cover;
  border-radius: 0.75rem;
  box-shadow: 0 0.9375rem 2.1875rem rgba(0, 0, 0, 0.4);
  position: relative;
  z-index: 2;
}

.seccion-texto-biometano {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  max-width: 31.25rem;
  position: relative;
  z-index: 2;
}

.titulo-biometano {
  color: #ffffff;
  text-align: right;
  font-size: 2.25rem;
  margin-bottom: 1.25rem;
}

.texto-biometano {
  color: rgba(255, 255, 255, 0.9);
  text-align: right;
  font-size: 1.125rem;
  line-height: 1.6;
}

.eolica {
  background: linear-gradient(135deg, #62CDFF, #a5e0fc, #3cbbf2, #98ddfc, #78d6ff);
  background-size: 400% 400%;
  animation: water-glow 15s ease infinite;
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
  gap: 3.75rem;
  padding: 3.75rem 5rem;
  position: relative;
  overflow: hidden;
}

.imagen-eolica {
  width: 45%;
  max-width: 31.25rem;
  height: 25.625rem;
  object-fit: cover;
  border-radius: 0.75rem;
  box-shadow: 0 0.9375rem 2.1875rem rgba(0, 0, 0, 0.4);
  z-index: 1;
}

.seccion-texto-eolica {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  max-width: 31.25rem;
  z-index: 1;
}

.titulo-eolica {
  color: #000000;
  text-align: left;
  font-size: 2.25rem;
  font-weight: 700;
  margin-bottom: 1.25rem;
}

.texto-eolica {
  color: #000000;
  text-align: left;
  font-size: 1.125rem;
  font-weight: 500;
  line-height: 1.6;
}

.solar {
  background: radial-gradient(15.625rem circle at var(--mouse-x, 50%) var(--mouse-y, 50%), rgba(255, 255, 255, calc(0.25 * var(--mouse-opacity, 0))), transparent 100%), #FDB813;
  flex-direction: row;
  justify-content: space-between;
  align-items: center;
  gap: 3.75rem;
  padding: 3.75rem 5rem;
  position: relative;
  overflow: hidden;
}

.imagen-solar {
  width: 45%;
  max-width: 31.25rem;
  height: 25.625rem;
  object-fit: cover;
  border-radius: 0.75rem;
  box-shadow: 0 0.9375rem 2.1875rem rgba(0, 0, 0, 0.4);
}

.seccion-texto-solar {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  max-width: 31.25rem;
}

.titulo-solar {
  color: #000000;
  text-align: right;
  font-size: 2.25rem;
  font-weight: 700;
  margin-bottom: 1.25rem;
}

.texto-solar {
  color: #000000;
  text-align: right;
  font-size: 1.125rem;
  font-weight: 500;
  line-height: 1.6;
}

.fade-left {
  opacity: 0;
  transform: translateX(-3.125rem) translateY(1.25rem);
  transition: opacity 0.8s ease, transform 0.8s ease;
}

.fade-right {
  opacity: 0;
  transform: translateX(3.125rem) translateY(1.25rem);
  transition: opacity 0.8s ease, transform 0.8s ease;
}

.fade-left-visible,
.fade-right-visible {
  opacity: 1;
  transform: translateX(0) translateY(0);
}

.btn-ver-mapa {
  display: inline-block;
  margin-top: 1.25rem;
  padding: 0.625rem 1.5rem;
  font-size: 0.9375rem;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.85);
  background: transparent;
  border: 1.5px solid rgba(255, 255, 255, 0.35);
  border-radius: 1.875rem;
  cursor: pointer;
  transition: all 0.3s ease;
  letter-spacing: 0.03125rem;
  box-shadow: 0 0 0.75rem rgba(255, 255, 255, 0.08);
}

.btn-ver-mapa:hover {
  transform: scale(1.02) translateY(-3px);
  color: #fff;
  background: rgba(255, 255, 255, 0.15);
  border-color: rgba(255, 255, 255, 0.85);
  box-shadow: 0 0.25rem 1.25rem rgba(255, 255, 255, 0.2);
}

.btn-ver-mapa .arrow {
  display: inline-block;
  transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  margin-left: 4px;
}

.btn-ver-mapa:hover .arrow {
  transform: translateX(5px);
}

/* Brillos Cromáticos por Energía */
.btn-ver-hidrogeno:hover {
  border-color: #00E5FF;
  box-shadow: 0 0 20px rgba(0, 229, 255, 0.45);
  background: rgba(0, 229, 255, 0.05);
}

.btn-ver-biodiesel:hover {
  border-color: #CDDC39;
  box-shadow: 0 0 20px rgba(205, 220, 57, 0.6);
  background: rgba(205, 220, 57, 0.06);
}

.btn-ver-biometano:hover {
  border-color: #81C784;
  box-shadow: 0 0 20px rgba(129, 199, 132, 0.45);
  background: rgba(129, 199, 132, 0.05);
}

.btn-ver-hidraulica:hover {
  border-color: #00B0FF;
  box-shadow: 0 0 20px rgba(0, 176, 255, 0.45);
  background: rgba(0, 176, 255, 0.05);
}

.btn-ver-eolica:hover {
  border-color: #0288D1;
  box-shadow: 0 0 20px rgba(2, 136, 209, 0.45);
  background: rgba(2, 136, 209, 0.05);
}

.btn-ver-solar:hover {
  border-color: #FFFFFF !important;
  box-shadow: 0 0 20px rgba(255, 255, 255, 0.6) !important;
  background: rgba(255, 255, 255, 0.15) !important;
}

.btn-volver-arriba {
  position: fixed;
  bottom: 2rem;
  right: 18px;
  width: 3.125rem;
  height: 3.125rem;
  border-radius: 50%;
  background: rgba(15, 15, 15, 0.75);
  border: 1.5px solid rgba(255, 255, 255, 0.25);
  color: white;
  display: flex;
  justify-content: center;
  align-items: center;
  cursor: pointer;
  z-index: 9999;
  backdrop-filter: blur(10px);
  opacity: 0;
  transform: translateY(20px);
  transition: all 0.3s ease, transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
}

.btn-volver-arriba.btn-visible {
  opacity: 1;
  transform: translateY(0);
}

.btn-volver-arriba:hover {
  background: rgba(0, 0, 0, 0.85);
  border-color: rgba(255, 255, 255, 0.9);
  transform: translateY(-3px) scale(1.08);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.5), 0 0 15px rgba(255, 255, 255, 0.3) !important;
}

/* ❄️ Clase para cuando está SOBRE el Footer */
.btn-volver-arriba.btn-light {
  background: rgba(255, 255, 255, 0.85);
  color: #111111;
  border-color: rgba(255, 255, 255, 0.6);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
}

.btn-volver-arriba.btn-light:hover {
  background: rgba(255, 255, 255, 0.95);
  color: #000000;
}

@media (max-width: 1024px) {
  .seccion {
    flex-direction: column !important;
    padding: 3.5rem 1.5rem !important;
    gap: 2rem !important;
    min-height: auto !important;
  }

  .solar,
  .biometano,
  .hidraulica {
    flex-direction: column-reverse !important;
  }

  .imagen-eolica,
  .imagen-solar,
  .imagen-hidrogeno,
  .imagen-biometano,
  .imagen-biodiesel,
  .imagen-hidraulica {
    width: 90% !important;
    max-width: 25rem !important;
    height: auto !important;
  }

  .seccion-texto-eolica,
  .seccion-texto-solar,
  .seccion-texto-hidrogeno,
  .seccion-texto-biometano,
  .seccion-texto-biodiesel,
  .seccion-texto-hidraulica {
    max-width: 100% !important;
    align-items: center !important;
    text-align: center !important;
  }

  .titulo-eolica,
  .titulo-solar {
    color: #000000 !important;
  }

  .titulo-eolica,
  .titulo-solar,
  .titulo-hidrogeno,
  .titulo-biometano,
  .titulo-biodiesel,
  .titulo-hidraulica,
  .texto-eolica,
  .texto-solar,
  .texto-hidrogeno,
  .texto-biometano,
  .texto-biodiesel,
  .texto-hidraulica {
    text-align: center !important;
  }
}

.eolica .btn-ver-mapa,
.solar .btn-ver-mapa {
  color: #000000 !important;
  border-color: rgba(0, 0, 0, 0.3) !important;
}

.eolica .btn-ver-mapa:hover,
.solar .btn-ver-mapa:hover {
  background: rgba(0, 0, 0, 0.04) !important;
  color: #000000 !important;
  border-color: #000000 !important;
  border-width: 2px !important;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
}

/* 🌊 Olas Gigantes para el Fondo de Sección - Hidráulica */
.seccion-olas {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 0;
  overflow: hidden;
}

.ola-sec {
  position: absolute;
  bottom: 0;
  height: 100%;
  stroke: rgba(255, 255, 255, 0.2);
  stroke-width: 0.4px;
}

.s1 {
  width: 160%;
  animation: olear-sec 12s ease-in-out infinite alternate;
}

.s2 {
  width: 160%;
  animation: olear-sec 18s ease-in-out infinite alternate-reverse;
}

@keyframes olear-sec {
  0% {
    transform: translateX(0);
  }

  100% {
    transform: translateX(-37.5%);
  }
}

/* ⚛️ Flujo de Gas para Fondo de Sección - Hidrógeno */
.seccion-flujo {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 0;
  overflow: hidden;
}

.seccion-flujo path {
  stroke-dasharray: 50 120;
  animation: seco-flujo 7s linear infinite;
}

.seccion-flujo path:nth-child(2) {
  animation-duration: 11s;
  animation-delay: -2s;
}

.seccion-flujo path:nth-child(3) {
  animation-duration: 9s;
  animation-delay: -4s;
}

.seccion-flujo path:nth-child(4) {
  animation-duration: 13s;
  animation-delay: -6s;
}

.seccion-flujo path:nth-child(5) {
  animation-duration: 10s;
  animation-delay: -1s;
}

@keyframes seco-flujo {
  from {
    stroke-dashoffset: 170;
  }

  to {
    stroke-dashoffset: 0;
  }
}

/* 🌬️ Aerogeneradores sutiles para fondo Eólica */
.eolica-fondo {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 0;
  overflow: hidden;
}

.turbina {
  position: absolute;
  overflow: visible;
}

.t1 {
  height: 80%;
  bottom: -5%;
  left: -12%;
}

.t2 {
  height: 60%;
  bottom: 0px;
  right: 55%;
}

.t3 {
  height: 100%;
  bottom: -5%;
  right: 22%;
}

.turbina .aspas {
  transform-origin: 12px 8px;
  animation: girar-molino 18s linear infinite;
}

.t2 .aspas {
  animation-duration: 25s;
  animation-delay: -4s;
}

.t3 .aspas {
  animation-duration: 32s;
  animation-delay: -7s;
}

@keyframes girar-molino {
  from {
    transform: rotate(0deg);
  }

  to {
    transform: rotate(360deg);
  }
}

@keyframes fluir-seccion {
  0% {
    background-position: 0 0px;
  }

  100% {
    background-position: 0 1200px;
  }
}

/* 📱 Adaptabilidad para Secciones en Móvil (Smartphones / Tablets) */
@media (max-width: 768px) {
  .seccion {
    flex-direction: column !important;
    /* Apilado vertical */
    padding: 2.5rem 1.5rem !important;
    /* Reducir márgenes laterales que asfixian el texto */
    justify-content: flex-start !important;
    gap: 2rem !important;
    height: auto !important;
    min-height: auto !important;
  }

  /* 🔄 Invertir orden en secciones con texto primero para que todas muestren la imagen arriba en móvil */
  .eolica,
  .hidrogeno,
  .biodiesel {
    flex-direction: column-reverse !important;
  }

  /* Ajustar Imágenes/Bloques visuales al 100% de ancho */
  .seccion>img,
  .seccion>.parallax-container,
  .seccion>.mapa-wrapper {
    width: 100% !important;
    max-width: 100% !important;
    height: 18rem !important;
    /* Altura controlada para tarjetas móviles */
    object-fit: cover !important;
  }

  /* Ajustar Bloques de Texto al 100% de ancho y centrados */
  .seccion-texto-hidrogeno,
  .seccion-texto-biodiesel,
  .seccion-texto-hidraulica,
  .seccion-texto-biometano,
  .seccion-texto-eolica,
  .seccion>div:last-child {
    max-width: 100% !important;
    width: 100% !important;
    align-items: center !important;
    text-align: center !important;
  }

  /* Forzar alineación tipográfica central para lectura natural en móvil */
  .seccion h1,
  .seccion h2,
  .seccion h3,
  .seccion p,
  .seccion span {
    text-align: center !important;
  }
}
</style>
