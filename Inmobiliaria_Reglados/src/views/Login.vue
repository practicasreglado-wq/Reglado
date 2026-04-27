<!--
  Pantalla de "redirección al login externo".

  El login real ocurre en GrupoReglado (servicio Vue separado en
  VITE_GRUPO_REGLADO_BASE_URL). Esta vista solo construye la URL con
  buildExternalAuthUrl() y redirige; cuando el usuario se autentica allí,
  vuelve a /auth/callback con el JWT (ver AuthCallback.vue).
-->
<template>
  <div class="auth-redirect">
    <div class="overlay"></div>

    <div class="card">
      <h2>Debes iniciar sesión</h2>
      <p>
        Para continuar, accede con tu cuenta a través de Grupo Reglado.
      </p>

      <button class="auth-btn" type="button" @click="goToGroupLogin">
        Iniciar sesión
      </button>
    </div>
  </div>
</template>

<script setup>
import { buildExternalAuthUrl } from "../services/backend";
import { clearAllAuthArtifacts } from "../services/auth";

function goToGroupLogin() {
  // Limpieza agresiva antes de salir hacia el login externo, para garantizar
  // que el usuario no vuelva con la sesión antigua aún activa.
  clearAllAuthArtifacts();
  const loginPath = import.meta.env.VITE_GRUPO_REGLADO_LOGIN_PATH || "/login";
  window.location.href = buildExternalAuthUrl(loginPath);
}
</script>

<style scoped>
.auth-redirect {
  position: relative;
  min-height: 100vh;
  background-image: url("@/assets/fondito.png");
  background-size: cover;
  background-position: center;
  display: grid;
  place-items: center;
}

.overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.35);
}

.card {
  position: relative;
  z-index: 1;
  width: min(420px, calc(100% - 32px));
  background: rgba(255, 255, 255, 0.96);
  border-radius: 16px;
  padding: 32px;
  text-align: center;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.18);
}

.card h2 {
  margin: 0 0 12px;
  color: #24386b;
}

.card p {
  margin: 0;
  color: #334155;
  line-height: 1.5;
}

.auth-btn {
  margin-top: 20px;
  width: 100%;
  padding: 14px 18px;
  border: none;
  border-radius: 10px;
  background: #24386b;
  color: #fff;
  font-weight: 700;
  cursor: pointer;
}
</style>