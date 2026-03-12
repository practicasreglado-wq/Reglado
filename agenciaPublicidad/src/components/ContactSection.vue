
<!-- 
  Sección de contacto con formulario.
  Configurar para que llegue a una direccion de correo electronico:
  1. Usar un servicio como EmailJS o Formspree
  2. Servidor propio con PHP
  3. Configurar que no lleguen a Bandeja Spam
-->
<template>
  <section id="contacto" class="contact">
    <div class="container">
      <div class="contact-card glass">
        <div class="contact-info">
          <h2>¿Listo para Despegar?</h2>
          <p>Únete a la élite de creadores de contenido. Analizamos tu perfil y te damos las herramientas para triunfar.</p>
          <div class="info-list">
            <div class="info-item">
              <span class="icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
              </span>
              <span>Madrid, España</span>
            </div>
            <div class="info-item">
              <span class="icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
              </span>
              <span>hola@agenciapublicidad.com</span>
            </div>
            <div class="info-item">
              <span class="icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              </span>
              <span>+34 600 000 000</span>
            </div>
          </div>
        </div>
        <form class="contact-form" @submit.prevent>
          <div class="form-group">
            <label>Nombre Completo</label>
            <input type="text" placeholder="Tu nombre">
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" placeholder="correo@ejemplo.com">
          </div>
          <div class="form-group">
            <label>Usuario / Red Social</label>
            <input type="text" placeholder="@tuusuario/RedSocial">
          </div>
          <div class="form-group">
            <label>Mensaje (Opcional)</label>
            <textarea placeholder="Cuéntanos un poco sobre tí y lo que quieres mejorar..."></textarea>
          </div>
          <button type="submit" class="btn-primary">Enviar Solicitud</button>
        </form>
      </div>
    </div>
  </section>
</template>

<style scoped>
.contact {
  background-image: radial-gradient(circle at 20% 20%, rgba(70, 137, 246, 0.05) 0%, transparent 50%),
                    radial-gradient(circle at 80% 80%, rgba(192, 192, 192, 0.1) 0%, transparent 50%);
}

.contact-card {
  display: grid;
  grid-template-columns: 1fr 1.5fr;
  border-radius: 40px;
  overflow: hidden;
  box-shadow: 0 30px 60px rgba(0,0,0,0.1);
}

.contact-info {
  background-color: var(--primary);
  color: var(--white);
  padding: 4rem;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.contact-info h2 {
  color: var(--white);
  font-size: 2.5rem;
  margin-bottom: 1.5rem;
}

.contact-info p {
  opacity: 0.9;
  font-size: 1.1rem;
  margin-bottom: 3rem;
}

.info-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  font-weight: 500;
}

.icon {
  width: 45px;
  height: 45px;
  background-color: var(--primary-dark);
  color: var(--white);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.icon svg {
  width: 20px;
  height: 20px;
}

.contact-form {
  padding: 4rem;
  background: white;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: var(--black);
}

.form-group input, 
.form-group textarea {
  width: 100%;
  padding: 1rem;
  border: 2px solid var(--silver-light);
  border-radius: 12px;
  font-family: inherit;
  transition: border-color 0.3s ease;
}

.form-group input:focus, 
.form-group textarea:focus {
  outline: none;
  border-color: var(--primary);
}

.form-group textarea {
  height: 120px;
  resize: none;
}

.btn-primary {
  width: 100%;
  font-size: 1.1rem;
  padding: 1rem;
}

@media (max-width: 992px) {
  .contact-card {
    grid-template-columns: 1fr;
  }
  .contact-info {
    padding: 3rem;
  }
  .contact-form {
    padding: 3rem;
  }
}
</style>
