<script setup>
import { onMounted, ref } from 'vue'
import gsap from 'gsap'

const heroTitle = ref(null)
const heroSubtitle = ref(null)

onMounted(() => {
  const tl = gsap.timeline()
  
  tl.from(".char", {
    y: 100,
    opacity: 0,
    duration: 1,
    stagger: 0.03,
    ease: "power4.out"
  })
  .from(heroSubtitle.value, {
    y: 20,
    opacity: 0,
    duration: 0.8,
    ease: "power3.out"
  }, "-=0.5")
  .from(".cta-btn", {
    scale: 0.8,
    opacity: 0,
    duration: 0.5,
    ease: "back.out(1.7)"
  }, "-=0.3")
})

const titleText = "Impulsamos tu Influencia"
const words = titleText.split(" ")
</script>

<!-- 
  Descripción: Sección de bienvenida (Hero) de la página principal.
-->
<template>
  <section class="hero">
    <div class="overlay"></div>
    <div class="container hero-content">
      <h1 ref="heroTitle" class="title">
        <span v-for="(word, wIndex) in words" :key="wIndex" class="word">
          <span v-for="(char, cIndex) in word.split('')" :key="cIndex" class="char">
            {{ char }}
          </span>
          <span class="word-space">&nbsp;</span>
        </span>
      </h1>
      <p ref="heroSubtitle" class="subtitle">
        Llevamos tu producción y visibilidad al siguiente nivel con estrategias de captación de élite.
      </p>
      <div class="cta-container">
        <a href="#contacto" class="btn-primary cta-btn">Empieza Ahora</a>
      </div>
    </div>
  </section>
</template>

<style scoped>
.hero {
  height: 100vh;
  width: 100%;
  position: relative;
  background-image: url('../assets/hero_bg.png');
  background-attachment: fixed;
  background-size: cover;
  background-position: center;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  color: var(--white);
  overflow: hidden;
}

.overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(to bottom, rgba(0,0,0,0.4), rgba(0,0,0,0.7));
  z-index: 1;
}

.hero-content {
  position: relative;
  z-index: 2;
  max-width: 900px;
}

.title {
  font-size: clamp(3rem, 8vw, 6rem);
  margin-bottom: 1.5rem;
  color: var(--white);
  overflow: hidden;
  line-height: 1.1;
}

.word {
  display: inline-block;
  white-space: nowrap;
}

.char {
  display: inline-block;
  will-change: transform, opacity;
}

.word-space {
  display: inline-block;
  width: 0.25em;
}

.subtitle {
  font-size: clamp(1.2rem, 3vw, 1.8rem);
  margin-bottom: 2.5rem;
  font-weight: 300;
  color: var(--silver-light);
  max-width: 700px;
  margin-left: auto;
  margin-right: auto;
}

.cta-container {
  margin-top: 2rem;
}

.btn-primary {
  font-size: 1.2rem;
  padding: 1.2rem 3rem;
  box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}

@media (max-width: 768px) {
  .hero {
    background-attachment: scroll; /* Mejos para móviles si el performance es tema */
  }
}
</style>
