<template>
  <article class="property-card">
    <div class="property-card__media">
      <img :src="property.imageUrl" :alt="property.titulo" />
      <div class="property-card__overlay"></div>

      <div class="property-card__actions">
        <div class="property-card__top-badges">
          <button
            v-if="hasMatch"
            class="match-badge"
            type="button"
            disabled
          >
            <span class="match-badge__label">Match</span>
            <strong class="match-badge__value">{{ formatMatch(matchValue) }}</strong>
          </button>

          <button
            class="favorite-button"
            :class="{ active: localFavorite, popping: isFavoritePopping }"
            type="button"
            :title="localFavorite ? 'Quitar de favoritos' : 'Guardar en favoritos'"
            aria-label="Guardar en favoritos"
            :disabled="favoriteLoading"
            @click="handleFavoriteClick"
          >
            <span class="favorite-icon">
              {{ localFavorite ? "★" : "☆" }}
            </span>

            <span class="favorite-text">
              {{ favoriteLoading ? "Procesando..." : localFavorite ? "Guardado" : "Favorito" }}
            </span>
          </button>

          <button
            v-if="showRemoveFavorite && localFavorite"
            class="remove-favorite-button"
            type="button"
            :disabled="favoriteLoading"
            title="Eliminar de favoritos"
            aria-label="Eliminar de favoritos"
            @click.stop.prevent="handleRemoveFavoriteClick"
          >
            {{ favoriteLoading ? "..." : "Eliminar" }}
          </button>
        </div>
      </div>
    </div>

    <div class="property-card__body">
      <div class="property-card__category">{{ property.categoria }}</div>
      <h3>{{ property.titulo }}</h3>

      <div class="property-card__meta">
        <span>{{ property.ciudad}}</span><span>{{ property.zona}}</span>
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

  emits: ["toggle-favorite", "remove-favorite"],

  props: {
    property: {
      type: Object,
      required: true,
    },
    showRemoveFavorite: {
      type: Boolean,
      default: false,
    },
    favoriteLoading: {
      type: Boolean,
      default: false,
    },
  },

  data() {
    return {
      favoritePopTimeout: null,
      isFavoritePopping: false,
      animationFrame: null,
      animatedMatch: 0,
      matchScale: 1,
    };
  },

  computed: {
    localFavorite() {
      return !!this.property.is_favorite;
    },

    matchValue() {
      const raw =
        this.property.match_percentage ??
        this.property.matchPercentage ??
        this.property.porcentaje_match ??
        this.property.match ??
        null;

      const num = Number(raw);

      if (!Number.isFinite(num)) {
        return null;
      }

      return num;
    },

    hasMatch() {
      return this.matchValue !== null && this.matchValue > 0;
    },
  },

  beforeUnmount() {
    if (this.animationFrame) cancelAnimationFrame(this.animationFrame);
    if (this.favoritePopTimeout) clearTimeout(this.favoritePopTimeout);
  },

  methods: {
    animateMatch() {
      const target = Number(this.matchValue || 0);
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

    formatMatch(value) {
      const percent = Number(value) || 0;
      return `${Math.round(percent)}%`;
    },

    handleFavoriteClick() {
      this.isFavoritePopping = false;

      if (this.favoriteLoading) {
        return;
      }

      if (this.favoritePopTimeout) clearTimeout(this.favoritePopTimeout);

      requestAnimationFrame(() => {
        this.isFavoritePopping = true;
      });

      this.favoritePopTimeout = setTimeout(() => {
        this.isFavoritePopping = false;
      }, 260);

      this.$emit("toggle-favorite", this.property);
    },

    handleRemoveFavoriteClick() {
      if (this.favoriteLoading) {
        return;
      }

      this.$emit("remove-favorite", this.property);
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
  max-width: calc(100% - 24px);
}

.property-card__top-badges {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.match-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 20px;
  border: none;
  background: rgba(31, 74, 168, 0.88);
  backdrop-filter: blur(8px);
  color: #fff;
  font-size: 0.8rem;
  font-weight: 700;
  cursor: default;
  opacity: 1;
}

.match-badge:disabled {
  opacity: 1;
}

.match-badge__label {
  line-height: 1;
  opacity: 0.92;
}

.match-badge__value {
  line-height: 1;
  font-size: 0.82rem;
}

.favorite-button {
  display: inline-flex;
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
  transition: transform 0.2s ease, background 0.2s ease, opacity 0.2s ease;
}

.favorite-button.active {
  background: rgba(22, 101, 52, 0.85);
}

.favorite-button.popping {
  transform: scale(1.06);
}

.favorite-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.favorite-icon {
  font-size: 0.95rem;
  line-height: 1;
}

.favorite-text {
  line-height: 1;
}

.remove-favorite-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 6px 12px;
  border-radius: 20px;
  border: 1px solid rgba(255, 255, 255, 0.34);
  background: rgba(180, 35, 24, 0.78);
  backdrop-filter: blur(8px);
  color: #fff;
  font-size: 0.8rem;
  font-weight: 700;
  cursor: pointer;
  transition: transform 0.2s ease, background 0.2s ease, opacity 0.2s ease;
}

.remove-favorite-button:hover:not(:disabled) {
  transform: translateY(-1px);
  background: rgba(180, 35, 24, 0.92);
}

.remove-favorite-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
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
  gap: 12px;
}

.property-card__footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
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

  .property-card__actions {
    top: 10px;
    right: 10px;
    max-width: calc(100% - 20px);
  }

  .property-card__top-badges {
    gap: 6px;
  }

  .favorite-button,
  .remove-favorite-button,
  .match-badge {
    padding: 6px 10px;
    font-size: 0.76rem;
  }
}

@media (max-width: 480px) {
  .property-card__media {
    height: 160px;
  }

  .property-card__body h3 {
    font-size: 1rem;
  }

  .property-card__footer {
    flex-direction: column;
    align-items: flex-start;
  }

  .property-card__footer strong {
    font-size: 1rem;
  }

  .property-card__actions {
    max-width: calc(100% - 16px);
  }
}
</style>