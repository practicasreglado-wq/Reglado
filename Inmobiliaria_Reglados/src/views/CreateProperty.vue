<template>
  <section class="create-property">
    <div class="content">
      <p class="eyebrow">Gestión de activos</p>
      <h2>Procesar propiedad</h2>
      <p class="intro">
        Describe la propiedad o adjunta un PDF con el dossier completo. El sistema detectará
        automáticamente el origen del texto, extraerá los datos obligatorios y generará un activo editable con su análisis.
      </p>

      <div class="mode-switch">
        <button
          class="mode-btn"
          :class="{ active: activeMode === 'text' }"
          type="button"
          @click="activeMode = 'text'"
        >
          Texto
        </button>
        <button
          class="mode-btn"
          :class="{ active: activeMode === 'pdf' }"
          type="button"
          @click="activeMode = 'pdf'"
        >
          PDF
        </button>
      </div>

      <div v-if="activeMode === 'text'" class="panel panel-text">
        <textarea
          v-model="description"
          placeholder="Por ejemplo: Piso de 120m² en Madrid, 3 habitaciones, 650.000€"
        ></textarea>
        <button
          class="primary-btn"
          :disabled="textProcessing"
          @click="handleProcessText"
        >
          {{ textProcessing ? "Procesando..." : "Procesar descripción" }}
        </button>
      </div>

      <div v-else class="panel panel-pdf">
        <label class="file-upload">
          <input type="file" accept=".pdf" @change="handleFileChange" :disabled="pdfProcessing" />
          <div class="upload-prompt">
            <strong>Adjunta un PDF</strong>
            <span>Extraeremos el texto completo, generaremos el análisis avanzado y el dossier profesional.</span>
          </div>
          <p class="file-name">{{ pdfLabel }}</p>
        </label>
        <button
          class="primary-btn"
          :disabled="pdfProcessing"
          @click="handleUploadPdf"
        >
          {{ pdfProcessing ? "Procesando PDF..." : "Procesar PDF" }}
        </button>
      </div>

      <p v-if="feedbackMessage" class="feedback">{{ feedbackMessage }}</p>
    </div>
  </section>
</template>

<script setup>
import { computed, ref } from "vue";
import {
  processPropertyFromText,
  uploadPropertyPdf,
} from "../services/properties";

const description = ref("");
const activeMode = ref("text");
const textProcessing = ref(false);
const pdfProcessing = ref(false);
const pdfFile = ref(null);
const feedbackMessage = ref("");

const pdfLabel = computed(() =>
  pdfFile.value ? pdfFile.value.name : "Ningún archivo seleccionado"
);

async function handleProcessText() {
  if (!description.value.trim()) {
    feedbackMessage.value = "Escribe una descripción antes de procesar.";
    return;
  }

  textProcessing.value = true;
  feedbackMessage.value = "";

  try {
    const payload = await processPropertyFromText(description.value.trim());
    feedbackMessage.value =
      payload.message ||
      `Propiedad procesada correctamente (ID ${payload.propertyId ?? "?"}).`;
    description.value = "";
  } catch (error) {
    feedbackMessage.value =
      error?.message || "No se pudo procesar la propiedad. Inténtalo de nuevo.";
  } finally {
    textProcessing.value = false;
  }
}

async function handleUploadPdf() {
  if (!pdfFile.value) {
    feedbackMessage.value = "Selecciona un PDF antes de enviarlo.";
    return;
  }

  pdfProcessing.value = true;
  feedbackMessage.value = "";

  try {
    const payload = await uploadPropertyPdf(pdfFile.value);
    feedbackMessage.value =
      payload.message ||
      `PDF procesado. Verifica la propiedad ID ${payload.propertyId ?? "?"}.`;
    pdfFile.value = null;
  } catch (error) {
    feedbackMessage.value =
      error?.message || "No se pudo procesar el PDF. Inténtalo de nuevo.";
  } finally {
    pdfProcessing.value = false;
  }
}

function handleFileChange(event) {
  pdfFile.value = event.target.files?.[0] ?? null;
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
  line-height: 1.4;
}

.mode-switch {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
}

.mode-btn {
  flex: 1;
  padding: 10px 0;
  border-radius: 999px;
  border: 1px solid #d0d7f1;
  background: #f9fafb;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.mode-btn.active {
  border-color: #172a5d;
  background: linear-gradient(135deg, #172a5d, #3654ae);
  color: white;
}

.panel {
  margin-bottom: 20px;
}

.file-upload {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 12px;
  padding: 18px;
  border-radius: 16px;
  border: 2px dashed #cfd7f4;
  width: 100%;
  cursor: pointer;
}

.file-upload input {
  display: none;
}

.upload-prompt strong {
  display: block;
  font-size: 1rem;
  color: #172a5d;
}

.upload-prompt span {
  color: #5b6480;
  font-size: 0.9rem;
}

.file-name {
  font-size: 0.9rem;
  color: #172a5d;
  font-weight: 600;
}

textarea {
  width: 100%;
  min-height: 200px;
  border-radius: 18px;
  border: 1px solid #d6dbf0;
  padding: 18px;
  font-size: 1rem;
  font-family: inherit;
  resize: vertical;
  margin-bottom: 20px;
  transition: border-color 0.2s ease;
}

textarea:focus {
  border-color: #3654ae;
  outline: none;
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
    min-height: 160px;
  }
}
</style>
