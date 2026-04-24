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
      <p>No hay propiedades disponibles para esta categoría.</p>
      <button
        v-if="canRequestIntent"
        type="button"
        class="intent-cta"
        @click="openIntentModal"
      >
        Avísame cuando alguien suba una así
      </button>
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

    <Teleport to="body">
      <div v-if="intentModal.open" class="intent-backdrop" @click.self="closeIntentModal">
        <div class="intent-window" role="dialog" aria-modal="true">
          <header class="intent-window__header">
            <h3>Avísame cuando alguien suba una propiedad así</h3>
            <button
              type="button"
              class="intent-window__close"
              aria-label="Cerrar"
              @click="closeIntentModal"
            >×</button>
          </header>

          <div class="intent-window__body">
            <template v-if="intentModal.hasCriteria">
              <p class="intent-window__hint">
                Registraremos tu búsqueda con los criterios que ya respondiste en
                tus preferencias y notificaremos a los demás usuarios. Cuando
                alguien suba una propiedad que encaje, recibirás un aviso con
                acceso directo a la ficha.
              </p>

              <div class="intent-summary">
                <div class="intent-summary__row">
                  <span class="intent-summary__label">Categoría</span>
                  <span class="intent-summary__value">{{ intentModal.category }}</span>
                </div>

                <div
                  v-for="entry in intentModal.entries"
                  :key="entry.key"
                  class="intent-summary__row"
                >
                  <span class="intent-summary__label">{{ entry.label }}</span>
                  <span class="intent-summary__value">{{ entry.value }}</span>
                </div>
              </div>
            </template>

            <div v-else class="intent-window__empty">
              <p>
                Para registrar la búsqueda necesitas seleccionar una categoría y
                responder las preguntas del cuestionario. Así sabemos qué buscas y
                podemos avisarte cuando alguien suba algo que encaje.
              </p>
              <button
                type="button"
                class="intent-btn intent-btn--primary"
                @click="goToPreferences"
              >
                Responder preguntas
              </button>
            </div>

            <p v-if="intentModal.error" class="intent-window__error">
              {{ intentModal.error }}
            </p>

            <p v-if="intentModal.successMessage" class="intent-window__success">
              {{ intentModal.successMessage }}
            </p>
          </div>

          <footer v-if="intentModal.hasCriteria" class="intent-window__footer">
            <button
              type="button"
              class="intent-btn intent-btn--ghost"
              @click="closeIntentModal"
              :disabled="intentModal.submitting"
            >
              Cerrar
            </button>
            <button
              type="button"
              class="intent-btn intent-btn--primary"
              @click="submitIntent"
              :disabled="intentModal.submitting || !!intentModal.successMessage"
            >
              {{ intentModal.submitting ? 'Enviando...' : 'Confirmar búsqueda' }}
            </button>
          </footer>
        </div>
      </div>
    </Teleport>
  </section>
</template>

