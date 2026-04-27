<template>
  <article class="property-card">
    <div class="property-card__media property-card__media--map">
      <div v-if="hasMapCoordinates" class="property-card__map-full">
  <div ref="mapContainer" class="property-card__leaflet-map"></div>

  <div class="property-card__map-overlay"></div>

  <div class="property-card__zone-radius-badge">
    Zona aprox. · {{ areaRadiusLabel }}
  </div>
</div>

      <div v-else class="property-card__map-fallback">
        <div class="property-card__map-fallback-content">
          <div class="property-card__map-fallback-icon">📍</div>
          <div class="property-card__map-fallback-title">Ubicación no disponible</div>
          <div class="property-card__map-fallback-text">
            No hay datos suficientes para mostrar una ubicación aproximada.
          </div>
        </div>
      </div>

      <div class="property-card__actions">
        <div class="property-card__top-badges">
          <div
            v-if="hasMatch"
            class="match-pill"
            :style="{ transform: `scale(${matchScale})` }"
          >
            <div class="match-pill__top">
              <span class="match-pill__label">Match</span>
              <strong class="match-pill__value">{{ formatMatch(animatedMatch) }}</strong>
            </div>

            <div class="match-pill__bar">
              <div
                class="match-pill__bar-fill"
                :style="{ width: `${Math.max(0, Math.min(animatedMatch, 100))}%` }"
              ></div>
            </div>
          </div>

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
      <div class="property-card__heading">
  <div class="property-card__category">{{ property.categoria }}</div>

  <span
    class="status-badge"
    :class="property.estado === 'vendido' ? 'status-badge--sold' : 'status-badge--available'"
  >
    {{ property.estado === 'vendido' ? 'Vendida' : 'Disponible' }}
  </span>
</div>

<h3>{{ property.titulo }}</h3>

      <div class="property-card__meta">
        <span>{{ property.ciudad || "Sin ciudad" }}</span>
        <span>{{ property.zona || "Sin zona" }}</span>
        <span>{{ formatSurface(property.metros_cuadrados) }}</span>
      </div>

      <div class="property-card__footer">
        <strong>{{ formatPrice(property.precio) }}</strong>

        <router-link
          :to="`/property/${property.id}`"
          :class="detailButtonClass"
        >
          {{ detailButtonLabel }}
        </router-link>
      </div>
    </div>

    <div class="property-card__footer-actions">
      <button
        v-if="showDeleteButton"
        class="property-card__delete-button"
        type="button"
        :disabled="deleteLoading"
        @click.stop="$emit('delete-property', property)"
      >
        {{ deleteLoading ? "Eliminando..." : "Eliminar" }}
      </button>
    </div>
  </article>
</template>

<script>
import L from "leaflet";
import "leaflet/dist/leaflet.css";

