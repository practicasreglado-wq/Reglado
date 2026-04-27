<!--
  Campanita de notificaciones del header.

  - Muestra badge numérico con `unreadCount` de useNotificationsStore.
  - Al click abre un dropdown con el listado (loadNotifications).
  - Marcar como leído llama a markAsRead, que actualiza local + servidor.
  - El polling automático lo arranca el componente padre (App.vue/Header.vue)
    con `notifications.startAutoRefresh()` cuando el usuario está logueado.
-->
<template>
  <div class="notification-bell">
    <button
      ref="anchorRef"
      type="button"
      class="notification-bell__button"
      @click="togglePanel"
      :aria-expanded="visible"
      aria-haspopup="dialog"
      title="Notificaciones"
    >
      <span class="sr-only">Abrir notificaciones</span>
      <span class="notification-bell__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="22" height="22" fill="none">
          <path
            d="M12 3c-3.1 0-5.6 2.2-6 5.1-.1.4-.1.9-.1 1.4l-1 1.1v1l2 2h12l2-2v-1l-1-1.1c0-.5 0-1-.1-1.4C17.6 5.2 15.1 3 12 3z"
            stroke="currentColor"
            stroke-width="1.6"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
          <path
            d="M9.9 20a2.1 2.1 0 0 0 4.2 0"
            stroke="currentColor"
            stroke-width="1.6"
            stroke-linecap="round"
          />
        </svg>
      </span>

      <span
        v-if="unreadCount > 0"
        class="notification-bell__badge"
        role="status"
        aria-live="polite"
      >
        {{ unreadCount }}
      </span>
    </button>

    <Teleport to="body">
      <div
        v-if="intentDetail.open"
        class="intent-detail-backdrop"
        @click.self="closeIntentDetail"
      >
        <div class="intent-detail-window" role="dialog" aria-modal="true">
          <header class="intent-detail-window__header">
            <h3>Un comprador busca una propiedad</h3>
            <button
              type="button"
              class="intent-detail-window__close"
              aria-label="Cerrar"
              @click="closeIntentDetail"
            >×</button>
          </header>

          <div class="intent-detail-window__body">
            <p v-if="intentDetail.loading" class="intent-detail-window__state">
              Cargando detalles...
            </p>

            <p
              v-else-if="intentDetail.error"
              class="intent-detail-window__state intent-detail-window__state--error"
            >
              {{ intentDetail.error }}
            </p>

            <template v-else-if="intentDetail.data">
              <p class="intent-detail-window__hint">
                Estos son los criterios que el comprador respondió en su
                cuestionario. Si tienes una propiedad que encaja, súbela y el
                sistema la emparejará automáticamente.
              </p>

              <div class="intent-detail-list">
                <div v-if="intentDetail.data.category" class="intent-detail-list__row">
                  <span class="intent-detail-list__label">Categoría</span>
                  <span class="intent-detail-list__value">{{ intentDetail.data.category }}</span>
                </div>

                <div
                  v-for="(entry, idx) in intentDetail.data.display || []"
                  :key="idx"
                  class="intent-detail-list__row"
                >
                  <span class="intent-detail-list__label">{{ entry.label }}</span>
                  <span class="intent-detail-list__value">{{ entry.value }}</span>
                </div>
              </div>
            </template>
          </div>

          <footer class="intent-detail-window__footer">
            <button
              type="button"
              class="intent-detail-btn intent-detail-btn--ghost"
              @click="closeIntentDetail"
            >
              Cerrar
            </button>
            <button
              type="button"
              class="intent-detail-btn intent-detail-btn--primary"
              :disabled="!intentDetail.actionUrl"
              @click="confirmIntentDetail"
            >
              Subir propiedad
            </button>
          </footer>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div v-show="visible" class="notification-bell__portal">
        <div
          ref="panelRef"
          class="notification-panel"
          :style="panelStyle"
          role="dialog"
          aria-label="Panel de notificaciones"
        >
          <header class="notification-panel__header">
            <span>Notificaciones</span>
            <button
              type="button"
              class="notification-panel__close"
              @click="close"
              aria-label="Cerrar panel"
            >
              ×
            </button>
          </header>

          <div class="notification-panel__body">
            <p v-if="loading && !notifications.length" class="notification-panel__state">
              Cargando notificaciones...
            </p>

            <p v-else-if="error" class="notification-panel__state">
              {{ error }}
            </p>

            <p v-else-if="!notifications.length" class="notification-panel__state">
              No hay notificaciones nuevas.
            </p>

            <div v-else class="notification-panel__list">
              <article
                v-for="notification in notifications"
                :key="notification.id"
                class="notification-panel__item"
                :class="{ 'notification-panel__item--read': notification.is_read }"
              >
                <div class="notification-panel__item-header">
                  <h4>{{ notification.title }}</h4>
                  <time>{{ formatDate(notification.created_at) }}</time>
                </div>

                <p class="notification-panel__item-message">
                  {{ notification.message }}
                </p>

                <div class="notification-panel__item-footer">
                  <span
                    class="notification-panel__item-status"
                    :aria-label="notification.is_read ? 'Leído' : 'Sin leer'"
                  >
                    {{ notification.is_read ? 'Leído' : 'Sin leer' }}
                  </span>

                  <div class="notification-panel__item-actions">
                    <button
                      v-if="notification.action_url"
                      type="button"
                      class="notification-panel__item-cta"
                      @click.stop="goToAction(notification)"
                    >
                      {{ actionLabel(notification) }}
                    </button>

                    <button
                      v-if="!notification.is_read"
                      type="button"
                      class="notification-panel__item-action"
                      @click.stop="markNotification(notification.id)"
                    >
                      Marcar como leído
                    </button>
                  </div>
                </div>
              </article>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { nextTick, reactive } from "vue";
