import { defineStore } from "pinia";
import { backendJson } from "../services/backend";

export const useNotificationsStore = defineStore("notifications", {
  state: () => ({
    notifications: [],
    unreadCount: 0,
    loading: false,
    error: null,
    intervalId: null,
  }),

  getters: {
    hasUnread(state) {
      return state.unreadCount > 0;
    },

    orderedNotifications(state) {
      return state.notifications.slice();
    },
  },

  actions: {
    startAutoRefresh(interval = 5000) {
      if (this.intervalId) return;

      this.loadNotifications();

      this.intervalId = setInterval(() => {
        this.loadNotifications(40, true);
      }, interval);
    },

    stopAutoRefresh() {
      if (this.intervalId) {
        clearInterval(this.intervalId);
        this.intervalId = null;
      }
    },

    async loadNotifications(limit = 40, silent = false) {
  if (this.loading && !silent) {
    return;
  }

  if (!silent) {
    this.loading = true;
  }

  this.error = null;

  try {
    const payload = await backendJson(`api/notifications.php?limit=${encodeURIComponent(limit)}`);

    const newNotifications = Array.isArray(payload.notifications)
      ? payload.notifications
      : [];

    const oldIds = this.notifications.map(n => n.id).join(',');
    const newIds = newNotifications.map(n => n.id).join(',');

    if (oldIds !== newIds) {
      this.notifications = newNotifications;

      console.log("🔔 Nueva notificación");
    }

    this.unreadCount =
      typeof payload.unread === "number"
        ? payload.unread
        : 0;

  } catch (error) {
    this.error = error?.message || "No se pudieron cargar las notificaciones.";
  } finally {
    if (!silent) {
      this.loading = false;
    }
  }
},

    async markAsRead(notificationId) {
      if (!notificationId || notificationId <= 0) {
        return;
      }

      try {
        const payload = await backendJson("api/notifications.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            action: "mark_read",
            notification_id: notificationId,
          }),
        });

        if (payload.updated) {
          this.notifications = this.notifications.map((item) =>
            item.id === notificationId ? { ...item, is_read: 1 } : item
          );
        }

        this.unreadCount = typeof payload.unread === "number"
          ? payload.unread
          : Math.max(0, this.unreadCount - 1);
      } catch (error) {
        this.error = error?.message || "No se pudo actualizar la notificación.";
      }
    },
  },
});

