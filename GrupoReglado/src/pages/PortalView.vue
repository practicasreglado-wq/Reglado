<template>
  <div class="landing">
    <section id="inicio" class="block block-hero" :style="heroStyle">
      <video class="hero-video" autoplay muted loop playsinline preload="metadata">
        <source :src="heroVideo" type="video/mp4" />
      </video>
      <div class="hero-overlay"></div>
      <div class="hero-particles"></div>

      <div class="hero-watermark-logo" aria-hidden="true"></div>

      <div class="hero-scroll-indicator" aria-hidden="true">
        <span></span>
      </div>

      <div class="hero-content">
        <p class="hero-kicker">Portal corporativo</p>
        <h1>Reglado Group</h1>

        <p class="hero-subtitle" :aria-label="heroSubtitle">
          <span v-for="(char, index) in heroSubtitleChars" :key="`hero-subtitle-${index}-${char}`" class="hero-char"
            :style="{ animationDelay: `${index * 0.016}s` }">
            {{ char === " " ? "\u00A0" : char }}
          </span>
        </p>

        <button class="hero-cta" type="button" @click="scrollToCompanies">Explorar empresas</button>

      </div>
    </section>

    <section id="grupo" class="block block-group">
      <div class="group-intro">
        <h2>Soluciones empresariales para crecimiento sostenible.</h2>
        <p>
          Reglado Group combina experiencia sectorial, tecnologia y vision de negocio en seis
          areas clave.
        </p>
      </div>

      <ul class="group-icons">
        <li class="group-card" tabindex="0">
          <span class="icon-wrap" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M4 19V10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
              <path d="M10 19V6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
              <path d="M16 19V13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
              <path d="M22 19V3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            </svg>
          </span>
          <strong>Consultoria</strong>
        </li>

        <li class="group-card" tabindex="0">
          <span class="icon-wrap" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M4 20H20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
              <path d="M6 20V10L12 5L18 10V20" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
              <path d="M10 20V14H14V20" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
            </svg>
          </span>
          <strong>Inmuebles</strong>
        </li>

        <li class="group-card" tabindex="0">
          <span class="icon-wrap" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M13 2L5 13H11L10 22L19 10H13L13 2Z" stroke="currentColor" stroke-width="1.8"
                stroke-linejoin="round" />
            </svg>
          </span>
          <strong>Energia</strong>
        </li>

        <li class="group-card" tabindex="0">
          <span class="icon-wrap" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
              <rect x="5" y="5" width="14" height="14" rx="2" stroke="currentColor" stroke-width="1.8" />
              <path d="M9 12H15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
              <path d="M12 9V15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            </svg>
          </span>
          <strong>Tecnologia</strong>
        </li>

        <li class="group-card" tabindex="0">
          <span class="icon-wrap" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M4 18H20M6 15L10 11L13 13L18 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                stroke-linejoin="round" />
              <path d="M14 8H18V12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            </svg>
          </span>
          <strong>Inversiones</strong>
        </li>

        <li class="group-card" tabindex="0">
          <span class="icon-wrap" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M12 3L13.9 8.1L19.4 8.4L15.1 11.8L16.6 17.1L12 14.1L7.4 17.1L8.9 11.8L4.6 8.4L10.1 8.1L12 3Z"
                stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
            </svg>
          </span>
          <strong>Innovacion</strong>
        </li>
      </ul>
    </section>

    <section id="empresas" class="block block-companies">
      <div class="section-head">
        <p class="section-label"></p>
        <h2>Empresas Reglado</h2>
      </div>

      <div class="carousel-wrapper">
        <button class="carousel-btn btn-left" type="button" aria-label="Anterior" @click="scrollCarousel('left')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"></polyline>
          </svg>
        </button>
        <button class="carousel-btn btn-right" type="button" aria-label="Siguiente" @click="scrollCarousel('right')">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="9 18 15 12 9 6"></polyline>
          </svg>
        </button>
        <div class="company-grid" ref="carouselGrid">
        <article v-for="company in companies" :key="company.name" class="company-card">
          <div class="company-image-wrap">
            <img class="company-image" :src="company.image" :alt="company.name" loading="lazy" />
            <div class="company-logo-pill">
              <img :src="company.logo" alt="Logo" />
              <span>{{ company.tag }}</span>
            </div>
          </div>

          <div class="company-content">
            <h3>{{ company.name }}</h3>
            <p>{{ company.description }}</p>
            <a class="company-link" :href="company.href" target="_blank" rel="noreferrer">Entrar</a>
          </div>
        </article>
        </div>
      </div>
    </section>

  </div>
