<template>
  <article class="history-card">
    <div class="history-card__top">
      <h3>{{ item.category }}</h3>
      <div class="history-card__date">{{ formattedDate }}</div>
    </div>

    <div class="history-card__section">
      <p class="history-card__section-title">Preferencias</p>

      <ul class="history-card__list">
        <li
          v-for="entry in visibleEntries"
          :key="entry.key"
          class="history-card__item"
        >
          <span class="history-card__item-label">{{ entry.label }}</span>
          <span class="history-card__item-value">{{ entry.value }}</span>
        </li>
      </ul>

      <button
        v-if="hiddenEntries.length"
        type="button"
        class="history-card__toggle"
        @click="expanded = !expanded"
      >
        {{ expanded ? "Ver menos ▲" : "Ver más ▼" }}
      </button>
    </div>

    <div class="history-card__actions">
      <button
        type="button"
        class="history-card__action history-card__action--primary"
        @click="$emit('apply', item)"
      >
        Aplicar búsqueda
      </button>

      <button
        type="button"
        class="history-card__action history-card__action--danger"
        @click="$emit('delete', item)"
      >
        Eliminar
      </button>
    </div>
  </article>
</template>

<script>
import { computed, ref } from "vue";
import { buildPreferenceEntries } from "../data/preferenceSchemas";

export default {
  emits: ["apply", "delete"],
  props: {
    item: {
      type: Object,
      required: true,
    },
  },
  setup(props) {
    const expanded = ref(false);

    const entries = computed(() =>
      buildPreferenceEntries(props.item.category, props.item.preferences)
    );

    const visibleEntries = computed(() =>
      expanded.value ? entries.value : entries.value.slice(0, 5)
    );

    const hiddenEntries = computed(() => entries.value.slice(5));

    const formattedDate = computed(() => {
      if (!props.item.created_at) {
        return "";
      }

      return new Intl.DateTimeFormat("es-ES", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
      }).format(new Date(props.item.created_at));
    });

    return {
      expanded,
      formattedDate,
      hiddenEntries,
      visibleEntries,
    };
  },
};
</script>

<style scoped>
.history-card {
  position: relative;
  overflow: hidden;
  background: linear-gradient(180deg, #ffffff, #f8fafc);
  border: 1px solid #dfe6f2;
  border-radius: 20px;
  padding: 24px;
  box-shadow: 0 14px 28px rgba(18, 38, 77, 0.08);
  display: flex;
  flex-direction: column;
  gap: 18px;
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.history-card::before {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(120deg, rgba(54, 84, 174, 0.08), transparent 35%, rgba(210, 180, 84, 0.08));
  opacity: 0;
  transition: opacity 0.25s ease;
  pointer-events: none;
}

.history-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 20px 44px rgba(18, 38, 77, 0.12);
}

.history-card:hover::before {
  opacity: 1;
}

.history-card__top {
  position: relative;
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: flex-start;
}

.history-card h3 {
  margin: 0;
  font-size: 1.45rem;
  color: #16294f;
}

.history-card__date {
  padding: 8px 12px;
  border-radius: 999px;
  background: #eef3ff;
  color: #284898;
  font-weight: 600;
  white-space: nowrap;
}

.history-card__section-title,
.history-card__item-label {
  font-weight: 700;
}

.history-card__section-title {
  margin: 0 0 12px;
  color: #172a5d;
}

.history-card__list {
  position: relative;
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  gap: 10px;
}

.history-card__item {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  padding: 12px 14px;
  border-radius: 14px;
  background: #f4f7fb;
  border: 1px solid #e4ebf6;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease, background 0.2s ease;
}

.history-card__item-label {
  color: #51627f;
  transition: color 0.2s ease;
}

.history-card__item-value {
  color: #172a5d;
  text-align: right;
  transition: color 0.2s ease;
}

.history-card__item:hover {
  transform: translateX(4px);
  border-color: #c5d4f1;
  background: linear-gradient(135deg, #f8fbff, #edf3ff);
  box-shadow: 0 12px 24px rgba(40, 72, 152, 0.08);
}

.history-card__item:hover .history-card__item-label {
  color: #284898;
}

.history-card__item:hover .history-card__item-value {
  color: #0f2147;
}

.history-card__toggle {
  position: relative;
  margin-top: 16px;
  padding: 10px 16px;
  border: 1px solid #d7e2f7;
  border-radius: 999px;
  background: #f5f8ff;
  color: #1f4aa8;
  font-weight: 700;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease, border-color 0.2s ease;
}

.history-card__toggle:hover {
  transform: translateY(-1px);
  background: #eaf1ff;
  border-color: #bfd0f4;
  box-shadow: 0 10px 20px rgba(31, 74, 168, 0.12);
}

.history-card__actions {
  position: relative;
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.history-card__action {
  padding: 11px 18px;
  border-radius: 999px;
  border: none;
  font-weight: 700;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.history-card__action:hover {
  transform: translateY(-2px);
}

.history-card__action--primary {
  background: linear-gradient(135deg, #172a5d, #3654ae);
  color: white;
  box-shadow: 0 10px 22px rgba(23, 42, 93, 0.18);
}

.history-card__action--danger {
  background: #fff4f4;
  color: #b33535;
  border: 1px solid #f1cccc;
}

.history-card__action--danger:hover {
  background: #d94b4b;
  color: white;
  border-color: #d94b4b;
  box-shadow: 0 12px 24px rgba(217, 75, 75, 0.22);
}

@media (max-width: 768px) {
  .history-card__top,
  .history-card__item,
  .history-card__actions {
    flex-direction: column;
  }

  .history-card__item-value {
    text-align: left;
  }

  .history-card__item:hover {
    transform: translateY(-2px);
  }
}
</style>
