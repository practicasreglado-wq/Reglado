<template>
  <section class="form-page">
    <div class="form-card confirm-card">
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

      <RouterLink
        v-if="state !== 'loading'"
        class="btn-primary inline-btn"
        to="/"
      >
        Ir al portal
      </RouterLink>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { RouterLink, useRoute } from "vue-router";
import { auth } from "../services/auth";

const route = useRoute();
const state = ref("loading"); // loading | confirmed | rejected | expired | invalid | error

const iconClass = computed(() => ({
  "icon-loading": state.value === "loading",
  "icon-success": state.value === "confirmed",
  "icon-danger": state.value === "rejected",
  "icon-warning": state.value === "expired" || state.value === "invalid" || state.value === "error",
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
.confirm-card {
  text-align: center;
}

.status-icon {
  display: inline-flex;
  width: 72px;
  height: 72px;
  border-radius: 50%;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.25rem;
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
  background: #e2e8f0;
  color: #64748b;
}
.icon-loading svg {
  animation: spin 1s linear infinite;
}
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.icon-success {
  background: #dcfce7;
  color: #16a34a;
}

.icon-danger {
  background: #fee2e2;
  color: #dc2626;
}

.icon-warning {
  background: #fef3c7;
  color: #d97706;
}

.confirm-card h1 {
  font-size: 1.4rem;
  margin: 0 0 0.75rem;
}

.description {
  color: var(--muted, #64748b);
  line-height: 1.6;
  margin: 0 0 1.5rem;
}
</style>
