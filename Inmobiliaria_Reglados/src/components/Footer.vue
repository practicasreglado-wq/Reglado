<!--
  Footer global. Estático: enlaces legales, info de contacto y copyright.
  Sin lógica reactiva.
-->
<template>
  <footer class="footer">
    <div class="container footer-inner">
      <div class="col brand-col">
        <div class="footer-pill">Reglado Real Estate</div>
        <p class="footer-copy">
          Boutique inmobiliaria especializada en activos singulares, edificios,
          hoteles y grandes operaciones. Discrecion, analisis y resultados.
        </p>
        <div class="footer-social" aria-label="Redes sociales">
          <a href="https://www.linkedin.com/in/reglado-consultores-90b7a0233?utm_source=share&utm_campaign=share_via&utm_content=profile&utm_medium=android_app" class="social-link" aria-label="LinkedIn" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z" fill="currentColor"/>
            </svg>
          </a>
          <a href="#" class="social-link" aria-label="Instagram">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <rect x="3.2" y="3.2" width="17.6" height="17.6" rx="4.6" fill="none" stroke="currentColor" stroke-width="1.8"/>
              <circle cx="12" cy="12" r="4.1" fill="none" stroke="currentColor" stroke-width="1.8"/>
              <circle cx="17.3" cy="6.9" r="1.1" fill="currentColor"/>
            </svg>
          </a>
        </div>
      </div>

      <div class="col">
        <div class="footer-pill">Navegacion</div>
        <div class="footer-links">
          <a href="#" @click.prevent="goWithoutLoader('/')">Inicio</a>
          <a href="#" @click.prevent="goWithoutLoader('/#about-us')">Quienes somos</a>
        </div>
      </div>

      <div class="col">
        <div class="footer-pill">Contacto</div>
        <div class="footer-contact-info">
          <div><strong>Tel:</strong> +34 911 462 674 / 615 641 081</div>
          <div><strong>Email:</strong> info@regladoconsultores.com</div>
        </div>
      </div>
    </div>

    <div class="container footer-bottom">
      <div class="small">© 2026 REGLADO REAL ESTATE. Todos los derechos reservados.</div>
      <div class="footer-legal">
        <router-link to="/legal">Aviso legal y privacidad</router-link>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { useRouter } from "vue-router";

const router = useRouter();

const markSkipNextLoader = () => {
  window.sessionStorage.setItem("skip-next-loader", "true");
};

const markSkipNextTransition = () => {
  window.sessionStorage.setItem("skip-next-page-transition", "true");
};

const goWithoutLoader = (target) => {
  markSkipNextLoader();
  markSkipNextTransition();
  router.push(target);
};

</script>

<style scoped>
.footer {
  background: linear-gradient(180deg, #0a1931, #16213e);
  color: #fff;
  padding: 60px 0 30px; /* Adjusted padding like energy */
  position: relative;
  overflow: hidden;
}

.footer::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(189, 155, 44, 0.3), transparent);
}

.container {
  width: min(1200px, 90%);
  margin: 0 auto;
}

.footer-inner {
  display: grid;
  grid-template-columns: 1.4fr 1fr 1fr;
  gap: 40px;
  align-items: stretch;
  margin-bottom: 40px;
}

.col {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.brand-col {
  align-items: flex-start;
}

.footer-pill {
  display: inline-flex;
  align-items: center;
  width: fit-content;
  padding: 6px 16px;
  background: rgba(189, 155, 44, 0.1);
  border: 1px solid rgba(189, 155, 44, 0.3);
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 800;
  color: #bd9b2c;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.footer-copy {
  margin: 0;
  line-height: 1.7;
  font-size: 0.95rem;
  color: rgba(255, 255, 255, 0.7);
}

.footer-social {
  display: flex;
  gap: 15px;
  margin-top: auto;
  padding-top: 20px;
}

.social-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #fff;
  transition: all 0.3s ease;
}

.social-link:hover {
  background: #bd9b2c;
  border-color: #bd9b2c;
  transform: translateY(-3px);
}

.social-link svg {
  width: 18px;
  height: 18px;
}

.footer-links {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.footer-links a {
  color: rgba(255, 255, 255, 0.6);
  text-decoration: none;
  font-size: 1rem;
  transition: color 0.2s ease;
}

.footer-links a:hover {
  color: #bd9b2c;
}

.footer-contact-info {
  display: flex;
  flex-direction: column;
  gap: 12px;
  color: rgba(255, 255, 255, 0.6);
  font-size: 1rem;
}

.footer-contact-info strong {
  color: #fff;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  opacity: 0.5;
}

.footer-bottom {
  padding-top: 25px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  flex-wrap: wrap;
}

.small {
  color: rgba(255, 255, 255, 0.4);
  font-size: 0.85rem;
}

.footer-legal a {
  color: rgba(255, 255, 255, 0.4);
  text-decoration: underline;
  text-underline-offset: 4px;
  transition: color 0.2s ease;
  font-size: 0.85rem;
}

.footer-legal a:hover {
  color: #fff;
}

@media (max-width: 980px) {
  .footer-inner {
    grid-template-columns: 1fr;
    gap: 40px;
  }
  
  .brand-col, .col {
    align-items: center;
    text-align: center;
  }
  
  .footer-bottom {
    flex-direction: column;
    text-align: center;
  }
}
</style>
