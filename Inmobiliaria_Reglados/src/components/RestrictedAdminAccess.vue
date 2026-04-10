<template>
  <div class="restricted-admin">
    <div class="restricted-admin__card">
      <div class="icon-header">
        <svg viewBox="0 0 24 24" width="60" height="60" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke-linecap="round" stroke-linejoin="round"/>
          <line x1="12" y1="8" x2="12" y2="12" stroke-linecap="round" stroke-linejoin="round"/>
          <line x1="12" y1="16" x2="12.01" y2="16" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <h2>Acceso restringido</h2>
      <p class="main-msg">Esta sección es solo para administradores.</p>
      <p class="sub-msg">Si crees que esto es un error, por favor contacta con el soporte técnico o con un administrador del sistema.</p>
      
      <div class="actions">
        <button class="btn btn--primary" @click="$router.push('/')">Volver al inicio</button>
        <button class="btn btn--secondary" @click="$router.push('/contacto')">Contactar soporte</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.restricted-admin {
  height: calc(100vh - 80px);
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #f1f5f9;
  padding: 20px;
}

.restricted-admin__card {
  background: white;
  padding: 50px 40px;
  border-radius: 24px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.08);
  text-align: center;
  max-width: 500px;
  width: 100%;
}

.icon-header {
  color: #ef4444;
  margin-bottom: 30px;
}

h2 {
  font-size: 2rem;
  color: #1e293b;
  margin: 0 0 15px 0;
}

.main-msg {
  font-size: 1.2rem;
  font-weight: 600;
  color: #475569;
  margin-bottom: 10px;
}

.sub-msg {
  color: #94a3b8;
  line-height: 1.6;
  margin-bottom: 35px;
}

.actions {
  display: flex;
  gap: 15px;
  justify-content: center;
}

.btn {
  padding: 12px 25px;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn--primary {
  background: #1e293b;
  color: white;
  border: none;
}

.btn--secondary {
  background: white;
  color: #1e293b;
  border: 1px solid #e2e8f0;
}

.btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

@media (max-width: 480px) {
  .actions {
    flex-direction: column;
  }
}
</style>
