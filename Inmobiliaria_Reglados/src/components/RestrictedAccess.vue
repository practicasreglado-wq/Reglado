<template>
  <div class="restricted-container">
    <div class="restricted-card">
      <div class="icon-wrapper">
        <svg viewBox="0 0 24 24" width="60" height="60" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 15v2m0-8V7m0-4h.01M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <h2>Acceso Restringido</h2>
      <p class="main-msg">Necesitas ser un usuario <strong>REAL</strong> para acceder a esta sección.</p>
      
      <div class="notice-box">
        <p>Tu cuenta actualmente tiene acceso limitado.</p>
        <p>Para acceder al catálogo completo de propiedades y a todas las herramientas de la plataforma necesitas una cuenta <strong>REAL</strong>.</p>
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
  background: white;
  padding: 40px;
  border-radius: 20px;
  box-shadow: 0 15px 35px rgba(0,0,0,0.1);
  text-align: center;
  max-width: 550px;
  width: 100%;
  border-top: 6px solid #bd9b2c;
}

.icon-wrapper {
  color: #bd9b2c;
  margin-bottom: 20px;
}

h2 {
  color: #172a5d;
  margin-bottom: 20px;
  font-size: 1.8rem;
}

.main-msg {
  font-size: 1.2rem;
  color: #333;
  margin-bottom: 20px;
}

.notice-box {
  background: #fdfaf0;
  border: 1px solid #faeec7;
  padding: 20px;
  border-radius: 10px;
  margin-bottom: 20px;
}

.notice-box p {
  margin: 5px 0;
  color: #5f4b08;
  font-size: 0.95rem;
  line-height: 1.5;
}

.sub-msg {
  color: #666;
  margin-bottom: 30px;
  font-weight: 500;
}

.contact-btn {
  background: #bd9b2c;
  color: white;
  border: none;
  padding: 12px 35px;
  border-radius: 30px;
  font-weight: 700;
  font-size: 1rem;
  cursor: pointer;
  transition: 0.3s;
}

.contact-btn:hover {
  background: #a48424;
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(189, 155, 44, 0.3);
}
</style>
