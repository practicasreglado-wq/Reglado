<template>
  <footer class="footer">
    <div class="footer-shell">
      <div class="footer-grid">
        <section class="footer-brand">
          <div class="footer-pill">Reglado RS</div>
          <p class="footer-copy">
            Boutique inmobiliaria especializada en activos singulares, edificios,
            hoteles y grandes operaciones. Discrecion, analisis y resultados.
          </p>

          <div class="footer-social">
            <a href="#" class="social-link" aria-label="LinkedIn">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path
                  d="M6.94 8.5V18M6.88 6.2a1.32 1.32 0 1 0 0-2.64 1.32 1.32 0 0 0 0 2.64ZM11 18v-5.36c0-1.56.84-2.56 2.22-2.56 1.29 0 1.93.88 1.93 2.56V18M3 3h18v18H3z"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.8"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
            </a>
            <a href="#" class="social-link" aria-label="Instagram">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <rect
                  x="3.2"
                  y="3.2"
                  width="17.6"
                  height="17.6"
                  rx="4.6"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.8"
                />
                <circle
                  cx="12"
                  cy="12"
                  r="4.1"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.8"
                />
                <circle cx="17.3" cy="6.9" r="1.1" fill="currentColor" />
              </svg>
            </a>
          </div>
        </section>

        <nav class="footer-column" aria-label="Navegacion del pie">
          <div class="footer-pill">Navegacion</div>
          <ul>
            <li><a href="#" @click.prevent="goWithoutLoader('/')">Inicio</a></li>
            <li><a href="#" @click.prevent="goWithoutLoader('/#about-us')">Quienes somos</a></li>
            <li><a href="#" @click.prevent="goWithoutLoader('/contacto')">Contacto</a></li>
          </ul>
        </nav>

        <section class="footer-column">
          <div class="footer-pill">Contacto</div>
          <ul class="footer-contact">
            <li><strong>Tel:</strong> +34 600 000 000</li>
            <li><strong>Email:</strong> contacto@rsrealestate.com</li>
            <li>Madrid · Barcelona · Costa del Sol</li>
          </ul>
        </section>
      </div>

      <div class="footer-bottom">
        <span>© 2026 RS Real Estate. Todos los derechos reservados.</span>
        <span>Aviso legal · Privacidad · Cookies · RGPD</span>
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
  background: linear-gradient(180deg, #08335e, #5a799f);
  color: #fff;
  padding: 68px 32px 30px;
}

.footer-shell {
  width: min(1200px, 100%);
  margin: 0 auto;
}

.footer-grid {
  display: grid;
  grid-template-columns: 1.3fr 0.9fr 0.9fr;
  gap: 64px;
  margin-bottom: 28px;
}

.footer-brand,
.footer-column {
  display: grid;
  align-content: start;
  gap: 18px;
}

.footer-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: fit-content;
  min-height: 40px;
  padding: 0 16px;
  border: 1px solid rgba(255, 255, 255, 0.45);
  border-radius: 10px;
  font-size: 1.05rem;
  font-weight: 700;
  letter-spacing: 0.03em;
}

.footer-copy {
  max-width: 430px;
  margin: 0;
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.88);
}

.footer-social {
  display: flex;
  gap: 14px;
  margin-top: 22px;
}

.social-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  text-decoration: none;
}

.social-link svg {
  width: 24px;
  height: 24px;
}

.footer-column ul {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 12px;
}

.footer-column li,
.footer-column a {
  color: rgba(255, 255, 255, 0.92);
  text-decoration: none;
  font-size: 1.06rem;
}

.footer-contact li {
  font-size: 1rem;
}

.footer-bottom {
  display: flex;
  justify-content: space-between;
  gap: 18px;
  padding-top: 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.16);
  color: rgba(255, 255, 255, 0.84);
  font-size: 0.94rem;
}

@media (max-width: 900px) {
  .footer-grid {
    grid-template-columns: 1fr 1fr;
    gap: 42px;
  }

  .footer-brand {
    grid-column: 1 / -1;
  }
}

@media (max-width: 640px) {
  .footer {
    padding: 54px 22px 26px;
  }

  .footer-grid {
    grid-template-columns: 1fr;
    gap: 32px;
  }

  .footer-bottom {
    flex-direction: column;
    text-align: left;
  }
}
</style>
