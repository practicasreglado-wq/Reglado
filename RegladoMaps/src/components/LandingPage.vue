<!--
  Módulo LandingPage (Héroe y Accesos Directos)
  Actúa como la puerta de entrada visual (Welcome Screen) aislando las variables de carga 
  inicial y la reproducción de vídeo de las capas densas de información.
  Despacha eventos de scroll (`scrollTo`) hacia el nodo padre para centralizar la 
  mecánica de navegación y ahorrar redundancia de código en los anclajes directos.
-->
<template>
  <div class="landing-page" :class="{ visible: showContent }">
      <h1 class="titulo">{{ msg }}</h1>
    <p class="descripcion">Descubre todas las plantas, parques y estaciones de energía renovable a través de nuestros datos interactivos.</p>
    <button class="landing-button" @click="irAlMapa">Abrir mapa interactivo</button>

    <div class="nav-anchors">
      <button 
        v-for="(item, index) in energyTypes" 
        :key="item.id" 
        @click="$emit('scrollTo', item.id)" 
        :class="['anchor-card', `card-${item.id}`]" 
        :style="{ animationDelay: `${index * 150}ms` }"
      >
        <!-- Decoración trasera de viento para Hidrógeno -->
        <svg v-if="item.id === 'hidrogeno'" class="decor-viento" viewBox="0 0 120 120" preserveAspectRatio="none">
          <path d="M-20,35 Q30,20 62,35 T140,35" stroke="rgba(0, 229, 255, 0)" stroke-width="2" fill="none" />
          <path d="M-20,62 Q35,80 70,62 T140,62" stroke="rgba(0, 229, 255, 0)" stroke-width="2" fill="none" />
          <path d="M-20,89 Q30,74 62,89 T140,89" stroke="rgba(0, 229, 255, 0)" stroke-width="2" fill="none" />
        </svg>

        <!-- Decoración trasera de hojas para Biometano -->
        <svg v-if="item.id === 'biometano'" class="decor-hojas" viewBox="0 0 100 100" preserveAspectRatio="none">
          <path d="M 0,5 Q 6,0 12,5 Q 6,10 0,5 Z" class="hoja h1" />
          <path d="M 0,4 Q 5,0 10,4 Q 5,8 0,4 Z" class="hoja h2" />
          <path d="M 0,5 Q 6,0 12,5 Q 6,10 0,5 Z" class="hoja h3" />
          <path d="M 0,6 Q 7,0 14,6 Q 7,12 0,6 Z" class="hoja h4" />
          <path d="M 0,4 Q 5,0 10,4 Q 5,8 0,4 Z" class="hoja h5" />
          <path d="M 0,5 Q 6,0 12,5 Q 6,10 0,5 Z" class="hoja h6" />
        </svg>

        <!-- Decoración trasera de olas para Hidráulica -->
        <svg v-if="item.id === 'hidraulica'" class="decor-olas" viewBox="0 0 100 100" preserveAspectRatio="none">
          <defs>
            <linearGradient id="aguaGrad1" x1="0%" y1="0%" x2="0%" y2="100%">
              <stop offset="0%" stop-color="#ffffff" stop-opacity="0.95" />
              <stop offset="12%" stop-color="#00E5FF" stop-opacity="0.8" />
              <stop offset="100%" stop-color="#0083B0" stop-opacity="0.6" />
            </linearGradient>
            <linearGradient id="aguaGrad2" x1="0%" y1="0%" x2="0%" y2="100%">
              <stop offset="0%" stop-color="#ffffff" stop-opacity="0.6" />
              <stop offset="15%" stop-color="#00B0FF" stop-opacity="0.6" />
              <stop offset="100%" stop-color="#005691" stop-opacity="0.4" />
            </linearGradient>
          </defs>
          <path d="M 0,50 Q 25,43 50,50 T 100,50 T 150,50 T 200,50 L 200,100 L 0,100 Z" class="ola o1" fill="url(#aguaGrad1)" />
          <path d="M 0,53 Q 25,59 50,53 T 100,53 T 150,53 T 200,53 L 200,100 L 0,100 Z" class="ola o2" fill="url(#aguaGrad2)" />
    </svg>
    
        <!-- (Fondo líquido se gestiona con gradient CSS en hover) -->
        <div class="card-emoji">
          <!-- 🌬️ Eólica: Molino Tradicional -->
          <svg v-if="item.id === 'eolica'" viewBox="0 0 24 24" width="2.5rem" height="2.5rem" fill="none" class="svg-eolica">
            <defs>
              <linearGradient id="towerGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                <stop offset="0%" stop-color="#CBD5E1"/>
                <stop offset="40%" stop-color="#FFFFFF"/>
                <stop offset="100%" stop-color="#E2E8F0"/>
              </linearGradient>
              <linearGradient id="bladeGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#FFFFFF"/>
                <stop offset="100%" stop-color="#CBD5E1"/>
              </linearGradient>
            </defs>
            <path d="M8 22h8" stroke="#E2E8F0" stroke-width="2" stroke-linecap="round" opacity="0.4"/>
            <!-- Pilar principal reforzado (más ancho para visibilidad en móvil) -->
            <path d="M10.8 22 L11.5 10 L12.5 10 L13.2 22 Z" fill="url(#towerGrad)" stroke="#FFFFFF" stroke-width="0.2" />
            <rect x="10.8" y="9" width="2.4" height="2" rx="0.4" fill="#FFFFFF" stroke="#E2E8F0" stroke-width="0.5" />
            <g class="molino-aspas">
              <!-- Aspas reforzadas (un poco más anchas) -->
              <path d="M12 10 L12.6 3.5 A 0.6 0.6 0 0 0 11.4 3.5 L12 10 Z" fill="url(#bladeGrad)" stroke="#FFFFFF" stroke-width="0.1" />
              <path d="M12 10 L12.6 3.5 A 0.6 0.6 0 0 0 11.4 3.5 L12 10 Z" fill="url(#bladeGrad)" stroke="#FFFFFF" stroke-width="0.1" transform="rotate(120, 12, 10)" />
              <path d="M12 10 L12.6 3.5 A 0.6 0.6 0 0 0 11.4 3.5 L12 10 Z" fill="url(#bladeGrad)" stroke="#FFFFFF" stroke-width="0.1" transform="rotate(240, 12, 10)" />
            </g>
            <circle cx="12" cy="10" r="1.5" fill="#FFFFFF" stroke="#E2E8F0" stroke-width="0.8" />
          </svg>

          <!-- 🌀 Hidrógeno: Solo H -->
          <svg v-else-if="item.id === 'hidrogeno'" viewBox="0 0 24 24" width="2rem" height="2rem" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" class="svg-hidrogeno">
            <path d="M7 4v16M17 4v16M7 12h10" />
          </svg>

          <template v-else>{{ item.emoji }}</template>
        </div>
        <span class="card-text">{{ item.label }}</span>
      </button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'LandingPage',
  props: {
    msg: String
  },
  data() {
    return {
      showContent: false,
      energyTypes: [
        { id: 'eolica', emoji: '🌪️', label: 'Eólica' },
        { id: 'solar', emoji: '☀️', label: 'Solar' },
        { id: 'hidrogeno', emoji: '🔋', label: 'Hidrógeno' },
        { id: 'biometano', emoji: '🌿', label: 'Biometano' },
        { id: 'biodiesel', emoji: '⛽', label: 'Biodiésel' },
        { id: 'hidraulica', emoji: '💧', label: 'Hidráulica' }
      ]
    }
  },
  mounted() {
    setTimeout(() => {
      this.showContent = true
    }, 300)
  },
  methods: {
    irAlMapa() {
      this.$router.push('/mapa');
    }
  }
}
</script>

