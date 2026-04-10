import { defineStore } from "pinia";
import { backendJson } from "../services/backend";

export const useNotificationsStore = defineStore("notifications", {
  state: () => ({
    notifications: [],
    unreadCount: 0,
    loading: false,
    error: null,
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
    async loadNotifications(limit = 40) {
      if (this.loading) {
        return;
      }

      this.loading = true;
      this.error = null;

      try {
        const payload = await backendJson(`api/notifications.php?limit=${encodeURIComponent(limit)}`);
        this.notifications = Array.isArray(payload.notifications) ? payload.notifications : [];
        this.unreadCount = typeof payload.unread === "number" ? payload.unread : 0;
      } catch (error) {
        this.error = error?.message || "No se pudieron cargar las notificaciones.";
      } finally {
        this.loading = false;
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

