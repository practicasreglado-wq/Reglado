<script setup>
import instagramIcon from '../assets/instagram.svg';
import linkedinIcon from '../assets/linkedin.svg';

const year = new Date().getFullYear();
</script>

<!-- 
  Pie de página de la aplicación.
-->
<template>
  <footer class="footer">
    <div class="container footer-inner">
      <!-- Columna 1: Marca y Redes -->
      <div class="col brand-col">
        <div class="title-badge">AGENCIA DE PUBLICIDAD</div>
        <p class="small-text">Liderando la revolución del marketing de influencers con producción de alta gama y visibilidad garantizada.</p>
        <div class="social-links" aria-label="Redes sociales">
          <a href="#" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn" class="social-link">
            <img :src="linkedinIcon" alt="LinkedIn" class="social-icon-svg">
          </a>
          <a href="#" target="_blank" rel="noopener noreferrer" aria-label="Instagram" class="social-link">
            <img :src="instagramIcon" alt="Instagram" class="social-icon-svg">
          </a>
        </div>
      </div>

      <!-- Columna 2: Navegación -->
      <div class="col">
        <div class="title-badge">Navegación</div>
        <div class="links">
          <a href="#">Inicio</a>
          <a href="#servicios">Servicios</a>
          <a href="#sobre-nosotros">Sobre nosotros</a>
          <a href="#contacto">Contacto</a>
        </div>
      </div>

      <!-- Columna 3: Contacto -->
      <div class="col">
        <div class="title-badge">Contacto</div>
        <div class="contact-info">
          <div><strong>Tel:</strong> +34 600 000 000</div>
          <div><strong>Email:</strong> hola@agenciapublicidad.com</div>
        </div>
      </div>
    </div>

    <!-- Parte Inferior -->
    <div class="container bottom-bar">
      <div class="bottom-text">© {{ year }} Agencia de Publicidad. Todos los derechos reservados.</div>
      <div class="bottom-text">Transparencia · Rigor técnico · Acompañamiento</div>
    </div>
  </footer>
</template>

<style scoped>
.footer {
  position: relative;
  z-index: 2;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  background: var(--dark-blue); /* Azul oscuro profesional */
  padding: 60px 0 30px;
  color: var(--white);
}

.footer-inner {
  display: grid;
  grid-template-columns: 1.5fr 1fr 1fr;
  gap: 40px;
  align-items: stretch;
  padding-bottom: 40px;
}

.brand-col {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.title-badge {
  font-weight: 800;
  font-size: 0.9rem;
  margin-bottom: 20px;
  letter-spacing: 1px;
  display: inline-block;
  padding: 8px 16px;
  border: 1px solid rgba(70, 137, 246, 0.4); /* Color primario suave */
  border-radius: 8px;
  background: rgba(70, 137, 246, 0.05);
  text-transform: uppercase;
}

.small-text {
  font-size: 0.95rem;
  color: rgba(255, 255, 255, 0.7);
  line-height: 1.6;
  margin-bottom: 25px;
  max-width: 350px;
}

.links {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.links a {
  color: rgba(255, 255, 255, 0.7);
  transition: color 0.3s ease;
  font-size: 1rem;
}

.links a:hover {
  color: var(--primary);
}

.contact-info {
  display: flex;
  flex-direction: column;
  gap: 10px;
  color: rgba(255, 255, 255, 0.7);
  font-size: 1rem;
}

.contact-info strong {
  color: var(--white);
}

.social-links {
  display: flex;
  gap: 15px; 
  margin-top: auto;
}

.social-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.3s ease;
  text-decoration: none;
}

.social-link:hover {
  transform: translateY(-3px);
}

.social-icon-svg {
  width: 24px;
  height: 24px;
  filter: brightness(0) invert(1); /* Asegura que sean blancos */
  transition: filter 0.3s ease;
}

.social-link:hover .social-icon-svg {
  filter: invert(51%) sepia(61%) saturate(3015%) hue-rotate(197deg) brightness(98%) contrast(97%); /* Azul corporativo #4689f6 */
}

.bottom-bar {
  margin-top: 20px;
  padding-top: 25px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  justify-content: space-between;
  gap: 15px;
  flex-wrap: wrap;
}

.bottom-text {
  font-size: 0.85rem;
  color: rgba(255, 255, 255, 0.5);
}

@media (max-width: 992px) {
  .footer-inner {
    grid-template-columns: 1fr;
    gap: 40px;
  }
}
</style>
