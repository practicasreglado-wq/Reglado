<template>
  <div class="restricted-container">
    <div class="restricted-card">
      <div class="icon-wrapper">
        <svg viewBox="0 0 24 24" width="60" height="60" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 15v2m0-8V7m0-4h.01M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <h2>Acceso Restringido</h2>
      <p class="main-msg">Necesitas ser un usuario <strong>PREMIUM</strong> para acceder a esta sección.</p>

      <div class="notice-box">
        <p>Tu cuenta actualmente tiene acceso limitado.</p>
        <p>Para acceder al catálogo completo de propiedades y a todas las herramientas de la plataforma necesitas una cuenta <strong>PREMIUM</strong>.</p>
      </div>

      <p class="sub-msg">Ponte en contacto con nosotros para activar todos los servicios.</p>
      <button class="contact-btn" @click="$router.push('/contacto')">Contactar ahora</button>
    </div>
  </div>
</template>

<script>
export default {
  name: "RestrictedAccess"
};
</script>

<style scoped>
.restricted-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 60vh;
  padding: 20px;
}

.restricted-card {
  background: rgba(255, 255, 255, 0.72);
  backdrop-filter: blur(20px);
  padding: 40px;
  border-radius: 32px;
  box-shadow:
    0 25px 50px rgba(18, 36, 77, 0.12),
    0 10px 20px rgba(18, 36, 77, 0.08);
  text-align: center;
  max-width: 550px;
  width: 100%;
  border: 1px solid rgba(255, 255, 255, 0.4);
  position: relative;
  overflow: hidden;
}

.restricted-card::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 6px;
  background: linear-gradient(90deg, #bd9b2c, #f2d46b);
}

.icon-wrapper {
  color: #bd9b2c;
  margin-bottom: 24px;
  filter: drop-shadow(0 4px 12px rgba(189, 155, 44, 0.2));
}

h2 {
  color: #12244d;
  margin-bottom: 20px;
  font-size: 2rem;
  font-weight: 800;
}

.main-msg {
  font-size: 1.25rem;
  color: #12244d;
  margin-bottom: 24px;
  opacity: 0.9;
}

.notice-box {
  background: rgba(189, 155, 44, 0.08);
  border: 1px solid rgba(189, 155, 44, 0.15);
  padding: 24px;
  border-radius: 20px;
  margin-bottom: 24px;
}

.notice-box p {
  margin: 8px 0;
  color: #5f4b08;
  font-size: 1rem;
  line-height: 1.6;
}

.sub-msg {
  color: #4b5563;
  margin-bottom: 32px;
  font-weight: 600;
}

.contact-btn {
  background: linear-gradient(135deg, #bd9b2c, #d3ae33);
  color: white;
  border: none;
  padding: 14px 40px;
  border-radius: 999px;
  font-weight: 800;
  font-size: 1rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 10px 20px rgba(189, 155, 44, 0.2);
}

.contact-btn:hover {
  transform: translateY(-3px);
  box-shadow: 0 15px 30px rgba(189, 155, 44, 0.3);
  filter: brightness(1.05);
}
</style>
