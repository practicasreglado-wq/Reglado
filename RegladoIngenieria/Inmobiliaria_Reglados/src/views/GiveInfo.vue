<template>
  <div class="asset-info-wrapper">
    <div class="bg-decoration"></div>

    <div class="asset-info-content">
      <header class="asset-header">
        <span class="tag">Servicio Exclusivo</span>
        <h1>Aportación de <span class="gold-text">Activos Singulares</span></h1>
        <p class="asset-subtitle">
          Integre su propiedad en nuestro ecosistema privado de inversión. 
          Un entorno donde la discreción es la norma y la eficacia el resultado.
        </p>
      </header>

      <div class="asset-features">
        <div class="feature-item">
          <div class="feature-icon">01</div>
          <h3>Privacidad Blindada</h3>
          <p>Gestión estricta bajo NDA. Usted decide qué información es pública y qué información es sensible.</p>
        </div>
        <div class="feature-item">
          <div class="feature-icon">02</div>
          <h3>Inversores Verificados</h3>
          <p>Filtramos cada petición de compra para asegurar que su activo solo sea visualizado por perfiles con interés real.</p>
        </div>
        <div class="feature-item">
          <div class="feature-icon">03</div>
          <h3>Análisis Técnico</h3>
          <p>Evaluamos el potencial de su activo para optimizar su posición en el mercado off-market.</p>
        </div>
      </div>

      <div class="asset-action-card">
        <div class="card-inner">
          <div class="card-text">
            <h2>¿Listo para empezar?</h2>
            <p>Acceda a su panel para completar la ficha técnica del activo.</p>
          </div>
          <button class="asset-login-btn" @click="$router.push('/login')">
            Iniciar Sesión
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Contenedor Principal */
.asset-info-wrapper {
  min-height: 100vh;
  padding-top: 120px;
  padding-bottom: 60px;
  background-color: #f8f9fa; /* Gris muy claro, limpio */
  display: flex;
  justify-content: center;
  position: relative;
  overflow: hidden;
  font-family: 'Inter', sans-serif;
}

/* Decoración geométrica sutil (estilo boutique) */
.bg-decoration {
  position: absolute;
  top: -10%;
  right: -5%;
  width: 500px;
  height: 500px;
  background: radial-gradient(circle, rgba(212, 175, 55, 0.05) 0%, rgba(255,255,255,0) 70%);
  z-index: 0;
}

.asset-info-content {
  position: relative;
  z-index: 1;
  width: 90%;
  max-width: 1100px;
}

/* Header */
.asset-header {
  text-align: center;
  margin-bottom: 70px;
}

.tag {
  color: #d4af37;
  text-transform: uppercase;
  letter-spacing: 3px;
  font-size: 0.8rem;
  font-weight: 700;
}

.asset-header h1 {
  font-size: 3rem;
  color: #1a2b4e; /* Tu azul oscuro */
  margin: 15px 0;
  font-weight: 800;
}

.gold-text {
  color: #d4af37;
}

.asset-subtitle {
  font-size: 1.2rem;
  color: #555;
  max-width: 700px;
  margin: 0 auto;
  line-height: 1.6;
}

/* Cuadrícula de características */
.asset-features {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 30px;
  margin-bottom: 60px;
}

.feature-item {
  background: white;
  padding: 40px 30px;
  border-radius: 15px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.03);
  transition: transform 0.3s ease;
  border: 1px solid #eee;
}

.feature-item:hover {
  transform: translateY(-5px);
  border-color: #d4af37;
}

.feature-icon {
  font-size: 0.9rem;
  font-weight: 800;
  color: #d4af37;
  margin-bottom: 20px;
  opacity: 0.5;
}

.feature-item h3 {
  color: #1a2b4e;
  margin-bottom: 15px;
  font-size: 1.2rem;
}

.feature-item p {
  font-size: 0.95rem;
  color: #777;
  line-height: 1.5;
}

/* Tarjeta de Acción Final */
.asset-action-card {
  background: #1a2b4e; /* Tu azul principal */
  border-radius: 20px;
  padding: 50px;
  color: white;
  box-shadow: 0 20px 40px rgba(26, 43, 78, 0.2);
}

.card-inner {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-text h2 {
  font-size: 2rem;
  margin-bottom: 10px;
}

.card-text p {
  opacity: 0.8;
  font-size: 1.1rem;
}

.asset-login-btn {
  background: #d4af37;
  color: white;
  border: none;
  padding: 18px 45px;
  border-radius: 10px;
  font-size: 1rem;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.3s ease;
  text-transform: uppercase;
}

.asset-login-btn:hover {
  background: #b8972f;
}

/* Responsive */
@media (max-width: 900px) {
  .asset-features {
    grid-template-columns: 1fr;
  }
  .card-inner {
    flex-direction: column;
    text-align: center;
    gap: 30px;
  }
  .asset-header h1 {
    font-size: 2.2rem;
  }
}
</style>