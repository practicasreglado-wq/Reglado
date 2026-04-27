<template>
  <main class="auth-page">
    <div class="auth-card center">
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
  </main>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import { auth } from "../services/auth";

const route = useRoute();
const state = ref("loading");

const iconClass = computed(() => ({
  "icon-loading": state.value === "loading",
  "icon-success": state.value === "confirmed",
  "icon-danger": state.value === "rejected",
  "icon-warning": ["expired", "invalid", "error"].includes(state.value),
}));

const title = computed(() => ({
  loading: "Procesando…",
  confirmed: "¡Gracias por confirmar!",
  rejected: "Sesión cerrada por seguridad",
  expired: "Enlace expirado",
  invalid: "Enlace no válido",
  error: "No hemos podido procesarlo",
}[state.value] || "Confirmación de acceso"));

const description = computed(() => ({
  loading: "Estamos registrando tu respuesta. Un momento…",
  confirmed: "Hemos registrado que reconoces este inicio de sesión. Tu cuenta sigue protegida y no necesitas hacer nada más.",
  rejected: "Hemos cerrado la sesión sospechosa. La próxima vez que accedas a tu cuenta te pediremos cambiar la contraseña y recibirás un correo con las instrucciones.",
  expired: "Este enlace ya fue usado o ha caducado. Si recibiste un aviso más reciente, abre ese email en su lugar.",
  invalid: "El enlace que has abierto no es correcto. Revisa el correo y vuelve a intentarlo desde el enlace que te enviamos.",
  error: "Hubo un problema al contactar con el servidor. Inténtalo de nuevo en unos minutos.",
}[state.value] || ""));

onMounted(async () => {
  const token = typeof route.query.token === "string" ? route.query.token : "";
  const decision = typeof route.query.decision === "string" ? route.query.decision : "";

  if (!token || !["me", "not-me"].includes(decision)) {
    state.value = "invalid";
    return;
  }

  try {
    const res = await auth.confirmLoginLocation(token, decision);
    const allowed = ["confirmed", "rejected", "expired", "invalid"];
    state.value = allowed.includes(res?.state) ? res.state : "error";
  } catch {
    state.value = "error";
  }
});
</script>

<style scoped>
.auth-page { padding: 80px 20px; background: #f5f7fa; min-height: calc(100vh - 160px); }

.auth-card {
  max-width: 520px;
  margin: 0 auto;
  background: #ffffff;
  border-radius: 16px;
  padding: 40px;
  box-shadow: 0 4px 20px rgba(15, 23, 42, 0.08);
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

.icon-loading { background: #f1f5f9; color: #64748b; }
.icon-loading svg { animation: spin 1s linear infinite; }

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.icon-success { background: #dcfce7; color: #16a34a; }
.icon-danger { background: #fee2e2; color: #dc2626; }
.icon-warning { background: rgba(36, 56, 107, 0.12); color: #24386b; }

h1 { margin: 0 0 12px; font-size: 1.5rem; color: #24386b; }

.description {
  color: #64748b;
  line-height: 1.6;
  margin: 0 0 1.5rem;
  font-size: 0.9375rem;
}

.btn-primary {
  display: inline-block;
  padding: 12px 24px;
  background: #24386b;
  color: #ffffff;
  border: none;
  border-radius: 10px;
  font-weight: 700;
  font-size: 0.9375rem;
  text-decoration: none;
  transition: all 0.18s ease;
  font-family: inherit;
}

.btn-primary:hover {
  background: #1a2b54;
  transform: translateY(-1px);
  box-shadow: 0 8px 20px rgba(36, 56, 107, 0.3);
}
</style>
