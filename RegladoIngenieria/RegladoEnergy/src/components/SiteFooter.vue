<!-- Footer global -->
<template>
  <footer class="footer">
    <div class="container footer-inner">
      <div class="col brand-col">
        <div class="title">REGLADO ENERGY</div>
        <p class="small">Optimizamos contratos de luz y gas, reducimos costes y te acompañamos en toda la gestión energética.</p>
        <p class="small">CIF: B24887754 <br>
          Avda. Isla Graciosa, 7, 28703 San Sebastián de los Reyes.</p>
        <div class="social-links" aria-label="Redes sociales">
          <a href="https://www.linkedin.com/in/reglado-abogados-y-consultores-90b7a0233/" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">
            <img :src="linkedinIcon" alt="" class="social-icon" />
          </a>
          <a href="https://www.instagram.com" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
            <img :src="instagramIcon" alt="" class="social-icon" />
          </a>
        </div>
      </div>

      <div class="col">
        <div class="title">NAVEGACIÓN</div><br><br>
        <div class="links">
          <router-link to="/servicios">Servicios</router-link>
          <router-link to="/clientes">Clientes</router-link>
          <router-link to="/recursos">Recursos</router-link>
          <router-link to="/sobre-nosotros">Sobre nosotros</router-link>
          <router-link to="/contacto">Contacto</router-link>
        </div>
      </div>

      <div class="col">
        <div class="title">CONTACTO</div>
        <div class="small">
          <br />
          <div><strong>Teléfono:</strong> +34 911462674</div>
          <div><strong>Email:</strong> info@regladoenergy.com</div>
        </div>
      </div>
    </div>

    <div class="container bottom">
      <div class="small">&copy; {{ year }} Reglado Energy. Todos los derechos reservados.</div>
      <div class="small footer-legal-links">
        <router-link to="/aviso-legal">Aviso legal</router-link> &middot;
        <router-link to="/politica-privacidad">Privacidad</router-link> &middot;
        <router-link to="/politica-cookies">Política de cookies</router-link>
      </div>
    </div>
  </footer>
</template>

<script setup>
import linkedinIcon from "../assets/iconos/linkedin.svg";
import instagramIcon from "../assets/iconos/instagram.svg";

const year = new Date().getFullYear();
</script>

<style scoped>
.footer {
  position: relative;
  z-index: 2;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  background: #2b2716;
  padding: 60px 0 40px;
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
  margin-right: 150px; /* Added margin to create separation from the navigation block */
}

.title {
  font-weight: 800;
  margin-bottom: 10px;
  letter-spacing: 0.6px;
  display: inline-block;
  padding: 8px 12px;
  border: 1px solid rgba(242, 197, 61, 0.6);
  border-radius: 6px;
  background: rgba(242, 197, 61, 0.08);
}

.links {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.links a {
  color: rgba(233, 238, 246, 0.78);
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
  filter: brightness(0) saturate(100%) invert(82%) sepia(46%) saturate(623%) hue-rotate(353deg) brightness(102%) contrast(90%);
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
    margin-right: 0; /* Remove right margin for mobile view */
  }
}
</style>
