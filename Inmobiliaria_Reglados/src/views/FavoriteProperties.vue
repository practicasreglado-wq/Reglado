<template>
  <section class="favorite-properties">
    <div class="favorite-properties__hero">
      <div class="favorite-properties__copy">
        <p class="eyebrow">Gestión Personal</p>
        <h2>Mis propiedades favoritas</h2>
        <p>
          Aquí tienes los activos que has guardado para revisarlos más tarde,
          compararlos y seguir sus oportunidades.
        </p>
      </div>
    </div>

    <div v-if="loading" class="favorite-properties__state">
      Cargando tus favoritos...
    </div>

    <div v-else-if="properties.length === 0" class="favorite-properties__state">
      <p>Todavía no has guardado propiedades favoritas.</p>
    </div>

    <div v-else class="favorite-properties__grid">
      <div
        v-for="property in properties"
        :key="property.id"
        class="favorite-properties__item"
      >
        <PropertyCard
          :property="property"
          :show-remove-favorite="true"
          :favorite-loading="pendingFavorites.has(property.id)"
          @toggle-favorite="toggleFavorite"
          @remove-favorite="removeFromFavorites"
        />
      </div>
    </div>
  </section>
</template>

<script>
import PropertyCard from "../components/PropertyCard.vue";
import {
  fetchFavoriteProperties,
  removeFavorite,
  saveFavorite,
} from "../services/properties";

export default {
  name: "FavoriteProperties",

  components: {
    PropertyCard,
  },

  data() {
    return {
      properties: [],
      loading: true,
      pendingFavorites: new Set(),
    };
  },

  mounted() {
    this.getFavorites();
  },

  methods: {
    async getFavorites() {
      this.loading = true;

      try {
        this.properties = await fetchFavoriteProperties();
      } catch (error) {
        console.error("Error cargando favoritos:", error);
        this.properties = [];
      } finally {
        this.loading = false;
      }
    },

    async toggleFavorite(property) {
      if (!property?.id || this.pendingFavorites.has(property.id)) {
        return;
      }

      this.pendingFavorites.add(property.id);

      try {
        if (property.is_favorite) {
          await removeFavorite(property.id);
          this.properties = this.properties.filter(
            (item) => item.id !== property.id
          );
        } else {
          await saveFavorite(property.id);
          await this.getFavorites();
        }
      } catch (error) {
        console.error("Error actualizando favorito:", error);
      } finally {
        this.pendingFavorites.delete(property.id);
      }
    },

    async removeFromFavorites(property) {
      if (!property?.id || this.pendingFavorites.has(property.id)) {
        return;
      }

      this.pendingFavorites.add(property.id);

      try {
        await removeFavorite(property.id);
        this.properties = this.properties.filter(
          (item) => item.id !== property.id
        );
      } catch (error) {
        console.error("Error eliminando favorito:", error);
      } finally {
        this.pendingFavorites.delete(property.id);
      }
    },
  },
};
</script>

<style scoped>
.favorite-properties {
  display: grid;
  gap: 28px;
  min-width: 0;
}

.favorite-properties__hero {
  position: relative;
  overflow: hidden;
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 20px;
  padding: 28px 30px;
  border-radius: 28px;
  background:
    radial-gradient(circle at top right, rgba(255, 215, 126, 0.28), transparent 34%),
    linear-gradient(135deg, #12244d 0%, #20386b 55%, #3a5ca9 100%);
  box-shadow: 0 22px 48px rgba(18, 36, 77, 0.24);
  color: #fff;
  background-repeat: no-repeat;
}

.favorite-properties__hero::before,
.favorite-properties__hero::after {
  content: "";
  position: absolute;
  border-radius: 999px;
  pointer-events: none;
  opacity: 0.72;
  transition: opacity 0.28s ease;
}

.favorite-properties__hero::before {
  width: 200px;
  height: 200px;
  right: -60px;
  top: -80px;
  background: rgba(255, 255, 255, 0.08);
}

.favorite-properties__hero::after {
  width: 140px;
  height: 140px;
  left: -50px;
  bottom: -80px;
  background: rgba(255, 204, 84, 0.14);
}

.favorite-properties__copy {
  position: relative;
  z-index: 2;
  display: grid;
  gap: 8px;
  align-content: center;
}

.eyebrow {
  margin: 0 0 10px;
  opacity: 0.82;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  font-size: 0.78rem;
  font-weight: 700;
  color: #ffffff;
}

.favorite-properties__hero h2 {
  margin: 0 0 10px;
  font-size: 2.2rem;
  line-height: 1.05;
  color: #ffffff;
}

.favorite-properties__hero p:last-child {
  margin: 0;
  max-width: 62ch;
  color: rgba(255, 255, 255, 0.84);
  line-height: 1.6;
}

.favorite-properties__state {
  padding: 28px;
  border-radius: 24px;
  background: linear-gradient(180deg, #ffffff, #f8fafc);
  border: 1px solid #dfe6f2;
  color: #5a6880;
  box-shadow: 0 14px 32px rgba(23, 42, 93, 0.08);
}

.favorite-properties__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 22px;
  min-width: 0;
  align-items: stretch;
}

.favorite-properties__item {
  min-width: 0;
  width: 100%;
  display: flex;
}

.favorite-properties__item :deep(.property-card) {
  width: 100%;
  min-width: 0;
  max-width: 100%;
  height: 100%;
}

@media (max-width: 1440px) {
  .favorite-properties__hero {
    padding: 22px 30px;
  }

  .favorite-properties__grid {
    gap: 18px;
  }
}

@media (max-width: 980px) {
  .favorite-properties__hero {
    padding: 22px;
  }

  .favorite-properties__grid {
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
  }
}

@media (max-width: 768px) {
  .favorite-properties {
    gap: 20px;
  }

  .favorite-properties__hero {
    padding: 22px;
    border-radius: 22px;
  }

  .favorite-properties__hero h2 {
    font-size: 1.9rem;
  }

  .favorite-properties__grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }
}

@media (max-width: 480px) {
  .favorite-properties {
    gap: 16px;
  }

  .favorite-properties__hero {
    padding: 20px;
    min-height: auto;
    border-radius: 20px;
  }

  .favorite-properties__hero h2 {
    font-size: 1.55rem;
  }

  .favorite-properties__hero p:last-child {
    font-size: 0.95rem;
    line-height: 1.5;
  }

  .eyebrow {
    font-size: 0.72rem;
  }

  .favorite-properties__state {
    padding: 20px;
    border-radius: 18px;
  }
}
</style>