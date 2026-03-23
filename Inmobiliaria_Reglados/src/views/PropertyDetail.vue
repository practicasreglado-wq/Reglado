<template>
  <section class="property-detail" v-if="loading">
    <p class="status">Cargando propiedad...</p>
  </section>

  <section class="property-detail" v-else-if="property">
    <header class="property-detail__hero">
      <div>
        <p class="eyebrow">{{ property.categoria }}</p>
        <h1>{{ property.titulo }}</h1>
        <p class="location">
          {{ property.ciudad || "Ciudad no disponible" }}
          <span v-if="property.zona">· {{ property.zona }}</span>
        </p>
      </div>
      <div class="hero-actions">
        <span class="input-pill" :class="tipoInputClass">
          {{ tipoInputLabel }}
        </span>
        <div class="price">{{ formatCurrency(property.precio) }}</div>
      </div>
    </header>

    <div class="property-detail__grid">
      <div class="info-block">
        <div class="metrics">
          <article>
            <span>Metros</span>
            <strong>{{ property.metros_cuadrados }} m²</strong>
          </article>
          <article>
            <span>Habitaciones</span>
            <strong>{{ property.habitaciones }}</strong>
          </article>
          <article>
            <span>Rentabilidad neta</span>
            <strong>{{ rentabilidadLabel }}</strong>
          </article>
        </div>

        <div class="secondary-metrics">
          <article v-for="metric in secondaryMetrics" :key="metric.label">
            <span>{{ metric.label }}</span>
            <strong>{{ metric.value }}</strong>
          </article>
        </div>

        <div class="analysis-summary" v-if="analysisSummary">
          <h3>Resumen del análisis</h3>
          <p>{{ analysisSummary }}</p>
        </div>

        <div class="caracteristics" v-if="hasCharacteristics">
          <h3>Características del activo</h3>
          <ul>
            <li v-for="(value, key) in property.caracteristicas" :key="key">
              <span>{{ formatKey(key) }}</span>
              <strong>{{ formatValue(value) }}</strong>
            </li>
          </ul>
        </div>
      </div>

      <div class="dossier-block">
        <h3>Acceso al dossier</h3>
        <p class="step-note">
          Para acceder al dossier completo debes firmar los documentos legales.
        </p>

        <div class="signature-status" :class="statusChipClass">
          <span class="signature-status__label">{{ signatureStatusLabel }}</span>
          <span class="signature-status__detail">
            {{ accessMessage || defaultStatusDetail }}
          </span>
        </div>

        <div class="dossier-actions">
          <button class="primary-btn" type="button" @click="openSignatureModal">
            Descargar documentos
          </button>
          <button class="secondary-btn" type="button" @click="openSignatureModal">
            Subir documentos firmados
          </button>
        </div>

        <button
          class="dossier-download-btn"
          type="button"
          :disabled="!accessGranted || !dossierLink"
          @click="openDossier"
        >
          {{ accessGranted ? "Descargar dossier" : "Firmar documentos para desbloquearlo" }}
        </button>

        <a
          v-if="accessGranted && dossierLink"
          :href="dossierLink"
          target="_blank"
          rel="noreferrer"
          class="secondary-btn"
        >
          Ver dossier completo
        </a>
      </div>
    </div>

    <div class="analysis-panels" v-if="analysisPanelVisible">
      <article class="analysis-panel" v-if="hasAnalysis">
        <h3>Análisis cualitativo</h3>
        <p v-if="analysisSummary && !analysisInfo?.resumen">
          {{ analysisSummary }}
        </p>
        <p v-else-if="analysisInfo?.resumen">
          {{ analysisInfo.resumen }}
        </p>

        <div v-if="analysisInfo?.puntos_fuertes?.length || analysisInfo?.puntos_fuertes">
          <h4>Puntos fuertes</h4>
          <ul>
            <li
              v-for="(item, index) in ensureArray(analysisInfo?.puntos_fuertes)"
              :key="`fuertes-${index}`"
            >
              {{ item }}
            </li>
          </ul>
        </div>

        <div v-if="analysisInfo?.riesgos">
          <h4>Riesgos</h4>
          <ul>
            <li
              v-for="(item, index) in ensureArray(analysisInfo?.riesgos)"
              :key="`riesgos-${index}`"
            >
              {{ item }}
            </li>
          </ul>
        </div>

        <div v-if="analysisInfo?.oportunidades">
          <h4>Oportunidades</h4>
          <ul>
            <li
              v-for="(item, index) in ensureArray(analysisInfo?.oportunidades)"
              :key="`oportunidades-${index}`"
            >
              {{ item }}
            </li>
          </ul>
        </div>

        <div v-if="analysisInfo?.perfil_inversor">
          <h4>Perfil inversor</h4>
          <p>{{ analysisInfo?.perfil_inversor }}</p>
        </div>
      </article>

      <article class="analysis-panel" v-if="hasMarketInfo">
        <h3>Mercado</h3>
        <p v-if="marketInfo?.analisis_zona">
          <strong>Análisis zona:</strong> {{ marketInfo.analisis_zona }}
        </p>
        <p v-if="marketInfo?.comparables">
          <strong>Comparables:</strong> {{ marketInfo.comparables }}
        </p>
        <p v-if="marketInfo?.tendencia">
          <strong>Tendencia:</strong> {{ marketInfo.tendencia }}
        </p>
      </article>

      <article class="analysis-panel" v-if="hasValuationInfo">
        <h3>Valoración</h3>
        <p v-if="valuationInfo?.valor_estimado">
          <strong>Valor estimado:</strong> {{ formatCurrency(valuationInfo.valor_estimado) }}
        </p>
        <p v-if="valuationInfo?.margen">
          <strong>Margen:</strong> {{ valuationInfo.margen }}
        </p>
        <p v-if="valuationInfo?.es_oportunidad !== undefined">
          <strong>¿Es oportunidad?</strong>
          {{ valuationInfo.es_oportunidad ? "Sí" : "No" }}
        </p>
      </article>

      <article class="analysis-panel" v-if="hasConclusionInfo">
        <h3>Conclusión final</h3>
        <p>{{ conclusionInfo?.recomendacion_final }}</p>
      </article>
    </div>

    <div class="purchase-block">
      <button
        v-if="accessGranted"
        class="primary-btn"
        type="button"
        :disabled="purchaseLoading"
        @click="confirmPurchase"
      >
        {{ purchaseLoading ? "Enviando..." : "Me interesa comprar" }}
      </button>
      <p class="status" v-else>
        Debes validar la firma y esperar la aprobación administrativa para solicitar la compra.
      </p>
      <p class="status" v-if="purchaseMessage">{{ purchaseMessage }}</p>
    </div>
  </section>

  <section class="property-detail" v-else>
    <p class="status">{{ errorMessage || "Propiedad no encontrada." }}</p>
  </section>

  <div v-if="showSignatureModal" class="signature-modal">
    <div class="signature-modal__card">
      <header class="signature-modal__header">
        <h4>Firmar documentos</h4>
        <button type="button" @click="closeSignatureModal">Cerrar</button>
      </header>
      <p>
        Descarga, firma digitalmente y sube el NDA y la LOI para desbloquear el dossier completo.
      </p>

      <div class="download-links">
        <a
          v-if="property.confidentiality_file"
          :href="uploadsUrl(property.confidentiality_file)"
          target="_blank"
          rel="noreferrer"
        >
          Descargar NDA
        </a>
        <a
          v-if="property.intention_file"
          :href="uploadsUrl(property.intention_file)"
          target="_blank"
          rel="noreferrer"
        >
          Descargar LOI
        </a>
      </div>

      <div class="upload-section">
        <label>
          NDA firmado
          <input type="file" accept=".pdf" @change="handleFileChange('nda', $event)" />
        </label>
        <label>
          LOI firmado
          <input type="file" accept=".pdf" @change="handleFileChange('loi', $event)" />
        </label>
      </div>

      <button
        class="primary-btn"
        type="button"
        :disabled="!canUpload"
        @click="submitDocuments"
      >
        {{ uploadingDocuments ? "Validando..." : "Enviar documentos" }}
      </button>

      <p class="status" v-if="modalMessage">{{ modalMessage }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";
import {
  fetchPropertyDetail,
  uploadSignedDocuments,
  checkSignedAccess,
  requestPropertyPurchase,
} from "../services/properties";
import { buildUploadsUrl } from "../services/backend";

const route = useRoute();
const propertyId = computed(() => Number(route.params.id ?? 0));
const property = ref(null);
const loading = ref(true);
const errorMessage = ref("");
const showSignatureModal = ref(false);
const ndaFile = ref(null);
const loiFile = ref(null);
const uploadingDocuments = ref(false);
const modalMessage = ref("");
const accessGranted = ref(false);
const defaultStatusDetail = "Documentos pendientes de firma.";
const accessMessage = ref(defaultStatusDetail);
const signatureStatus = ref("pendiente");
const signatureStatusLabel = computed(() => {
  switch (signatureStatus.value) {
    case "firmado":
      return "Firmado";
    case "validado":
      return "Validado";
    default:
      return "Pendiente";
  }
});
const statusChipClass = computed(() => `signature-status signature-status--${signatureStatus.value}`);
const verifyingAccess = ref(false);
const dossierLink = ref(null);
const purchaseLoading = ref(false);
const purchaseMessage = ref("");

const tipoInputLabel = computed(() => {
  const tipo = property.value?.tipo_input ?? "";
  return tipo ? tipo.toUpperCase() : "TEXTO";
});

const tipoInputClass = computed(() => {
  return property.value?.tipo_input === "pdf" ? "pill--pdf" : "pill--text";
});

const rentabilidadLabel = computed(() => {
  return (
    property.value?.rentabilidad_neta ??
    property.value?.rentabilidad_bruta ??
    "No disponible"
  );
});

const secondaryMetrics = computed(() => {
  const safe = (value) =>
    value === null || value === undefined || value === "" ? "No disponible" : value;

  return [
    {
      label: "Precio/m²",
      value: formatCurrency(property.value?.precio_m2),
    },
    { label: "EBITDA", value: formatCurrency(property.value?.EBITDA) },
    { label: "Cash Flow", value: formatCurrency(property.value?.cash_flow) },
    { label: "Cap Rate", value: safe(property.value?.cap_rate) },
    { label: "ROI", value: safe(property.value?.roi) },
    { label: "Payback", value: safe(property.value?.payback) },
  ];
});

const analysisJsonData = computed(() => {
  const raw = property.value?.analisis_json;
  if (!raw) {
    return null;
  }

  try {
    return JSON.parse(raw);
  } catch {
    return null;
  }
});

const analysisInfo = computed(() => analysisJsonData.value?.analisis ?? null);
const marketInfo = computed(() => analysisJsonData.value?.mercado ?? null);
const valuationInfo = computed(() => analysisJsonData.value?.valoracion ?? null);
const conclusionInfo = computed(() => analysisJsonData.value?.conclusion ?? null);
const analysisSummary = computed(() => property.value?.analisis ?? "");

const hasAnalysis = computed(() => {
  return (
    Boolean(analysisSummary.value) || Boolean(analysisInfo.value && isPopulated(analysisInfo.value))
  );
});

const hasMarketInfo = computed(() => Boolean(marketInfo.value && isPopulated(marketInfo.value)));
const hasValuationInfo = computed(() => Boolean(valuationInfo.value && isPopulated(valuationInfo.value)));
const hasConclusionInfo = computed(() => Boolean(conclusionInfo.value?.recomendacion_final));
const analysisPanelVisible = computed(
  () => hasAnalysis.value || hasMarketInfo.value || hasValuationInfo.value || hasConclusionInfo.value
);

const hasCharacteristics = computed(() => {
  return property.value?.caracteristicas && Object.keys(property.value.caracteristicas).length > 0;
});

const canUpload = computed(() => {
  return !!ndaFile.value && !!loiFile.value && !uploadingDocuments.value;
});

const uploadsUrl = (fileName) => buildUploadsUrl(fileName);

function formatCurrency(value) {
  if (value === null || value === undefined || value === "") {
    return "No disponible";
  }

  const numberValue = Number(value);
  if (Number.isNaN(numberValue)) {
    return "No disponible";
  }

  return new Intl.NumberFormat("es-ES", {
    style: "currency",
    currency: "EUR",
    maximumFractionDigits: 0,
  }).format(numberValue);
}

function formatKey(key) {
  return key
    ? key.replace(/_/g, " ").replace(/\b\w/g, (char) => char.toUpperCase())
    : "Detalle";
}

function formatValue(value) {
  if (typeof value === "boolean") {
    return value ? "Sí" : "No";
  }
  return value ?? "No disponible";
}

function ensureArray(value) {
  if (value === null || value === undefined || value === "") {
    return [];
  }

  if (Array.isArray(value)) {
    return value;
  }

  return [value];
}

function isPopulated(value) {
  if (value === null || value === undefined) {
    return false;
  }

  if (typeof value === "string") {
    return value.trim() !== "";
  }

  if (Array.isArray(value)) {
    return value.length > 0;
  }

  if (typeof value === "object") {
    return Object.values(value).some((item) => isPopulated(item));
  }

  return true;
}

async function loadProperty() {
  if (!propertyId.value) {
    errorMessage.value = "Propiedad no identificada.";
    loading.value = false;
    return;
  }

  loading.value = true;
  property.value = null;
  errorMessage.value = "";

  try {
    const payload = await fetchPropertyDetail(propertyId.value);
    if (!payload) {
      errorMessage.value = "Propiedad no encontrada.";
    } else {
      property.value = payload;
      dossierLink.value = buildUploadsUrl(payload.dossier_file);
      await refreshAccessState();
    }
  } catch (err) {
    errorMessage.value = err?.message || "No se pudo cargar la propiedad.";
  } finally {
    loading.value = false;
  }
}

function handleFileChange(field, event) {
  const file = event.target.files?.[0] ?? null;

  if (field === "nda") {
    ndaFile.value = file;
  } else if (field === "loi") {
    loiFile.value = file;
  }
}

function openSignatureModal() {
  showSignatureModal.value = true;
  modalMessage.value = "";
}

function closeSignatureModal() {
  showSignatureModal.value = false;
}

function openDossier() {
  if (!accessGranted.value || !dossierLink.value) {
    return;
  }

  window.open(dossierLink.value, "_blank", "noopener,noreferrer");
}

async function refreshAccessState() {
  if (!propertyId.value) {
    return;
  }

  verifyingAccess.value = true;
  accessMessage.value = "";

  try {
    const response = await checkSignedAccess(propertyId.value);
    const status = response.status || "pendiente";
    signatureStatus.value = status;
    accessMessage.value = response.message || defaultStatusDetail;
    accessGranted.value = status === "validado";
    if (accessGranted.value) {
      dossierLink.value =
        dossierLink.value || buildUploadsUrl(property.value?.dossier_file);
    }
  } catch (err) {
    accessGranted.value = false;
    signatureStatus.value = "pendiente";
    accessMessage.value = err?.message || "No se pudo comprobar el acceso firmado.";
  } finally {
    verifyingAccess.value = false;
  }
}

async function submitDocuments() {
  if (!canUpload.value || !property.value) {
    return;
  }

  uploadingDocuments.value = true;
  modalMessage.value = "";

  try {
    const response = await uploadSignedDocuments(
      propertyId.value,
      ndaFile.value,
      loiFile.value
    );
    modalMessage.value = response.message || "Documentos enviados correctamente.";
    signatureStatus.value = response.status || "firmado";
    accessMessage.value =
      response.message || "Documentos firmados. Espera validación administrativa.";
    ndaFile.value = null;
    loiFile.value = null;
    dossierLink.value =
      response.dossier_url || buildUploadsUrl(property.value.dossier_file);
    await refreshAccessState();
  } catch (err) {
    modalMessage.value = err?.message || "No se pudo validar los documentos.";
  } finally {
    uploadingDocuments.value = false;
  }
}

async function confirmPurchase() {
  if (!property.value) {
    return;
  }

  if (!accessGranted.value) {
    purchaseMessage.value = "Debes validar el acceso al dossier antes de solicitar la compra.";
    return;
  }

  const confirmed = window.confirm("¿Estás seguro?");
  if (!confirmed) {
    return;
  }

  purchaseLoading.value = true;
  purchaseMessage.value = "";

  try {
    const response = await requestPropertyPurchase(property.value.id);
    purchaseMessage.value = response.message || "Solicitud enviada correctamente.";
  } catch (err) {
    purchaseMessage.value = err?.message || "No se pudo enviar la solicitud.";
  } finally {
    purchaseLoading.value = false;
  }
}

onMounted(loadProperty);

watch(
  () => propertyId.value,
  () => {
    loadProperty();
  }
);
</script>

<style scoped>
.property-detail {
  padding: 32px;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.property-detail__hero {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 16px;
}

.property-detail__hero h1 {
  margin: 0;
  font-size: clamp(2rem, 1.5rem + 1vw, 2.4rem);
}

.eyebrow {
  text-transform: uppercase;
  font-size: 0.8rem;
  letter-spacing: 0.18em;
  color: #6b7b95;
  margin-bottom: 8px;
}

.location {
  margin: 0;
  color: #4c566a;
}

.hero-actions {
  display: flex;
  align-items: center;
  gap: 18px;
  justify-content: flex-end;
}

.input-pill {
  padding: 6px 14px;
  border-radius: 999px;
  border: 1px solid #172a5d;
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  background: #f2f4ff;
  color: #172a5d;
}

.input-pill.pill--pdf {
  background: #172a5d;
  color: #fff;
}

.price {
  font-size: 1.5rem;
  font-weight: 700;
  color: #172a5e;
}

.property-detail__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
}

.info-block,
.dossier-block {
  background: #ffffff;
  padding: 24px;
  border-radius: 24px;
  box-shadow: 0 18px 40px rgba(23, 42, 93, 0.15);
}

.signature-status {
  margin: 16px 0;
  padding: 12px 16px;
  border-radius: 14px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  font-weight: 600;
  background: #f5f7ff;
  color: #1b3a7a;
}

.signature-status--pendiente {
  background: #fff5e6;
  color: #a35e00;
}

.signature-status--firmado {
  background: #eef3ff;
  color: #1b3a7a;
}

.signature-status--validado {
  background: #e6f9ee;
  color: #0c6b34;
}

.signature-status__label {
  font-size: 0.95rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.signature-status__detail {
  font-size: 0.95rem;
}

.dossier-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 12px;
}

.dossier-download-btn {
  border-radius: 999px;
  border: none;
  padding: 12px 28px;
  font-weight: 700;
  cursor: pointer;
  color: #fff;
  background: linear-gradient(135deg, #172a5d, #3654ae);
  transition: opacity 0.2s ease, transform 0.2s ease;
  margin-bottom: 12px;
}

.dossier-download-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.metrics {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}

.metrics article {
  background: #f6f7fb;
  border-radius: 16px;
  padding: 14px;
  text-align: center;
}

.metrics span {
  display: block;
  font-size: 0.8rem;
  color: #6b7b95;
}

.metrics strong {
  display: block;
  margin-top: 6px;
  font-size: 1.1rem;
}

.caracteristics h3 {
  margin: 0 0 12px;
  font-size: 1.1rem;
  color: #172a5e;
}

.caracteristics ul {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  gap: 10px;
}

.caracteristics li {
  display: flex;
  justify-content: space-between;
  border-bottom: 1px solid #e3e7f0;
  padding-bottom: 6px;
  font-size: 0.95rem;
}

.caracteristics span {
  color: #6b7b95;
}

.secondary-metrics {
  margin-top: 16px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 12px;
}

.secondary-metrics article {
  background: #f6f7fb;
  padding: 14px;
  border-radius: 14px;
  border: 1px solid #e2e8f0;
}

.analysis-summary {
  margin-top: 20px;
  background: #fdf7f2;
  padding: 18px;
  border-radius: 16px;
  border: 1px solid #f3d7c5;
}

.analysis-panels {
  margin-top: 24px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 18px;
}

.analysis-panel {
  background: #fff;
  border-radius: 16px;
  border: 1px solid #e3e7f0;
  padding: 20px;
  box-shadow: 0 12px 30px rgba(23, 42, 93, 0.08);
}

.analysis-panel h3 {
  margin-top: 0;
  margin-bottom: 12px;
  font-size: 1.2rem;
  color: #172a5e;
}

.analysis-panel h4 {
  margin: 14px 0 8px;
  font-size: 0.95rem;
  color: #4b5563;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.analysis-panel ul {
  padding-left: 18px;
  margin: 0;
  color: #172a5e;
}

.analysis-panel li {
  margin-bottom: 6px;
}

.analysis-panel strong {
  font-weight: 600;
}

.caracteristics strong {
  color: #172a5e;
}

.dossier-block h3 {
  margin-top: 0;
  font-size: 1.2rem;
}

.step-note {
  margin: 8px 0 16px;
  color: #4c566a;
}

.primary-btn,
.secondary-btn {
  border: none;
  border-radius: 999px;
  padding: 12px 24px;
  font-weight: 700;
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.primary-btn {
  background: linear-gradient(135deg, #172a5d, #3654ae);
  color: #fff;
}

.primary-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.secondary-btn {
  background: transparent;
  border: 1px solid #172a5d;
  color: #172a5d;
  display: inline-flex;
  justify-content: center;
  margin-top: 12px;
}

.status {
  margin-top: 12px;
  color: #172a5e;
}

.purchase-block {
  background: #ffffff;
  padding: 24px;
  border-radius: 24px;
  box-shadow: 0 18px 40px rgba(23, 42, 93, 0.15);
  display: flex;
  flex-direction: column;
  gap: 12px;
  align-items: flex-start;
}

.signature-modal {
  position: fixed;
  inset: 0;
  background: rgba(15, 22, 49, 0.64);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  z-index: 100;
}

.signature-modal__card {
  width: min(560px, 100%);
  background: #fff;
  padding: 28px;
  border-radius: 24px;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
}

.signature-modal__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.signature-modal__header button {
  border: none;
  background: transparent;
  font-weight: 600;
  cursor: pointer;
}

.download-links {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.download-links a {
  flex: 1;
  text-decoration: none;
  padding: 10px 14px;
  border-radius: 12px;
  border: 1px dashed #172a5d;
  text-align: center;
  color: #172a5d;
  font-weight: 600;
}

.upload-section {
  display: grid;
  gap: 12px;
  margin-bottom: 16px;
}

.upload-section label {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-weight: 600;
  color: #172a5e;
}

.upload-section input {
  border-radius: 10px;
  border: 1px solid #d6dbf0;
  padding: 8px;
}

@media (max-width: 768px) {
  .property-detail__grid {
    grid-template-columns: 1fr;
  }

  .metrics {
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  }

  .signature-modal__card {
    padding: 20px;
  }
}
</style>
