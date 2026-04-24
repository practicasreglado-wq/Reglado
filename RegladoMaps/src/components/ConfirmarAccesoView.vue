<template>
  <section class="auth-page">
    <div class="auth-card narrow center">
      <div class="status-icon" :class="iconClass" aria-hidden="true">
        <svg viewBox="0 0 24 24" v-if="state === 'loading'">
          <circle cx="12" cy="12" r="10" stroke-dasharray="50" stroke-dashoffset="25" />
        </svg>
        <svg viewBox="0 0 24 24" v-else-if="state === 'confirmed'">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
          <polyline points="22 4 12 14.01 9 11.01" />
        </svg>
        <svg viewBox="0 0 24 24" v-else-if="state === 'rejected'">
          <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
          <line x1="12" y1="9" x2="12" y2="13" />
          <line x1="12" y1="17" x2="12.01" y2="17" />
        </svg>
        <svg viewBox="0 0 24 24" v-else-if="state === 'expired'">
          <circle cx="12" cy="12" r="10" />
          <polyline points="12 6 12 12 16 14" />
        </svg>
        <svg viewBox="0 0 24 24" v-else>
          <circle cx="12" cy="12" r="10" />
          <line x1="12" y1="8" x2="12" y2="13" />
          <line x1="12" y1="16" x2="12.01" y2="16" />
        </svg>
      </div>

      <h1>{{ title }}</h1>
      <p class="description">{{ description }}</p>

      <router-link v-if="state !== 'loading'" class="btn-primary" to="/">
        Ir al inicio
      </router-link>
    </div>
  </section>
</template>

<script>
import { auth } from "../services/auth";

const TITLES = {
  loading: "Procesando…",
  confirmed: "¡Gracias por confirmar!",
  rejected: "Sesión cerrada por seguridad",
  expired: "Enlace expirado",
  invalid: "Enlace no válido",
  error: "No hemos podido procesarlo",
};

const DESCRIPTIONS = {
  loading: "Estamos registrando tu respuesta. Un momento…",
  confirmed: "Hemos registrado que reconoces este inicio de sesión. Tu cuenta sigue protegida y no necesitas hacer nada más.",
  rejected: "Hemos cerrado la sesión sospechosa. La próxima vez que accedas a tu cuenta te pediremos cambiar la contraseña y recibirás un correo con las instrucciones.",
  expired: "Este enlace ya fue usado o ha caducado. Si recibiste un aviso más reciente, abre ese email en su lugar.",
  invalid: "El enlace que has abierto no es correcto. Revisa el correo y vuelve a intentarlo desde el enlace que te enviamos.",
  error: "Hubo un problema al contactar con el servidor. Inténtalo de nuevo en unos minutos.",
};

export default {
  name: "ConfirmarAccesoView",
  data() {
    return {
      state: "loading",
    };
  },
  computed: {
    iconClass() {
      return {
        "icon-loading": this.state === "loading",
        "icon-success": this.state === "confirmed",
        "icon-danger": this.state === "rejected",
        "icon-warning": ["expired", "invalid", "error"].includes(this.state),
      };
    },
    title() { return TITLES[this.state] || "Confirmación de acceso"; },
    description() { return DESCRIPTIONS[this.state] || ""; },
  },
  async mounted() {
    const token = typeof this.$route.query.token === "string" ? this.$route.query.token : "";
    const decision = typeof this.$route.query.decision === "string" ? this.$route.query.decision : "";

    if (!token || !["me", "not-me"].includes(decision)) {
      this.state = "invalid";
      return;
    }

    try {
      const res = await auth.confirmLoginLocation(token, decision);
      const allowed = ["confirmed", "rejected", "expired", "invalid"];
      this.state = allowed.includes(res?.state) ? res.state : "error";
    } catch {
      this.state = "error";
    }
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

.status-icon {
  display: inline-flex;
  width: 72px;
  height: 72px;
  border-radius: 50%;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
}

.status-icon svg {
  width: 36px;
  height: 36px;
  stroke: currentColor;
  stroke-width: 2;
  stroke-linecap: round;
  stroke-linejoin: round;
  fill: none;
}

.icon-loading {
  background: rgba(255, 255, 255, 0.08);
  color: rgba(233, 238, 246, 0.75);
}

.icon-loading svg { animation: spin 1s linear infinite; }

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.icon-success {
  background: rgba(28, 183, 87, 0.18);
  color: #4ade80;
}

.icon-danger {
  background: rgba(220, 38, 38, 0.22);
  color: #f87171;
}

.icon-warning {
  background: rgba(0, 229, 255, 0.18);
  color: #00E5FF;
}

h1 {
  margin: 0 0 12px;
  font-size: 24px;
  color: #ffffff;
}

.description {
  color: rgba(233, 238, 246, 0.75);
  line-height: 1.6;
  margin: 0 0 1.5rem;
  font-size: 14px;
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
  text-decoration: none;
  transition: all 0.2s;
  font-family: inherit;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 229, 255, 0.35);
}
</style>
