<template>
  <section v-if="error" class="auth-section">
    <div class="auth-card">
      <h2>Acceso no disponible</h2>
      <p>{{ error }}</p>
      <router-link to="/" class="btn-return">Volver al inicio</router-link>
    </div>
  </section>
  <section v-else class="auth-section">
    <div class="auth-card">
      <h2>Iniciando sesión...</h2>
      <p>Validando credenciales, por favor espera.</p>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { auth } from "../services/auth";

const route = useRoute();
const router = useRouter();
const error = ref("");

onMounted(async () => {
  const token = typeof route.query.token === "string" ? route.query.token : "";

  if (!token) {
    error.value = "No se encontró el token de acceso seguro.";
    return;
  }

  try {
    auth.setSession(token, null);
    await auth.initialize();

    if (!auth.state.user) {
      throw new Error("No se pudo validar la sesión correctamente en el servidor.");
    }

    // Auth OK, go back home
    await router.replace("/");
  } catch (err) {
    auth.clearSession();
    error.value = err instanceof Error ? err.message : "No se pudo iniciar sesión ni verificar tus credenciales.";
  }
});
</script>

<style scoped>
.auth-section {
  min-height: 100vh;
  width: 100vw;
  display: flex;
  justify-content: center;
  align-items: center;
  background: #0b0d10;
  color: #fff;
  font-family: 'Outfit', sans-serif;
}
.auth-card {
  max-width: 480px;
  width: 90%;
  text-align: center;
  background: rgba(255, 255, 255, 0.05);
  padding: 3rem 2rem;
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 10px 40px rgba(0,0,0,0.5);
  backdrop-filter: blur(8px);
}
.auth-card h2 {
  font-size: 1.8rem;
  margin-bottom: 1rem;
  color: #00e5ff;
}
.auth-card p {
  color: rgba(255,255,255,0.7);
  margin-bottom: 2rem;
  font-size: 1.1rem;
}
.btn-return {
  display: inline-block;
  padding: 0.8rem 1.8rem;
  background: rgba(0, 196, 125, 0.2);
  color: #00c47d;
  text-decoration: none;
  border-radius: 2rem;
  border: 1px solid rgba(0, 196, 125, 0.5);
  transition: all 0.3s ease;
  font-weight: 600;
}
.btn-return:hover {
  background: rgba(0, 196, 125, 0.4);
  color: #fff;
  transform: translateY(-2px);
}
</style>