export default {
  name: "PropertyCard",

  emits: ["toggle-favorite", "remove-favorite", "delete-property"],

  props: {
    property: {
      type: Object,
      required: true,
    },
    favoriteLoading: {
      type: Boolean,
      default: false,
    },
    showDeleteButton: {
      type: Boolean,
      default: false,
    },
    deleteLoading: {
      type: Boolean,
      default: false,
    },
    showRemoveFavorite: {
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
    map: null,
    mapCircle: null,
    tileLayer: null,
    mapHasBeenFitted: false,
    resizeObserver: null,
    resizeTimeout: null,
  };
},

  computed: {
    localFavorite() {
      return !!this.property.is_favorite;
    },

    documentAccessStatus() {
      const validadoAdmin = Number(this.property.validado_admin ?? 0);
      const status = String(this.property.status ?? "").toLowerCase();

      const ndaUploaded = Boolean(
        this.property.nda_uploaded ??
        this.property.signed_nda_uploaded ??
        false
      );

      const loiUploaded = Boolean(
        this.property.loi_uploaded ??
        this.property.signed_loi_uploaded ??
        false
      );

      const ndaApproved = Boolean(
        this.property.nda_approved ??
        false
      );

      const loiApproved = Boolean(
        this.property.loi_approved ??
        false
      );

      const dossierUnlocked = Boolean(
        this.property.dossier_unlocked ??
        this.property.acceso_dossier ??
        false
      );

      if (validadoAdmin === -1 || status === "rejected") {
        return "rejected";
      }

      if (dossierUnlocked || (ndaApproved && loiApproved) || validadoAdmin === 1 || status === "approved") {
        return "approved";
      }

      if (ndaUploaded || loiUploaded || status === "pending") {
        return "pending";
      }

      return "idle";
    },

detailButtonLabel() {
  switch (this.documentAccessStatus) {
    case "pending":
      return "Pendiente";
    case "rejected":
      return "Rechazado";
    case "approved":
      return "Aceptado";
    default:
      return "Ver ficha";
  }
},

detailButtonClass() {
  return `detail-link detail-link--${this.documentAccessStatus}`;
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

      return Math.max(0, Math.min(Math.round(num), 100));
    },

    hasMatch() {
      return this.matchValue !== null && this.matchValue > 0;
    },

    normalizedZona() {
      return this.normalizeLocationPart(this.property.zona);
    },

    normalizedCiudad() {
      return this.normalizeLocationPart(this.property.ciudad);
    },

    normalizedProvincia() {
      return this.normalizeLocationPart(this.property.provincia);
    },

    normalizedPais() {
      return this.normalizeLocationPart(this.property.pais) || "España";
    },

    lat() {
      const raw =
        this.property.map_latitud ??
        this.property.latitud ??
        this.property.latitude ??
        this.property.geo_lat ??
        null;

      const value = Number(raw);

      if (!Number.isFinite(value)) {
        return null;
      }

      if (Math.abs(value) < 0.000001) {
        return null;
      }

      return value;
    },

    lon() {
      const raw =
        this.property.map_longitud ??
        this.property.longitud ??
        this.property.longitude ??
        this.property.geo_lng ??
        this.property.lng ??
        null;

      const value = Number(raw);

      if (!Number.isFinite(value)) {
        return null;
      }

      if (Math.abs(value) < 0.000001) {
        return null;
      }

      return value;
    },

    hasMapCoordinates() {
      return this.lat !== null && this.lon !== null;
    },

    approximateLocationLabel() {
      if (this.normalizedCiudad && this.normalizedZona) {
        return `${this.normalizedCiudad}, ${this.normalizedZona}`;
      }

      if (this.normalizedCiudad) {
        return this.normalizedCiudad;
      }

      if (this.normalizedZona) {
        return this.normalizedZona;
      }

      return "Ubicación aproximada";
    },

    mapRadiusMeters() {
      if (this.normalizedZona && this.normalizedCiudad) {
        return 900;
      }

      if (this.normalizedCiudad && this.normalizedProvincia) {
        return 2500;
      }

      if (this.normalizedCiudad) {
        return 3000;
      }

      if (this.normalizedProvincia) {
        return 6000;
      }

      return 2000;
    },

    areaRadiusLabel() {
      const meters = this.mapRadiusMeters;

      if (meters < 1000) {
        return `${meters} m`;
      }

      return `${(meters / 1000).toLocaleString("es-ES", {
        minimumFractionDigits: meters % 1000 === 0 ? 0 : 1,
        maximumFractionDigits: 1,
      })} km`;
    },

    displayCenter() {
      if (!this.hasMapCoordinates) {
        return null;
      }

      return this.getOffsetCoordinates(this.lat, this.lon);
    },
  },

  mounted() {
  if (this.hasMatch) {
    this.animateMatch();
  }

  if (this.hasMapCoordinates) {
    this.$nextTick(() => {
      this.initMap();
      this.setupResizeObserver();
    });
  }
},

  watch: {
    matchValue(newValue) {
      if (Number.isFinite(newValue) && newValue > 0) {
        this.animateMatch();
      } else {
        this.animatedMatch = 0;
        this.matchScale = 1;
      }
    },

    hasMapCoordinates(newValue) {
      if (newValue) {
        this.$nextTick(() => {
          this.initMap();
          this.setupResizeObserver();
        });
      } else {
        this.destroyMap();
      }
    },

    lat() {
      this.refreshMapCircle(true);
    },

    lon() {
      this.refreshMapCircle(true);
    },

    mapRadiusMeters() {
      this.refreshMapCircle(true);
    },
  },

  beforeUnmount() {
    if (this.animationFrame) cancelAnimationFrame(this.animationFrame);
    if (this.favoritePopTimeout) clearTimeout(this.favoritePopTimeout);
    if (this.resizeTimeout) clearTimeout(this.resizeTimeout);
    this.teardownResizeObserver();
    this.destroyMap();
  },

  methods: {
    normalizeLocationPart(value) {
      const normalized = String(value ?? "").trim();

      if (
        !normalized ||
        normalized.toLowerCase() === "null" ||
        normalized.toLowerCase() === "undefined" ||
        normalized.toLowerCase() === "n/a" ||
        normalized.toLowerCase() === "-" ||
        normalized.toLowerCase() === "sin definir"
      ) {
        return "";
      }

      return normalized;
    },

    getStableOffsetSeed() {
      const base = String(
        this.property?.id ??
        `${this.lat ?? ""}-${this.lon ?? ""}`
      );

      let hash = 0;
      for (let i = 0; i < base.length; i += 1) {
        hash = (hash * 31 + base.charCodeAt(i)) >>> 0;
      }

      return hash;
    },

    getOffsetCoordinates(lat, lon) {
      const seed = this.getStableOffsetSeed();

      const offsetMeters = 400 + (seed % 90);
      const angleDeg = seed % 360;
      const angle = angleDeg * (Math.PI / 180);

      const earthRadius = 6378137;

      const dLat =
        (offsetMeters * Math.cos(angle)) / earthRadius * (180 / Math.PI);

      const dLon =
        (offsetMeters * Math.sin(angle)) /
        (earthRadius * Math.cos((lat * Math.PI) / 180)) *
        (180 / Math.PI);

      return [
        lat + dLat,
        lon + dLon,
      ];
    },

    initMap() {
      if (!this.$refs.mapContainer || !this.hasMapCoordinates || !this.displayCenter) {
        return;
      }

      if (this.map) {
        this.refreshMapCircle(false);
        return;
      }

      this.map = L.map(this.$refs.mapContainer, {
        zoomControl: true,
        attributionControl: false,
        dragging: true,
        scrollWheelZoom: true,
        doubleClickZoom: true,
        boxZoom: false,
        keyboard: false,
        tap: true,
        preferCanvas: true,
      });

      this.map.setView(this.displayCenter, 14);

      this.tileLayer = L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
      });

      this.tileLayer.addTo(this.map);

      this.refreshMapCircle(false);

      setTimeout(() => {
        if (this.map) {
          this.map.invalidateSize();
          this.map.setView(this.displayCenter, 14);
        }
      }, 300);
    },

    refreshMapCircle(resetView = false) {
      if (!this.map || !this.hasMapCoordinates || !this.displayCenter) {
        return;
      }

      const center = this.displayCenter;
      const radius = this.mapRadiusMeters;

      if (this.mapCircle) {
        this.map.removeLayer(this.mapCircle);
      }

      this.mapCircle = L.circle(center, {
        radius,
        stroke: true,
        color: "#2563eb",
        weight: 2,
        opacity: 0.9,
        fill: true,
        fillColor: "#2563eb",
        fillOpacity: 0.18,
      }).addTo(this.map);

      if (!this.mapHasBeenFitted || resetView) {
        this.map.setView(center, 14);
        this.mapHasBeenFitted = true;
      }

      setTimeout(() => {
        if (this.map) {
          this.map.invalidateSize();
        }
      }, 50);
    },

    destroyMap() {
      this.teardownResizeObserver();

      if (this.map) {
        this.map.remove();
        this.map = null;
        this.mapCircle = null;
        this.tileLayer = null;
        this.mapHasBeenFitted = false;
      }
    },

    animateMatch() {
      if (this.animationFrame) {
        cancelAnimationFrame(this.animationFrame);
      }

      const target = Number(this.matchValue || 0);
      const start = performance.now();
      const duration = 1200;

      const tick = (timestamp) => {
        const progress = Math.min((timestamp - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);

        this.animatedMatch = Math.round(target * eased);
        this.matchScale = 1 + (1 - eased) * 0.08;

        if (progress < 1) {
          this.animationFrame = requestAnimationFrame(tick);
        } else {
          this.animatedMatch = target;
          this.matchScale = 1;
        }
      };

      this.animatedMatch = 0;
      this.matchScale = 1.05;
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

    setupResizeObserver() {
  if (!this.$refs.mapContainer || this.resizeObserver) {
    return;
  }

  this.resizeObserver = new ResizeObserver(() => {
    if (this.resizeTimeout) {
      clearTimeout(this.resizeTimeout);
    }

    this.resizeTimeout = setTimeout(() => {
      this.handleMapResize();
    }, 80);
  });
    this.resizeObserver.observe(this.$refs.mapContainer);
  },

    teardownResizeObserver() {
      if (this.resizeObserver) {
        this.resizeObserver.disconnect();
        this.resizeObserver = null;
      }
    },

    handleMapResize() {
      if (!this.map || !this.hasMapCoordinates || !this.displayCenter) {
        return;
      }

      this.$nextTick(() => {
        setTimeout(() => {
          if (!this.map) return;

          this.map.invalidateSize();

          const center = this.displayCenter;
          this.map.setView(center, this.map.getZoom() || 14);
        }, 60);
      });
    },
  },
};