import { storeToRefs } from "pinia";
import { useRouter } from "vue-router";
import { useNotificationsStore } from "../stores/notifications";
import { useAnchoredPanel } from "../composables/useAnchoredPanel";
import { fetchBuyerIntent } from "../services/buyerIntents";

const store = useNotificationsStore();
const { notifications, unreadCount, loading, error } = storeToRefs(store);
const router = useRouter();

const intentDetail = reactive({
  open: false,
  loading: false,
  error: "",
  data: null,
  actionUrl: "",
  notificationId: null,
});

const {
  anchorRef,
  panelRef,
  visible,
  panelStyle,
  open,
  close,
  reposition,
} = useAnchoredPanel();

const dateFormatter = new Intl.DateTimeFormat("es-ES", {
  dateStyle: "medium",
  timeStyle: "short",
});

const formatDate = (value) => {
  if (!value) return "";
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? value : dateFormatter.format(date);
};

const togglePanel = async () => {
  if (visible.value) {
    close();
    return;
  }
  open();
  await store.loadNotifications(30);
  await nextTick();
  reposition();
};

const markNotification = async (notificationId) => {
  await store.markAsRead(notificationId);
};

const actionLabel = (notification) => {
  const type = String(notification.type || "").toLowerCase();
  if (type === "buyer_intent") return "Abrir";
  if (type === "intent_match") return "Comprar";
  return "Abrir";
};

const goToAction = async (notification) => {
  if (!notification?.action_url) return;

  const type = String(notification.type || "").toLowerCase();

  // Para notificaciones de búsqueda de comprador, abrimos el modal con el
  // detalle completo (y dentro del modal se ofrece el botón "Subir").
  if (type === "buyer_intent") {
    await openIntentDetail(notification);
    return;
  }

  if (!notification.is_read) {
    try {
      await store.markAsRead(notification.id);
    } catch {
      // Si falla el markAsRead, no bloqueamos la navegación.
    }
  }

  close();

  try {
    await router.push(notification.action_url);
  } catch {
    // push puede fallar si la ruta es la misma; ignorar.
  }
};

