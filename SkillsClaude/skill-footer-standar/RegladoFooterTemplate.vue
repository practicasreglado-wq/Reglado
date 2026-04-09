<template>
  <footer class="reglado-group-footer">
    <div class="footer-container">
      <div class="footer-grid">
        <!-- Brand Column -->
        <div class="footer-col brand-col">
          <div class="brand-badge">Grupo Reglado</div>
          <p class="brand-description">
            Grupo empresarial líder en consultoría, energía, tecnología, inmuebles e inversión. 
            Transformando el futuro con soluciones integrales.
          </p>
          <div class="brand-company-data">
            <div>CIF: B23982762</div>
            <div>Avda. Isla Graciosa, 7, 28703 San Sebastián de los Reyes.</div>
          </div>
          <div class="social-links" aria-label="Redes sociales">
            <a href="#" target="_blank" rel="noreferrer" aria-label="LinkedIn" class="social-icon-wrapper">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-linkedin"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle></svg>
            </a>
            <a href="#" target="_blank" rel="noreferrer" aria-label="Twitter" class="social-icon-wrapper">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-twitter"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path></svg>
            </a>
          </div>
        </div>

        <!-- Navigation Column -->
        <div class="footer-col">
          <h3 class="col-title">Navegación</h3>
          <nav class="footer-nav">
            <a href="#" class="nav-link">Abogados</a>
            <a href="#" class="nav-link">Energía</a>
            <a href="#" class="nav-link">Real Estate</a>
            <a href="#" class="nav-link">Mapas y Datos</a>
            <a href="#" class="nav-link">Ingeniería</a>
          </nav>
        </div>

        <!-- Contact Column -->
        <div class="footer-col">
          <h3 class="col-title">Contacto</h3>
          <div class="contact-info">
            <p><strong>Teléfono:</strong> +34 91 146 26 74</p>
            <p><strong>Email:</strong> info@regladoconsultores.com</p>
            <p><strong>Sede:</strong> Madrid, España</p>
          </div>
        </div>
      </div>

      <!-- Bottom Bar -->
      <div class="footer-bottom">
        <p class="copyright">&copy; {{ currentYear }} Reglado Group. Todos los derechos reservados.</p>
        <div class="legal-links">
          <a href="#" class="legal-link">Aviso legal</a>
          <span class="separator">&middot;</span>
          <a href="#" class="legal-link">Privacidad</a>
          <span class="separator">&middot;</span>
          <a href="#" class="legal-link">Política de cookies</a>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { computed } from 'vue';

// Valor computado para el año actual
const currentYear = computed(() => new Date().getFullYear());
</script>

<style scoped>
.reglado-group-footer {
  position: relative;
  z-index: 10;
  /* Fondo oscuro estilo premium, similar al theme original pero más pulido */
  background: linear-gradient(145deg, #0f172a, #1e293b);
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  padding: 4rem 1.5rem 1.5rem;
  color: #e2e8f0;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  overflow: hidden;
}

/* Efecto sutil de brillo en la parte superior del footer */
.reglado-group-footer::before {
  content: '';
  position: absolute;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 60%;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(96, 165, 250, 0.4), transparent);
}

.footer-container {
  max-width: 1160px; /* Acorde al diseño de Grupo Reglado */
  margin: 0 auto;
}

.footer-grid {
  display: grid;
  grid-template-columns: 1.5fr 1fr 1fr;
  gap: 3rem;
  margin-bottom: 3rem;
}

.brand-col {
  padding-right: 2rem;
}

.brand-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.5rem 1.25rem;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px; /* Estilo badge cuadrado redondeado */
  font-size: 0.85rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #f8fafc;
  margin-bottom: 1.5rem;
  backdrop-filter: blur(8px);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.brand-description {
  margin-bottom: 1.5rem;
  max-width: 380px;
}

.brand-company-data {
  font-size: 0.85rem;
  line-height: 1.5;
  color: rgba(255, 255, 255, 0.5);
  margin-bottom: 2rem;
}

.social-links {
  display: flex;
  gap: 1rem;
}

.social-icon-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  color: rgba(255, 255, 255, 0.75);
  text-decoration: none;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.social-icon-wrapper:hover {
  background: rgba(255, 255, 255, 0.1);
  transform: translateY(-3px);
  color: #fff;
  border-color: rgba(96, 165, 250, 0.5);
  box-shadow: 0 4px 12px rgba(96, 165, 250, 0.15);
}

.col-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: #f8fafc;
  margin-bottom: 1.5rem;
  position: relative;
  display: inline-block;
  letter-spacing: 0.02em;
}

/* Subrayado de acento para los títulos de las columnas */
.col-title::after {
  content: '';
  position: absolute;
  left: 0;
  bottom: -0.5rem;
  width: 1.5rem;
  height: 2px;
  background: #3b82f6; 
  border-radius: 2px;
  transition: width 0.3s ease;
}

.col-title:hover::after {
  width: 100%;
}

.footer-nav, .contact-info {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.nav-link {
  color: rgba(255, 255, 255, 0.7);
  text-decoration: none;
  font-size: 0.95rem;
  transition: all 0.2s ease;
  width: fit-content;
  position: relative;
}

.nav-link::after {
  content: '';
  position: absolute;
  width: 0;
  height: 1px;
  bottom: -2px;
  left: 0;
  background-color: #60a5fa;
  transition: width 0.3s ease;
}

.nav-link:hover {
  color: #f8fafc;
  transform: translateX(4px);
}

.nav-link:hover::after {
  width: 100%;
}

.contact-info p {
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.95rem;
  margin: 0;
}

.contact-info strong {
  color: #e2e8f0;
  font-weight: 600;
}

.footer-bottom {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  padding-top: 1.5rem;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  gap: 1rem;
}

.copyright {
  font-size: 0.85rem;
  color: rgba(255, 255, 255, 0.5);
  margin: 0;
}

.legal-links {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.legal-link {
  color: rgba(255, 255, 255, 0.5);
  text-decoration: none;
  font-size: 0.85rem;
  transition: color 0.2s ease;
}

.legal-link:hover {
  color: #f8fafc;
}

.separator {
  color: rgba(255, 255, 255, 0.2);
}

/* Responsive adjustments */
@media (max-width: 992px) {
  .footer-grid {
    grid-template-columns: 1fr 1fr;
    gap: 2.5rem;
  }
  .brand-col {
    grid-column: 1 / -1;
    padding-right: 0;
  }
}

@media (max-width: 640px) {
  .footer-grid {
    grid-template-columns: 1fr;
    gap: 2rem;
  }
  .footer-bottom {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
  }
  .reglado-group-footer {
    padding: 3rem 1.25rem 1.25rem;
  }
}
</style>