</script>

<style scoped>
.property-card__heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 32px;
  padding: 6px 12px;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  border: 1px solid transparent;
  white-space: nowrap;
  line-height: 1;
}

.status-badge--available {
  background: linear-gradient(180deg, #ecfdf3 0%, #dcfce7 100%);
  color: #15803d;
  border-color: rgba(34, 197, 94, 0.22);
  box-shadow: 0 6px 14px rgba(34, 197, 94, 0.08);
}

.status-badge--sold {
  background: linear-gradient(180deg, #fff1f2 0%, #ffe4e6 100%);
  color: #be123c;
  border-color: rgba(244, 63, 94, 0.22);
  box-shadow: 0 6px 14px rgba(244, 63, 94, 0.08);
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
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 110px;
}

.detail-link:hover {
  background: var(--azul-principal);
  color: #fff;
}

.detail-link--idle {
  border-color: var(--azul-principal);
  color: var(--azul-principal);
}

.detail-link--idle:hover {
  background: var(--azul-principal);
  color: #fff;
}

.detail-link--pending {
  border-color: #f3d37e;
  background: #fff7db;
  color: #92400e;
}

.detail-link--pending:hover {
  background: #f8e7a8;
  color: #7c2d12;
}

.detail-link--rejected {
  border-color: #fecdd3;
  background: #fff1f2;
  color: #be123c;
}

.detail-link--rejected:hover {
  background: #ffe4e6;
  color: #9f1239;
}

.detail-link--approved {
  border-color: #bfe8cf;
  background: #e8f8ef;
  color: #166534;
}

.detail-link--approved:hover {
  background: #d9f3e4;
  color: #14532d;
}
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
  height: clamp(190px, 22vw, 260px);
  overflow: hidden;
  background: #dfe8f5;
}

.property-card__media--map {
  padding: 0;
}

.property-card__map-full,
.property-card__map-fallback {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
}

.property-card__leaflet-map {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  z-index: 1;
}

.property-card__map-overlay {
  position: absolute;
  inset: 0;
  background:
    linear-gradient(180deg, rgba(8, 15, 35, 0.08) 0%, rgba(8, 15, 35, 0.02) 35%, rgba(8, 15, 35, 0.28) 100%);
  pointer-events: none;
  z-index: 401;
}

.property-card__zone-radius-badge {
  position: absolute;
  right: 14px;
  bottom: 14px;
  z-index: 402;
  min-height: 36px;
  display: inline-flex;
  align-items: center;
  padding: 0 12px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.94);
  color: var(--azul-principal);
  font-size: 0.76rem;
  font-weight: 800;
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.14);
}