</template>

<script setup>
import { computed, ref } from "vue";
import { auth } from "../services/auth";

import companyEnProceso from "../assets/company-enproceso.png";
import companyProceso from "../assets/company-proceso.png";
import RegladoConsultoresCard from "../assets/RegladoConsultoresCard.png";
import RegladoEnergyCard from "../assets/RegladoEnergyCard.png";
import RegladoMapsCard from "../assets/RegladoMapsCard.png";
import RegladoRealStateCard from "../assets/RegladoRealStateCard.png";
import balanceIcon from "../assets/Balance.svg";
import boltIcon from "../assets/Bolt.svg";
import heroVideo from "../assets/Bissness.mp4";
import mapIcon from "../assets/Map.svg";
import apartmentIcon from "../assets/Apartment.svg";
import addHomeIcon from "../assets/add_home.svg";
import engineeringIcon from "../assets/Enginering.svg";
import corporateLogo from "../assets/reglado-logo.svg";

const heroSubtitle =
  "Grupo empresarial especializado en consultoria, inmuebles, energia, tecnologia, inversiones e innovacion.";

const heroSubtitleChars = computed(() => Array.from(heroSubtitle));

const heroStyle = computed(() => ({
  "--hero-logo": `url('${corporateLogo}')`,
}));

const realstateUrl = import.meta.env.VITE_REGLADO_REALSTATE_URL || "#";
const energyUrl = import.meta.env.VITE_REGLADO_ENERGY_URL || "http://localhost:5174";
const mapasUrl = import.meta.env.VITE_REGLADO_MAPAS_URL || "https://teal-bat-675895.hostingersite.com/";
const mapasEntryUrl = computed(() => buildExternalProductUrl(mapasUrl));
const enProcesoUrl = import.meta.env.VITE_REGLADO_ENPROCESO_URL || "#";
const energyEntryUrl = computed(() => buildExternalProductUrl(energyUrl));

const companies = computed(() => [
  {
    name: "Reglado Abogados",
    tag: "Abogados",
    description: "Consultoria estrategica y legal para operaciones, crecimiento y desarrollo empresarial.",
    href: "https://regladoconsultores.com/",
    image: RegladoConsultoresCard,
    logo: balanceIcon,
  },
  {
    name: "Reglado Energy",
    tag: "Energy",
    description: "Optimizacion energetica, analisis de consumo y gestion de contratos.",
    href: energyEntryUrl.value,
    image: RegladoEnergyCard,
    logo: boltIcon,
  },
  {
    name: "Reglado Real Estate",
    tag: "Real Estate",
    description: "Consultoria estrategica y legal enfocada a operaciones inmobiliarias.",
    href: realstateUrl,
    image: RegladoRealStateCard,
    logo: addHomeIcon,
  },
  {
    name: "Reglado Mapas",
    tag: "Mapas",
    description: "Plataforma geografica y visualizacion avanzada para decisiones de negocio.",
    href: mapasEntryUrl.value,
    image: RegladoMapsCard,
    logo: mapIcon,
  },
  {
    name: "Reglado Ingeniería",
    tag: "Ingeniería",
    description: "Servicios integrales de ingeniería para el desarrollo y optimización de proyectos.",
    href: "#",
    image: companyProceso,
    logo: engineeringIcon,
  },
  {
    name: "Reglado RBR",
    tag: "RBR",
    description: "Servicios especializados de Recuperación de Bienes y Rentas (RBR).",
    href: "#",
    image: companyProceso,
    logo: apartmentIcon,
  },
]);