<script>
import PropertyCard from "../components/PropertyCard.vue";
import {
  fetchProperties,
  removeFavorite,
  saveFavorite,
  checkSignedAccess,
} from "../services/properties";
import { createBuyerIntent } from "../services/buyerIntents";
import { buildPreferenceEntries } from "../data/preferenceSchemas";
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
      intentModal: {
        open: false,
        submitting: false,
        error: "",
        successMessage: "",
        hasCriteria: false,
        category: "",
        city: "",
        maxPrice: null,
        minM2: null,
        entries: [],
      },
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

      // Umbral mínimo del 50% para filtrar propiedades poco afines. Las
      // propiedades que hicieron match con un intent activo del comprador
      // reciben match_percentage=100 en getProperties(), así que siempre
      // superan el umbral y aparecen arriba.
      return [...list]
        .filter((property) => (property.match_percentage ?? 0) >= 50)
        .sort((a, b) => (b.match_percentage || 0) - (a.match_percentage || 0));
    },

    canRequestIntent() {
      // Solo compradores (role=real o admin) con categoría seleccionada y
      // al menos una pregunta del cuestionario respondida.
      if (!this.userStore?.isReal) {
        return false;
      }

      const cat = String(this.effectiveCategory || "").trim();
      if (!cat) {
        return false;
      }

      const prefs = this.preferences || {};
      const hasAnswers = Object.values(prefs).some(
        (value) => typeof value === "string" && value.trim() !== ""
      );

      return hasAnswers;
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

        const enrichedProperties = await Promise.all(
          rawProperties.map(async (property) => {
            try {
              const accessResponse = await checkSignedAccess(property.id);

              return {
                ...property,
                match_percentage: this.calculateMatchPercentage(property),
                nda_uploaded: Boolean(accessResponse.access?.nda_uploaded),
                loi_uploaded: Boolean(accessResponse.access?.loi_uploaded),
                nda_approved: Boolean(accessResponse.access?.nda_approved),
                loi_approved: Boolean(accessResponse.access?.loi_approved),
                dossier_unlocked: Boolean(accessResponse.access?.dossier_unlocked),
                validado_admin: Number(accessResponse.access?.validado_admin ?? 0),
                status: String(accessResponse.access?.status ?? ""),
              };
            } catch (error) {
              return {
                ...property,
                match_percentage: this.calculateMatchPercentage(property),
                nda_uploaded: false,
                loi_uploaded: false,
                nda_approved: false,
                loi_approved: false,
                dossier_unlocked: false,
                validado_admin: 0,
                status: "",
              };
            }
          })
        );

        this.properties = enrichedProperties;
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

    // Tokens significativos de una respuesta (palabras ≥5 chars, sin
    // stopwords). Permite un fallback genérico cuando la heurística
    // específica no reconoce la respuesta (p. ej. categorías que no son
    // Edificios/residencial).
    extractAnswerTokens(answer) {
      const STOPWORDS = new Set([
        "para", "como", "esto", "esta", "este", "pero", "desde", "hasta",
        "sobre", "entre", "solo", "todo", "toda", "todos", "todas",
        "otro", "otra", "otros", "otras", "segun", "mismo", "misma",
        "cual", "cuales", "donde", "cuando", "puede", "pueden", "tenga",
      ]);
      return this.normalizeText(answer)
        .split(/[\s,.;:()\/\-]+/)
        .filter((token) => token.length >= 5 && !STOPWORDS.has(token));
    },

    // Match genérico: al menos un token significativo aparece en el texto.
    answerMatchesFullText(answer, fulltext) {
      const tokens = this.extractAnswerTokens(answer);
      if (tokens.length === 0) return false;
      return tokens.some((t) => fulltext.includes(t));
    },

    // Banda numérica genérica a partir de respuestas tipo "Hasta X" /
    // "De X a Y" / "Más de X". Usa parseAnswerBand (ya definido abajo).
    valueInParsedBand(value, answer) {
      const { lower, upper } = this.parseAnswerBand(answer);
      const num = Number(value);
      if (!Number.isFinite(num)) return false;
      if (lower !== null && lower !== undefined && num < lower) return false;
      if (upper !== null && upper !== undefined && num > upper) return false;
      return true;
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

      // Texto agregado de la propiedad para el fallback genérico por tokens.
      // Incluye todo lo que razonablemente describe el activo.
      const fulltext = norm(
        [
          property.titulo,
          property.categoria,
          property.tipo_propiedad,
          property.subtipo,
          property.zona,
          property.ciudad,
          property.ubicacion_general,
          chars.uso_principal,
          chars.uso_alternativo,
          chars.propiedad_tipo,
          property.analisis,
          property.situacion,
          property.estado_ocupacion,
          property.disponibilidad,
        ]
          .filter((v) => v !== null && v !== undefined && String(v).trim() !== "")
          .join(" ")
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

        const originalMatch =
          (wanted.includes("residencial") && (usoPrincipal.includes("residencial") || tipoPropiedad.includes("residencial"))) ||
          (wanted.includes("oficinas") && (usoPrincipal.includes("oficina") || usoAlternativo.includes("oficina"))) ||
          (wanted.includes("mixto") && (usoAlternativo !== "" || usoPrincipal.includes("mixto"))) ||
          (wanted.includes("comercial") && (usoPrincipal.includes("comercial") || usoAlternativo.includes("comercial")));

        if (originalMatch || this.answerMatchesFullText(prefTipoEdificio, fulltext)) {
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

        const originalMatch =
          (wanted.includes("centro") && (ciudad.includes("madrid") || zona.includes("centro") || titulo.includes("centro"))) ||
          (wanted.includes("premium") && (ciudad.includes("madrid") || zona.includes("salamanca") || zona.includes("prime"))) ||
          (wanted.includes("periferia") && !(ciudad.includes("centro") || zona.includes("centro"))) ||
          (wanted.includes("potencial") && (usoAlternativo !== "" || operacionTexto.includes("reform") || operacionTexto.includes("mejor")));

        if (originalMatch || this.answerMatchesFullText(prefUbicacion, fulltext)) {
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
        const originalMatch = band !== null && this.inRangeBand(parcela, band);

        if (originalMatch || this.valueInParsedBand(parcela, prefTamano)) {
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
        const originalMatch = band !== null && this.inRangeBand(precio, band);

        if (originalMatch || this.valueInParsedBand(precio, prefPresupuesto)) {
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

        const originalMatch =
          (wanted.includes("vivienda") && (usoPrincipal.includes("residencial") || tipoPropiedad.includes("residencial"))) ||
          (wanted.includes("oficina") && (usoPrincipal.includes("oficina") || usoAlternativo.includes("oficina"))) ||
          (wanted.includes("local") && (usoPrincipal.includes("comercial") || usoAlternativo.includes("comercial"))) ||
          (wanted.includes("mixto") && (usoAlternativo !== "" || usoPrincipal.includes("mixto")));

        if (originalMatch || this.answerMatchesFullText(prefUsoPrincipal, fulltext)) {
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

        const originalMatch =
          (wanted.includes("si") && usoAlternativo !== "") ||
          (wanted.includes("depende") && usoAlternativo !== "") ||
          (wanted === "no" && usoAlternativo === "");

        if (originalMatch || this.answerMatchesFullText(prefOtroUso, fulltext)) {
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
        const originalMatch = band !== null && this.inRangeBand(construida, band);

        if (originalMatch || this.valueInParsedBand(construida, prefConstruida)) {
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

        const originalMatch =
          wanted.includes("propiedad completa") ||
          wanted.includes("me da igual");

        if (originalMatch || this.answerMatchesFullText(prefCompra, fulltext)) {
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

        const originalMatch =
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
          ));

        if (originalMatch || this.answerMatchesFullText(prefOperacion, fulltext)) {
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
    },

    parseNumericToken(token) {
      const str = String(token ?? "").trim();
      if (!str) return null;

      // "M€" (o "m€") es el marcador de millones. Sin él, el número se lee
      // literal: "500.000 €" = 500000, "1.500 m²" = 1500.
      const hasMillionMarker = /\b[mM]\s*€/.test(str);

      const match = str.match(/[\d.,]+/);
      if (!match) return null;

      // Formato español: "." separador de miles, "," decimal.
      const normalized = match[0].replace(/\./g, "").replace(",", ".");
      const num = Number(normalized);
      if (!Number.isFinite(num)) return null;

      return hasMillionMarker ? num * 1_000_000 : num;
    },

    parseAnswerBand(answer) {
      const original = String(answer ?? "").trim();
      if (!original) return { lower: null, upper: null };

      const norm = this.normalizeText(original);

      if (norm.startsWith("hasta ")) {
        // "Hasta X" → solo cota superior.
        const rest = original.replace(/^hasta\s+/i, "");
        return { lower: null, upper: this.parseNumericToken(rest) };
      }

      if (norm.startsWith("mas de ")) {
        // "Más de X" → solo cota inferior.
        const rest = original.replace(/^m[aá]s\s+de\s+/i, "");
        return { lower: this.parseNumericToken(rest), upper: null };
      }

      if (norm.startsWith("de ")) {
        // "De X a Y" → dos cotas.
        const rest = original.replace(/^de\s+/i, "");
        const parts = rest.split(/\s+a\s+/i);
        if (parts.length === 2) {
          return {
            lower: this.parseNumericToken(parts[0]),
            upper: this.parseNumericToken(parts[1]),
          };
        }
      }

      // Fallback: intenta leer un número directamente.
      const fallback = this.parseNumericToken(original);
      return { lower: null, upper: fallback };
    },

    mapBudgetToMaxPrice(answer) {
      const { upper } = this.parseAnswerBand(answer);
      return upper && upper > 0 ? upper : null;
    },

    mapSizeToMinM2(answer) {
      const { lower } = this.parseAnswerBand(answer);
      return lower && lower > 0 ? lower : null;
    },

    buildIntentCriteriaFromPreferences() {
      const category = String(this.effectiveCategory || "").trim();

      const budgetAnswer = this.getPreference(
        "presupuesto_maximo",
        "presupuestoMaximo",
        "question_4",
        "q4",
        "presupuesto"
      );
      const maxPrice = this.mapBudgetToMaxPrice(budgetAnswer);

      const sizeAnswer = this.getPreference(
        "tamano_minimo",
        "tamanoMinimo",
        "question_3",
        "q3"
      );
      const minM2 = this.mapSizeToMinM2(sizeAnswer);

      return { category, maxPrice, minM2 };
    },

    openIntentModal() {
      const { category, maxPrice, minM2 } =
        this.buildIntentCriteriaFromPreferences();

      // Para un match útil hay que tener al menos categoría. El resto son
      // filtros opcionales que se leen de las respuestas del cuestionario.
      const hasCriteria = Boolean(category);

      // Todas las respuestas del cuestionario para mostrar al comprador
      // (confirmación) y al vendedor (detalle en la notificación).
      const entries = category
        ? buildPreferenceEntries(category, this.preferences || {})
        : [];

      this.intentModal = {
        open: true,
        submitting: false,
        error: "",
        successMessage: "",
        hasCriteria,
        category,
        city: "",
        maxPrice,
        minM2,
        entries,
      };
    },

    closeIntentModal() {
      this.intentModal.open = false;
    },

    async submitIntent() {
      if (!this.intentModal.hasCriteria) {
        this.intentModal.error =
          "Completa primero tus preferencias para registrar la búsqueda.";
        return;
      }

      this.intentModal.submitting = true;
      this.intentModal.error = "";

      try {
        await createBuyerIntent({
          category: this.intentModal.category,
          city: this.intentModal.city,
          max_price: this.intentModal.maxPrice,
          min_m2: this.intentModal.minM2,
          criteria_display: this.intentModal.entries.map((entry) => ({
            label: entry.label,
            value: entry.value,
          })),
        });

        this.intentModal.successMessage = "Búsqueda registrada.";
      } catch (error) {
        this.intentModal.error =
          error?.message || "No se pudo registrar la búsqueda.";
      } finally {
        this.intentModal.submitting = false;
      }
    },

    goToPreferences() {
      this.closeIntentModal();
      this.$router.push("/questions");
    },

    formatMoney(value) {
      const num = Number(value);
      if (!Number.isFinite(num) || num <= 0) return "";
      return new Intl.NumberFormat("es-ES", {
        style: "currency",
        currency: "EUR",
        maximumFractionDigits: 0,
      }).format(num);
    },

    formatArea(value) {
      const num = Number(value);
      if (!Number.isFinite(num) || num <= 0) return "";
      return new Intl.NumberFormat("es-ES").format(num) + " m²";
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
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  text-align: center;
}

.properties-sale__state p {
  margin: 0;
}

.intent-cta {
  background: linear-gradient(135deg, #1e3a8a, #2563eb);
  color: #fff;
  border: none;
  border-radius: 999px;
  padding: 10px 22px;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 10px 24px rgba(37, 99, 235, 0.22);
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.intent-cta:hover {
  transform: translateY(-1px);
  box-shadow: 0 14px 28px rgba(37, 99, 235, 0.32);
}

.intent-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  padding: 24px;
}

.intent-window {
  width: min(520px, 95vw);
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 30px 60px rgba(15, 23, 42, 0.32);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.intent-window__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 20px;
  border-bottom: 1px solid #e5e7eb;
  background: #f8fafc;
}

.intent-window__header h3 {
  margin: 0;
  font-size: 1rem;
  color: #0f172a;
}

.intent-window__close {
  background: transparent;
  border: none;
  font-size: 1.6rem;
  line-height: 1;
  color: #475569;
  cursor: pointer;
}

.intent-window__body {
  padding: 18px 20px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.intent-window__hint {
  margin: 0;
  font-size: 0.9rem;
  color: #475569;
  line-height: 1.5;
}

.intent-summary {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 14px 16px;
  border-radius: 12px;
  background: #f8fafc;
  border: 1px solid #cbd5e1;
}

.intent-summary__row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  font-size: 0.92rem;
}

.intent-summary__label {
  color: #475569;
  font-weight: 500;
}

.intent-summary__value {
  color: #0f172a;
  font-weight: 700;
  text-align: right;
  word-break: break-word;
}

.intent-window__empty {
  padding: 14px 16px;
  border-radius: 12px;
  background: #fff7ed;
  border: 1px solid #fed7aa;
  color: #7c2d12;
  display: flex;
  flex-direction: column;
  gap: 14px;
  align-items: flex-start;
}

.intent-window__empty p {
  margin: 0;
  font-size: 0.92rem;
  line-height: 1.5;
}

.intent-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
  font-size: 0.85rem;
  color: #1e293b;
  font-weight: 600;
}

.intent-field input {
  width: 100%;
  padding: 10px 12px;
  border-radius: 10px;
  border: 1px solid #cbd5e1;
  background: #fff;
  font-size: 0.95rem;
  color: #0f172a;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.intent-field input:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.intent-field-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.intent-window__error {
  margin: 0;
  padding: 10px 12px;
  border-radius: 8px;
  background: #fef2f2;
  border: 1px solid #fca5a5;
  color: #b91c1c;
  font-size: 0.85rem;
}

.intent-window__success {
  margin: 0;
  padding: 10px 12px;
  border-radius: 8px;
  background: #ecfdf5;
  border: 1px solid #86efac;
  color: #15803d;
  font-size: 0.85rem;
}

.intent-window__footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 14px 20px;
  border-top: 1px solid #e5e7eb;
  background: #f8fafc;
}

.intent-btn {
  padding: 10px 18px;
  border-radius: 999px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  border: 1px solid transparent;
  transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
}

.intent-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.intent-btn--ghost {
  background: transparent;
  color: #475569;
  border-color: #cbd5e1;
}

.intent-btn--ghost:hover:not(:disabled) {
  background: #e2e8f0;
}

.intent-btn--primary {
  background: linear-gradient(135deg, #1e3a8a, #2563eb);
  color: #fff;
}

.intent-btn--primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 12px 24px rgba(37, 99, 235, 0.28);
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