.property-card__map-fallback {
  display: flex;
  align-items: center;
  justify-content: center;
  background:
    radial-gradient(circle at top left, rgba(37, 99, 235, 0.22), transparent 35%),
    radial-gradient(circle at bottom right, rgba(15, 23, 42, 0.18), transparent 30%),
    linear-gradient(135deg, #eef4ff 0%, #dbeafe 100%);
}

.property-card__map-fallback-content {
  text-align: center;
  padding: 24px;
  color: var(--azul-principal);
}

.property-card__map-fallback-icon {
  font-size: 2rem;
  margin-bottom: 10px;
}

.property-card__map-fallback-title {
  font-size: 1rem;
  font-weight: 800;
  margin-bottom: 6px;
}

.property-card__map-fallback-text {
  font-size: 0.86rem;
  color: #4b5b77;
  line-height: 1.4;
}

.property-card__actions {
  position: absolute;
  top: 12px;
  right: 12px;
  z-index: 403;
  max-width: calc(100% - 24px);
}

.property-card__top-badges {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.match-pill {
  min-width: 132px;
  padding: 8px 10px;
  border-radius: 14px;
  background: rgba(16, 55, 130, 0.92);
  color: #fff;
  box-shadow: 0 10px 18px rgba(16, 55, 130, 0.28);
  backdrop-filter: blur(8px);
  transition: transform 0.2s ease;
}

.match-pill__top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.match-pill__label {
  font-size: 0.72rem;
  font-weight: 700;
  opacity: 0.92;
}

.match-pill__value {
  font-size: 0.82rem;
  line-height: 1;
}

.match-pill__bar {
  width: 100%;
  height: 7px;
  margin-top: 7px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.2);
  overflow: hidden;
}

.match-pill__bar-fill {
  height: 100%;
  border-radius: 999px;
  background: linear-gradient(90deg, #8fd3ff 0%, #ffffff 100%);
  transition: width 0.18s linear;
}

.favorite-button {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.35);
  background: linear-gradient(180deg, rgba(255, 94, 125, 0.96), rgba(226, 35, 95, 0.96));
  backdrop-filter: blur(8px);
  color: #ffffff;
  font-size: 0.82rem;
  font-weight: 800;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease, filter 0.2s ease;
  box-shadow: 0 10px 22px rgba(226, 35, 95, 0.32);
}

.favorite-button:hover:not(:disabled) {
  transform: translateY(-1px);
  filter: brightness(1.03);
  box-shadow: 0 12px 26px rgba(226, 35, 95, 0.4);
}

.favorite-button.active {
  background: linear-gradient(180deg, rgba(255, 186, 73, 0.98), rgba(245, 158, 11, 0.98));
  color: #1f2937;
  border-color: rgba(255, 238, 186, 0.85);
  box-shadow: 0 10px 24px rgba(245, 158, 11, 0.34);
}

.favorite-button.popping {
  transform: scale(1.06);
}

.favorite-button:disabled {
  opacity: 0.75;
  cursor: not-allowed;
}

.favorite-icon {
  font-size: 1rem;
  line-height: 1;
}

.favorite-text {
  line-height: 1;
}

.remove-favorite-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 7px 12px;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.34);
  background: rgba(180, 35, 24, 0.84);
  backdrop-filter: blur(8px);
  color: #fff;
  font-size: 0.8rem;
  font-weight: 700;
  cursor: pointer;
  transition: transform 0.2s ease, background 0.2s ease, opacity 0.2s ease;
}