const carouselGrid = ref(null);

function scrollCarousel(direction) {
  if (!carouselGrid.value) return;
  // Desplazamiento de unos 360px (ancho tarjeta + gap aprox)
  const offset = direction === 'left' ? -380 : 380;
  carouselGrid.value.scrollBy({ left: offset, behavior: 'smooth' });
}

function scrollToCompanies() {
  const target = document.getElementById("empresas");
  if (!target) {
    return;
  }

  target.scrollIntoView({ behavior: "smooth", block: "start" });
}

function buildExternalProductUrl(baseUrl) {
  if (!baseUrl || baseUrl === "#") {
    return "#";
  }

  const cleanBase = String(baseUrl).replace(/\/+$/, "");
  const token = auth.state.token;

  if (!token) {
    return cleanBase;
  }

  return `${cleanBase}/auth/callback?token=${encodeURIComponent(token)}`;
}

</script>

<style scoped>
.landing {
  display: grid;
  gap: 0;
}

.block {
  border-radius: 22px;
  overflow: hidden;
}

.section-label {
  margin: 0;
  color: #3f5b84;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  font-weight: 700;
}

.block-hero {
  width: 100vw;
  margin-left: calc(50% - 50vw);
  margin-right: calc(50% - 50vw);
  margin-top: calc(-1 * var(--content-top-padding) - var(--topbar-height));
  min-height: 100svh;
  position: relative;
  border: 1px solid rgba(255, 255, 255, 0.18);
  border-left: 0;
  border-right: 0;
  border-radius: 0;
  background-color: #1f324c;
  box-shadow: 0 24px 48px rgba(13, 26, 45, 0.25);
  display: grid;
  align-items: end;
}

.hero-video {
  position: absolute;
  inset: -1px;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  pointer-events: none;
  display: block;
}

.hero-overlay {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(circle at 80% 15%, rgba(255, 213, 135, 0.2), transparent 42%),
    linear-gradient(180deg, rgba(8, 17, 30, 0.12), rgba(8, 17, 30, 0.58));
}

.hero-particles {
  position: absolute;
  inset: 0;
  background-image:
    radial-gradient(circle at 12% 20%, rgba(255, 255, 255, 0.2) 1px, transparent 1px),
    radial-gradient(circle at 35% 60%, rgba(163, 197, 255, 0.16) 1px, transparent 1px),
    radial-gradient(circle at 75% 35%, rgba(255, 255, 255, 0.17) 1px, transparent 1px);
  background-size: 260px 240px, 300px 280px, 340px 280px;
  opacity: 0.5;
  animation: particleDrift 18s linear infinite;
}

.hero-watermark-logo {
  position: absolute;
  top: 50%;
  right: clamp(0.1rem, 1.5vw, 1rem);
  transform: translateY(-50%);
  width: min(48vw, 610px);
  aspect-ratio: 1 / 1;
  background: rgba(39, 61, 92, 0.18);
  -webkit-mask-image: var(--hero-logo);
  -webkit-mask-repeat: no-repeat;
  -webkit-mask-position: center;
  -webkit-mask-size: contain;
  mask-image: var(--hero-logo);
  mask-repeat: no-repeat;
  mask-position: center;
  mask-size: contain;
  filter: blur(0.2px);
  pointer-events: none;
}

.hero-content {
  position: relative;
  z-index: 2;
  padding: clamp(1.5rem, 4vw, 3rem);
  max-width: 890px;
}

