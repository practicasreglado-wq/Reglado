<template>
  <section class="properties-sale">
    <div class="properties-sale__hero">
      <div class="properties-sale__copy">
        <p class="eyebrow">Catálogo inmobiliario</p>
        <h2>Propiedades en venta</h2>
        <p>
          Explora oportunidades filtradas según tus preferencias y encuentra
          los activos que mejor encajan con tu estrategia.
        </p>
      </div>

      <div class="hero-actions">
        <span class="hero-badge">{{ filteredProperties.length }} activos</span>
      </div>
    </div>

    <div v-if="loading" class="properties-sale__state">
      Cargando propiedades...
    </div>

    <div v-else-if="!filteredProperties.length" class="properties-sale__state">
      No hay propiedades disponibles para esta categoría.
    </div>

    <div v-else class="properties-sale__grid">
      <div
        v-for="property in filteredProperties"
        :key="property.id"
        class="properties-sale__item"
      >
        <PropertyCard
          :property="property"
          :favorite-loading="pendingFavorites.has(property.id)"
          @toggle-favorite="toggleFavorite"
        />
      </div>
    </div>
  </section>
</template>

<script>
import PropertyCard from "../components/PropertyCard.vue";
import {
  fetchProperties,
  removeFavorite,
  saveFavorite,
} from "../services/properties";
import { useUserStore } from "../stores/user";

