<template>
  <footer class="footer">
    <div class="footer-container">
      <div class="footer-col brand">
        <h2>INMOBILIARIA · RS</h2>
        <p>
          Boutique inmobiliaria especializada en activos singulares,
          edificios, hoteles y grandes operaciones.
          Discreción, análisis y resultados.
        </p>
      </div>

      <div class="footer-col">
        <h3>ACTIVOS</h3>
        <ul>
          <li>Hoteles</li>
          <li>Edificios</li>
          <li>Parking</li>
          <li>Activos Singulares</li>
          <li>Fincas</li>
        </ul>
      </div>

      <div class="footer-col">
        <h3>EMPRESA</h3>
        <ul>
          <li>
            <router-link to="/#about-us" class="footer-link">Quiénes somos</router-link>
          </li>
          <li>
            <router-link to="/contacto" class="footer-link">Contacto</router-link>
          </li>
 </ul>
      </div>

      <div class="footer-col">
        <h3>ACCESO</h3>
        <ul>
          <li>Panel Privado</li>
          <li>Deja tu búsqueda</li>
          <li>Solicitar NDA</li>
          <li>
            <a href="#" @click.prevent="irAAportar" class="footer-link highlight">
            Aportar activo
            </a>
          </li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom">
      <span>© 2026 RS Real Estate · Todos los derechos reservados</span>
      <span>Aviso legal · Privacidad · Cookies · RGPD</span>
    </div>
  </footer>
</template>

<script setup>
import { useUserStore } from "@/stores/user";
import { useRouter } from "vue-router";

const userStore = useUserStore();
const router = useRouter();

const irAAportar = () => {
  if (userStore.isLoggedIn) {
    router.push('/contribute-assets');
  } else {
    router.push('/give-info');
  }
};
</script>
<style scoped>
.footer {
  background: linear-gradient(
    180deg,
    #08335e,
    #5a799f
  );
  color: white;
  padding: 70px 80px 30px 80px;
}

.footer-container {
  display: flex;
  justify-content: space-between;
  gap: 60px;
  margin-bottom: 50px;
}

.footer-col {
  flex: 1;
}

.brand h2 {
  font-size: 1.6rem;
  margin-bottom: 20px;
  font-weight: 700;
}

.brand p {
  line-height: 1.7;
  opacity: 0.9;
  max-width: 300px;
}

.footer-col h3 {
  margin-bottom: 20px;
  font-size: 1.1rem;
  letter-spacing: 1px;
}

.footer-col ul {
  list-style: none;
  padding: 0;
}

.footer-col li {
  margin-bottom: 12px;
  opacity: 0.85;
  cursor: pointer;
  transition: 0.3s ease;
}

.footer-link {
  text-decoration: none;
  color: inherit;
  display: block;
}

.footer-col li:hover {
  opacity: 1;
}

.footer-bottom {
  display: flex;
  justify-content: space-between;
  border-top: 1px solid rgba(255, 255, 255, 0.2);
  padding-top: 20px;
  font-size: 0.9rem;
  opacity: 0.85;
}


/* =========================
TABLET
========================= */

@media (max-width: 1024px) {

.footer {
  padding: 60px 50px 30px 50px;
}

.footer-container {
  gap: 40px;
}

.brand p {
  max-width: 250px;
}

}


/* =========================
TABLET PEQUEÑA
========================= */

@media (max-width: 768px) {

.footer {
  padding: 60px 40px 30px 40px;
}

.footer-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 40px;
}

.brand {
  grid-column: span 2;
}

.brand p {
  max-width: 100%;
}

.footer-bottom {
  flex-direction: column;
  gap: 10px;
  text-align: center;
}

}


/* =========================
MOVIL
========================= */

@media (max-width: 480px) {

.footer {
  padding: 50px 25px 25px 25px;
}

.footer-container {
  grid-template-columns: 1fr;
  gap: 35px;
}

.brand {
  grid-column: span 1;
}

.brand h2 {
  font-size: 1.4rem;
}

.footer-col h3 {
  font-size: 1rem;
}

.footer-col li {
  font-size: 0.95rem;
}

.footer-bottom {
  font-size: 0.8rem;
}

}
</style>