<template>
  <section v-if="isReal" class="properties-sale">
    <div class="properties-sale__hero" v-reveal="0">
      <div>
        <p class="eyebrow">Activos captados</p>
        <h2>Propiedades disponibles</h2>
        <p>
          Revisa todas las propiedades registradas, con análisis de inversión y dossier completo cuando el activo proviene de un PDF.
        </p>
      </div>

      <div class="hero-badge">
        <span>{{ properties.length }} activos</span>
      </div>
    </div>

    <div v-if="loading" class="properties-sale__state" v-reveal="1">
      Cargando propiedades...
    </div>

    <div v-else-if="properties.length === 0" class="properties-sale__state" v-reveal="1">
      Todavía no hay propiedades registradas.
    </div>

      <transition-group v-else key="results" name="stagger-list" tag="div" class="properties-sale__grid">
        <div
          v-for="(property, index) in properties"
          :key="property.id"
          class="properties-sale__item"
          v-reveal="index + 1"
          :style="{ transitionDelay: `${Math.min(index * 70, 420)}ms` }"
        >
          <PropertyCard
            :property="property"
            @toggle-favorite="toggleFavorite"
          />
        </div>
      </transition-group>
  </section>
</template>

<script>
import { computed } from "vue";
import { storeToRefs } from "pinia";
import PropertyCard from "../components/PropertyCard.vue";
import { useUserStore } from "../stores/user";
import {
  fetchProperties,
  removeFavorite,
  saveFavorite,
} from "../services/properties";

const CATEGORY_LABELS = {
  activos: "Activos",
  edificios: "Edificios",
  fincas: "Fincas",
  hoteles: "Hoteles",
  parking: "Parking",
};

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
    };
  },

  setup() {
    const userStore = useUserStore()
    const { preferences, isReal } = storeToRefs(userStore)

    return {
      preferences,
      isReal,
    };
  },

  mounted() {
    this.loadProperties();
  },

  methods: {
    async loadProperties() {
      this.loading = true

      try {
        this.properties = await fetchProperties()
      } catch (error) {
        console.error(error);
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

.properties-sale__copy {
  display: grid;
  align-content: center;
}

.properties-sale__insights {
  display: grid;
  gap: 14px;
  align-content: center;
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

.hero-insight-card {
  padding: 18px 20px;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.14);
  backdrop-filter: blur(12px);
  box-shadow: 0 10px 24px rgba(7, 16, 36, 0.14);
}

.hero-insight-label {
  margin: 0 0 8px;
  font-size: 0.82rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.65);
}

.hero-insight-value {
  display: block;
  font-size: 1.55rem;
  line-height: 1.1;
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

  .properties-sale__insights {
    gap: 10px;
  }

  .hero-insight-card {
    padding: 12px 14px;
    border-radius: 16px;
  }

  .hero-insight-label {
    margin: 0 0 4px;
    font-size: 0.68rem;
  }

  .hero-insight-value {
    font-size: 1.1rem;
  }

  .properties-sale__state {
    padding: 16px;
    font-size: 0.84rem;
  }

  .properties-sale__grid {
    grid-template-columns: 1fr;
    gap: 14px;
  }
}
</style>