.remove-favorite-button:hover:not(:disabled) {
  transform: translateY(-1px);
  background: rgba(180, 35, 24, 0.94);
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

.property-card {
  display: flex;
  flex-direction: column;
  height: 100%;
  border-radius: 24px;
  overflow: hidden;
  background: #fff;
}

.property-card__media {
  height: 240px; /* fija */
  flex-shrink: 0;
}

.property-card__body {
  display: flex;
  flex-direction: column;
  flex: 1;
  padding: 20px;
}

.property-card__content {
  flex: 1;
}

.property-card__footer {
  margin-top: auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
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

.detail-link:hover {
  background: var(--azul-principal);
  color: #fff;
}

.property-card__footer-actions {
  display: flex;
  justify-content: center;
  margin-bottom: 10px;
}

.property-card__delete-button {
  min-height: 40px;
  padding: 0 16px;
  border: 3px solid rgba(220, 38, 38, 0.18);
  border-radius: 12px;
  background: linear-gradient(180deg, #fff5f5, #fee2e2);
  color: #b91c1c;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.property-card__delete-button:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 10px 18px rgba(185, 28, 28, 0.12);
}

.property-card__delete-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .property-card__media {
    height: 270px;
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
  .match-pill {
    font-size: 0.76rem;
  }

  .favorite-button {
    padding: 7px 11px;
  }

  .remove-favorite-button {
    padding: 6px 10px;
  }

  .match-pill {
    min-width: 118px;
    padding: 7px 9px;
  }

  .property-card__zone-radius-badge {
    right: 10px;
    bottom: 10px;
    min-height: 34px;
    padding: 0 11px;
    font-size: 0.72rem;
  }
}

:deep(.leaflet-container) {
  width: 100%;
  height: 100%;
  background: #dfe8f5;
  font-family: inherit;
}

:deep(.leaflet-pane),
:deep(.leaflet-tile),
:deep(.leaflet-marker-icon),
:deep(.leaflet-marker-shadow),
:deep(.leaflet-pane img),
:deep(.leaflet-container img) {
  max-width: none !important;
  max-height: none !important;
}

:deep(.leaflet-top),
:deep(.leaflet-bottom) {
  z-index: 400;
}

:deep(.leaflet-pane) {
  z-index: 200;
}

:deep(.leaflet-tile-pane) {
  z-index: 200;
}

:deep(.leaflet-overlay-pane) {
  z-index: 250;
}

@media (max-width: 480px) {
  .property-card__heading {
  align-items: flex-start;
  gap: 8px;
}

.status-badge {
  font-size: 0.68rem;
  padding: 5px 10px;
  min-height: 28px;
}
  .property-card__media {
    height: 400px;
  }

  .property-card__body {
  padding: 14px;
  gap: 10px;
}

  .property-card__body h3 {
    font-size: 1rem;
  }

  .property-card__footer {
    flex-direction: column;
    align-items: stretch;
    gap: 8px;
  }

  .detail-link {
  width: 100%;
  text-align: center;
}

  .property-card__footer strong {
    font-size: 1rem;
  }

  .property-card__actions {
    max-width: calc(100% - 16px);
  }

  .property-card__zone-radius-badge {
    right: 10px;
    bottom: 10px;
    font-size: 0.68rem;
    min-height: 30px;
    padding: 0 9px;
  }
}
</style>