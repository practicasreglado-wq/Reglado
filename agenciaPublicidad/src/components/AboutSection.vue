
<!-- 
  Sección informativo "Sobre Nosotros".
-->
<template>
  <section id="sobre-nosotros" class="about">
    <div class="container about-container">
      <div class="about-image">
        <div class="image-stack">
          <div class="image-main">
            <img src="https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&q=80&w=800" alt="Creative Agency">
          </div>
          <div class="image-accent glass">
            <p><strong>+5 años</strong> liderando el mercado de influencers.</p>
          </div>
        </div>
      </div>
      <div class="about-content">
        <span class="tag">Nuestra Misión</span>
        <h2>Transformamos Potencial en Influencia Real</h2>
        <p>En nuestra agencia, no solo buscamos seguidores; buscamos impacto. Entendemos el ecosistema digital como un espacio de oportunidades ilimitadas para creadores con visión.</p>
        <p>Nuestro equipo de expertos en marketing y producción trabaja codo a codo contigo para refinar tu voz, pulir tu estética y conectarte con las marcas que importan.</p>
        <ul class="benefit-list">
          <li><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> Estrategia personalizada por perfil.</li>
          <li><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> Acceso a campañas exclusivas.</li>
          <li><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> Soporte técnico y creativo 24/7.</li>
        </ul>
      </div>
    </div>
  </section>
</template>

<style scoped>
.about {
  background-color: #f8faff;
}

.about-container {
  display: flex;
  align-items: center;
  gap: 5rem;
  flex-wrap: wrap;
}

.about-image {
  flex: 1;
  min-width: 350px;
}

.image-stack {
  position: relative;
  padding-bottom: 50px;
}

.image-main img {
  border-radius: 30px;
  box-shadow: 0 20px 40px rgba(0,0,0,0.1);
}

.image-accent {
  position: absolute;
  bottom: 0;
  right: -20px;
  padding: 2rem;
  border-radius: 20px;
  max-width: 200px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.05);
}

.about-content {
  flex: 1.2;
  min-width: 350px;
}

.tag {
  color: var(--primary);
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 2px;
  font-size: 0.9rem;
  display: block;
  margin-bottom: 1rem;
}

.about-content h2 {
  font-size: 3rem;
  margin-bottom: 2rem;
}

.about-content p {
  color: var(--text-muted);
  font-size: 1.1rem;
  margin-bottom: 1.5rem;
}

.benefit-list {
  margin-top: 2rem;
}

.benefit-list li {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
  font-weight: 600;
  color: var(--black);
}

.benefit-list li svg {
  color: var(--primary);
}

@media (max-width: 992px) {
  .about-container {
    gap: 3rem;
  }
}
</style>
