<template>
  <footer class="footer">
    <div class="container footer-inner">
      <div class="col brand-col">
        <div class="title">GRUPO REGLADO</div>
        <p class="small">Grupo empresarial en consultoría, energía, tecnología, inmuebles e inversión.</p>
        <div class="footer-company-data small">
          <div>CIF: B23982762</div>
          <div>Avda. Isla Graciosa, 7, 28703 San Sebastián de los Reyes.</div>
        </div>
        <div class="social-links" aria-label="Redes sociales">
          <a class="social-link" href="https://www.linkedin.com/in/reglado-abogados-y-consultores-90b7a0233/"
            target="_blank" rel="noreferrer" aria-label="LinkedIn">
            <img :src="linkedinIcon" alt="LinkedIn" class="social-icon" />
          </a>
        </div>
      </div>

      <div class="col">
        <div class="title">Navegación</div>
        <div class="links">
          <a href="https://regladoconsultores.com/" target="_blank" rel="noreferrer">Abogados</a>
          <a :href="energyUrl" target="_blank" rel="noreferrer">Energía</a>
          <a :href="realstateUrl" target="_blank" rel="noreferrer">Inmobiliaria</a>
          <a :href="mapasUrl" target="_blank" rel="noreferrer">Mapas</a>
          <a href="#" target="_blank" rel="noreferrer">Ingeniería</a>
          <a href="#" target="_blank" rel="noreferrer">RBR</a>
        </div>
      </div>

      <div class="col">
        <div class="title">Contacto</div>
        <div class="small contact-list">
          <div><strong>Teléfono:</strong> +34 911462674</div>
          <div><strong>Email:</strong> info@regladoconsultores.com</div>
        </div>
      </div>
    </div>

    <div class="container bottom">
      <div class="small">&copy; {{ year }} Reglado Group. Todos los derechos reservados.</div>
      <div class="small footer-legal-links">
        <router-link to="/aviso-legal">Aviso legal</router-link> &middot;
        <router-link to="/politica-privacidad">Privacidad</router-link> &middot;
        <router-link to="/politica-cookies">Política de cookies</router-link>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { computed } from "vue";
import linkedinIcon from "../assets/linkedin.svg";
import { auth } from "../services/auth";

const realstateUrl = import.meta.env.VITE_REGLADO_REALSTATE_URL || "#";
const rawMapasUrl = import.meta.env.VITE_REGLADO_MAPAS_URL || "https://teal-bat-675895.hostingersite.com/";
const mapasUrl = computed(() => buildExternalProductUrl(rawMapasUrl));
const rawEnergyUrl = import.meta.env.VITE_REGLADO_ENERGY_URL || "http://localhost:5174";
const energyUrl = computed(() => buildExternalProductUrl(rawEnergyUrl));
const year = new Date().getFullYear();

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
.footer {
  position: relative;
  z-index: 2;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  background: linear-gradient(135deg, rgba(23, 39, 61, 0.98), rgba(39, 61, 92, 0.95));
  padding: 60px 0 40px;
  color: #fff;
  text-align: left;
}

.container {
  max-width: min(1160px, 92%);
  margin: 0 auto;
}

.footer-inner {
  display: grid;
  grid-template-columns: 1.4fr 1fr 1fr;
  gap: 18px;
  align-items: stretch;
}

.brand-col {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  margin-right: 150px;
}

.col {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0;
}

.title {
  font-weight: 800;
  margin-bottom: 32px;
  letter-spacing: 0.6px;
  display: inline-block;
  padding: 8px 12px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.05);
  text-transform: uppercase;
}

.links,
.contact-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.links a {
  color: rgba(233, 238, 246, 0.78);
  text-decoration: none;
}

.links a:hover {
  color: #fff;
}

.social-links {
  display: flex;
  justify-content: center;
  gap: 18px;
  margin-top: auto;
  padding-top: 28px;
}

.social-links a {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.2s ease;
  text-decoration: none;
}

.social-links a:hover {
  transform: translateY(-2px);
}

.social-icon {
  width: 24px;
  height: 24px;
  display: block;
  opacity: 0.92;
  transition: opacity 0.2s ease, transform 0.2s ease, filter 0.2s ease;
  filter: brightness(0) saturate(100%) invert(100%);
}

.social-links a:hover .social-icon {
  opacity: 1;
  transform: scale(1.05);
}

.bottom {
  margin-top: 60px;
  padding-top: 18px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.footer-legal-links a {
  color: inherit;
  text-decoration: none;
  transition: color 0.2s ease;
}

.small {
  font-size: 1rem;
  color: rgba(255, 255, 255, 0.7);
  line-height: 1.6;
  margin: 0;
}

.footer-legal-links a:hover {
  color: #fff;
}

.footer-company-data {
  margin-top: 10px;
  opacity: 0.85;
}

@media (max-width: 980px) {
  .footer-inner {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .brand-col {
    margin-right: 0;
  }
}
</style>
