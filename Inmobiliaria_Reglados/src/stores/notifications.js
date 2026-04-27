/**
 * Store de notificaciones in-app (Pinia).
 *
 * Lo consume NotificationBell.vue del header para mostrar el badge de no
 * leídas + el dropdown con la lista. Usa polling cada 15s
 * (`startAutoRefresh`) contra api/notifications_unread_count.php para
 * mantener el contador sin recargar la lista entera (ahorra ancho de banda).
 *
 * El listado completo solo se descarga (loadNotifications) cuando el
 * usuario abre el dropdown. Marcar como leído (markAsRead) actualiza
 * tanto el array local como el contador del servidor.
 */

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
    startAutoRefresh(interval = 15000) {
      if (this.intervalId) return;

      this.loadUnreadCount(true);

      this.intervalId = setInterval(() => {
        this.loadUnreadCount(true);
      }, interval);
    },

    stopAutoRefresh() {
      if (this.intervalId) {
        clearInterval(this.intervalId);
        this.intervalId = null;
      }
    },

     resetState() {
      this.notifications = [];
      this.unreadCount = 0;
      this.loading = false;
      this.error = null;
    },

    async loadUnreadCount(silent = false) {
      if (!silent) {
        this.error = null;
      }

      try {
        const payload = await backendJson("api/notifications_unread_count.php");
        this.unreadCount = typeof payload.unread === "number" ? payload.unread : 0;
      } catch (error) {
        if (!silent) {
          this.error = error?.message || "No se pudo actualizar el contador de notificaciones.";
        }
      }
    },

    async loadNotifications(limit = 30, silent = false) {
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
        this.notifications = newNotifications;

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

