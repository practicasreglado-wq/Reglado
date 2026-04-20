<template>
  <div class="auth-redirect">
    <div class="overlay"></div>
    <div class="card">
      <h2>Iniciar sesión</h2>
      <p>Te estamos redirigiendo a Grupo Reglado para acceder con tu cuenta.</p>
      <button class="auth-btn" type="button" @click="goNow">
        Iniciar sesión
      </button>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from "vue";
import { buildExternalAuthUrl } from "../services/backend";

function goNow() {
  const loginPath = import.meta.env.VITE_GRUPO_REGLADO_LOGIN_PATH || "/login";
  window.location.href = buildExternalAuthUrl(loginPath);
}

onMounted(() => {
  goNow();
});
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
