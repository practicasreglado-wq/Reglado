<template>
  <div class="metodologia">
    <div class="overlay"></div>
    
    <div class="metodologia-texto">
      <h1>
        Nuestra <span class="highlight">Metodología</span>
      </h1>
      <p>
        En Fiscal Protect, nuestra intervención está diseñada para ofrecer una defensa eficiente, técnica y orientada a resultados en cada fase del proceso tributario.
      </p>
      
      <div class="resumen-metodo">
        <p>
          Protegemos tus intereses y maximizamos los resultados favorables mediante un análisis exhaustivo y una estrategia jurídica sólida.
        </p>
      </div>
    </div>

    <div class="metodologia-card">
      <h2>Proceso de Intervención</h2>
      
      <div class="pasos-container">
        <div class="paso-item">
          <div class="paso-header">
            <span class="dot"></span>
            <h3>Diagnóstico Exprés</h3>
          </div>
          <p>Revisión del acto administrativo, plazos, exposición económica y objetivos de éxito.</p>
        </div>

        <div class="paso-item">
          <div class="paso-header">
            <span class="dot"></span>
            <h3>Estrategia Jurídica</h3>
          </div>
          <p>Identificación de mapa de riesgos, preparación de pruebas y definición de la línea de defensa.</p>
        </div>

        <div class="paso-item">
          <div class="paso-header">
            <span class="dot"></span>
            <h3>Defensa Activa</h3>
          </div>
          <p>Gestión de requerimientos, presentación de alegaciones y negociaciones técnicas en fase inspectora.</p>
        </div>

        <div class="paso-item">
          <div class="paso-header">
            <span class="dot"></span>
            <h3>Impugnación</h3>
          </div>
          <p>Recursos y vía contencioso-administrativa para anular actos administrativos y sanciones.</p>
        </div>

        <div class="paso-item">
          <div class="paso-header">
            <span class="dot"></span>
            <h3>Cierre y Prevención</h3>
          </div>
          <p>Informe ejecutivo de resultados y recomendaciones para evitar futuras reincidencias.</p>
        </div>
      </div>

      <button class="cta-btn" @click="$router.push('/contacto')">Solicitar Asesoría</button>
    </div>

    <div class="info-footer">
      Defensa integral orientada a proteger su patrimonio.
    </div>
  </div>
</template>

<script>
export default {
  name: "Metodologia",
  data() {
    return {
      // Puedes añadir lógica de estados aquí si deseas expandir los textos
    };
  }
};
</script>

<style scoped>
.metodologia {
  position: relative;
  min-height: calc(100vh - 90px);
  margin-top: 90px;
  background-color: var(--gris-claro);
  /* Asegúrate de tener esta imagen o cambiar la ruta */
  background-image: url('@/assets/aboutimg.jpg'); 
  background-size: cover;
  background-position: center;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 100px 0 80px;
  gap: 60px;
}

.overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.5); /* Un poco más oscuro para legibilidad */
  z-index: 0;
}

.metodologia-texto,
.metodologia-card {
  position: relative;
  z-index: 1;
}

/* ----------- TEXTO IZQUIERDA ----------- */
.metodologia-texto {
  color: white;
  max-width: 550px;
  transform: translateY(-20px);
}

.metodologia-texto h1 {
  font-size: 3.5rem;
  line-height: 1.1;
  margin-bottom: 25px;
}

.highlight {
  color: #d4af37; /* Dorado */
}

.metodologia-texto p {
  font-size: 1.4rem;
  line-height: 1.6;
  opacity: 0.9;
}

.resumen-metodo {
  margin-top: 30px;
  border-left: 4px solid #d4af37;
  padding-left: 20px;
}

/* ----------- TARJETA DERECHA (PASOS) ----------- */
.metodologia-card {
  width: 550px;
  background: rgba(255, 255, 255, 0.98);
  padding: 35px;
  border-radius: 20px;
  box-shadow: 0 15px 40px rgba(0,0,0,0.3);
}

h2 {
  margin-bottom: 30px;
  font-size: 2rem;
  color: #111;
  text-align: center;
}

.pasos-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
  margin-bottom: 30px;
}

.paso-item {
  border-bottom: 1px solid #eee;
  padding-bottom: 10px;
}

.paso-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 5px;
}

.paso-header h3 {
  font-size: 1.1rem;
  color: var(--azul-principal);
  font-weight: bold;
  margin: 0;
}

.dot {
  width: 10px;
  height: 10px;
  background-color: #d4af37;
  border-radius: 50%;
  flex-shrink: 0;
}

.paso-item p {
  font-size: 0.95rem;
  color: #444;
  margin-left: 22px;
  line-height: 1.4;
}

/* Botón de acción */
.cta-btn {
  width: 100%;
  font-size: 1.1rem;
  background-color: var(--azul-principal);
  color: white;
  padding: 12px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: 0.3s ease;
  font-weight: bold;
}

.cta-btn:hover {
  background-color: var(--azul-secundario);
  transform: translateY(-2px);
}

.info-footer {
  position: absolute;
  bottom: 30px;
  left: 80px;
  color: rgba(255, 255, 255, 0.7);
  font-size: 1rem;
  z-index: 2;
}

/* Responsive básico */
@media (max-width: 1024px) {
  .metodologia {
    flex-direction: column;
    padding: 40px 20px;
    text-align: center;
  }
  .metodologia-texto {
    transform: none;
    margin-bottom: 40px;
  }
  .metodologia-card {
    width: 100%;
    max-width: 500px;
  }
  .resumen-metodo {
    border-left: none;
    border-top: 2px solid #d4af37;
    padding: 20px 0 0 0;
  }
}
</style>