<template>
  <section class="properties-sale">
    <div class="properties-sale__hero">
      <div class="properties-sale__copy">
        <p class="eyebrow">Gestión Personal</p>
        <h2>Mis propiedades</h2>
        <p>
          Administra y supervisa tus activos inmobiliarios publicados. 
          Aquí puedes ver el estado de tus anuncios y gestionar tus propiedades.
        </p>
      </div>

      <div class="hero-actions">
        <button class="hero-action hero-action--primary" @click="goCreate">
          Crear nueva propiedad
        </button>
      </div>
    </div>

    <div v-if="loading" class="properties-sale__state">
      Cargando tus propiedades...
    </div>

    <div v-else-if="properties.length === 0" class="properties-sale__state">
      <p>Todavía no tienes propiedades publicadas. ¡Crea la primera!</p>
    </div>

    <div v-else class="properties-sale__grid">
      <div
        v-for="property in properties"
        :key="property.id"
        class="properties-sale__item"
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
import { backendJson } from "../services/backend";
import PropertyCard from "../components/PropertyCard.vue";
import { normalizeProperty, removeFavorite, saveFavorite } from "../services/properties";

export default {
  name: "MyPropertiesForSale",

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
    this.getProperties();
  },

  methods: {
    goCreate() {
      this.$router.push("/profile/create-property");
    },

    async getProperties() {
      try {
        const payload = await backendJson("api/get_user_properties_for_sale.php");
        
        // El backend devuelve: id, nombre, ubicacion, precio, tipo
        // Normalizamos para PropertyCard (titulo, categoria, ubicacion_general)
        this.properties = (payload || []).map(p => normalizeProperty({
          ...p,
          titulo: p.nombre,
          categoria: p.tipo,
          ubicacion_general: p.ubicacion
        }));
      } catch (error) {
        console.error("Error cargando propiedades:", error);
      } finally {
        this.loading = false;
      }
    },

    async toggleFavorite(property) {
      if (this.pendingFavorites.has(property.id)) return;
      this.pendingFavorites.add(property.id);

      try {
        if (property.is_favorite) {
          await removeFavorite(property.id);
        } else {
          await saveFavorite({
            property_id: property.id,
            categoria: property.categoria
          });
        }

        const res = await axios.get(
          "http://localhost/Reglado/Inmobiliaria_Reglados/backend/api/get_user_properties_for_sale.php",
          { withCredentials: true }
        );

        // Si el backend devuelve directamente el array
this.properties = Array.isArray(res.data)
  ? res.data.filter(p => p && p.nombre && p.precio)
  : [];
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

.hero-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
}

.hero-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 46px;
  padding: 0 24px;
  border-radius: 14px;
  text-decoration: none;
  font-weight: 700;
  transition: all 0.22s ease;
  cursor: pointer;
  border: none;
}

.hero-action--primary {
  background: #f4d078;
  color: #172a5d;
  box-shadow: 0 14px 28px rgba(244, 208, 120, 0.22);
}

.hero-action:hover {
  transform: translateY(-2px);
  filter: brightness(1.05);
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
}

@media (max-width: 768px) {
  .properties-sale__hero {
    grid-template-columns: 1fr;
    padding: 22px;
  }
  
  .hero-actions {
    justify-content: flex-start;
  }

  .properties-sale__grid {
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 16px;
  }
}
</style>