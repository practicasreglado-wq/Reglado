<template>
  <section class="equipo-hero">
    <div class="overlay"></div>

    <div class="equipo-texto">
      <template v-if="!user">
        <h1>
          Nuestro <span class="highlight">Compromiso</span>
        </h1>
        <p>
          En Fiscal Protect, la especialización técnica y la agilidad organizativa son los pilares que garantizan la seguridad de nuestros clientes.
        </p>
      </template>

      <template v-else>
        <h1>
          <span class="highlight">Hola, {{ user.nombre }}</span>
        </h1>
        <p>
          Estás respaldado por un equipo de expertos jurídicos y administrativos dedicados exclusivamente a proteger tus intereses tributarios.
        </p>
      </template>
    </div>

    <div class="equipo-card">
      <h2>El Equipo</h2>
      
      <div class="equipo-descripcion">
        <p>
          Contamos con un equipo altamente especializado, compuesto por profesionales con amplia experiencia en el ámbito fiscal y tributario. 
        </p>
        <p>
          Nuestra estructura está dirigida por perfiles con una sólida formación jurídica, respaldados por un soporte administrativo que garantiza una gestión eficiente de cada caso.
        </p>
        <p>
          Utilizamos tecnología avanzada de gestión documental para asegurar una intervención puntual y precisa en todas las fases del proceso tributario.
        </p>
      </div>

      <div class="stats-container">
        <div class="stat-item">
          <span class="dot"></span>
          <p><strong>Especialización</strong> técnica avanzada</p>
        </div>
        <div class="stat-item">
          <span class="dot"></span>
          <p><strong>Comunicación</strong> constante y directa</p>
        </div>
        <div class="stat-item">
          <span class="dot"></span>
          <p><strong>Organización</strong> ágil y flexible</p>
        </div>
      </div>

      <button @click="$router.push('/contacto')" class="btn-accion">
        Hablar con un experto
      </button>
    </div>

    <div class="login-link" v-if="!user">
      ¿Deseas una gestión personalizada? 
      <span @click="$router.push('/login')">Acceder al panel</span>
    </div>
  </section>
</template>

<script>
export default {
  name: "Equipo",
  data() {
    return {
      user: null
    };
  },
  mounted() {
    const userData = localStorage.getItem("user");
    if (userData) {
      this.user = JSON.parse(userData);
    }
  }
};
</script>

<style scoped>
.equipo-hero {
  position: relative;
  min-height: calc(100vh - 90px);
  margin-top: 90px;
  background-image: url('@/assets/aboutimg.jpg'); /* Usamos la imagen del edificio de fondo */
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
  background: rgba(0, 0, 0, 0.6); /* Overlay algo más oscuro para que el texto resalte */
  z-index: 0;
}

.equipo-texto,
.equipo-card {
  position: relative;
  z-index: 1;
}

/* ----------- TEXTO IZQUIERDA ----------- */
.equipo-texto {
  color: white;
  max-width: 550px;
  transform: translateY(-50px);
}

.equipo-texto h1 {
  font-family: 'Playfair Display', serif;
  font-size: 4.5rem;
  margin-bottom: 25px;
  line-height: 1.1;
}

.highlight {
  color: #d4af37;
}

.equipo-texto p {
  font-size: 1.8rem;
  line-height: 1.4;
  opacity: 0.95;
}

/* ----------- TARJETA DERECHA ----------- */
.equipo-card {
  width: 600px;
  background: rgba(255, 255, 255, 0.98);
  padding: 50px;
  border-radius: 15px;
  box-shadow: 0 20px 50px rgba(0,0,0,0.3);
}

.equipo-card h2 {
  font-size: 2.5rem;
  color: #111;
  margin-bottom: 25px;
  font-weight: 700;
}

.equipo-descripcion p {
  font-size: 1.05rem;
  color: #444;
  line-height: 1.6;
  margin-bottom: 15px;
}

.stats-container {
  margin: 30px 0;
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 15px;
}

.stat-item p {
  margin: 0;
  font-size: 1.1rem;
  color: #222;
}

.dot {
  width: 12px;
  height: 12px;
  background-color: #d4af37;
  border-radius: 50%;
  flex-shrink: 0;
}

.btn-accion {
  width: 100%;
  padding: 15px;
  background-color: var(--azul-principal);
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 1.1rem;
  font-weight: 600;
  cursor: pointer;
  transition: 0.3s ease;
}

.btn-accion:hover {
  background-color: #003366;
  transform: translateY(-2px);
}

/* Enlace inferior */
.login-link {
  position: absolute;
  bottom: 40px;
  left: 80px;
  color: white;
  font-size: 1.2rem;
  z-index: 2;
}

.login-link span {
  color: #d4af37;
  cursor: pointer;
  font-weight: bold;
  margin-left: 10px;
}

/* Responsive */
@media (max-width: 1200px) {
  .equipo-hero {
    flex-direction: column;
    padding: 120px 20px 60px;
    height: auto;
    text-align: center;
  }
  .equipo-texto {
    transform: none;
  }
  .login-link {
    position: static;
    margin-top: 30px;
  }
}
</style>