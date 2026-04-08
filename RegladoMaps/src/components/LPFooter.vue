<template>
  <footer class="footer">
    <div class="container footer-inner">
      <div class="col brand-col">
        <div class="title">REGLADO MAPS</div>
        <p class="small">Plataforma cartográfica avanzada para la visualización, análisis y gestión geoespacial de activos e infraestructuras de energía renovable en tiempo real.</p>
        <div class="social-links" aria-label="Redes sociales">
          <a href="https://www.linkedin.com/in/reglado-abogados-y-consultores-90b7a0233/" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">
            <svg class="social-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">
              <path d="M6.75 8.25C7.57843 8.25 8.25 7.57843 8.25 6.75C8.25 5.92157 7.57843 5.25 6.75 5.25C5.92157 5.25 5.25 5.92157 5.25 6.75C5.25 7.57843 5.92157 8.25 6.75 8.25Z" fill="currentColor"/>
              <path d="M5.5 9.75H8V18.5H5.5V9.75Z" fill="currentColor"/>
              <path d="M10 9.75H12.4V10.95H12.43C12.76 10.32 13.57 9.65 14.78 9.65C17.3 9.65 17.75 11.23 17.75 13.28V18.5H15.25V13.88C15.25 12.78 15.23 11.37 13.72 11.37C12.18 11.37 11.95 12.55 11.95 13.8V18.5H9.45V9.75H10Z" fill="currentColor"/>
              <rect x="3.5" y="3.5" width="17" height="17" rx="3.5" stroke="currentColor" stroke-width="1.5" />
            </svg>
          </a>
          <a href="https://www.instagram.com" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
            <svg class="social-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
              <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
              <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
            </svg>
          </a>
        </div>
      </div>

      <div class="col">
        <div class="title">Navegación</div>
        <div class="links">
          <a href="#" @click.prevent="$emit('scrollTo', 'eolica')">Eólica</a>
          <a href="#" @click.prevent="$emit('scrollTo', 'solar')">Solar</a>
          <a href="#" @click.prevent="$emit('scrollTo', 'hidrogeno')">Hidrógeno</a>
          <a href="#" @click.prevent="$emit('scrollTo', 'biometano')">Biometano</a>
          <a href="#" @click.prevent="$emit('scrollTo', 'biodiesel')">Biodiésel</a>
          <a href="#" @click.prevent="$emit('scrollTo', 'hidraulica')">Hidráulica</a>
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
      <div class="small">&copy; {{ year }} Reglado Maps. Todos los derechos reservados.</div>
      <div class="small footer-legal-links">
        <router-link to="/aviso-legal">Aviso legal</router-link> &middot; 
        <router-link to="/politica-cookies">Política de cookies</router-link>
      </div>
    </div>
  </footer>
</template>

<script setup>
import linkedinIcon from '../assets/linkedin.svg';
import instagramIcon from '../assets/instagram.svg';

const year = new Date().getFullYear();
defineEmits(['scrollTo']);
</script>

<style scoped>
.footer {
  position: relative;
  z-index: 2;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  background: linear-gradient(180deg, #020c16 0%, #06090e 100%);
  padding: 60px 0 40px;
  color: #fff;
  text-align: left;
  font-size: 0.8rem;
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
  font-size: 0.8rem;
  margin-bottom: 32px;
  letter-spacing: 0.6px;
  display: inline-block;
  padding: 8px 12px;
  border: 1px solid rgba(0, 196, 125, 0.4);
  border-radius: 6px;
  background: rgba(0, 196, 125, 0.05);
  color: #00c47d;
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
  font-size: 0.8rem;
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
  color: #fff;
  transition: all 0.2s ease;
}

a[aria-label="LinkedIn"] .social-icon {
  transform: scale(1.15);
}

a[aria-label="LinkedIn"]:hover .social-icon {
  color: #0077b5;
}

a[aria-label="Instagram"]:hover .social-icon {
  color: #e4405f;
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
  font-size: 0.8rem;
  color: rgba(255, 255, 255, 0.7);
  line-height: 1.6;
  margin: 0;
}

.footer-legal-links a:hover {
  color: #fff;
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
