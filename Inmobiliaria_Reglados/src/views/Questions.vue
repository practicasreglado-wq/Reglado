<template>
  <div v-if="!hasCategory" class="carousel-screen">
    <div class="carousel-prompt">
      <p class="carousel-kicker">Comienza el matching</p>
      <h2>Elige la categoría que mejor encaja con tu estrategia</h2>
      <p>
        Elige la tipología de activos que quieres priorizar. Guardaremos tu selección y
        desplegaremos las preguntas específicas para esa categoría.
      </p>
    </div>
    <Carousel />
  </div>

  <div v-else class="questions-layout">
    <div ref="leftSideRef" class="left-side">
      <div class="left-content">
        <div class="logo">RS</div>
        <span class="label">Deja tu búsqueda a nosotros</span>
        <h1>Cuéntanos qué<br>es lo que buscas...</h1>
      </div>
    </div>

    <div class="right-side">
      <div class="category-form-shell">
        <form @submit.prevent="submit" class="category-form">
          <div class="form-heading">
            <div>
              <span class="section-kicker">Tus preferencias</span>
              <h2 class="form-title">Para {{ category }}</h2>
            </div>
          </div>

          <p v-if="errorMessage" class="form-feedback form-feedback--error">
            {{ errorMessage }}
          </p>
          <p v-else-if="successMessage" class="form-feedback form-feedback--success">
            {{ successMessage }}
          </p>

          <component
            v-if="currentForm"
            :is="currentForm"
            :form="form"
          />

          <button type="submit" class="submit-btn" :disabled="isSaving">
            {{ isSaving ? "Guardando..." : "Guardar preferencias" }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { computed, ref, watch, onMounted, onBeforeUnmount, nextTick } from "vue";
import { storeToRefs } from "pinia";
import { useRouter, useRoute } from "vue-router";
import Carousel from "../components/Carousel.vue";
import HotelesForm from "../components/HotelesForm.vue";
import ParkingForm from "../components/ParkingForm.vue";
import EdificiosForm from "../components/EdificiosForm.vue";
import FincasForm from "../components/FincasForm.vue";
import ActivosForm from "../components/ActivosForm.vue";
import { useUserStore } from "../stores/user";
import { backendJson } from "../services/backend";
import { getPreferenceSchema, sanitizePreferences } from "../data/preferenceSchemas";

const CATEGORY_OPTIONS = ["Hoteles", "Fincas", "Parking", "Edificios", "Activos"];

function normalizeCategoryName(value) {
  if (!value) {
    return null;
  }

  const trimmed = String(value).trim();
  if (!trimmed) {
    return null;
  }

  return (
    CATEGORY_OPTIONS.find(
      (option) => option.toLowerCase() === trimmed.toLowerCase()
    ) || null
  );
}

export default {
  components: {
    Carousel,
    HotelesForm,
    ParkingForm,
    EdificiosForm,
    FincasForm,
    ActivosForm,
  },

  setup() {
    const userStore = useUserStore();
    const router = useRouter();
    const route = useRoute();
    const { preferences, selectedCategory } = storeToRefs(userStore);

    const leftSideRef = ref(null);

    const initialCategory =
      normalizeCategoryName(route.query.category ?? selectedCategory.value) || null;
    const category = ref(initialCategory);
    const form = ref({});
    const isSaving = ref(false);
    const resetting = ref(false);
    const errorMessage = ref("");
    const successMessage = ref("");
    const lastPersistedCategory = ref(null);

    const forms = {
      Hoteles: HotelesForm,
      Parking: ParkingForm,
      Edificios: EdificiosForm,
      Fincas: FincasForm,
      Activos: ActivosForm,
    };

    const currentForm = computed(() => forms[category.value] || null);
    const currentSchema = computed(() => getPreferenceSchema(category.value));
    const hasCategory = computed(() => Boolean(category.value && currentForm.value));

    const initializeForm = (cat) => {
      form.value = {};

      if (!cat) {
        return;
      }

      const schema = getPreferenceSchema(cat);
      const existingPrefs = sanitizePreferences(cat, preferences.value || {});

      schema?.questions?.forEach((question) => {
        form.value[question.key] = existingPrefs[question.key] || "";
      });
    };

    const persistCategorySelection = async (value) => {
      if (!value) {
        return;
      }

      try {
        await backendJson("api/match_preferences.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            category: value,
            answers: sanitizePreferences(value, preferences.value || {}),
          }),
        });
        lastPersistedCategory.value = value;
      } catch (err) {
        console.error("No se pudo guardar la categoría seleccionada", err);
      }
    };

    watch(
      () => route.query.category,
      (next) => {
        const normalized = normalizeCategoryName(next);

        if (normalized) {
          if (category.value !== normalized) {
            category.value = normalized;
            userStore.setCategory(normalized);
          }
          return;
        }

        if (!selectedCategory.value) {
          category.value = null;
        }
      }
    );

    watch(
      () => selectedCategory.value,
      (next) => {
        const normalized = normalizeCategoryName(next);
        if (normalized && normalized !== category.value) {
          category.value = normalized;
        }
      }
    );

    watch(
      () => category.value,
      (next) => {
        if (!next) {
          form.value = {};
          return;
        }

        initializeForm(next);

        if (lastPersistedCategory.value !== next) {
          persistCategorySelection(next);
        }
      },
      { immediate: true }
    );

    watch(
      () => preferences.value,
      () => {
        if (category.value) {
          initializeForm(category.value);
        }
      },
      { deep: true }
    );

    const submit = async () => {
      const schema = currentSchema.value;

      if (!schema) {
        return;
      }

      const answeredCount = Object.entries(form.value).filter(
        ([key, value]) => key.startsWith("q") && value !== ""
      ).length;

      if (answeredCount < (schema.questions?.length || 0)) {
        errorMessage.value = "Por favor, responde todas las preguntas antes de continuar.";
        successMessage.value = "";
        return;
      }

      const cleanedPreferences = sanitizePreferences(category.value, form.value);

      isSaving.value = true;
      errorMessage.value = "";
      successMessage.value = "";

      try {
        await backendJson("api/match_preferences.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            category: category.value,
            answers: cleanedPreferences,
          }),
        });

        userStore.setCategory(category.value);
        userStore.setPreferences(cleanedPreferences);
        successMessage.value = "Preferencias guardadas correctamente.";

        await router.push({
          path: "/profile/properties-for-sale",
          query: {
            category: category.value,
            t: Date.now(),
          },
        });
      } catch (err) {
        errorMessage.value = err.message || "No se pudo guardar la información.";
      } finally {
        isSaving.value = false;
      }
    };

    const resetPreferences = async () => {
      resetting.value = true;
      errorMessage.value = "";
      successMessage.value = "";

      try {
        await backendJson("api/match_preferences.php", {
          method: "DELETE",
        });

        userStore.setCategory(null);
        userStore.setPreferences(null);
        category.value = null;
        lastPersistedCategory.value = null;
        form.value = {};
        await router.replace({ path: "/questions" });
      } catch (err) {
        errorMessage.value = err.message || "No se pudo reiniciar el flujo.";
      } finally {
        resetting.value = false;
      }
    };

    let ticking = false;

    const updateLeftSidePosition = () => {
      const leftEl = leftSideRef.value;

      if (!leftEl) {
        return;
      }

      if (window.innerWidth <= 1024) {
        leftEl.style.transform = "translateY(0)";
        return;
      }

      const footerEl = document.querySelector("footer");

      if (!footerEl) {
        leftEl.style.transform = "translateY(0)";
        return;
      }

      const footerRect = footerEl.getBoundingClientRect();
      const overlap = window.innerHeight - footerRect.top;

      if (overlap > 0) {
        leftEl.style.transform = `translateY(-${overlap}px)`;
      } else {
        leftEl.style.transform = "translateY(0)";
      }
    };

    const requestUpdateLeftSidePosition = () => {
      if (ticking) {
        return;
      }

      ticking = true;

      window.requestAnimationFrame(() => {
        updateLeftSidePosition();
        ticking = false;
      });
    };

    onMounted(async () => {
      await nextTick();
      requestUpdateLeftSidePosition();
      window.addEventListener("scroll", requestUpdateLeftSidePosition, { passive: true });
      window.addEventListener("resize", requestUpdateLeftSidePosition);
    });

    onBeforeUnmount(() => {
      window.removeEventListener("scroll", requestUpdateLeftSidePosition);
      window.removeEventListener("resize", requestUpdateLeftSidePosition);
    });

    return {
      category,
      form,
      currentForm,
      isSaving,
      hasCategory,
      errorMessage,
      successMessage,
      resetting,
      submit,
      resetPreferences,
      leftSideRef,
    };
  },
};
</script>

