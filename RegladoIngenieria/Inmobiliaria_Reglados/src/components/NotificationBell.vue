<template>
  <div class="notification-bell">
    <button
      ref="bellRef"
      type="button"
      class="notification-bell__button"
      @click="togglePanel"
      :aria-expanded="panelVisible"
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
      <div v-show="panelVisible" class="notification-bell__portal">
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
              @click="closePanel"
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

                  <button
                    v-if="!notification.is_read"
                    type="button"
                    class="notification-panel__item-action"
                    @click.stop="markNotification(notification.id)"
                  >
                    Marcar como leído
                  </button>
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
import {
  computed,
  nextTick,
  onMounted,
  onBeforeUnmount,
  ref,
  watch,
} from "vue";
import { storeToRefs } from "pinia";
import { useNotificationsStore } from "../stores/notifications";

const store = useNotificationsStore();
const { notifications, unreadCount, loading, error } = storeToRefs(store);

const bellRef = ref(null);
const panelRef = ref(null);
const panelVisible = ref(false);
const panelStyle = ref({});

const dateFormatter = new Intl.DateTimeFormat("es-ES", {
  dateStyle: "medium",
  timeStyle: "short",
});

const formatDate = (value) => {
  if (!value) return "";

  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? value : dateFormatter.format(date);
};

const positionPanel = () => {
  const buttonEl = bellRef.value;
  const panelEl = panelRef.value;

  if (!buttonEl || !panelEl) return;

  const margin = 12;
  const maxWidth = Math.min(360, window.innerWidth - margin * 2);

  panelStyle.value = {
    width: `${maxWidth}px`,
    top: "0px",
    left: "0px",
  };

  window.requestAnimationFrame(() => {
    const panelHeight = panelEl.offsetHeight || 280;
    const buttonRect = buttonEl.getBoundingClientRect();

    const spaceBelow = window.innerHeight - buttonRect.bottom - margin;
    const openBelow = spaceBelow >= panelHeight;

    const top = openBelow
      ? buttonRect.bottom + margin
      : Math.max(margin, buttonRect.top - panelHeight - margin);

    let left = buttonRect.right - maxWidth;
    left = Math.max(margin, Math.min(left, window.innerWidth - maxWidth - margin));

    panelStyle.value = {
      width: `${maxWidth}px`,
      top: `${Math.max(margin, Math.min(top, window.innerHeight - panelHeight - margin))}px`,
      left: `${left}px`,
    };
  });
};

const closePanel = () => {
  panelVisible.value = false;
};

const openPanel = async () => {
  panelVisible.value = true;
  await store.loadNotifications(40, true);
  await nextTick();
  positionPanel();
};

const togglePanel = () => {
  if (panelVisible.value) {
    closePanel();
  } else {
    openPanel();
  }
};

const markNotification = async (notificationId) => {
  await store.markAsRead(notificationId);
};

const handleDocumentClick = (event) => {
  const target = event.target;

  if (panelRef.value?.contains(target) || bellRef.value?.contains(target)) {
    return;
  }

  closePanel();
};

const handleKeyDown = (event) => {
  if (event.key === "Escape") {
    closePanel();
  }
};

const handleViewportChange = () => {
  if (panelVisible.value) {
    positionPanel();
  }
};

watch(panelVisible, async (value) => {
  if (value) {
    document.addEventListener("mousedown", handleDocumentClick);
    document.addEventListener("keydown", handleKeyDown);
    await nextTick();
    positionPanel();
  } else {
    document.removeEventListener("mousedown", handleDocumentClick);
    document.removeEventListener("keydown", handleKeyDown);
  }
});

onMounted(() => {
  window.addEventListener("resize", handleViewportChange);
  window.addEventListener("scroll", handleViewportChange, true);
});

onBeforeUnmount(() => {
  document.removeEventListener("mousedown", handleDocumentClick);
  document.removeEventListener("keydown", handleKeyDown);
  window.removeEventListener("resize", handleViewportChange);
  window.removeEventListener("scroll", handleViewportChange, true);
});
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
  top: 4px;
  right: 4px;
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
</style>