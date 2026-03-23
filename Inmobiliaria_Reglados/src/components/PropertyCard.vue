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
  border-radius: 24px;
  overflow: hidden;
  background: linear-gradient(180deg, rgba(255,255,255,0.98), #f7faff);
  border: 1px solid rgba(197, 212, 241, 0.82);
  box-shadow: 0 18px 40px rgba(23, 42, 93, 0.12);
  transition: z-index 0s, transform 0.32s ease, box-shadow 0.32s ease, border-color 0.32s ease, filter 0.32s ease;
  will-change: transform, box-shadow;
}

.property-card.popper-open,
.property-card:hover {
  z-index: 50;
  border-color: #bfd0f4;
  box-shadow: 0 24px 46px rgba(23, 42, 93, 0.16);
  transform: translateY(-5px) scale(1.01);
}

.property-card__media {
  position: relative;
  height: 240px;
  border-radius: 24px 24px 0 0;
  overflow: hidden;
}

.property-card__media img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  user-select: none;
  transition: transform 0.42s cubic-bezier(0.22, 1, 0.36, 1);
  will-change: transform;
}

.property-card__overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(10,21,46,0.06), rgba(10,21,46,0.5));
  transition: opacity 0.32s ease;
  user-select: none;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
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

/* FAVORITE BUTTON */

.favorite-button{
  display:flex;
  align-items:center;
  gap:6px;
  padding:4px 10px;
  border-radius:999px;
  border:none;
  background:rgba(255,255,255,0.18);
  backdrop-filter:blur(8px);
  font-size:0.9rem;
  font-weight:600;
  color:#fff;
  cursor:pointer;
  transition:all 0.2s ease;
  user-select: none;
}

.property-card:hover .property-card__media img,
.property-card.popper-open .property-card__media img {
  transform: scale(1.03);
}

.property-card:hover .property-card__overlay,
.property-card.popper-open .property-card__overlay {
  opacity: 0.88;
}

.favorite-icon{
  font-size:1.1rem;
  padding-bottom: 5px;
}

.favorite-button:hover{
  background:#c1a115;
  transform:scale(1.05);
  color:#ffffff;
}

.favorite-button.active{
  background:linear-gradient(135deg, #f4d078, #bd9b2c);
  color:#ffffff;
  transform:scale(1.1);
}

.favorite-button.popping{
  animation:favoritePop 0.35s ease;
}

@keyframes favoritePop{
  0%{ transform:scale(1); }
  50%{ transform:scale(1.3); }
  100%{ transform:scale(1.1); }
}

/* MATCH */

.property-card__body{
  padding:22px;
  display:grid;
  gap:14px;
}

.property-card__category{
  margin-bottom:0;
  color:#58709a;
  font-size:0.82rem;
  font-weight:700;
  letter-spacing:0.08em;
  text-transform:uppercase;
}

.property-card__body h3{
  margin:0;
  color:#172a5d;
  font-size:1.35rem;
  overflow-wrap:anywhere;
  line-height:1.25;
}

.property-card__meta,
.property-card__footer{
  display:flex;
  justify-content:space-between;
  gap:12px;
}

.property-card__meta{
  color:#5c6980;
  padding:12px 14px;
  border-radius:16px;
  background:#f3f7fd;
  border:1px solid #e1eaf7;
}

.property-card__footer{
  align-items:end;
  color:#172a5d;
  flex-wrap:wrap;
}

.property-card__footer strong{
  font-size:1.15rem;
  padding:10px 14px;
  border-radius:14px;
  background:linear-gradient(135deg, rgba(54, 84, 174, 0.08), rgba(244, 208, 120, 0.12));
}

.detail-link{
  margin-top:6px;
  padding:6px 16px;
  border-radius:999px;
  border:1px solid #172a5d;
  color:#172a5d;
  font-weight:600;
  text-decoration:none;
  transition:background 0.2s ease,color 0.2s ease;
}

.detail-link:hover{
  background:#172a5d;
  color:#fff;
}

/* =========================================
   RESPONSIVE (768px / 480px)
   ========================================= */

@media (max-width: 768px) {
  .property-card {
    border-radius: 20px;
  }
  .property-card__media {
    height: 200px;
  }
  .property-card__body {
    padding: 18px;
  }
  .property-card__body h3 {
    font-size: 1.2rem;
    margin: 0 0 10px;
  }
  .property-card__category {
    font-size: 0.75rem;
  }
  .property-card__meta,
  .property-card__footer {
    gap: 8px;
  }
  .property-card__footer {
    flex-direction: column;
    align-items: flex-start;
  }
  .property-card__meta {
    flex-direction: column;
    align-items: flex-start;
    font-size: 0.9rem;
    margin-bottom: 14px;
  }
  .property-card__footer strong {
    font-size: 1rem;
  }
  .favorite-button {
    padding: 8px 12px;
    font-size: 0.9rem;
  }
  .favorite-icon {
    font-size: 1rem;
    padding-bottom: 3px;
  }

}

@media (max-width: 480px) {
  .property-card {
    border-radius: 18px;
  }
  .property-card__media {
    height: 180px;
  }
  .property-card__body {
    padding: 16px;
  }
  .property-card__body h3 {
    font-size: 1.1rem;
  }
  .property-card__category {
    font-size: 0.7rem;
    margin-bottom: 8px;
  }
  .property-card__meta {
    font-size: 0.85rem;
    margin-bottom: 12px;
  }
  .property-card__footer strong {
    font-size: 0.95rem;
  }
  .property-card__actions {
    top: 12px;
    right: 12px;
    left: 12px;
  }
  .favorite-button {
    padding: 3px 6px;
    font-size: 0.65rem;
  }
  .favorite-icon {
    font-size: 0.75rem;
    padding-bottom: 2px;
  }
}

</style>
