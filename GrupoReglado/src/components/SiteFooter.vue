<template>
  <footer class="site-footer">
    <div class="container footer-layout">
      <div class="footer-inner">
        <div class="footer-col brand-col">
          <div class="title-badge">Grupo Reglado</div>
          <p class="small-text">Grupo empresarial en consultoria, energia, tecnologia, inmuebles e inversion.</p>
          <div class="social-links" aria-label="Redes sociales">
            <a class="social-link" href="https://www.linkedin.com/in/reglado-abogados-y-consultores-90b7a0233/"
              target="_blank" rel="noreferrer" aria-label="LinkedIn">
              <img :src="linkedinIcon" alt="LinkedIn" class="social-icon" />
            </a>
          </div>
        </div>

        <div class="footer-col">
          <div class="title-badge">Navegación</div>
          <div class="footer-links">
            <a href="https://regladoconsultores.com/" target="_blank" rel="noreferrer">Reglado Consultores</a>
            <a :href="energyUrl" target="_blank" rel="noreferrer">Energy</a>
            <a href="#">Mapas</a>
            <a :href="realstateUrl" target="_blank" rel="noreferrer">Real Estate</a>
          </div>
        </div>

        <div class="footer-col">
          <div class="title-badge">Contacto</div>
          <div class="footer-contact">
            <div><strong>Teléfono:</strong> +34 911462674</div>
            <div><strong>Email:</strong> info@regladoconsultores.com</div>
          </div>
        </div>
      </div>

      <div class="bottom">
        <div class="bottom-text">&copy; {{ year }} Reglado Group. Todos los derechos reservados.</div>
        <div class="bottom-text">Transparencia &middot; Rigor t&eacute;cnico &middot; Acompa&ntilde;amiento</div>
      </div>
    </div>
  </footer>
</template>

<script setup>
import linkedinIcon from "../assets/linkedin.svg";

const realstateUrl = import.meta.env.VITE_REGLADO_REALSTATE_URL || "#";
const energyUrl = import.meta.env.VITE_REGLADO_ENERGY_URL || "http://localhost:5174";
const year = new Date().getFullYear();
</script>

<style scoped>
.site-footer {
  position: relative;
  z-index: 2;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  background: linear-gradient(135deg, rgba(23, 39, 61, 0.98), rgba(39, 61, 92, 0.95));
  padding: 60px 0 30px;
  color: #fff;
}

.container {
  max-width: min(1160px, 92%);
  margin: 0 auto;
}

.footer-layout {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.footer-inner {
  display: grid;
  grid-template-columns: 1.5fr 1fr 1fr;
  gap: 40px;
  align-items: start;
  padding-bottom: 40px;
}

.brand-col {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.footer-col {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0;
}

.title-badge {
  font-weight: 800;
  font-size: 0.9rem;
  margin-bottom: 20px;
  letter-spacing: 1px;
  display: inline-block;
  padding: 8px 16px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.05);
  text-transform: uppercase;
  color: #fff;
}

.small-text {
  font-size: 0.95rem;
  color: rgba(255, 255, 255, 0.7);
  line-height: 1.6;
  margin: 0 0 25px;
  max-width: 350px;
}

.social-links {
  display: flex;
  gap: 15px;
  margin-top: 0;
  position: relative;
  top: 25px;
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

.social-icon {
  width: 26px;
  height: 26px;
  display: block;
  opacity: 0.75;
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.social-link:hover .social-icon {
  opacity: 1;
  transform: scale(1.05);
}

.footer-links {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.footer-links a {
  color: rgba(255, 255, 255, 0.7);
  text-decoration: none;
  font-size: 1rem;
  transition: color 0.3s ease;
  width: fit-content;
}

.footer-links a:hover {
  color: #fff;
}

.footer-contact {
  display: flex;
  flex-direction: column;
  gap: 10px;
  color: rgba(255, 255, 255, 0.7);
  font-size: 1rem;
}

.footer-contact strong {
  color: #fff;
}

.bottom {
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

  .bottom {
    flex-direction: column;
    gap: 0.45rem;
  }
}
</style>
