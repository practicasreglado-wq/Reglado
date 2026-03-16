<!-- componente de Footer global en toda la web (App principal) -->
<template>
  <footer class="footer">
    <div class="container footer-inner">
      <div class="col brand-col">
        <div class="title">REGLADO ENERGY</div>
        <p class="small">Optimizamos contratos de luz y gas, reducimos costes y te acompañamos en toda la gestión energética.</p>
        <div class="social-links" aria-label="Redes sociales">
          <a href="https://www.linkedin.com/company/reglado-energy" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">
            <span class="social-icon" aria-hidden="true" :style="{ '--icon-url': `url(${linkedinIcon})` }"></span>
          </a>
          <a href="https://www.instagram.com" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
            <span class="social-icon" aria-hidden="true" :style="{ '--icon-url': `url(${instagramIcon})` }"></span>
          </a>
         
        </div>
      </div>
      <div class="col">
        <div class="title">Navegación</div>
        <div class="links">
          <router-link to="/servicios">Servicios</router-link>
          <router-link to="/clientes">Clientes</router-link>
          <router-link to="/recursos">Recursos</router-link>
          <router-link to="/sobre-nosotros">Sobre nosotros</router-link>
          <router-link to="/contacto">Contacto</router-link>
        </div>
      </div>
      <div class="col">
        <div class="title">Contacto</div>
        <div class="small">
          <br>
          <div><strong>Teléfono:</strong> +34 911462674</div>
          <div><strong>Email:</strong> info@regladoconsultores.com</div>
        </div>
      </div>
    </div>
    <div class="container bottom">
      <div class="small">© {{ year }} Reglado Energy. Todos los derechos reservados.</div>
      <div class="small">Transparencia · Rigor técnico · Acompañamiento</div>
    </div>
  </footer>
</template>

<script setup>
import linkedinIcon from '../assets/iconos/linkedin.svg';
import instagramIcon from '../assets/iconos/instagram.svg';

const year = new Date().getFullYear();
</script>

<style scoped>
.footer{
  position: relative;
  z-index: 2;
  border-top: 1px solid rgba(255,255,255,.08);
  background: #2b2716;
  padding: 40px 0 140px;
}
.footer-inner{ display:grid; grid-template-columns: 1.4fr 1fr 1fr; gap: 18px; align-items: stretch; }
.brand-col{ display: flex; flex-direction: column; align-items: flex-start; }
.title{ font-weight: 800; margin-bottom: 10px; letter-spacing: .6px; display: inline-block; padding: 8px 12px; border: 1px solid rgba(242,197,61,.6); border-radius: 6px; background: rgba(242,197,61,.08); }
.links{ display:flex; flex-direction:column; gap: 10px; }
.links a{ color: rgba(233,238,246,.78); }
.links a:hover{ color: #fff; }
.social-links{
  display:flex;
  justify-content: center;
  gap: 18px;
  margin-top: auto;
  padding-top: 28px;
}
.social-links a{
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: transform .2s ease;
}
.social-links a:hover{
  transform: translateY(-2px);
}
.social-icon{
  width: 24px;
  height: 24px;
  background-color: #fff;
  -webkit-mask-image: var(--icon-url);
  mask-image: var(--icon-url);
  -webkit-mask-repeat: no-repeat;
  mask-repeat: no-repeat;
  -webkit-mask-position: center;
  mask-position: center;
  -webkit-mask-size: contain;
  mask-size: contain;
  transition: background-color .2s ease;
}
.social-links a:hover .social-icon{
  background-color: #f2c53d;
}
.bottom{ margin-top: 22px; padding-top: 18px; border-top: 1px solid rgba(255,255,255,.08); display:flex; justify-content:space-between; gap: 12px; flex-wrap: wrap; }
@media (max-width: 980px){ .footer-inner{ grid-template-columns: 1fr; } }

</style>