<style>
/* Estilos originales de LandingPage de vuelta */
.landing-page { min-height: 100vh; height: auto; display: flex; flex-direction: column; justify-content: flex-start; align-items: center; text-align: center; padding: 1.25rem; padding-top: 12.5rem; box-sizing: border-box; opacity: 0; transform: translateY(0.625rem); transition: opacity 0.8s ease, transform 0.8s ease; }
.landing-page.visible { opacity: 1; transform: translateY(0); }
.titulo { font-size: 3.75rem; margin-bottom: 0.5rem; }
.descripcion { font-size: 1.25rem; margin-top: 0.5rem; }
.landing-button { margin-top: 1.875rem; padding: 1rem 2.25rem; font-size: 1.125rem; font-weight: 600; border: none; border-radius: 0.625rem; background-color: #00A86B; color: white; cursor: pointer; box-shadow: 0 0.25rem 0.9375rem rgba(0, 168, 107, 0.3); transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.landing-button:hover { transform: translateY(-4px); background-color: #00c67d; box-shadow: 0 0.625rem 1.25rem rgba(0, 168, 107, 0.5); }
.nav-anchors { margin-top: 3.75rem; display: grid; grid-template-columns: repeat(6, 1fr); gap: 1.25rem; width: 100%; max-width: 68.75rem; }
.anchor-card { position: relative; overflow: hidden; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1.5rem 0.625rem; background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.4); border-radius: 1rem; backdrop-filter: blur(10px); cursor: pointer; opacity: 0; transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94), border-color 0.3s ease, box-shadow 0.3s ease, background 0.3s ease; z-index: 1; }

