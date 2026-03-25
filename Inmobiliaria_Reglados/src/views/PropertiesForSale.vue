<template>
  <section class="properties-sale">
    <div class="properties-sale__hero" v-reveal="0">
      <div class="properties-sale__copy">
        <p class="eyebrow">Catálogo Inmobiliario</p>
        <h2>Propiedades en venta</h2>
        <p>
          Explora nuestra selección de oportunidades inmobiliarias exclusivas
          disponibles en nuestra plataforma.
        </p>
      </div>

      <div class="hero-badges">
        <span class="hero-badge">{{ filteredProperties.length }} activos</span>
      </div>
    </div>

    <div class="properties-sale__filters" v-reveal="1">
      <button
        type="button"
        :class="['filter-btn', { active: effectiveCategory === '' }]"
        @click="localCategory = ''"
      >
        Todas
      </button>

      <button
        type="button"
        :class="['filter-btn', { active: effectiveCategory === 'hoteles' }]"
        @click="localCategory = 'hoteles'"
      >
        Hoteles
      </button>

      <button
        type="button"
        :class="['filter-btn', { active: effectiveCategory === 'fincas' }]"
        @click="localCategory = 'fincas'"
      >
        Fincas
      </button>

      <button
        type="button"
        :class="['filter-btn', { active: effectiveCategory === 'parking' }]"
        @click="localCategory = 'parking'"
      >
        Parking
      </button>

      <button
        type="button"
        :class="['filter-btn', { active: effectiveCategory === 'edificios' }]"
        @click="localCategory = 'edificios'"
      >
        Edificios
      </button>

      <button
        type="button"
        :class="['filter-btn', { active: effectiveCategory === 'activos' }]"
        @click="localCategory = 'activos'"
      >
        Activos
      </button>
    </div>

    <div class="properties-sale__state">
      DEBUG → isReal: {{ isReal }} | selectedCategory: {{ selectedCategory }} |
      localCategory: {{ localCategory }} | total properties: {{ properties.length }} |
      filtered: {{ filteredProperties.length }}
    </div>

    <div v-if="loading" class="properties-sale__state" v-reveal="2">
      Cargando propiedades...
    </div>

    <div
      v-else-if="!filteredProperties.length"
      class="properties-sale__state"
      v-reveal="2"
    >
      No hay propiedades disponibles para esta categoría.
    </div>

    <div v-else class="properties-sale__grid">
      <div
        v-for="(property, index) in filteredProperties"
        :key="property.id"
        class="properties-sale__item"
        v-reveal="index + 3"
        :style="{ transitionDelay: `${Math.min(index * 70, 420)}ms` }"
      >
        <PropertyCard
          :property="property"
          @toggle-favorite="toggleFavorite"
        />
      </div>
    </div>
  </section>
</template>

<script>
import { storeToRefs } from "pinia";
import PropertyCard from "../components/PropertyCard.vue";
import { useUserStore } from "../stores/user";
import {
  fetchProperties,
  removeFavorite,
  saveFavorite,
} from "../services/properties";

export default {
  name: "PropertiesForSale",

  components: {
    PropertyCard,
  },

  data() {
    return {
      loading: true,
      properties: [],
      pendingFavorites: new Set(),
      localCategory: "",
    };
  },

  setup() {
    const userStore = useUserStore();
    const { preferences, selectedCategory, isReal } = storeToRefs(userStore);

    return {
      preferences,
      selectedCategory,
      isReal,
    };
  },

  mounted() {
    this.loadProperties();
  },

  computed: {
    effectiveCategory() {
      return (
        (this.selectedCategory || "").trim() ||
        (this.localCategory || "").trim()
      );
    },

    filteredProperties() {
      const selected = this.effectiveCategory.toLowerCase();

      if (!selected) {
        return this.properties;
      }

      return this.properties.filter((property) => {
        const categoria = String(
          property.categoria || property.tipo_propiedad || ""
        )
          .trim()
          .toLowerCase();

        return categoria === selected;
      });
    },
  },

  methods: {
    async loadProperties() {
      this.loading = true;

      try {
        const response = await fetchProperties();
        console.log("Propiedades cargadas:", response);
        this.properties = response || [];
      } catch (error) {
        console.error("Error cargando propiedades:", error);
        this.properties = [];
      } finally {
        this.loading = false;
      }
    },

    async toggleFavorite(property) {
      if (this.pendingFavorites.has(property.id)) {
        return;
      }

      this.pendingFavorites.add(property.id);

      try {
        if (property.is_favorite) {
          await removeFavorite(property.id);
        } else {
          await saveFavorite({
            property_id: property.id,
            categoria: property.categoria,
            preferences: this.preferences,
          });
        }

        this.properties = this.properties.map((current) =>
          current.id === property.id
            ? { ...current, is_favorite: !current.is_favorite }
            : current
        );
      } catch (error) {
        console.error(error);
      } finally {
        this.pendingFavorites.delete(property.id);
      }
    },
  },
};
</script>