.hero-kicker {
  margin: 0 0 0.7rem;
  display: inline-block;
  padding: 0.35rem 0.78rem;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.34);
  color: #f3f7ff;
  background: rgba(255, 255, 255, 0.08);
  font-size: 0.84rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.block-hero h1 {
  margin: 0;
  background: linear-gradient(97deg, #ffffff 0%, #d9ebff 30%, #82b4ff 70%, #4f88df 100%);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
  background-size: 150% 150%;
  animation: titleSheen 7s ease-in-out infinite;
  font-size: clamp(2.6rem, 7.4vw, 6rem);
  line-height: 1.02;
  letter-spacing: 0.01em;
  filter: drop-shadow(0 10px 22px rgba(8, 18, 34, 0.42));
  padding-bottom: 0.08em;
}

.hero-subtitle {
  margin: 1rem 0 1.45rem;
  color: rgba(235, 242, 254, 0.96);
  font-size: clamp(1.04rem, 2.2vw, 1.3rem);
  line-height: 1.45;
  max-width: 760px;
  text-shadow: 0 4px 20px rgba(8, 19, 37, 0.45);
}

.hero-char {
  opacity: 0;
  display: inline-block;
  animation: charIn 0.4s ease forwards;
}

.hero-cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.78rem 1.2rem;
  border-radius: 20px;
  text-decoration: none;
  font-weight: 700;
  color: #fff;
  background: #273d5c;
  border: 1px solid rgba(255, 255, 255, 0.38);
  box-shadow: 0 10px 24px rgba(13, 24, 40, 0.34);
  transition: transform 0.2s ease, background 0.2s ease;
  cursor: pointer;
}

.hero-cta:hover {
  transform: translateY(-2px);
  background: #1f324d;
}

.hero-scroll-indicator {
  position: absolute;
  right: clamp(0.35rem, 1.2vw, 1rem);
  bottom: clamp(2rem, 4vw, 3rem);
  width: 26px;
  height: 40px;
  border: 1px solid rgba(255, 255, 255, 0.62);
  border-radius: 999px;
  display: grid;
  justify-items: center;
  align-items: start;
  padding-top: 6px;
}

.hero-scroll-indicator span {
  width: 5px;
  height: 8px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.95);
  animation: scrollDot 1.4s ease-in-out infinite;
}

