<template>
  <footer class="site-footer">
    <div class="container footer-layout">
      <div class="footer-main">
        <div class="footer-col">
          <h3>Grupo Reglado</h3>
          <p>Grupo empresarial en consultoria, energia, tecnologia, inmuebles e inversion.</p>
          <a class="social-link" href="#" aria-label="LinkedIn">
            <img :src="linkedinIcon" alt="" />
          </a>
        </div>

        <div class="footer-col">
          <h3>Navegacion</h3>
          <div class="footer-links">
            <a href="https://regladoconsultores.com/" target="_blank" rel="noreferrer">Reglado Consultores</a>
            <a :href="energyUrl" target="_blank" rel="noreferrer">Energy</a>
            <a href="#">Mapas</a>
            <a :href="realstateUrl" target="_blank" rel="noreferrer">Real Estate</a>
          </div>
        </div>

        <div class="footer-col">
          <h3>Contacto</h3>
          <div class="footer-contact">
            <div><strong>Telefono:</strong> +34 600 000 000</div>
            <div><strong>Email:</strong> info@regladogroup.com</div>
            <div><strong>Soporte:</strong> soporte@regladogroup.com</div>
          </div>
        </div>
      </div>

      <div class="bottom">
        <div class="small">&copy; {{ year }} Reglado Group. Todos los derechos reservados.</div>
        <div class="small">Transparencia &middot; Rigor t&eacute;cnico &middot; Acompa&ntilde;amiento</div>
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
  border-top: 1px solid rgba(255, 255, 255, 0.16);
  background: linear-gradient(135deg, rgba(23, 39, 61, 0.95), rgba(39, 61, 92, 0.88));
  min-height: 170px;
  display: flex;
  align-items: stretch;
  padding-inline: clamp(1rem, 2.5vw, 2rem);
}

.container {
  width: min(1160px, 100%);
  margin: 0 auto;
}

.footer-layout {
  width: 100%;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 1.4rem 0 1rem;
  gap: 1rem;
}

.footer-main {
  display: grid;
  grid-template-columns: 1.35fr 1fr 1fr;
  gap: 1.25rem;
}

.footer-col {
  display: grid;
  align-content: start;
  gap: 0.6rem;
}

.footer-main h3 {
  margin: 0;
  color: #f6f8fc;
  font-size: 1.2rem;
}

.footer-main p {
  margin: 0.35rem 0 0;
  color: rgba(241, 246, 255, 0.82);
  line-height: 1.5;
}

.social-link {
  width: fit-content;
  height: fit-content;
  margin-top: 0.8rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.2s ease, opacity 0.2s ease;
}

.social-link:hover {
  transform: translateY(-1px);
  opacity: 0.82;
}

.social-link img {
  width: 26px;
  height: 26px;
  display: block;
}

.footer-links {
  display: grid;
  gap: 0.45rem;
}

.footer-links a {
  width: fit-content;
  color: rgba(241, 246, 255, 0.82);
  text-decoration: none;
}

.footer-links a:hover {
  color: #fff;
}

.footer-contact {
  display: grid;
  gap: 0.45rem;
  color: rgba(241, 246, 255, 0.82);
}

.bottom {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  width: 100%;
  padding-top: 0.9rem;
  border-top: 1px solid rgba(255, 255, 255, 0.14);
}

.small {
  font-size: 0.9rem;
  color: rgba(241, 246, 255, 0.72);
}

@media (max-width: 760px) {
  .footer-main {
    grid-template-columns: 1fr;
  }

  .bottom {
    flex-direction: column;
    gap: 0.45rem;
  }
}
</style>