<style scoped>
.properties-sale {
  display: grid;
  gap: 28px;
}

.properties-sale__hero {
  position: relative;
  overflow: hidden;
  display: grid;
  grid-template-columns: minmax(0, 1.35fr) minmax(220px, 0.7fr);
  gap: 20px;
  padding: 28px 30px;
  border-radius: 28px;
  background:
    radial-gradient(circle at 14% 80%, transparent 0 9px, transparent 10px),
    radial-gradient(circle at 86% 16%, transparent 0 11px, transparent 12px),
    radial-gradient(circle at top right, rgba(244, 208, 120, 0.24), transparent 30%),
    linear-gradient(135deg, #12244d 0%, #20386b 55%, #3a5ca9 100%);
  color: #fff;
  box-shadow: 0 22px 48px rgba(18, 36, 77, 0.22);
  background-repeat: no-repeat;
}

.properties-sale__hero::before,
.properties-sale__hero::after {
  content: "";
  position: absolute;
  border-radius: 999px;
  pointer-events: none;
  opacity: 0.72;
  transition: opacity 0.28s ease;
}

.properties-sale__hero::before {
  width: 220px;
  height: 220px;
  right: -70px;
  top: -90px;
  background: rgba(255, 255, 255, 0.08);
}

.properties-sale__hero::after {
  width: 160px;
  height: 160px;
  left: -50px;
  bottom: -90px;
  background: rgba(255, 204, 84, 0.14);
}

.properties-sale__hero > * {
  position: relative;
  z-index: 2;
}

.hero-badges {
  display: flex;
  justify-content: flex-end;
  align-items: center;
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  padding: 14px 26px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.22);
  backdrop-filter: blur(14px);
  font-weight: 800;
  font-size: 1.35rem;
  color: #fff;
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.18);
  border: 1px solid rgba(255, 255, 255, 0.12);
}

.properties-sale__copy {
  display: grid;
  align-content: center;
}

.properties-sale__filters {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.filter-btn {
  border: 1px solid #d6dfef;
  background: #fff;
  color: #172a5d;
  border-radius: 999px;
  padding: 10px 18px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.filter-btn:hover {
  transform: translateY(-1px);
}

.filter-btn.active {
  background: #172a5d;
  color: #fff;
  border-color: #172a5d;
}

.eyebrow {
  margin: 0 0 10px;
  opacity: 0.82;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  font-size: 0.78rem;
  font-weight: 700;
}

.properties-sale__hero h2 {
  margin: 0 0 10px;
  font-size: 2.2rem;
  line-height: 1.05;
}

.properties-sale__hero p:last-child {
  margin: 0;
  max-width: 62ch;
  color: rgba(255, 255, 255, 0.82);
  line-height: 1.6;
}

.properties-sale__state {
  padding: 28px;
  border-radius: 24px;
  background: linear-gradient(180deg, #ffffff, #f8fafc);
  border: 1px solid #dfe6f2;
  color: #5a6880;
  box-shadow: 0 14px 32px rgba(23, 42, 93, 0.08);
}

.properties-sale__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
  gap: 22px;
  position: relative;
}

.properties-sale__item {
  min-width: 0;
}

@media (max-width: 1440px) {
  .properties-sale__hero {
    padding: 24px;
    gap: 16px;
  }

  .properties-sale__hero h2 {
    font-size: 1.8rem;
  }
}

@media (max-width: 768px) {
  .properties-sale {
    gap: 20px;
  }

  .properties-sale__hero {
    grid-template-columns: 1fr;
    padding: 22px;
  }

  .properties-sale__grid {
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 16px;
  }
}

@media (max-width: 480px) {
  .properties-sale__hero {
    gap: 12px;
    padding: 14px;
    border-radius: 20px;
  }

  .eyebrow {
    margin: 0 0 6px;
    font-size: 0.66rem;
  }

  .hero-badge {
    padding: 10px 18px;
    font-size: 1rem;
  }

  .properties-sale__copy {
    gap: 0;
  }

  .properties-sale__hero h2 {
    margin: 0 0 6px;
    font-size: 1.22rem;
  }

  .properties-sale__hero p:last-child {
    font-size: 0.82rem;
    line-height: 1.45;
  }

  .properties-sale__state {
    padding: 16px;
    font-size: 0.84rem;
  }

  .properties-sale__grid {
    grid-template-columns: 1fr;
    gap: 14px;
  }

  .properties-sale__filters {
    gap: 8px;
  }

  .filter-btn {
    padding: 8px 14px;
    font-size: 0.82rem;
  }
}
</style>