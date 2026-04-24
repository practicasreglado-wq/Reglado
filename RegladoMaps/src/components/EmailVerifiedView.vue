<template>
  <section class="auth-page">
    <div class="auth-card narrow center">
      <div class="badge">Verificación</div>
      <h1>Verificación de correo</h1>

      <p v-if="loading" class="lead">Validando acceso…</p>

      <template v-else-if="error">
        <p class="feedback error">{{ error }}</p>
        <router-link class="btn-primary" to="/">Ir al inicio</router-link>
      </template>

      <template v-else>
        <p class="feedback success">Se ha verificado tu correo correctamente.</p>
        <p class="lead">Serás redirigido en {{ countdown }} segundos…</p>
        <router-link class="btn-primary" to="/">Ir ahora</router-link>
      </template>
    </div>
  </section>
</template>

<script>
import { auth } from "../services/auth";
import { redirectToStore } from "../services/ssoClient";

export default {
  name: "EmailVerifiedView",
  data() {
    return {
      loading: true,
      error: "",
      countdown: 5,
      countdownInterval: null,
      redirectTimeout: null,
    };
  },
  async mounted() {
    const token = typeof this.$route.query.token === "string" ? this.$route.query.token : "";

    if (!token) {
      this.loading = false;
      this.error = "No se encontró el token de verificación.";
      return;
    }

    try {
      auth.setSession(token, null);
      await auth.initialize();

      if (!auth.state.user) {
        throw new Error("No se pudo iniciar la sesión con el token recibido.");
      }

      this.startAutoRedirect();
    } catch (err) {
      auth.clearSession();
      this.error = err instanceof Error ? err.message : "No se pudo completar la verificación";
    } finally {
      this.loading = false;
    }
  },
  beforeUnmount() {
    if (this.countdownInterval) clearInterval(this.countdownInterval);
    if (this.redirectTimeout) clearTimeout(this.redirectTimeout);
  },
  methods: {
    startAutoRedirect() {
      this.countdownInterval = setInterval(() => {
        if (this.countdown > 1) this.countdown -= 1;
      }, 1000);

      this.redirectTimeout = setTimeout(() => {
        // Propaga la sesión recién verificada al hub antes de enviar al home.
        redirectToStore(auth.state.token, window.location.origin + "/");
      }, 5000);
    },
  },
};
</script>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 120px 20px 60px;
  background: radial-gradient(ellipse at top, rgba(0, 229, 255, 0.08) 0%, transparent 60%), #02060f;
  color: #e9eef6;
}

.auth-card {
  width: 100%;
  max-width: 520px;
  background: rgba(10, 15, 25, 0.92);
  border: 1px solid rgba(0, 229, 255, 0.28);
  border-radius: 20px;
  padding: 36px 40px;
  box-shadow: 0 28px 60px rgba(0, 0, 0, 0.5), 0 0 22px rgba(0, 229, 255, 0.08);
}

.auth-card.center { text-align: center; }

.badge {
  display: inline-block;
  padding: 4px 12px;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #00E5FF;
  background: rgba(0, 229, 255, 0.1);
  border: 1px solid rgba(0, 229, 255, 0.3);
  border-radius: 999px;
  margin-bottom: 16px;
}

h1 {
  margin: 0 0 12px;
  font-size: 26px;
  color: #ffffff;
}

.lead {
  margin: 12px 0;
  color: rgba(233, 238, 246, 0.7);
  font-size: 15px;
}

.feedback {
  margin: 14px 0;
  padding: 12px 14px;
  border-radius: 10px;
  font-size: 14px;
  border: 1px solid transparent;
}

.feedback.error {
  color: #ffb7b7;
  background: rgba(183, 28, 28, 0.18);
  border-color: rgba(255, 183, 183, 0.25);
}

.feedback.success {
  color: #b7ffc7;
  background: rgba(28, 183, 87, 0.15);
  border-color: rgba(183, 255, 199, 0.25);
}

.btn-primary {
  display: inline-block;
  padding: 12px 24px;
  background: linear-gradient(135deg, #00E5FF 0%, #0288D1 100%);
  color: #001a2e;
  border: none;
  border-radius: 10px;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.2s;
  font-family: inherit;
  margin-top: 8px;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 229, 255, 0.35);
}
</style>
