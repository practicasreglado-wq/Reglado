<template>
  <footer class="footer">
    <!-- Main footer -->
    <div class="footer-main">
      <div class="footer-grid">
        <!-- Brand -->
        <div class="footer-brand">
          <div class="footer-brand-header">
            <div class="footer-logo-wrapper">
              <img src="@/img/RBRaices_Logo.png" alt="RBR" class="footer-logo-img" />
              <div class="footer-logo-text-group">
                <p class="footer-logo-text">Reglado</p>
                <p class="footer-logo-subtext">Bienes Raíces</p>
              </div>
            </div>
          </div>
          <p class="footer-description">
            Entidad dedicada a la gestión, análisis y mediación técnica de activos inmobiliarios. Enfoque corporativo basado en el rigor metodológico.
          </p>
          <div class="social-links">
            <a href="https://www.linkedin.com/in/reglado-consultores-90b7a0233?utm_source=share&utm_campaign=share_via&utm_content=profile&utm_medium=android_app" target="_blank" rel="noopener" class="social-link" aria-label="LinkedIn">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6z"></path>
                <rect x="2" y="9" width="4" height="12"></rect>
                <circle cx="4" cy="4" r="2"></circle>
              </svg>
            </a>
            <a href="https://instagram.com" target="_blank" rel="noopener" class="social-link" aria-label="Instagram">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                <path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"></path>
                <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
              </svg>
            </a>
          </div>
        </div>

        <!-- Navigation -->
        <div class="footer-nav">
          <p class="footer-section-title">Navegación</p>
          <ul class="footer-nav-list">
            <li v-for="link in footerLinks" :key="link.to">
              <a
                :href="link.to"
                class="footer-nav-link"
              >
                {{ link.label }}
              </a>
            </li>
          </ul>
        </div>

        <!-- Contact -->
        <div class="footer-contact">
          <p class="footer-section-title">Contacto</p>
          <ul class="footer-contact-list">
            <li>
              <p class="contact-label">Dirección</p>
              <p class="contact-value">Avda. Isla Graciosa, 7<br>28703 S.S. de los Reyes</p>
            </li>
            <li>
              <p class="contact-label">Teléfono</p>
              <a href="tel:+34 911462674" class="contact-link">+34 911 462 674</a>
            </li>
            <li>
              <p class="contact-label">Email</p>
              <a href="mailto:info@regladoconsultores.com" class="contact-link">info@regladoconsultores.com</a>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Bottom bar -->
    <div class="footer-bottom">
      <div class="footer-bottom-container">
        <p class="copyright">
          © {{ currentYear }} Reglado Bienes Raíces, S.L. Todos los derechos reservados.
        </p>
        <div class="legal-links">
          <a href="#aviso-legal" class="legal-link">Aviso legal y Privacidad</a>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
const currentYear = new Date().getFullYear()

const footerLinks = [
  { to: '#hero', label: 'Inicio' },
  { to: '#que-hacemos', label: 'Qué hacemos' },
  { to: '#sobre-nosotros', label: 'Sobre nosotros' },
]
</script>

<style scoped>
.footer {
  background-color: var(--ink-default);
  color: var(--white);
}

.footer-main {
  max-width: 80rem;
  margin: 0 auto;
  padding: 5rem 1.5rem;
}

@media (min-width: 1024px) {
  .footer-main {
    padding-left: 3rem;
    padding-right: 3rem;
  }
}

.footer-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 3rem;
}

@media (min-width: 768px) {
  .footer-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 1024px) {
  .footer-grid {
    grid-template-columns: repeat(4, 1fr);
    gap: 2rem;
  }
}

.footer-brand {
  grid-column: 1 / -1;
}

@media (min-width: 1024px) {
  .footer-brand {
    grid-column: span 2;
  }
}

.footer-brand-header {
  margin-bottom: 1.5rem;
}

.footer-logo-wrapper {
  margin-bottom: 2.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.footer-logo-img {
  height: 3rem;
  width: auto;
}

.footer-logo-text-group {
  display: flex;
  flex-direction: column;
}

.footer-logo-text {
  font-family: var(--font-display);
  font-size: 1.5rem;
  font-weight: 600;
  letter-spacing: 0.025em;
  line-height: 1;
}

.footer-logo-subtext {
  font-family: var(--font-mono);
  font-size: 10px;
  letter-spacing: var(--letter-spacing-widest3);
  text-transform: uppercase;
  color: var(--gold-400);
  margin-top: 4px;
}

.footer-tagline {
  font-family: var(--font-mono);
  font-size: 10px;
  letter-spacing: var(--letter-spacing-widest3);
  text-transform: uppercase;
  color: var(--gold-400);
}

.footer-description {
  font-family: var(--font-body);
  font-size: 0.875rem;
  color: rgba(255, 255, 255, 0.5);
  line-height: 1.6;
  max-width: 20rem;
}

.social-links {
  display: flex;
  gap: 1rem;
  margin-top: 2rem;
}

.social-link {
  width: 2.25rem;
  height: 2.25rem;
  border: 1px solid rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(255, 255, 255, 0.5);
  text-decoration: none;
  transition: all 0.3s ease;
}

.social-link svg {
  width: 1.125rem;
  height: 1.125rem;
}

.social-link:hover {
  border-color: var(--gold-500);
  color: var(--gold-400);
}

.footer-section-title {
  font-family: var(--font-mono);
  font-size: 10px;
  letter-spacing: var(--letter-spacing-widest3);
  text-transform: uppercase;
  color: var(--gold-500);
  margin-bottom: 1.5rem;
}

.footer-nav-list {
  list-style: none;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.footer-nav-link {
  font-family: var(--font-body);
  font-size: 0.875rem;
  color: rgba(255, 255, 255, 0.5);
  text-decoration: none;
  transition: color 0.3s ease;
}

.footer-nav-link:hover {
  color: var(--gold-400);
}

.footer-contact-list {
  list-style: none;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.contact-label {
  font-family: var(--font-mono);
  font-size: 10px;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.3);
  margin-bottom: 0.25rem;
}

.contact-value, .contact-link {
  font-family: var(--font-body);
  font-size: 0.875rem;
  color: rgba(255, 255, 255, 0.6);
  text-decoration: none;
  transition: color 0.3s ease;
}

.contact-link:hover {
  color: var(--gold-400);
}

.footer-bottom {
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.footer-bottom-container {
  max-width: 80rem;
  margin: 0 auto;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

@media (min-width: 640px) {
  .footer-bottom-container {
    flex-direction: row;
    justify-content: space-between;
  }
}

@media (min-width: 1024px) {
  .footer-bottom-container {
    padding-left: 3rem;
    padding-right: 3rem;
  }
}

.copyright {
  font-family: var(--font-body);
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.3);
}

.legal-links {
  display: flex;
  gap: 1.5rem;
}

.legal-link {
  font-family: var(--font-body);
  font-size: 0.8125rem;
  color: rgba(255, 255, 255, 0.4);
  text-decoration: underline; /* User requested underline */
  text-decoration-color: rgba(255, 255, 255, 0.15); /* Very subtle underline by default */
  text-underline-offset: 4px; /* Separates the line slightly for elegance */
  transition: all 0.3s ease;
}

.legal-link:hover {
  color: var(--white);
  text-decoration-color: var(--white); /* Brightens the line entirely on hover */
}
</style>