.block-group {
  width: 100vw;
  margin-left: calc(50% - 50vw);
  margin-right: calc(50% - 50vw);
  position: relative;
  background:
    radial-gradient(circle at 12% 14%, rgba(96, 156, 255, 0.3), transparent 30%),
    linear-gradient(155deg, #16273e 0%, #1f3553 56%, #253f62 100%);
  border: 1px solid rgba(133, 167, 219, 0.24);
  border-left: 0;
  border-right: 0;
  border-radius: 0;
  padding: clamp(1.15rem, 2.8vw, 1.85rem);
  margin-top: 0;
}

.block-companies {
  margin-top: 1.4rem;
}

.block-group::before {
  content: "";
  position: absolute;
  inset: 0;
  pointer-events: none;
  background:
    linear-gradient(120deg, rgba(160, 198, 255, 0.08), transparent 35%),
    radial-gradient(circle at 86% 90%, rgba(111, 166, 255, 0.14), transparent 35%);
}

.group-intro {
  position: relative;
  z-index: 1;
  max-width: 920px;
  background: transparent;
  border: 0;
  border-radius: 0;
  padding: 0.9rem 1rem;
  box-shadow: none;
  margin: 0 auto;
  text-align: center;
}

.group-intro h2 {
  margin: 0;
  color: rgba(185, 214, 255, 0.92);
  font-size: clamp(1.6rem, 3vw, 2.35rem);
  line-height: 1.2;
  letter-spacing: -0.01em;
}

.group-intro p {
  margin: 0.68rem 0 0;
  color: #fff;
  font-size: clamp(0.92rem, 1.35vw, 1rem);
  line-height: 1.45;
  max-width: 760px;
  margin-inline: auto;
  text-align: center;
}

.group-icons {
  position: relative;
  z-index: 1;
  margin: 1rem 0 0;
  padding: 0;
  list-style: none;
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 0.65rem;
}

.group-card {
  position: relative;
  overflow: hidden;
  border: 1px solid rgba(152, 190, 242, 0.42);
  border-radius: 13px;
  min-height: 108px;
  padding: 0.75rem 0.65rem;
  background: linear-gradient(180deg, rgba(37, 63, 98, 0.75) 0%, rgba(32, 55, 87, 0.84) 100%);
  display: grid;
  justify-items: center;
  align-content: center;
  gap: 0.5rem;
  text-align: center;
  box-shadow: 0 10px 22px rgba(7, 15, 29, 0.22);
  transition:
    transform 0.28s ease,
    border-color 0.28s ease,
    box-shadow 0.28s ease,
    background 0.28s ease;
  animation: groupCardIn 0.6s ease both;
}

.group-card::before {
  content: "";
  position: absolute;
  top: -120%;
  left: -20%;
  width: 62%;
  height: 260%;
  background: linear-gradient(180deg, transparent 0%, rgba(170, 208, 255, 0.3) 45%, transparent 100%);
  transform: rotate(24deg);
  transition: transform 0.55s ease;
}

.group-card:nth-child(2) {
  animation-delay: 0.08s;
}

.group-card:nth-child(3) {
  animation-delay: 0.16s;
}

.group-card:nth-child(4) {
  animation-delay: 0.24s;
}

.group-card:nth-child(5) {
  animation-delay: 0.32s;
}

.group-card:nth-child(6) {
  animation-delay: 0.4s;
}

.group-card:hover,
.group-card:focus-visible {
  transform: translateY(-7px);
  border-color: rgba(183, 217, 255, 0.92);
  box-shadow: 0 18px 32px rgba(5, 14, 29, 0.38);
  background: linear-gradient(180deg, rgba(48, 82, 125, 0.88) 0%, rgba(39, 66, 103, 0.9) 100%);
  outline: none;
}

.group-card:hover::before,
.group-card:focus-visible::before {
  transform: translateX(190%) rotate(24deg);
}

.icon-wrap {
  width: 36px;
  height: 36px;
  border-radius: 9px;
  display: grid;
  place-items: center;
  color: #f3f8ff;
  background: linear-gradient(150deg, rgba(126, 176, 255, 0.38), rgba(76, 133, 223, 0.44));
  transition: transform 0.28s ease, background 0.28s ease;
}

.group-card:hover .icon-wrap,
.group-card:focus-visible .icon-wrap {
  transform: translateY(-2px) scale(1.05);
  background: linear-gradient(150deg, rgba(170, 205, 255, 0.64), rgba(116, 169, 247, 0.8));
}

.icon-wrap svg {
  width: 20px;
  height: 20px;
}

.group-card strong {
  font-size: 0.88rem;
  color: #f4f8ff;
  letter-spacing: 0.01em;
}

.block-companies {
  border: 1px solid #d8e0ed;
  background: #fff;
  padding: clamp(1.2rem, 3vw, 1.8rem);
}

.section-head {
  margin-bottom: 1.5rem;
}

.section-head h2 {
  margin: 0.55rem 0 0;
  color: #273d5c;
  font-size: clamp(1.4rem, 3.2vw, 2rem);
}

.carousel-wrapper {
  position: relative;
  margin: 0 calc(-1 * clamp(1.2rem, 3vw, 1.8rem));
  padding: 0 clamp(1.2rem, 3vw, 1.8rem) 1.5rem;
}

.carousel-btn {
  position: absolute;
  top: calc(50% - 1.5rem);
  transform: translateY(-50%);
  z-index: 10;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  border: 1px solid #d8e0ed;
  background: #ffffff;
  color: #273d5c;
  display: grid;
  place-items: center;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 4px 10px rgba(15, 32, 57, 0.15);
}

.btn-left {
  left: 5px;
}

.btn-right {
  right: 5px;
}

.carousel-btn:hover {
  background: #f1f5fb;
  border-color: #bcc9dd;
  transform: translateY(-50%) scale(1.05);
  box-shadow: 0 6px 14px rgba(15, 32, 57, 0.2);
}

.carousel-btn svg {
  width: 20px;
  height: 20px;
}

.company-grid {
  display: flex;
  gap: 1.2rem;
  overflow-x: auto;
  scroll-snap-type: x mandatory;
  scroll-behavior: smooth;
  scrollbar-width: none; /* Firefox */
  -ms-overflow-style: none;  /* IE and Edge */
  padding-bottom: 1rem;
}

.company-grid::-webkit-scrollbar {
  display: none; /* Chrome, Safari, Opera */
}

.company-card {
  flex: 0 0 min(100%, 350px);
  scroll-snap-align: start;
  border: 1px solid #d7e0ee;
  border: 1px solid #d7e0ee;
  border-radius: 14px;
  background: #fff;
  overflow: hidden;
  min-height: 460px;
  box-shadow: 0 12px 24px rgba(15, 32, 57, 0.08);
  transition: transform 0.23s ease, box-shadow 0.23s ease;
}

.company-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 16px 30px rgba(15, 32, 57, 0.16);
}

