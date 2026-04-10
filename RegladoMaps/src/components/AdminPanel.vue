<template>
  <div class="admin-panel">
    <div class="admin-card">
      <h1 class="h1">Panel de Administrador</h1>
      <p class="desc">Aquí podrás gestionar usuarios, licencias y parámetros avanzados de Reglado Maps. Este panel actualmente se encuentra en desarrollo.</p>
      
      <div class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="empty-icon" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <p>No hay funcionalidades habilitadas todavía.</p>
      </div>

      <router-link to="/" class="btn-primary">Volver al Mapa</router-link>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';

onMounted(() => {
  document.title = 'Panel de Administrador | Reglado Maps';
});
</script>

<style scoped>
.admin-panel {
  min-height: 100vh;
  width: 100vw;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  background: #0b0d10;
  color: #fff;
  font-family: 'Outfit', sans-serif;
  padding: 120px 20px 60px 20px;
  box-sizing: border-box;
}

.admin-card {
  max-width: 600px;
  width: 100%;
  background: rgba(255, 255, 255, 0.03);
  padding: 3rem;
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 10px 40px rgba(0,0,0,0.5);
  backdrop-filter: blur(8px);
  text-align: center;
}

.admin-card .h1 {
  font-size: 2rem;
  margin-bottom: 1rem;
  color: #00e5ff;
  font-weight: 700;
}

.desc {
  color: rgba(255, 255, 255, 0.7);
  line-height: 1.6;
  margin-bottom: 2rem;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 3rem;
  background: rgba(255, 255, 255, 0.02);
  border-radius: 12px;
  margin-bottom: 2.5rem;
}

.empty-icon {
  width: 48px;
  height: 48px;
  color: rgba(255, 255, 255, 0.3);
}

.empty-state p {
  color: rgba(255, 255, 255, 0.5);
  font-size: 0.95rem;
}

.btn-primary {
  display: inline-block;
  background: #00c47d;
  color: #fff;
  text-decoration: none;
  padding: 0.8rem 2rem;
  border-radius: 2rem;
  font-weight: 600;
  transition: all 0.3s ease;
}

.btn-primary:hover {
  background: #00e5ff;
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(0, 229, 255, 0.3);
  color: #111;
}

@media (max-width: 600px) {
  .admin-card {
    padding: 2rem 1.5rem;
  }
}
</style>
