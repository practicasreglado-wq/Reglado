<template>
  <article 
    class="property-card" 
    :class="{ 'popper-open': popperVisible }"
    @click="closePopper"
  >
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

        <div class="match-pill" :style="matchBackgroundStyle">
          <span class="match-pill__icon">❤</span>
          <span class="match-pill__value" :style="matchValueStyle">
            {{ animatedMatch }}%
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

        <!-- BOTON MATCH DETAILS CON POPPER INTEGRADO -->
        <div class="match-details-wrapper">
          <button
            class="match-details-button"
            @click.stop="togglePopper"
          >
            {{ property.match_count }}/{{ property.match_total }} coincidencias
          </button>

          <MatchDetailsPopper
            v-if="property.match_details && property.match_details.length > 0"
            :visible="popperVisible"
            :details="property.match_details"
            :matchCount="property.match_count"
            :matchTotal="property.match_total"
            @close="closePopper"
          />
        </div>

      </div>
    </div>
  </article>
</template>

<script>
import MatchDetailsPopper from "./MatchDetailsPopper.vue";

export default {
  name: "PropertyCard",

  emits: [
    "toggle-favorite"
  ],

  components: {
    MatchDetailsPopper
  },

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
      popperVisible: false,
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
          rgb(256, 60, 60,1) ${percentage}%,
          rgba(255,255,255,0.75) ${percentage}%
        )`,
      };
    },
  },

  mounted() {
    this.animateMatch();
  },

  beforeUnmount() {
    if (this.animationFrame) cancelAnimationFrame(this.animationFrame);
    if (this.favoritePopTimeout) clearTimeout(this.favoritePopTimeout);
  },

  watch: {
    "property.match_percentage"() {
      this.animateMatch();
    },
  },

  methods: {
    animateMatch() {
      if (this.animationFrame) cancelAnimationFrame(this.animationFrame);

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

    togglePopper() {
      this.popperVisible = !this.popperVisible;
    },

    closePopper() {
      this.popperVisible = false;
    }
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
  background: #fff;
  box-shadow: 0 18px 40px rgba(23, 42, 93, 0.12);
  transition: z-index 0s, transform 0.28s ease, box-shadow 0.28s ease, filter 0.28s ease;
}

.property-card.popper-open,
.property-card:hover {
  z-index: 50;
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
}

.property-card__overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(10,21,46,0.08), rgba(10,21,46,0.42));
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
  background:rgba(255,255,255,0.95);
  backdrop-filter:blur(8px);
  font-size:0.9rem;
  font-weight:600;
  color:#c1a115;
  cursor:pointer;
  transition:all 0.2s ease;
  user-select: none;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
}

.favorite-icon{
  font-size:1.1rem;
  padding-bottom: 5px;
}

.favorite-button:hover{
  background:#c1a115;
  transform:scale(1.05);
  color: white;
}

.favorite-button.active{
  background:#c1a115;
  color:white;
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

/* MATCH */

.match-pill{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:10px 14px;
  border-radius:999px;
  backdrop-filter:blur(10px);
  color:#17305e;
  font-weight:700;
  overflow:hidden;

  transition:
    transform 0.25s ease,
    background 0.45s ease,
    box-shadow 0.25s ease;
  user-select: none;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
}

/* HOVER DEL PILL */
.match-pill:hover{
  transform:scale(1.15);
  box-shadow:0 6px 18px rgba(0,0,0,0.18);
}

.match-pill:hover .match-pill__icon{
  transform:scale(1.15);
}

/* CORAZON */

.match-pill__icon{ 
  color:#aa1132;
  display:inline-block;
  transition:transform 0.25s ease;
}

/* VALOR */

.match-pill__value{
  display:inline-block;
  transform-origin:center;
}

/* FOOTER */

.property-card__body{
  padding:22px;
}

.property-card__category{
  margin-bottom:10px;
  color:#58709a;
  font-size:0.82rem;
  font-weight:700;
  letter-spacing:0.08em;
  text-transform:uppercase;
}

.property-card__body h3{
  margin:0 0 12px;
  color:#172a5d;
  font-size:1.35rem;
  overflow-wrap:anywhere;
}

.property-card__meta,
.property-card__footer{
  display:flex;
  justify-content:space-between;
  gap:12px;
}

.property-card__meta{
  margin-bottom:18px;
  color:#5c6980;
}

.property-card__footer{
  align-items:end;
  color:#172a5d;
}

.property-card__footer strong{
  font-size:1.1rem;
}

/* BOTON MATCH DETAILS */

.match-details-button{
  border:none;
  background:#172a5d;
  cursor:pointer;
  color:#ffffff;
  font-weight:600;
  padding:6px 12px;
  border-radius:999px;
  transition:all .2s ease;
  user-select: none;
}

.match-details-button:hover{
  text-decoration:underline;
}

/* WRAPPER DEL BOTON Y POPPER */
.match-details-wrapper {
  position: relative;
  display: inline-block;
}

/* Modificamos el popper para que se comporte como un menú contextual absoluto */
:deep(.popper-overlay) {
  position: absolute;
  inset: auto;
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%);
  margin-bottom: 12px;
  width: max-content;
  z-index: 100;
  pointer-events: none; /* Para no bloquear clics de alrededor si es muy grande */
}

:deep(.popper-card) {
  position: relative;
  bottom: auto !important;
  left: auto !important;
  transform: none;
  pointer-events: auto; /* Reactivar puntero en la tarjeta */
}

:deep(.popper-card::after) {
  content: "";
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  border-width: 8px;
  border-style: solid;
  border-color: #172a5d transparent transparent transparent;
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
  .match-pill,
  .favorite-button {
    padding: 8px 12px;
    font-size: 0.9rem;
  }
  .favorite-icon {
    font-size: 1rem;
    padding-bottom: 3px;
  }

  /* Force Popper scaling */
  :deep(.popper-card) {
    width: 180px !important;
    padding: 14px 16px !important;
  }
  :deep(.popper-card h3) {
    font-size: 1.1rem !important;
  }
  :deep(.popper-card li) {
    font-size: 0.85rem !important;
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
  .match-pill,
  .favorite-button {
    padding: 3px 6px;
    font-size: 0.65rem;
  }
  .favorite-icon {
    font-size: 0.75rem;
    padding-bottom: 2px;
  }
  .match-details-button {
    padding: 4px 10px;
    font-size: 0.7rem;
    white-space: normal;
    text-align: left;
  }

  /* Force Popper scaling */
  :deep(.popper-card) {
    width: 120px !important;
    padding: 12px 14px !important;
  }
  :deep(.popper-card h3) {
    font-size: 0.75rem !important;
  }
  :deep(.popper-card li) {
    font-size: 0.65rem !important;
    padding: 2px 0 !important;
  }
  
  /* Hide scrollbar in Popper list */
  :deep(.popper-card ul) {
    scrollbar-width: none; /* Firefox */
  }
  :deep(.popper-card ul::-webkit-scrollbar) {
    display: none; /* WebKit/Chromium */
  }

  :deep(.popper-card .icon svg) {
    width: 10px !important;
    height: 10px !important;
  }

}

</style>

