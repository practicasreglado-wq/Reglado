<template>
  <article class="property-card">
    <div class="property-card__media">
      <img :src="property.imageUrl" :alt="property.titulo" />
      <div class="property-card__overlay"></div>

      <div class="property-card__actions">
        <button
          class="favorite-button"
          :class="{ active: localFavorite, popping: isFavoritePopping }"
          type="button"
          :title="localFavorite ? 'Quitar de favoritos' : 'Guardar en favoritos'"
          aria-label="Guardar en favoritos"
          @click="handleFavoriteClick"
        >
          <span class="favorite-icon">
            {{ localFavorite ? "★" : "☆" }}
          </span>

          <span class="favorite-text">
            {{ localFavorite ? "Guardado" : "Favorito" }}
          </span>
        </button>

      </div>
    </div>

    <div class="property-card__body">
      <div class="property-card__category">{{ property.categoria }}</div>
      <h3>{{ property.titulo }}</h3>

      <div class="property-card__meta">
        <span>{{ property.ubicacion_general }}</span>
        <span>{{ formatSurface(property.metros_cuadrados) }}</span>
      </div>

    <div class="property-card__footer">
      <strong>{{ formatPrice(property.precio) }}</strong>

      <router-link :to="`/property/${property.id}`" class="detail-link">
        Ver ficha
      </router-link>

    </div>
  </div>
</article>
</template>

<script>
export default {
  name: "PropertyCard",

  emits: ["toggle-favorite"],

  props: {
    property: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      favoritePopTimeout: null,
      isFavoritePopping: false,
    };
  },

  computed: {
    localFavorite() {
      return !!this.property.is_favorite;
    },
  },

  beforeUnmount() {
    if (this.animationFrame) cancelAnimationFrame(this.animationFrame);
    if (this.favoritePopTimeout) clearTimeout(this.favoritePopTimeout);
  },

  watch: {
    // Sistema de matching eliminado
  },

  methods: {
    animateMatch() {
      // Nota: Esta animación se mantiene con fines visuales usando el valor estático del objeto

      const target = Number(this.property.match_percentage || 0);
      const start = performance.now();
      const duration = 1150;

      const tick = (timestamp) => {
        const progress = Math.min((timestamp - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);

        this.animatedMatch = Math.round(target * eased);
        this.matchScale = 1 + (1 - eased) * 0.18;

        if (progress < 1) {
          this.animationFrame = requestAnimationFrame(tick);
        } else {
          this.matchScale = 1;
        }
      };

      this.animatedMatch = 0;
      this.matchScale = 1.18;
      this.animationFrame = requestAnimationFrame(tick);
    },

    formatPrice(value) {
      return new Intl.NumberFormat("es-ES", {
        style: "currency",
        currency: "EUR",
        maximumFractionDigits: 0,
       }).format(Number(value || 0));
    },

    formatSurface(value) {
      return `${new Intl.NumberFormat("es-ES").format(Number(value || 0))} m²`;
    },

    handleFavoriteClick() {
      this.isFavoritePopping = false;

      if (this.favoritePopTimeout) clearTimeout(this.favoritePopTimeout);

      requestAnimationFrame(() => {
        this.isFavoritePopping = true;
      });

      this.favoritePopTimeout = setTimeout(() => {
        this.isFavoritePopping = false;
      }, 260);

      this.$emit("toggle-favorite", this.property);
    },

  },
};
</script>

<style scoped>

.property-card {
  position: relative;
  z-index: 1;
  width: 100%;
  min-width: 0;
  border-radius: 20px;
  overflow: hidden;
  background: linear-gradient(180deg, rgba(255,255,255,0.98), #f7faff);
  border: 1px solid rgba(197, 212, 241, 0.8);
  box-shadow: 0 12px 30px rgba(23, 42, 93, 0.08);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.property-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 40px rgba(23, 42, 93, 0.12);
}

.property-card__media {
  position: relative;
  height: clamp(160px, 20vw, 240px);
  overflow: hidden;
}

.property-card__media img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s ease;
}

.property-card:hover .property-card__media img {
  transform: scale(1.05);
}

.property-card__overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, transparent, rgba(10,21,46,0.4));
}

.property-card__actions {
  position: absolute;
  top: var(--spacing-sm);
  right: var(--spacing-sm);
  z-index: 2;
}

.favorite-button {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 6px 12px;
  border-radius: 20px;
  border: none;
  background: rgba(255,255,255,0.2);
  backdrop-filter: blur(8px);
  color: #fff;
  font-size: 0.8rem;
  font-weight: 700;
  cursor: pointer;
}

.property-card__body {
  padding: var(--spacing-md);
  display: grid;
  gap: var(--spacing-sm);
}

.property-card__category {
  color: var(--azul-secundario);
  font-size: 0.75rem;
  font-weight: 800;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.property-card__body h3 {
  margin: 0;
  color: var(--azul-principal);
  font-size: clamp(1.1rem, 1.5vw, 1.3rem);
  line-height: 1.3;
}

.property-card__meta {
  display: flex;
  justify-content: space-between;
  padding: 10px 14px;
  border-radius: 12px;
  background: #f3f7fd;
  font-size: 0.85rem;
  color: #5c6980;
}

.property-card__footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 4px;
}

.property-card__footer strong {
  font-size: 1.1rem;
  color: var(--azul-principal);
}

.detail-link {
  padding: 6px 16px;
  border-radius: 20px;
  border: 1.5px solid var(--azul-principal);
  color: var(--azul-principal);
  font-size: 0.85rem;
  font-weight: 700;
  text-decoration: none;
  transition: all 0.2s ease;
}

.detail-link:hover {
  background: var(--azul-principal);
  color: #fff;
}

@media (max-width: 768px) {
  .property-card__media {
    height: 180px;
  }
  
  .property-card__body {
    padding: var(--spacing-sm);
  }

  .property-card__meta {
    flex-direction: row;
    font-size: 0.8rem;
  }
}

@media (max-width: 480px) {
  .property-card__media {
    height: 160px;
  }
  
  .property-card__body h3 {
    font-size: 1rem;
  }

  .property-card__footer strong {
    font-size: 1rem;
  }
}

</style>