const openIntentDetail = async (notification) => {
  intentDetail.open = true;
  intentDetail.loading = true;
  intentDetail.error = "";
  intentDetail.data = null;
  intentDetail.actionUrl = notification.action_url || "";
  intentDetail.notificationId = notification.id;

  // Cerramos el panel de notificaciones para que el modal quede limpio.
  close();

  try {
    const intentId = Number(notification.related_request_id);
    if (!Number.isFinite(intentId) || intentId <= 0) {
      throw new Error("Solicitud sin identificador válido.");
    }
    const intent = await fetchBuyerIntent(intentId);
    intentDetail.data = intent;
  } catch (err) {
    intentDetail.error = err?.message || "No se pudo cargar la solicitud.";
  } finally {
    intentDetail.loading = false;
  }
};

const closeIntentDetail = () => {
  intentDetail.open = false;
  intentDetail.loading = false;
  intentDetail.error = "";
  intentDetail.data = null;
  intentDetail.actionUrl = "";
  intentDetail.notificationId = null;
};

const confirmIntentDetail = async () => {
  const target = intentDetail.actionUrl;
  const notifId = intentDetail.notificationId;

  if (notifId) {
    try {
      await store.markAsRead(notifId);
    } catch {
      // No bloqueamos la navegación si markAsRead falla.
    }
  }

  closeIntentDetail();

  if (target) {
    try {
      await router.push(target);
    } catch {
      // push puede fallar si la ruta es la misma; ignorar.
    }
  }
};
</script>

<style scoped>
.notification-bell {
  position: relative;
  display: inline-flex;
  align-items: center;
}

.notification-bell__button {
  position: relative;
  width: 44px;
  height: 44px;
  border-radius: 14px;
  border: 1px solid rgba(255, 255, 255, 0.4);
  background: rgba(255, 255, 255, 0.08);
  color: inherit;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.notification-bell__button:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
}

.notification-bell__icon {
  display: inline-flex;
}

.notification-bell__badge {
  position: absolute;
  top: -2px;
  right: -2px;
  min-width: 18px;
  height: 18px;
  padding: 0 4px;
  border-radius: 999px;
  background: #ec3d3d;
  color: #fff;
  font-size: 0.7rem;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  letter-spacing: 0.03em;
  box-shadow: 0 0 0 2px #fff;
}

.notification-bell__portal {
  position: fixed;
  inset: 0;
  z-index: 2000;
  pointer-events: none;
}

.notification-panel {
  position: fixed;
  pointer-events: auto;
  padding: 0;
  background: #fff;
  border-radius: 18px;
  border: 1px solid rgba(15, 23, 42, 0.12);
  box-shadow: 0 32px 60px rgba(15, 23, 42, 0.24);
  max-height: calc(100vh - 32px);
  overflow: hidden;
  min-width: 260px;
  width: 320px;
}

.notification-panel__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  border-bottom: 1px solid rgba(15, 23, 42, 0.08);
  font-weight: 700;
  color: #0f172a;
  background: #f8fafc;
}

.notification-panel__close {
  background: transparent;
  border: none;
  font-size: 1.5rem;
  line-height: 1;
  color: #475569;
  cursor: pointer;
}

.notification-panel__body {
  padding: 12px 16px 16px;
  max-height: calc(100vh - 110px);
  overflow-y: auto;
}

.notification-panel__state {
  margin: 0;
  color: #475569;
  font-size: 0.9rem;
  text-align: center;
}

.notification-panel__list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.notification-panel__item {
  padding: 12px;
  border-radius: 14px;
  border: 1px solid rgba(148, 163, 184, 0.4);
  background: #f8fafc;
  color: #0f172a;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.notification-panel__item--read {
  opacity: 0.7;
}

.notification-panel__item-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
}

.notification-panel__item-header h4 {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
}