.company-image-wrap {
  position: relative;
  height: 250px;
  overflow: hidden;
}

.company-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.company-image-wrap::after {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, transparent 30%, rgba(10, 22, 39, 0.62));
}

.company-logo-pill {
  position: absolute;
  z-index: 2;
  top: 0.7rem;
  right: 0.7rem;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.52);
  padding: 0.28rem 0.52rem;
  background: rgba(255, 255, 255, 0.9);
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
}

.company-logo-pill img {
  width: 18px;
  height: 18px;
}

.company-logo-pill span {
  color: #273d5c;
  font-size: 0.76rem;
  font-weight: 700;
}

.company-content {
  padding: 0.9rem;
  display: grid;
  gap: 0.7rem;
}

.company-content h3 {
  margin: 0;
  color: #223754;
  font-size: 1.03rem;
}

.company-content p {
  margin: 0;
  color: #5f6e85;
  font-size: 0.92rem;
  line-height: 1.4;
}

.company-link {
  width: fit-content;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  align-self: start;
  justify-self: center;
  text-decoration: none;
  border: 1px solid #bcc9dd;
  color: #ffffff;
  background-color: #1f3553;
  border-radius: 20px;
  padding: 0.5rem 0.82rem;
  font-weight: 700;
  transition: background 0.2s ease;
}

.company-link:hover {
  background: #2d4c79;
}

@keyframes charIn {
  from {
    opacity: 0;
    transform: translateY(8px);
    filter: blur(2px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
    filter: blur(0);
  }
}

@keyframes particleDrift {
  0% {
    transform: translateY(0);
  }

  50% {
    transform: translateY(-8px);
  }

  100% {
    transform: translateY(0);
  }
}

@keyframes scrollDot {
  0% {
    opacity: 0.1;
    transform: translateY(0);
  }

  40% {
    opacity: 1;
    transform: translateY(8px);
  }

  100% {
    opacity: 0.12;
    transform: translateY(0);
  }
}

@keyframes groupCardIn {
  from {
    opacity: 0;
    transform: translateY(14px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes titleSheen {
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

@media (max-width: 1140px) {
  .group-icons {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 760px) {
  .block-hero {
    margin-top: calc(-1 * var(--content-top-padding) - var(--topbar-height));
    min-height: 100svh;
    border-radius: 0;
    align-items: center;
  }

  .hero-watermark-logo {
    top: auto;
    right: 0.1rem;
    bottom: 1.25rem;
    transform: none;
    width: min(58vw, 290px);
  }

  .group-icons {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .group-card {
    min-height: 0;
    aspect-ratio: 1 / 1;
    padding: 0.65rem 0.45rem;
    text-align: center;
  }
}

@media (max-width: 520px) {
  .group-icons {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .group-card strong {
    font-size: 0.8rem;
  }
}
</style>