.anchor-card:hover { transform: translateY(-0.75rem) scale(1.05); background: rgba(255, 255, 255, 0.12); border-color: rgba(255, 255, 255, 0.9) !important; box-shadow: 0 1rem 1.875rem rgba(0, 0, 0, 0.3), 0 0 30px rgba(255, 255, 255, 0.35) !important; }
.card-eolica { border-color: rgba(98, 205, 255, 0.5) !important; }
.card-solar { border-color: rgba(253, 184, 19, 0.5) !important; }
.card-hidrogeno { border-color: rgba(0, 150, 220, 0.5) !important; }
.card-biometano { border-color: rgba(0, 180, 100, 0.5) !important; }
.card-biodiesel { border-color: rgba(120, 160, 20, 0.5) !important; }
.card-hidraulica { border-color: rgba(0, 170, 255, 0.5) !important; }

.card-eolica:hover { background: linear-gradient(135deg, rgba(98, 205, 255, 0.9), rgba(98, 205, 255, 0.7)) !important; border-color: rgb(98, 205, 255) !important; box-shadow: 0 0px 10px rgba(98,205,255,0.4), 0 0px 30px rgba(98,205,255,0.9), 0 0px 60px rgba(98,205,255,0.7), inset 0 0px 20px rgba(98,205,255,0.8) !important; }
.card-solar:hover { background: linear-gradient(135deg, rgba(253, 184, 19, 0.9), rgba(253, 184, 19, 0.7)) !important; border-color: rgb(253, 184, 19) !important; box-shadow: 0 0px 10px rgba(253,184,19,0.4), 0 0px 30px rgba(253,184,19,0.9), 0 0px 60px rgba(253,184,19,0.7), inset 0 0px 20px rgba(253,184,19,0.8) !important; }
.card-hidrogeno:hover { background: linear-gradient(135deg, rgba(0, 150, 220, 0.9), rgba(0, 150, 220, 0.7)) !important; border-color: rgb(0, 150, 220) !important; box-shadow: 0 0px 10px rgba(0,150,220,0.4), 0 0px 30px rgba(0,150,220,0.9), 0 0px 60px rgba(0,150,220,0.7), inset 0 0px 20px rgba(0,150,220,0.8) !important; }
.card-biometano:hover { background: linear-gradient(135deg, rgba(0, 180, 100, 0.9), rgba(0, 180, 100, 0.7)) !important; border-color: rgb(0, 180, 100) !important; box-shadow: 0 0px 10px rgba(0,180,100,0.4), 0 0px 30px rgba(0,180,100,0.9), 0 0px 60px rgba(0,180,100,0.7), inset 0 0px 20px rgba(0,180,100,0.8) !important; }
.card-biodiesel {
  position: relative;
  z-index: 1;
}