.notification-panel__item-header time {
  font-size: 0.75rem;
  color: #64748b;
}

.notification-panel__item-message {
  margin: 0;
  font-size: 0.9rem;
  color: #1e293b;
}

.notification-panel__item-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.notification-panel__item-status {
  font-size: 0.8rem;
  color: #0f172a;
  padding: 4px 10px;
  border-radius: 999px;
  border: 1px solid rgba(15, 23, 42, 0.15);
  background: rgba(59, 130, 246, 0.08);
}

.notification-panel__item-action {
  border: none;
  background: #0f172a;
  color: #fff;
  padding: 6px 14px;
  border-radius: 999px;
  font-size: 0.8rem;
  cursor: pointer;
  transition: background 0.2s ease;
}

.notification-panel__item-action:hover {
  background: #1e3a8a;
}

.notification-panel__item-actions {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.notification-panel__item-cta {
  border: none;
  background: #2563eb;
  color: #fff;
  padding: 6px 14px;
  border-radius: 999px;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s ease, transform 0.15s ease;
}

.notification-panel__item-cta:hover {
  background: #1d4ed8;
  transform: translateY(-1px);
}

.notification-panel__item--read .notification-panel__item-status {
  background: rgba(107, 114, 128, 0.12);
}

.notification-panel__item--read .notification-panel__item-action {
  opacity: 0.5;
  cursor: default;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  border: 0;
}

.intent-detail-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2100;
  padding: 24px;
}

.intent-detail-window {
  width: min(560px, 95vw);
  max-height: 85vh;
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 30px 60px rgba(15, 23, 42, 0.32);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.intent-detail-window__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 20px;
  border-bottom: 1px solid #e5e7eb;
  background: #f8fafc;
}

.intent-detail-window__header h3 {
  margin: 0;
  font-size: 1rem;
  color: #0f172a;
}

.intent-detail-window__close {
  background: transparent;
  border: none;
  font-size: 1.6rem;
  line-height: 1;
  color: #475569;
  cursor: pointer;
}

.intent-detail-window__body {
  padding: 18px 20px;
  flex: 1;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.intent-detail-window__hint {
  margin: 0;
  font-size: 0.92rem;
  color: #475569;
  line-height: 1.5;
}

.intent-detail-window__state {
  margin: 0;
  text-align: center;
  color: #475569;
  padding: 20px 0;
}

.intent-detail-window__state--error {
  color: #b91c1c;
}

.intent-detail-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 14px 16px;
  border-radius: 12px;
  background: #f8fafc;
  border: 1px solid #cbd5e1;
}

.intent-detail-list__row {
  display: grid;
  grid-template-columns: 140px minmax(0, 1fr);
  gap: 12px;
  align-items: baseline;
  font-size: 0.92rem;
}

.intent-detail-list__label {
  color: #475569;
  font-weight: 500;
}

.intent-detail-list__value {
  color: #0f172a;
  font-weight: 600;
  text-align: right;
  overflow-wrap: anywhere;
  word-break: break-word;
}

.intent-detail-window__footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 14px 20px;
  border-top: 1px solid #e5e7eb;
  background: #f8fafc;
}

.intent-detail-btn {
  padding: 10px 18px;
  border-radius: 999px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  border: 1px solid transparent;
  transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
}

.intent-detail-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.intent-detail-btn--ghost {
  background: transparent;
  color: #475569;
  border-color: #cbd5e1;
}

.intent-detail-btn--ghost:hover:not(:disabled) {
  background: #e2e8f0;
}

.intent-detail-btn--primary {
  background: linear-gradient(135deg, #1e3a8a, #2563eb);
  color: #fff;
}

.intent-detail-btn--primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 12px 24px rgba(37, 99, 235, 0.28);
}

@media (max-width: 500px) {
  .intent-detail-list__row {
    grid-template-columns: 1fr;
  }
  .intent-detail-list__value {
    text-align: left;
  }
}
</style>
