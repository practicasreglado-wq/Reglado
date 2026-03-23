<template>
  <section class="history-view">
    <div class="history-header">
      <div class="header-content">
        <div class="header-main">
          <p class="history-kicker">Perfil del comprador</p>
          <h2>Historial de búsquedas</h2>
        </div>
        <div class="history-counter">{{ history.length }} guardadas</div>
      </div>
    </div>

    <transition name="content-fade" mode="out-in">
    <div v-if="loading" key="loading" class="history-state">
      Cargando historial...
    </div>

    <div v-else-if="error" key="error" class="history-state history-state--error">
      {{ error }}
    </div>

    <div v-else-if="!history.length" key="empty" class="history-state">
      Aún no has guardado búsquedas.
    </div>

    <transition-group v-else key="results" name="stagger-list" tag="div" class="history-grid">
      <div
        v-for="(item, index) in history"
        :key="item.id"
        class="history-grid__item"
        :style="{ transitionDelay: `${Math.min(index * 70, 420)}ms` }"
      >
        <SearchHistoryItem
          :item="item"
          @apply="applySearch"
          @delete="deleteSearch"
        />
      </div>
    </transition-group>
    </transition>
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
      // 1. Actualizar el store de Pinia
      userStore.setCategory(item.category);
      userStore.setPreferences({ ...item.preferences });

      // 2. Sincronizar preferencias con el backend
      try {
        await backendJson("api/save_preferences.php", {
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

      // 3. Navegar a la lista de propiedades con un timestamp para forzar actualización
      await router.push({
        path: "/profile/properties-for-sale",
        query: {
          category: item.category,
          restoredSearch: String(item.id),
          t: Date.now()
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
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-height: 160px;
  padding: 30px 34px;
  border-radius: 28px;
  background:
    radial-gradient(circle at top right, rgba(255, 215, 126, 0.28), transparent 34%),
    linear-gradient(135deg, #12244d 0%, #20386b 55%, #3a5ca9 100%);
  box-shadow: 0 22px 48px rgba(18, 36, 77, 0.24);
  color: #fff;
}

.history-header::before,
.history-header::after {
  content: "";
  position: absolute;
  border-radius: 999px;
  pointer-events: none;
  opacity: 0.72;
  transition: opacity 0.28s ease;
}

.history-header::before {
  width: 240px;
  height: 240px;
  right: -80px;
  top: -100px;
  background: rgba(255, 255, 255, 0.08);
}

.history-header::after {
  width: 180px;
  height: 180px;
  left: -70px;
  bottom: -100px;
  background: rgba(255, 204, 84, 0.14);
}

.header-content {
  position: relative;
  z-index: 2;
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

.header-main {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.history-kicker {
  display: inline-flex;
  align-items: center;
  width: max-content;
  padding: 7px 12px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.14);
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  margin: 0;
  color: #ffffff;
}

.history-header h2 {
  margin: 0;
  font-size: clamp(1.8rem, 1.3rem + 1.2vw, 2.6rem);
  line-height: 1.1;
  color: #ffffff;
}

.history-counter {
  display: inline-flex;
  align-items: center;
  padding: 10px 18px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.14);
  backdrop-filter: blur(10px);
  font-weight: 600;
  white-space: nowrap;
  font-size: 0.95rem;
}

.history-grid {
  display: flex;
  flex-direction: column;
  gap: 18px;
  position: relative;
}

.history-grid__item {
  min-width: 0;
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
    padding: 26px 30px;
    min-height: 140px;
  }
}

@media (max-width: 980px) {
  .history-header {
    padding: 22px 26px;
    min-height: 120px;
  }
}

@media (max-width: 768px) {
  .history-view {
    gap: 20px;
  }
  .history-header {
    padding: 24px;
    border-radius: 22px;
  }
  .header-content {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }
  .history-counter {
    padding: 8px 14px;
    font-size: 0.92rem;
  }
}

@media (max-width: 480px) {
  .history-header {
    padding: 20px;
    min-height: auto;
  }
  .history-header h2 {
    font-size: 1.5rem;
  }
  .history-kicker {
    font-size: 0.72rem;
    padding: 6px 10px;
  }
}
</style>