.card-biodiesel::before {
  content: '';
  position: absolute;
  top: 0; left: 0; width: 100%; height: 100%;
                     background: repeating-linear-gradient(to bottom, 
    #1A3E11 0px, 
    #265418 40px, 
    #33691E 80px, 
    #447A26 120px, 
    #558B2F 160px, 
    #447A26 200px, 
    #33691E 240px, 
    #265418 280px, 
    #1A3E11 320px);
  background-size: 100% 320px;
  opacity: 0;
  transition: opacity 0.4s ease;
  z-index: -1;
  border-radius: inherit;
}

.card-biodiesel:hover::before {
  opacity: 1;
  animation: fluir-boton 5s linear infinite;
}

.card-biodiesel:hover { 
  background: transparent !important; 
  border-color: #8BC34A !important; 
  box-shadow: 0 0px 20px rgba(139,195,74,0.6), 0 0px 40px rgba(139,195,74,0.8), inset 0 0px 15px rgba(139,195,74,0.9) !important; 
}
.card-hidraulica:hover { background: linear-gradient(135deg, rgba(0, 170, 255, 0.9), rgba(0, 170, 255, 0.7)) !important; border-color: rgb(0, 170, 255) !important; box-shadow: 0 0px 10px rgba(0,170,255,0.4), 0 0px 30px rgba(0,170,255,0.9), 0 0px 60px rgba(0,170,255,0.7), inset 0 0px 20px rgba(0,170,255,0.8) !important; }
.landing-page.visible .anchor-card { animation: cardEntrance 0.8s ease forwards; }
.anchor-card:hover .card-emoji { transform: scale(1.3); }
.card-emoji { font-size: 2rem; margin-bottom: 0.5rem; transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); display: flex; align-items: center; justify-content: center; }
.svg-eolica { filter: drop-shadow(0 0 8px rgba(0, 229, 255, 0.3)); transition: all 0.3s ease; }
.svg-hidrogeno { color: #00e5ff; filter: drop-shadow(0 0 6px rgba(0, 229, 255, 0.4)); transition: all 0.3s ease; }

/* 🌟 Animación de Aspas */
.molino-aspas { transform-origin: 12px 10px; animation: desarrolla 4s linear infinite; }
@keyframes desarrolla { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

.anchor-card:hover .svg-hidrogeno {
  filter: none !important;
}

.anchor-card:hover .svg-eolica {
  filter: drop-shadow(0 0 12px rgba(0, 229, 255, 0.8)) !important;
}

.card-solar:hover .card-emoji {
  filter: drop-shadow(0 0 15px rgba(253, 184, 19, 0.95)) !important;
  text-shadow: 0 0 12px #ffffff, 0 0 25px rgba(253, 184, 19, 1) !important;
}

/* Acelerar rotación en Hover */
.anchor-card:hover .molino-aspas {
  animation-duration: 1.5s;
}

/* 🌀 Líneas de Viento Decorativas (Hidrógeno) */
.decor-viento {
  position: absolute;
  top: 0; left: 0; width: 100%; height: 100%;
  pointer-events: none;
  z-index: 0;
  opacity: 0;
  transition: opacity 0.4s ease;
}

.card-hidrogeno:hover .decor-viento { 
  opacity: 1; 
}

.card-hidrogeno:hover .decor-viento path {
  stroke: rgba(255, 255, 255, 0.6);
  stroke-dasharray: 40 40;
  animation: viento-flujo 1.4s linear infinite;
}

.card-hidrogeno:hover .decor-viento path:nth-child(2) {
  animation-duration: 2s;
  animation-delay: -0.3s;
}

/* 🍃 Hojas Orgánicas - Biometano */
.decor-hojas {
  position: absolute;
  top: 0; left: 0; width: 100%; height: 100%;
  pointer-events: none;
  z-index: -1; /* Al fondo, detrás del texto y emoji */
  opacity: 0;
  transition: opacity 0.4s ease;
}

.card-biometano:hover .decor-hojas {
  opacity: 1;
}

.hoja {
  fill: #81C784; /* Verde Natural o Planta */
  opacity: 0; /* Asegura que empieza oculta durante el delay */
  filter: drop-shadow(0 1px 1px rgba(0,0,0,0.12));
}

/* Caída Lenta */
.card-biometano:hover .h1 { animation: caer-h1 3.5s linear infinite; animation-delay: 0s; }
.card-biometano:hover .h2 { animation: caer-h2 4.2s linear infinite; animation-delay: 0.5s; }
.card-biometano:hover .h3 { animation: caer-h3 3.0s linear infinite; animation-delay: 1.1s; }
.card-biometano:hover .h4 { animation: caer-h4 3.8s linear infinite; animation-delay: 1.7s; }
.card-biometano:hover .h5 { animation: caer-h5 3.2s linear infinite; animation-delay: 2.3s; }
.card-biometano:hover .h6 { animation: caer-h6 4.0s linear infinite; animation-delay: 2.8s; }

@keyframes caer-h1 { 0% { transform: translate(15px, -20px) scale(0.6) rotate(45deg); opacity: 0; } 10% { opacity: 0.8; } 90% { opacity: 0.8; } 100% { transform: translate(20px, 120px) scale(1.2) rotate(225deg); opacity: 0; } }
@keyframes caer-h2 { 0% { transform: translate(35px, -20px) scale(0.5) rotate(-20deg); opacity: 0; } 10% { opacity: 0.8; } 90% { opacity: 0.8; } 100% { transform: translate(30px, 120px) scale(1.1) rotate(160deg); opacity: 0; } }
@keyframes caer-h3 { 0% { transform: translate(55px, -20px) scale(0.7) rotate(0deg); opacity: 0; } 10% { opacity: 0.8; } 90% { opacity: 0.8; } 100% { transform: translate(60px, 120px) scale(1.3) rotate(180deg); opacity: 0; } }
@keyframes caer-h4 { 0% { transform: translate(75px, -20px) scale(0.6) rotate(60deg); opacity: 0; } 10% { opacity: 0.8; } 90% { opacity: 0.8; } 100% { transform: translate(70px, 120px) scale(1.1) rotate(240deg); opacity: 0; } }
@keyframes caer-h5 { 0% { transform: translate(90px, -20px) scale(0.4) rotate(-45deg); opacity: 0; } 10% { opacity: 0.8; } 90% { opacity: 0.8; } 100% { transform: translate(85px, 120px) scale(0.9) rotate(135deg); opacity: 0; } }
@keyframes caer-h6 { 0% { transform: translate(25px, -20px) scale(0.6) rotate(30deg); opacity: 0; } 10% { opacity: 0.8; } 90% { opacity: 0.8; } 100% { transform: translate(30px, 120px) scale(1.2) rotate(210deg); opacity: 0; } }

/* 🌊 Olas de Agua - Hidráulica */
.decor-olas {
  position: absolute;
  top: 0; left: 0; width: 100%; height: 100%;
  pointer-events: none;
  z-index: -1; /* Al fondo, detrás del texto y emoji */
  opacity: 0;
  transition: opacity 0.4s ease;
  overflow: hidden;
}

.card-hidraulica:hover .decor-olas {
  opacity: 1;
}

.ola {
  position: absolute;
  bottom: 0;
  height: 100%;
  stroke: #ffffff; /* Borde de espuma brillante */
  stroke-width: 0.8px;
}

.o1 {
  width: 160%;
  animation: olear-css 5s linear infinite;
}

.o2 {
  width: 160%;
  animation: olear-css 7s linear infinite;
  animation-delay: -2s;
}

/* 
  Bucle de desplazamiento continuo sin saltos.
  Se traslada el 50% de la anchura del vector (que contiene exactamente un ciclo de onda) 
  para que el reinicio de fotograma sea invisible al ojo humano.
*/
@keyframes olear-css {
  0% { transform: translateX(-50%); }
  100% { transform: translateX(0%); }
}

@keyframes viento-flujo {
  from { stroke-dashoffset: 80; }
  to { stroke-dashoffset: 0; }
}

/* ⛽ Animación de Flujo (Cascada) para Biodiésel */
@keyframes fluir-boton {
  0% { background-position: 0 0px; }
  100% { background-position: 0 320px; }
}

.card-text { font-size: 0.82rem; font-weight: 700; color: rgba(255, 255, 255, 0.9); text-transform: uppercase; letter-spacing: 0.06rem; transition: color 0.3s ease; }
.anchor-card:hover .card-text { color: #ffffff; }
@keyframes cardEntrance { from { opacity: 0; transform: translateY(0.625rem); } to { opacity: 1; transform: translateY(0); } }
@media (max-width: 64rem) { .nav-anchors { grid-template-columns: repeat(3, 1fr) !important; } }
@media (max-width: 30rem) { .nav-anchors { grid-template-columns: repeat(2, 1fr) !important; } }

@media (max-width: 768px) {
  .landing-page { padding-top: 6rem !important; }
  .titulo { font-size: 2rem !important; }
}

@media (max-width: 360px) {
  .landing-page { padding-top: 7.5rem !important; }
  .titulo { font-size: 1.7rem !important; }
}

@media (max-height: 480px) {
  .landing-page { 
    padding-top: 4rem !important; 
    justify-content: center !important; 
  }
  .titulo { 
    font-size: 1.8rem !important; 
  }
  .nav-anchors {
    margin-top: 1.5rem !important;
  }
}
</style>
