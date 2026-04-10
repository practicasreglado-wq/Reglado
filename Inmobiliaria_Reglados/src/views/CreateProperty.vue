<template>
  <section class="create-property">
    <div class="content">
      <div class="header-actions">
        <button class="back-link" @click="$router.back()">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
          Volver
        </button>
      </div>

      <p class="eyebrow">Gestión de activos</p>
      <h2>Procesar propiedad</h2>
      <p class="intro">
        Describe la propiedad en texto libre. Debes incluir datos obligatorios como, tipo de propiedad, ciudad, zona, metros cuadrados, precio y direccion.
      </p>

      <div class="panel panel-text">
        <textarea
          v-model="description"
          placeholder="Por ejemplo: Tengo un edificio residencial en Madrid centro, 4.395 m² construidos, precio 20.000.000 €, en calle..."
        ></textarea>

        <button
          class="primary-btn"
          :disabled="textProcessing"
          @click="handleProcessText"
        >
          {{ textProcessing ? "Procesando..." : "Procesar descripción" }}
        </button>
      </div>

      <p v-if="feedbackMessage" class="feedback">{{ feedbackMessage }}</p>
    </div>
  </section>
</template>

<script setup>
import { ref } from "vue";
import { processPropertyFromText } from "../services/properties";

const description = ref("");
const textProcessing = ref(false);
const feedbackMessage = ref("");

async function handleProcessText() {
  const clean = description.value.trim();

  if (!clean) {
    feedbackMessage.value = "Escribe una descripción antes de procesar.";
    return;
  }

  if (clean.length < 20) {
    feedbackMessage.value = "La descripción es demasiado corta. Añade más detalles del activo.";
    return;
  }

  textProcessing.value = true;
  feedbackMessage.value = "";

  try {
    const payload = await processPropertyFromText(clean);
    feedbackMessage.value =
      payload.message ||
      `Propiedad procesada correctamente`;
    description.value = "";
  } catch (error) {
    feedbackMessage.value =
      error?.message || "No se pudo procesar la propiedad. Inténtalo de nuevo.";
  } finally {
    textProcessing.value = false;
  }
}
</script>

<style scoped>
.create-property {
  min-height: 65vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
}

.content {
  width: min(720px, 100%);
  background: #fff;
  padding: 32px;
  border-radius: 24px;
  box-shadow: 0 24px 60px rgba(23, 42, 93, 0.15);
}

.header-actions {
  display: flex;
  margin-bottom: 20px;
}

.back-link {
  display: flex;
  align-items: center;
  gap: 6px;
  background: transparent;
  border: none;
  color: #6b7b95;
  font-weight: 700;
  font-size: 0.9rem;
  cursor: pointer;
  padding: 0;
  transition: color 0.2s ease, transform 0.2s ease;
}

.back-link:hover {
  color: #172a5d;
  transform: translateX(-3px);
}

.eyebrow {
  text-transform: uppercase;
  font-size: 0.8rem;
  letter-spacing: 0.2em;
  color: #6b7b95;
  margin-bottom: 8px;
}

.content h2 {
  margin: 0 0 12px;
  font-size: 2rem;
}

.intro {
  margin: 0 0 24px;
  color: #4c566a;
  line-height: 1.5;
}

.panel {
  margin-bottom: 20px;
}

textarea {
  width: 100%;
  min-height: 220px;
  border-radius: 18px;
  border: 1px solid #d6dbf0;
  padding: 18px;
  font-size: 1rem;
  font-family: inherit;
  resize: vertical;
  margin-bottom: 20px;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

textarea:focus {
  border-color: #3654ae;
  outline: none;
  box-shadow: 0 0 0 4px rgba(54, 84, 174, 0.08);
}

.primary-btn {
  border: none;
  border-radius: 999px;
  padding: 14px 28px;
  font-size: 1rem;
  font-weight: 700;
  color: #fff;
  background: linear-gradient(135deg, #172a5d, #3654ae);
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.primary-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.primary-btn:not(:disabled):hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 30px rgba(23, 42, 93, 0.25);
}

.feedback {
  margin-top: 16px;
  color: #172a5e;
  font-weight: 600;
}

@media (max-width: 768px) {
  .content {
    padding: 24px;
  }

  textarea {
    min-height: 180px;
  }
}
</style>