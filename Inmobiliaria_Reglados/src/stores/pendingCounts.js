import { defineStore } from "pinia";
import { backendJson } from "../services/backend";

export const usePendingCountsStore = defineStore("pendingCounts", {
  state: () => ({
    total: 0,
    counts: { roles: 0, documents: 0, purchases: 0, appointments: 0, property_deletions: 0 },
    error: null,
    intervalId: null,
  }),

  getters: {
    hasPending(state) {
      return state.total > 0;
    },
  },

  actions: {
    startAutoRefresh(interval = 15000) {
      if (this.intervalId) return;

      this.loadCount(true);

      this.intervalId = setInterval(() => {
        this.loadCount(true);
      }, interval);
    },

    stopAutoRefresh() {
      if (this.intervalId) {
        clearInterval(this.intervalId);
        this.intervalId = null;
      }
    },

    resetState() {
      this.total = 0;
      this.counts = { roles: 0, documents: 0, purchases: 0, appointments: 0, property_deletions: 0 };
      this.error = null;
    },

    async loadCount(silent = false) {
      if (!silent) {
        this.error = null;
      }

      try {
        const payload = await backendJson("api/get_pending_counts.php");
        this.total = typeof payload.total === "number" ? payload.total : 0;
        this.counts = {
          roles: payload.counts?.roles ?? 0,
          documents: payload.counts?.documents ?? 0,
          purchases: payload.counts?.purchases ?? 0,
          appointments: payload.counts?.appointments ?? 0,
          property_deletions: payload.counts?.property_deletions ?? 0,
        };
      } catch (error) {
        if (!silent) {
          this.error =
            error?.message || "No se pudo actualizar el contador de solicitudes.";
        }
      }
    },
  },
});
