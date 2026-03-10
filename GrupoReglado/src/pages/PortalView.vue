<template>
  <div class="landing">
    <section id="inicio" class="block block-hero" :style="heroStyle">
      <div class="hero-overlay"></div>
      <div class="hero-particles"></div>

      <img class="hero-watermark-logo" :src="corporateLogo" alt="" aria-hidden="true" />

      <div class="hero-content">
        <p class="hero-kicker">Portal corporativo</p>
        <h1>Reglado Group</h1>

        <p class="hero-subtitle" :aria-label="heroSubtitle">
          <span
            v-for="(char, index) in heroSubtitleChars"
            :key="`hero-subtitle-${index}-${char}`"
            class="hero-char"
            :style="{ animationDelay: `${index * 0.016}s` }"
          >
            {{ char === " " ? "\u00A0" : char }}
          </span>
        </p>

        <a class="hero-cta" href="#empresas">Explorar empresas</a>

        <div class="hero-scroll-indicator" aria-hidden="true">
          <span></span>
        </div>
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
              <path
                d="M6 20V10L12 5L18 10V20"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linejoin="round"
              />
              <path d="M10 20V14H14V20" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
            </svg>
          </span>
          <strong>Inmuebles</strong>
        </li>

        <li class="group-card" tabindex="0">
          <span class="icon-wrap" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
              <path
                d="M13 2L5 13H11L10 22L19 10H13L13 2Z"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linejoin="round"
              />
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
              <path
                d="M4 18H20M6 15L10 11L13 13L18 8"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
              <path d="M14 8H18V12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            </svg>
          </span>
          <strong>Inversiones</strong>
        </li>

        <li class="group-card" tabindex="0">
          <span class="icon-wrap" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
              <path
                d="M12 3L13.9 8.1L19.4 8.4L15.1 11.8L16.6 17.1L12 14.1L7.4 17.1L8.9 11.8L4.6 8.4L10.1 8.1L12 3Z"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linejoin="round"
              />
            </svg>
          </span>
          <strong>Innovacion</strong>
        </li>
      </ul>
    </section>

    <section id="empresas" class="block block-companies">
      <div class="section-head">
        <p class="section-label">Bloque 3 · Empresas del grupo</p>
        <h2>Acceso directo a las 4 empresas</h2>
      </div>

      <div class="company-grid">
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
    </section>

    <section id="contacto" class="block block-contact">
      <p class="section-label">Bloque 4 · Contacto</p>
      <h2>Contacto</h2>

      <div class="contact-boxes">
        <a class="contact-item" :href="`mailto:${contactEmail}`">
          <span>Email</span>
          <strong>{{ contactEmail }}</strong>
        </a>

        <a class="contact-item" :href="`tel:${contactPhone.replace(/\s+/g, '')}`">
          <span>Telefono</span>
          <strong>{{ contactPhone }}</strong>
        </a>

        <a class="contact-whatsapp" :href="whatsappUrl" target="_blank" rel="noreferrer">
          Contactar Wasaap
        </a>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed } from "vue";

import companyEnergy from "../assets/company-energy.png";
import companyEnProceso from "../assets/company-enproceso.png";
import companyMapas from "../assets/company-mapas.webp";
import companyRealstate from "../assets/company-realstate.png";
import heroBackground from "../assets/hero-reglado-group.png";
import corporateLogo from "../assets/reglado-energy-logo.svg";

const heroSubtitle =
  "Grupo empresarial especializado en consultoria, inmuebles, energia, tecnologia, inversiones e innovacion.";

const heroSubtitleChars = computed(() => Array.from(heroSubtitle));

const heroStyle = computed(() => ({
  "--hero-image": `url('${heroBackground}')`,
}));

const realstateUrl = import.meta.env.VITE_REGLADO_REALSTATE_URL || "#";
const energyUrl = import.meta.env.VITE_REGLADO_ENERGY_URL || "http://localhost:5174";
const mapasUrl = import.meta.env.VITE_REGLADO_MAPAS_URL || "#";
const enProcesoUrl = import.meta.env.VITE_REGLADO_ENPROCESO_URL || "#";

const companies = [
  {
    name: "Reglado Realstate",
    tag: "Realstate",
    description: "Consultoria estrategica y legal enfocada a operaciones inmobiliarias.",
    href: realstateUrl,
    image: companyRealstate,
    logo: corporateLogo,
  },
  {
    name: "Reglado Energy",
    tag: "Energy",
    description: "Optimizacion energetica, analisis de consumo y gestion de contratos.",
    href: energyUrl,
    image: companyEnergy,
    logo: corporateLogo,
  },
  {
    name: "Reglado Mapas",
    tag: "Mapas",
    description: "Plataforma geografica y visualizacion avanzada para decisiones de negocio.",
    href: mapasUrl,
    image: companyMapas,
    logo: corporateLogo,
  },
  {
    name: "Reglado EnProceso",
    tag: "EnProceso",
    description: "Tecnologia, innovacion e inversion para proyectos corporativos.",
    href: enProcesoUrl,
    image: companyEnProceso,
    logo: corporateLogo,
  },
];

const contactEmail = import.meta.env.VITE_REGLADO_CONTACT_EMAIL || "info@regladogroup.com";
const contactPhone = import.meta.env.VITE_REGLADO_CONTACT_PHONE || "+34 600 000 000";

