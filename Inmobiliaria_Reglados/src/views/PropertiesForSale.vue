<template>
  <section class="properties-sale">
    <div class="properties-sale__hero" v-reveal="0">
      <div>
        <p class="eyebrow">Matching inmobiliario</p>
        <h2>Propiedades en venta</h2>
        <p>
          Seleccionamos oportunidades según tus preferencias guardadas y calculamos
          el porcentaje de coincidencia en tiempo real.
        </p>
      </div>

      <div class="hero-badge">
        <span>{{ selectedCategory || "Sin categoría" }}</span>
      </div>
    </div>

    <div v-if="loading" class="properties-sale__state" v-reveal="1">
      Cargando propiedades compatibles...
    </div>

    <div v-else-if="!selectedCategory" class="properties-sale__state" v-reveal="1">
      Guarda primero tus preferencias para poder calcular matches.
    </div>

    <div v-else-if="properties.length === 0" class="properties-sale__state" v-reveal="1">
      Todavía no hay propiedades de ejemplo para {{ selectedCategory }}.
    </div>

    <div v-else class="properties-sale__grid">
      <PropertyCard
        v-for="(property, index) in properties"
        :key="property.id"
        v-reveal="index + 1"
        :property="property"
        @toggle-favorite="toggleFavorite"
      />
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
    };
  },
  setup() {
    const userStore = useUserStore();
    const { selectedCategory } = storeToRefs(userStore);
    return { selectedCategory };
  },
  mounted() {
    this.loadProperties();
  },
  watch: {
    selectedCategory() {
      this.loadProperties();
    },
  },
  methods: {
    async loadProperties() {
      if (!this.selectedCategory) {
        this.properties = [];
        this.loading = false;
        return;
      }

      this.loading = true;

      try {
        this.properties = await fetchProperties(this.selectedCategory);
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
          await saveFavorite(property.id);
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
  display: flex;
  justify-content: space-between;
  gap: 20px;
  padding: 28px 30px;
  border-radius: 24px;
  background: linear-gradient(135deg, #172a5d, #3352a4);
  color: #fff;
}

.eyebrow {
  margin: 0 0 10px;
  opacity: 0.72;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  font-size: 0.78rem;
}

.properties-sale__hero h2 {
  margin: 0 0 10px;
  font-size: 2rem;
}

.properties-sale__hero p:last-child {
  margin: 0;
  max-width: 62ch;
}

.hero-badge {
  display: flex;
  align-items: center;
}

.hero-badge span {
  padding: 14px 24px;
  border-radius: 999px;
  background: linear-gradient(135deg, #f3c94b, #e5b62f);
  color: #172a5d;
  font-size: 1.05rem;
  font-weight: 800;
}

.properties-sale__state {
  padding: 28px;
  border-radius: 20px;
  background: #fff;
  color: #5a6880;
  box-shadow: 0 12px 26px rgba(23, 42, 93, 0.08);
}

.properties-sale__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
  gap: 22px;
}

@media (max-width: 768px) {
  .properties-sale__hero {
    flex-direction: column;
    padding: 22px;
  }
}
</style>