<style scoped>
.carousel-screen {
  min-height: calc(100vh - 90px);
  display: flex;
  flex-direction: column;
  gap: 32px;
  padding: 60px 40px 80px;
  align-items: stretch;
  justify-content: center;
  text-align: center;
  background: radial-gradient(circle at top, rgba(23, 62, 132, 0.7), transparent 45%),
    linear-gradient(180deg, #061029, #0b1b3a 65%, #16284f);
  color: #fff;
}

.carousel-prompt {
  max-width: 600px;
  margin: 0 auto;
}

.carousel-kicker {
  font-size: 0.85rem;
  letter-spacing: 0.5em;
  text-transform: uppercase;
  color: #d4af37;
  margin-bottom: 12px;
}

.carousel-screen h2 {
  font-size: clamp(2rem, 3vw, 2.8rem);
  margin-bottom: 16px;
}

.carousel-screen p {
  max-width: 600px;
  margin: 0 auto;
  color: rgba(255, 255, 255, 0.78);
  line-height: 1.6;
}

.questions-layout {
  position: relative;
  display: flex;
  min-height: calc(100vh - 90px);
  margin-top: 90px;
}

/* Fondo fijo ocupando toda la pantalla */
.questions-layout::before {
  content: "";
  position: fixed;
  inset: 0;
  background-image: url('@/assets/fondito.png');
  background-position: center center;
  background-repeat: no-repeat;
  background-size: cover;
  z-index: -2;
}

.questions-layout::after {
  content: "";
  position: fixed;
  inset: 0;
  background: rgba(3, 7, 19, 0.42);
  z-index: -1;
}

.left-side {
  width: 42%;
  position: fixed;
  top: 90px;
  left: 0;
  height: calc(100vh - 90px);
  display: flex;
  align-items: center;
  padding: 40px 60px;
  z-index: 2;
  background: transparent;
  transition: transform 0.08s linear;
  will-change: transform;
  pointer-events: none;
}

.left-content {
  color: #fff;
  max-width: 520px;
}

.left-content .logo {
  font-size: 3rem;
  font-weight: 700;
  margin-bottom: 12px;
}

.label {
  display: block;
  font-size: 1rem;
  letter-spacing: 0.4em;
  text-transform: uppercase;
  color: #d4af37;
  margin-bottom: 18px;
}

.left-content h1 {
  margin: 0;
  font-size: clamp(2.8rem, 5vw, 3.7rem);
  line-height: 1.2;
}

/* Formulario por delante */
.right-side {
  width: 58%;
  margin-left: auto;
  min-height: calc(100vh - 90px);
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 40px;
  position: relative;
  z-index: 3;
  background: transparent;
}

.category-form-shell {
  width: 100%;
  max-width: 680px;
  background: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  border-radius: 24px;
  padding: 32px;
  box-shadow: 0 20px 45px rgba(16, 38, 90, 0.18);
}

.category-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-heading {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 20px;
}

.section-kicker {
  display: inline-flex;
  align-items: center;
  padding: 6px 14px;
  border-radius: 999px;
  background: #edf1ff;
  color: #1f4aa8;
  font-size: 0.75rem;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  margin-bottom: 6px;
}

.form-title {
  margin: 0;
  font-size: 2rem;
  color: #172a5d;
  line-height: 1.2;
}

.form-feedback {
  margin: 0;
  padding: 12px 16px;
  border-radius: 12px;
  font-weight: 500;
  font-size: 0.95rem;
}

.form-feedback--error {
  background: #ffe9ea;
  color: #9f2d2d;
  border: 1px solid #f4c2c5;
}

.form-feedback--success {
  background: #ecf8f1;
  color: #1e7f5c;
  border: 1px solid #a4e1c8;
}

.submit-btn {
  width: 60%;
  align-self: center;
  padding: 16px 0;
  border-radius: 30px;
  border: none;
  background: linear-gradient(135deg, #172a5d, #1f4aa8);
  color: #fff;
  font-size: 1.05rem;
  font-weight: 700;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.submit-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 12px 24px rgba(31, 74, 168, 0.2);
}

.submit-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

/* Tablet */
@media (max-width: 1024px) {
  .questions-layout {
    flex-direction: column;
  }

  .left-side {
    width: 100%;
    position: relative;
    top: 0;
    left: 0;
    height: auto;
    padding: 32px 24px 12px;
    pointer-events: auto;
    transform: none !important;
  }

  .right-side {
    width: 100%;
    margin-left: 0;
    min-height: auto;
    padding: 20px 24px 40px;
    align-items: flex-start;
  }
}

/* Móvil */
@media (max-width: 768px) {
  .left-side {
    display: none;
  }

  .right-side {
    width: 100%;
    padding: 20px;
  }

  .category-form-shell {
    padding: 24px;
    border-radius: 20px;
  }

  .submit-btn {
    width: 100%;
  }
}
</style>