const rawWhatsapp = (import.meta.env.VITE_REGLADO_CONTACT_WHATSAPP || "34600000000").replace(
  /\D/g,
  ""
);
const whatsappUrl = `https://wa.me/${rawWhatsapp || "34600000000"}`;
</script>

<style scoped>
.landing {
  display: grid;
  gap: 1.4rem;
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
  margin-top: -7.25rem;
  min-height: 100vh;
  position: relative;
  border: 1px solid rgba(255, 255, 255, 0.18);
  border-left: 0;
  border-right: 0;
  border-radius: 0 0 28px 28px;
  background-color: #1f324c;
  background-image:
    linear-gradient(115deg, rgba(14, 27, 45, 0.46), rgba(39, 61, 92, 0.3)),
    var(--hero-image);
  background-size: cover, contain;
  background-position: center, center;
  background-repeat: no-repeat, no-repeat;
  background-attachment: scroll, fixed;
  box-shadow: 0 24px 48px rgba(13, 26, 45, 0.25);
  display: grid;
  align-items: end;
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
  inset: 0;
  margin: auto;
  width: min(58vw, 780px);
  max-height: 70%;
  opacity: 0.1;
  filter: grayscale(100%) brightness(150%);
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
  line-height: 0.94;
  letter-spacing: 0.01em;
  filter: drop-shadow(0 10px 22px rgba(8, 18, 34, 0.42));
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
  border-radius: 12px;
  text-decoration: none;
  font-weight: 700;
  color: #fff;
  background: #273d5c;
  border: 1px solid rgba(255, 255, 255, 0.38);
  box-shadow: 0 10px 24px rgba(13, 24, 40, 0.34);
  transition: transform 0.2s ease, background 0.2s ease;
}

.hero-cta:hover {
  transform: translateY(-2px);
  background: #1f324d;
}

.hero-scroll-indicator {
  margin-top: 1rem;
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
  background: linear-gradient(160deg, #dcecff 0%, #cfe3ff 100%);
  border: 1px solid rgba(149, 185, 239, 0.7);
  border-radius: 14px;
  padding: 0.9rem 1rem;
  box-shadow: 0 8px 18px rgba(9, 22, 40, 0.16);
}

.group-intro h2 {
  margin: 0;
  color: #162b45;
  font-size: clamp(1.25rem, 2.2vw, 1.75rem);
  line-height: 1.2;
  letter-spacing: -0.01em;
}

.group-intro p {
  margin: 0.68rem 0 0;
  color: #2c4c73;
  font-size: clamp(0.92rem, 1.35vw, 1rem);
  line-height: 1.45;
  max-width: 760px;
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
  margin-bottom: 1rem;
}

.section-head h2 {
  margin: 0.55rem 0 0;
  color: #273d5c;
  font-size: clamp(1.4rem, 3.2vw, 2rem);
}

.company-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 0.8rem;
}

.company-card {
  border: 1px solid #d7e0ee;
  border-radius: 14px;
  background: #fff;
  overflow: hidden;
  box-shadow: 0 12px 24px rgba(15, 32, 57, 0.08);
  transition: transform 0.23s ease, box-shadow 0.23s ease;
}

.company-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 16px 30px rgba(15, 32, 57, 0.16);
}

.company-image-wrap {
  position: relative;
  height: 190px;
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
  left: 0.7rem;
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
  text-decoration: none;
  border: 1px solid #bcc9dd;
  color: #223754;
  border-radius: 10px;
  padding: 0.5rem 0.82rem;
  font-weight: 700;
  transition: background 0.2s ease;
}

.company-link:hover {
  background: #f1f5fb;
}

.block-contact {
  border: 1px solid #cbd7e7;
  background: linear-gradient(175deg, #f6f9ff 0%, #eef3fb 100%);
  padding: clamp(1.2rem, 3vw, 1.8rem);
}

.block-contact h2 {
  margin: 0.55rem 0 0.95rem;
  color: #273d5c;
}

.contact-boxes {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto;
  gap: 0.75rem;
  align-items: center;
}

.contact-item {
  text-decoration: none;
  background: #fff;
  border: 1px solid #d3ddec;
  border-radius: 12px;
  padding: 0.74rem;
  display: grid;
  gap: 0.2rem;
}

.contact-item span {
  font-size: 0.82rem;
  color: #6b7c95;
}

.contact-item strong {
  color: #223754;
}

.contact-whatsapp {
  text-decoration: none;
  border-radius: 12px;
  padding: 0.74rem 1rem;
  font-weight: 700;
  color: #fff;
  background: #273d5c;
  border: 1px solid rgba(255, 255, 255, 0.32);
  min-height: 48px;
  display: inline-flex;
  align-items: center;
}

.contact-whatsapp:hover {
  background: #1f324d;
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

  .company-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 760px) {
  .block-hero {
    margin-top: -5.5rem;
    min-height: 74vh;
    border-radius: 0 0 18px 18px;
    background-size: cover, cover;
    background-attachment: scroll, scroll;
  }

  .group-icons {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .company-grid,
  .contact-boxes {
    grid-template-columns: 1fr;
  }

  .group-card {
    min-height: 94px;
    text-align: center;
  }
}

@media (max-width: 520px) {
  .group-icons,
  .company-grid,
  .contact-boxes {
    grid-template-columns: 1fr;
  }
}
</style>
