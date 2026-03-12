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
          aria-label="Guardar en favoritos"
          @click="handleFavoriteClick"
        >
          {{ localFavorite ? "★" : "☆" }}
        </button>

        <div class="match-pill" :style="matchBackgroundStyle">
          <span class="match-pill__icon">❤</span>
          <span class="match-pill__value" :style="matchValueStyle">
            {{ animatedMatch }}% match
          </span>
        </div>
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
        <small>{{ property.match_count }}/{{ property.match_total }} coincidencias</small>
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
      animatedMatch: 0,
      animationFrame: null,
      matchScale: 1,
      favoritePopTimeout: null,
      isFavoritePopping: false,
    };
  },
  computed: {
    localFavorite() {
      return !!this.property.is_favorite;
    },

    matchValueStyle() {
      return {
        transform: `scale(${this.matchScale})`,
      };
    },

    matchBackgroundStyle() {
      const percentage = this.animatedMatch || 0;

      return {
        background: `linear-gradient(
        90deg,
        rgb(255, 101, 116) ${percentage}%,
        rgba(255,255,255,0.8) ${percentage}%
        )`,
      };
    },
  },

  mounted() {
    this.animateMatch();
  },

  beforeUnmount() {
    if (this.animationFrame) {
      cancelAnimationFrame(this.animationFrame);
    }

    if (this.favoritePopTimeout) {
      clearTimeout(this.favoritePopTimeout);
    }
  },

  watch: {
    "property.match_percentage"() {
      this.animateMatch();
    },
  },

  methods: {
    animateMatch() {
      if (this.animationFrame) {
        cancelAnimationFrame(this.animationFrame);
      }

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

      if (this.favoritePopTimeout) {
        clearTimeout(this.favoritePopTimeout);
      }

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
  overflow: hidden;
  border-radius: 24px;
  background: #fff;
  box-shadow: 0 18px 40px rgba(23, 42, 93, 0.12);
}

.property-card__media {
  position: relative;
  height: 240px;
}

.property-card__media img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.property-card__overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(10, 21, 46, 0.08), rgba(10, 21, 46, 0.42));
}

.property-card__actions {
  position: absolute;
  top: 18px;
  right: 18px;
  left: 18px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.favorite-button {
  padding: 0;
  border: none;
  background: transparent;
  font-size: 1.8rem;
  line-height: 1;
  color: rgba(255, 255, 255, 0.94);
  cursor: pointer;
  text-shadow: 0 6px 18px rgba(10, 21, 46, 0.34);
  transition: transform 0.18s ease, color 0.18s ease;
}

.favorite-button.active {
  color: #f2b632;
}

.favorite-button.popping {
  transform: scale(1.24) rotate(-8deg);
}

.match-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  border-radius: 999px;
  backdrop-filter: blur(10px);
  color: #17305e;
  font-weight: 700;
  overflow: hidden;
  transition: background 0.45s ease;
}

.match-pill__icon {
  color: #cd2a4e;
}

.match-pill__value {
  display: inline-block;
  transform-origin: center;
}

.property-card__body {
  padding: 22px;
}

.property-card__category {
  margin-bottom: 10px;
  color: #58709a;
  font-size: 0.82rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.property-card__body h3 {
  margin: 0 0 12px;
  color: #172a5d;
  font-size: 1.35rem;
}

.property-card__meta,
.property-card__footer {
  display: flex;
  justify-content: space-between;
  gap: 12px;
}

.property-card__meta {
  margin-bottom: 18px;
  color: #5c6980;
}

.property-card__footer {
  align-items: end;
  color: #172a5d;
}

.property-card__footer strong {
  font-size: 1.1rem;
}

.property-card__footer small {
  color: #73819b;
}
</style>