export default {
  name: "PropertiesForSale",

  components: {
    PropertyCard,
  },

  data() {
    return {
      properties: [],
      loading: true,
      pendingFavorites: new Set(),
      localCategory: "",
      userStore: null,
    };
  },

  computed: {
    selectedCategory() {
      return this.userStore?.selectedCategory || "";
    },

    preferences() {
      return this.userStore?.preferences || {};
    },

    effectiveCategory() {
      return this.localCategory || this.selectedCategory || "";
    },

    filteredProperties() {
      const category = String(this.effectiveCategory || "").trim().toLowerCase();

      const list = category
        ? this.properties.filter(
            (property) =>
              String(property.categoria || "").trim().toLowerCase() === category
          )
        : this.properties;

      return [...list]
      /*50 AQUI ESTA EL PORCENTAJE DEL MATCH*/
        .filter((property) => (property.match_percentage ?? 0) >= 50)
        .sort((a, b) => (b.match_percentage || 0) - (a.match_percentage || 0));
    },
  },

  mounted() {
    this.userStore = useUserStore();
    this.getProperties();
  },

  methods: {
    async getProperties() {
      this.loading = true;

      try {
        const rawProperties = await fetchProperties();

        this.properties = rawProperties.map((property) => ({
          ...property,
          match_percentage: this.calculateMatchPercentage(property),
        }));
      } catch (error) {
        console.error("Error cargando propiedades:", error);
        this.properties = [];
      } finally {
        this.loading = false;
      }
    },

    getPreference(...keys) {
      const prefs = this.preferences || {};

      for (const key of keys) {
        if (prefs[key] !== undefined && prefs[key] !== null && prefs[key] !== "") {
          return prefs[key];
        }
      }

      return null;
    },

    normalizeText(value) {
      return String(value ?? "")
        .trim()
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "");
    },

    toNumber(value) {
      if (value === null || value === undefined || value === "") {
        return null;
      }

      if (typeof value === "number") {
        return Number.isFinite(value) ? value : null;
      }

      const cleaned = String(value)
        .replace(/\./g, "")
        .replace(/€/g, "")
        .replace(/,/g, ".")
        .replace(/[^\d.-]/g, "");

      const num = Number(cleaned);
      return Number.isFinite(num) ? num : null;
    },

    inRangeBand(value, band) {
      if (value === null || band === null) {
        return false;
      }

      switch (band) {
        case "hasta_1000":
          return value <= 1000;
        case "1000_3000":
          return value >= 1000 && value <= 3000;
        case "3000_10000":
          return value >= 3000 && value <= 10000;
        case "mas_10000":
          return value > 10000;

        case "hasta_2m":
          return value <= 2000000;
        case "2m_10m":
          return value > 2000000 && value <= 10000000;
        case "10m_30m":
          return value > 10000000 && value <= 30000000;
        case "mas_30m":
          return value > 30000000;

        case "hasta_2000":
          return value <= 2000;
        case "2000_5000":
          return value > 2000 && value <= 5000;
        case "5000_15000":
          return value > 5000 && value <= 15000;
        case "mas_15000":
          return value > 15000;

        default:
          return false;
      }
    },

    mapSizeBand(answer) {
      const text = this.normalizeText(answer);

      if (text.includes("hasta 1.000")) return "hasta_1000";
      if (text.includes("1.000 a 3.000")) return "1000_3000";
      if (text.includes("3.000 a 10.000")) return "3000_10000";
      if (text.includes("mas de 10.000")) return "mas_10000";

      return null;
    },

    mapBudgetBand(answer) {
      const text = this.normalizeText(answer);

      if (text.includes("hasta 2 m")) return "hasta_2m";
      if (text.includes("2 m") && text.includes("10 m")) return "2m_10m";
      if (text.includes("10 m") && text.includes("30 m")) return "10m_30m";
      if (text.includes("mas de 30 m")) return "mas_30m";

      return null;
    },

    mapBuiltBand(answer) {
      const text = this.normalizeText(answer);

      if (text.includes("hasta 2.000")) return "hasta_2000";
      if (text.includes("2.000 a 5.000")) return "2000_5000";
      if (text.includes("5.000 a 15.000")) return "5000_15000";
      if (text.includes("mas de 15.000")) return "mas_15000";

      return null;
    },

    calculateMatchPercentage(property) {
      const norm = this.normalizeText;
      const chars = property.caracteristicas || {};

      const category = norm(property.categoria || "");
      const tipoPropiedad = norm(property.tipo_propiedad || "");
      const ciudad = norm(property.ciudad || property.ubicacion_general || "");
      const zona = norm(property.zona || property.ubicacion_general || "");
      const titulo = norm(property.titulo || "");
      const usoPrincipal = norm(chars.uso_principal || property.uso_principal || "");
      const usoAlternativo = norm(chars.uso_alternativo || property.uso_alternativo || "");
      const operacionTexto = norm(property.analisis || "");
      const ownerType = norm(chars.propiedad_tipo || property.propiedad_tipo || "");
      const occupancyText = norm(
        property.estado_ocupacion ||
        property.disponibilidad ||
        property.situacion ||
        property.analisis ||
        ""
      );

      const parcela = this.toNumber(property.metros_cuadrados);
      const construida = this.toNumber(
        chars.superficie_construida ||
        property.superficie_construida ||
        property.metros_cuadrados
      );
      const precio = this.toNumber(property.precio);

      let score = 0;
      let total = 0;

      const prefTipoEdificio = this.getPreference(
        "tipo_edificio",
        "tipoEdificio",
        "question_1",
        "q1",
        "tipo"
      );

      if (prefTipoEdificio) {
        total += 12;
        const wanted = norm(prefTipoEdificio);

        if (
          (wanted.includes("residencial") && (usoPrincipal.includes("residencial") || tipoPropiedad.includes("residencial"))) ||
          (wanted.includes("oficinas") && (usoPrincipal.includes("oficina") || usoAlternativo.includes("oficina"))) ||
          (wanted.includes("mixto") && (usoAlternativo !== "" || usoPrincipal.includes("mixto"))) ||
          (wanted.includes("comercial") && (usoPrincipal.includes("comercial") || usoAlternativo.includes("comercial")))
        ) {
          score += 12;
        }
      }

      const prefUbicacion = this.getPreference(
        "ubicacion_preferida",
        "ubicacionPreferida",
        "question_2",
        "q2",
        "ubicacion"
      );

      if (prefUbicacion) {
        total += 10;
        const wanted = norm(prefUbicacion);

        if (
          (wanted.includes("centro") && (ciudad.includes("madrid") || zona.includes("centro") || titulo.includes("centro"))) ||
          (wanted.includes("premium") && (ciudad.includes("madrid") || zona.includes("salamanca") || zona.includes("prime"))) ||
          (wanted.includes("periferia") && !(ciudad.includes("centro") || zona.includes("centro"))) ||
          (wanted.includes("potencial") && (usoAlternativo !== "" || operacionTexto.includes("reform") || operacionTexto.includes("mejor")))
        ) {
          score += 10;
        }
      }

      const prefTamano = this.getPreference(
        "tamano_minimo",
        "tamanoMinimo",
        "question_3",
        "q3"
      );

      if (prefTamano) {
        total += 10;
        const band = this.mapSizeBand(prefTamano);
        if (this.inRangeBand(parcela, band)) {
          score += 10;
        }
      }

      const prefPresupuesto = this.getPreference(
        "presupuesto_maximo",
        "presupuestoMaximo",
        "question_4",
        "q4",
        "presupuesto"
      );

      if (prefPresupuesto) {
        total += 12;
        const band = this.mapBudgetBand(prefPresupuesto);
        if (this.inRangeBand(precio, band)) {
          score += 12;
        }
      }

      const prefUsoPrincipal = this.getPreference(
        "uso_principal_preferido",
        "usoPrincipalPreferido",
        "question_5",
        "q5",
        "uso_principal"
      );

      if (prefUsoPrincipal) {
        total += 12;
        const wanted = norm(prefUsoPrincipal);

        if (
          (wanted.includes("vivienda") && (usoPrincipal.includes("residencial") || tipoPropiedad.includes("residencial"))) ||
          (wanted.includes("oficina") && (usoPrincipal.includes("oficina") || usoAlternativo.includes("oficina"))) ||
          (wanted.includes("local") && (usoPrincipal.includes("comercial") || usoAlternativo.includes("comercial"))) ||
          (wanted.includes("mixto") && (usoAlternativo !== "" || usoPrincipal.includes("mixto")))
        ) {
          score += 12;
        }
      }

      const prefOtroUso = this.getPreference(
        "otro_uso",
        "otroUso",
        "question_6",
        "q6"
      );

      if (prefOtroUso) {
        total += 8;
        const wanted = norm(prefOtroUso);

        if (
          (wanted.includes("si") && usoAlternativo !== "") ||
          (wanted.includes("depende") && usoAlternativo !== "") ||
          (wanted === "no" && usoAlternativo === "")
        ) {
          score += 8;
        }
      }

      const prefConstruida = this.getPreference(
        "superficie_construida_preferida",
        "superficieConstruidaPreferida",
        "question_7",
        "q7",
        "superficie_construida"
      );

      if (prefConstruida) {
        total += 10;
        const band = this.mapBuiltBand(prefConstruida);
        if (this.inRangeBand(construida, band)) {
          score += 10;
        }
      }

      const prefCompra = this.getPreference(
        "forma_compra",
        "formaCompra",
        "question_8",
        "q8"
      );

      if (prefCompra) {
        total += 8;
        const wanted = norm(prefCompra);

        if (
          wanted.includes("propiedad completa") ||
          wanted.includes("me da igual")
        ) {
          score += 8;
        }
      }

      const prefLibre = this.getPreference(
        "libre_inicio",
        "libreInicio",
        "question_9",
        "q9",
        "libre"
      );

      if (prefLibre) {
        total += 8;
        const wanted = norm(prefLibre);
        const isFree =
          occupancyText.includes("vacio") ||
          occupancyText.includes("libre") ||
          operacionTexto.includes("vacio");

        if (
          (wanted.includes("si") && isFree) ||
          (wanted.includes("me da igual")) ||
          (wanted.includes("no es necesario") && !isFree)
        ) {
          score += 8;
        }
      }

      const prefOperacion = this.getPreference(
        "tipo_operacion",
        "tipoOperacion",
        "question_10",
        "q10",
        "operacion"
      );

      if (prefOperacion) {
        total += 10;
        const wanted = norm(prefOperacion);

        if (
          (wanted.includes("genere ingresos") && (
            this.toNumber(property.ingresos_actuales) > 0 ||
            operacionTexto.includes("rent")
          )) ||
          (wanted.includes("mejorar") && (
            usoAlternativo !== "" ||
            operacionTexto.includes("mejor") ||
            operacionTexto.includes("potencial")
          )) ||
          (wanted.includes("reformar") && (
            usoAlternativo !== "" ||
            operacionTexto.includes("cambio de uso") ||
            operacionTexto.includes("reform")
          )) ||
          (wanted.includes("riesgo") && (
            usoAlternativo !== "" ||
            operacionTexto.includes("potencial")
          ))
        ) {
          score += 10;
        }
      }

      if (total === 0) {
        return null;
      }

      return Math.round((score / total) * 100);
    },

    async toggleFavorite(property) {
      if (!property?.id || this.pendingFavorites.has(property.id)) {
        return;
      }

      this.pendingFavorites.add(property.id);

      const previousValue = !!property.is_favorite;

      try {
        if (previousValue) {
          await removeFavorite(property.id);
        } else {
          await saveFavorite(property.id);
        }

        const target = this.properties.find((item) => item.id === property.id);
        if (target) {
          target.is_favorite = !previousValue;
        }
      } catch (error) {
        console.error("Error actualizando favorito:", error);
      } finally {
        this.pendingFavorites.delete(property.id);
      }
    }
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

.hero-badge {
  display: inline-flex;
  align-items: center;
  padding: 9px 14px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.14);
  backdrop-filter: blur(10px);
  font-weight: 700;
}

.properties-sale__filters {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.filter-btn {
  border: 1px solid #d9e2f1;
  background: #fff;
  color: #172a5d;
  padding: 10px 14px;
  border-radius: 999px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.filter-btn:hover {
  transform: translateY(-1px);
  border-color: #bd9b2c;
}

.filter-btn.active {
  background: #172a5d;
  color: #fff;
  border-color: #172a5d;
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