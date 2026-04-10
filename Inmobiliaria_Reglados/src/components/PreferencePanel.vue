<template>
  <section class="preference-panel">
    <div class="panel-header">
      <div>
        <p class="panel-kicker">Categoría: {{ category }}</p>
        <h3>Preferencias del comprador</h3>
      </div>
      <div class="panel-count">{{ entries.length }} preferencias</div>
    </div>

    <ul class="preference-list">
      <li v-for="entry in visibleEntries" :key="entry.key" class="preference-item">
        <span class="preference-label">{{ entry.label }}</span>
        <span class="preference-value">{{ entry.value }}</span>
      </li>
    </ul>

    <button
      v-if="hiddenEntries.length"
      type="button"
      class="toggle-button"
      @click="expanded = !expanded"
    >
      {{ expanded ? "Ver menos preferencias ▲" : "Ver más preferencias ▼" }}
    </button>
  </section>
</template>

<script>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";

export default {
  props: {
    category: {
      type: String,
      required: true,
    },
    entries: {
      type: Array,
      required: true,
    },
  },
  setup(props) {
    const expanded = ref(false);
    const isMobile = ref(false);
    let mobileQuery = null;

    const syncViewport = () => {
      isMobile.value = window.innerWidth <= 480;
    };

    const collapsedCount = computed(() => (isMobile.value ? 3 : 5));
    const hiddenEntries = computed(() => props.entries.slice(collapsedCount.value));
    const visibleEntries = computed(() =>
      expanded.value ? props.entries : props.entries.slice(0, collapsedCount.value)
    );

    onMounted(() => {
      syncViewport();
      mobileQuery = window.matchMedia("(max-width: 480px)");
      mobileQuery.addEventListener("change", syncViewport);
    });

    onBeforeUnmount(() => {
      if (mobileQuery) {
        mobileQuery.removeEventListener("change", syncViewport);
      }
    });

    return {
      expanded,
      collapsedCount,
      hiddenEntries,
      isMobile,
      visibleEntries,
    };
  },
};
</script>

<style scoped>
.preference-panel {
  position: relative;
  overflow: hidden;
  background: linear-gradient(180deg, #ffffff, #f8fafc);
  border: 1px solid #dfe6f2;
  border-radius: 20px;
  padding: 26px;
  box-shadow: 0 14px 32px rgba(18, 38, 77, 0.08);
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.preference-panel::before {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(120deg, rgba(54, 84, 174, 0.08), transparent 35%, rgba(210, 180, 84, 0.08));
  opacity: 0;
  transition: opacity 0.25s ease;
  pointer-events: none;
}

.preference-panel:hover {
  transform: translateY(-3px);
  box-shadow: 0 20px 44px rgba(18, 38, 77, 0.12);
}

.preference-panel:hover::before {
  opacity: 1;
}

.panel-header {
  position: relative;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 20px;
}

.panel-kicker {
  margin: 0 0 6px;
  color: #7d8cab;
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.panel-header h3 {
  margin: 0;
  color: #16294f;
  font-size: 1.6rem;
}

.panel-count {
  padding: 8px 14px;
  border-radius: 999px;
  background: #eef3ff;
  color: #284898;
  font-weight: 600;
  white-space: nowrap;
}

.preference-list {
  position: relative;
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  gap: 12px;
}

.preference-item {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 16px;
  border-radius: 14px;
  background: #f4f7fb;
  border: 1px solid #e4ebf6;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease, background 0.2s ease;
  cursor: default;
}

.preference-item:hover {
  transform: translateX(4px);
  border-color: #c5d4f1;
  background: linear-gradient(135deg, #f8fbff, #edf3ff);
  box-shadow: 0 12px 24px rgba(40, 72, 152, 0.08);
}

.preference-label {
  color: #51627f;
  font-weight: 600;
  transition: color 0.2s ease;
}

.preference-value {
  color: #172a5d;
  text-align: right;
  transition: color 0.2s ease;
}

.preference-item:hover .preference-label {
  color: #284898;
}

.preference-item:hover .preference-value {
  color: #0f2147;
}

.toggle-button {
  position: relative;
  margin-top: 18px;
  padding: 10px 16px;
  border: 1px solid #d7e2f7;
  border-radius: 999px;
  background: #f5f8ff;
  color: #1f4aa8;
  font-weight: 700;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease, border-color 0.2s ease;
}

.toggle-button:hover {
  transform: translateY(-1px);
  background: #eaf1ff;
  border-color: #bfd0f4;
  box-shadow: 0 10px 20px rgba(31, 74, 168, 0.12);
}

.toggle-button:focus-visible {
  outline: 2px solid #1f4aa8;
  outline-offset: 3px;
}

@media (max-width: 768px) {
  .preference-panel {
    padding: 20px;
  }

  .panel-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .panel-header h3 {
    font-size: 1.4rem;
  }

  .preference-item {
    flex-direction: column;
    gap: 4px;
    padding: 12px;
  }

  .preference-value {
    text-align: left;
    font-size: 0.95rem;
  }

  .preference-item:hover {
    transform: translateY(-2px);
  }
}

@media (max-width: 480px) {
  .preference-panel {
    padding: 16px;
    border-radius: 16px;
  }

  .panel-kicker {
    font-size: 0.8rem;
  }

  .panel-header h3 {
    font-size: 1.2rem;
  }

  .panel-count {
    padding: 6px 12px;
    font-size: 0.85rem;
  }

  .preference-list {
    gap: 10px;
  }

  .preference-item {
    padding: 10px;
    border-radius: 12px;
  }

  .preference-label {
    font-size: 0.85rem;
  }

  .preference-value {
    font-size: 0.9rem;
  }

  .toggle-button {
    width: 100%;
    font-size: 0.9rem;
    padding: 8px 14px;
  }
}
</style>
