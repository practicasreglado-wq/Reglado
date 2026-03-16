<template>
  <section class="history-view">
    <div class="history-header">
      <div>
        <p class="history-kicker">Perfil del comprador</p>
        <h2>Historial de búsquedas</h2>
      </div>
      <div class="history-counter">{{ history.length }} guardadas</div>
    </div>

    <div v-if="loading" class="history-state">
      Cargando historial...
    </div>

    <div v-else-if="error" class="history-state history-state--error">
      {{ error }}
    </div>

    <div v-else-if="!history.length" class="history-state">
      Aún no has guardado búsquedas.
    </div>

    <div v-else class="history-grid">
      <SearchHistoryItem
        v-for="item in history"
        :key="item.id"
        :item="item"
        @apply="applySearch"
        @delete="deleteSearch"
      />
    </div>
  </section>
</template>

<script>
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { useUserStore } from "../stores/user";
import { backendJson } from "../services/backend";
import SearchHistoryItem from "../components/SearchHistoryItem.vue";

export default {
  components: {
    SearchHistoryItem,
  },
  setup() {
    const router = useRouter();
    const userStore = useUserStore();
    const loading = ref(true);
    const error = ref("");
    const history = ref([]);

    const loadHistory = async () => {
      loading.value = true;
      error.value = "";

      try {
        const payload = await backendJson("api/get_search_history.php");
        history.value = Array.isArray(payload.history) ? payload.history : [];
      } catch (err) {
        error.value = err.message || "No se pudo cargar el historial.";
      } finally {
        loading.value = false;
      }
    };

    const applySearch = async (item) => {
      // 1. Update Pinia Store
      userStore.setCategory(item.category);
      userStore.setPreferences({ ...item.preferences });

      // 2. Sync with Backend (Crucial for match calculations)
      try {
        await backendJson("save_preferences.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            categoria: item.category,
            preferencias: item.preferences
          })
        });
      } catch (err) {
        console.error("Error syncing preferences to backend:", err);
      }

      // 3. Navigate with a unique ID to trigger refresh in target view
      await router.push({
        path: "/profile/properties-for-sale",
        query: {
          category: item.category,
          restoredSearch: String(item.id),
          t: Date.now() // Unique timestamp to force watch trigger
        },
      });
    };

    const deleteSearch = async (item) => {
      error.value = "";

      try {
        await backendJson("api/delete_search_history.php", {
          method: "DELETE",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            search_id: item.id,
            user_id: userStore.user?.iduser,
          }),
        });

        history.value = history.value.filter((entry) => entry.id !== item.id);
      } catch (err) {
        error.value = err.message || "No se pudo eliminar la búsqueda.";
      }
    };

    onMounted(loadHistory);

    return {
      applySearch,
      deleteSearch,
      error,
      history,
      loading,
    };
  },
};
</script>

<style scoped>
.history-view {
  display: grid;
  gap: 22px;
}

.history-header {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: flex-start;
  padding: 24px 28px;
  border-radius: 20px;
  background: linear-gradient(135deg, #172a5d, #3654ae);
  color: white;
}

.history-kicker {
  margin: 0 0 8px;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: rgba(255, 255, 255, 0.72);
}

.history-header h2 {
  margin: 0;
  font-size: 2rem;
}

.history-counter {
  padding: 10px 14px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.14);
  font-weight: 700;
  white-space: nowrap;
}

.history-grid {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.history-state {
  padding: 24px;
  border-radius: 18px;
  background: white;
  box-shadow: 0 10px 24px rgba(18, 38, 77, 0.08);
  color: #51627f;
}

.history-state--error {
  color: #9f2d2d;
}

/* =========================================
   RESPONSIVE (1440px / 768px / 480px)
   ========================================= */

@media (max-width: 1440px) {
  .history-header {
    gap: 16px;
    padding: 22px 24px;
  }
  .history-header h2 {
    font-size: 1.8rem;
  }
}

@media (max-width: 768px) {
  .history-view {
    gap: 20px;
  }
  .history-header {
    flex-direction: column;
    align-items: flex-start;
    padding: 20px;
  }
  .history-header h2 {
    font-size: 1.6rem;
  }
  .history-counter {
    padding: 8px 14px;
    font-size: 0.95rem;
  }
}

@media (max-width: 480px) {
  .history-header {
    padding: 16px;
  }
  .history-header h2 {
    font-size: 1.5rem;
  }
  .history-kicker {
    font-size: 0.72rem;
  }
  .history-counter {
    padding: 6px 12px;
    font-size: 0.85rem;
  }
  .history-state {
    padding: 18px;
    font-size: 0.9rem;
  }
}
</style>
