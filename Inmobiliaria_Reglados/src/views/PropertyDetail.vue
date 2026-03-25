<template>
  <section class="property-detail" v-if="loading">
    <p class="status">Cargando propiedad...</p>
  </section>

  <section class="property-detail" v-else-if="property">
    <header class="property-detail__hero">
      <div>
        <p class="eyebrow">{{ property.categoria || "Activo" }}</p>
        <h1>{{ property.tipo_propiedad || property.titulo || "Activo inmobiliario" }}</h1>
        <p class="location">
          {{ property.ciudad || "Ciudad no disponible" }}
          <span v-if="property.zona">· {{ property.zona }}</span>
        </p>
      </div>

      <div class="hero-actions">
        <span class="input-pill pill--text">TEXTO</span>
        <div class="price">{{ formatCurrency(property.precio) }}</div>
      </div>
    </header>

    <div class="property-detail__grid">
      <div class="info-block">
        <div class="metrics">
          <article>
            <span>Tipo de propiedad</span>
            <strong>{{ property.tipo_propiedad || "No disponible" }}</strong>
          </article>

          <article>
            <span>Ciudad</span>
            <strong>{{ property.ciudad || "No disponible" }}</strong>
          </article>

          <article>
            <span>Zona</span>
            <strong>{{ property.zona || "No disponible" }}</strong>
          </article>

          <article>
            <span>Metros cuadrados</span>
            <strong>{{ formatSurface(property.metros_cuadrados) }}</strong>
          </article>

          <article>
            <span>Precio</span>
            <strong>{{ formatCurrency(property.precio) }}</strong>
          </article>
        </div>
      </div>

      <div class="dossier-block">
        <h3>Acceso al dossier</h3>
        <p class="step-note">
          Para acceder al dossier completo debes descargar, firmar y subir los documentos legales.
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

        <div class="document-status-grid">
          <article v-for="step in documentSteps" :key="step.type">
            <div>
              <strong>{{ step.title }}</strong>
              <p>{{ step.detail }}</p>
            </div>
            <span :class="['status-chip', `status-${step.state}`]">
              {{ step.badge }}
            </span>
          </article>
        </div>

        <button
          class="dossier-download-btn"
          type="button"
          :disabled="!accessGranted || !dossierLink"
          @click="openDossier"
        >
          {{ accessGranted ? "Descargar dossier" : "Firmar documentos para desbloquearlo" }}
        </button>

        <p v-if="downloadError" class="status">{{ downloadError }}</p>
      </div>
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
        <button
          v-if="property.confidentiality_file"
          type="button"
          class="download-link"
          @click="downloadDocument(property.confidentiality_file)"
        >
          Descargar NDA
        </button>

        <button
          v-if="property.intention_file"
          type="button"
          class="download-link"
          @click="downloadDocument(property.intention_file)"
        >
          Descargar LOI
        </button>
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

const defaultStatusDetail = "Documentos pendientes de firma.";
const accessMessage = ref(defaultStatusDetail);
const verifyingAccess = ref(false);
const dossierLink = ref("");
const downloadError = ref("");

const purchaseLoading = ref(false);
const purchaseMessage = ref("");

const documentsAccess = ref({
  nda_uploaded: false,
  loi_uploaded: false,
  nda_approved: false,
  loi_approved: false,
  dossier_unlocked: false,
});

const signatureStatus = computed(() => {
  if (documentsAccess.value.dossier_unlocked) {
    return "validado";
  }
  if (documentsAccess.value.nda_uploaded || documentsAccess.value.loi_uploaded) {
    return "firmado";
  }
  return "pendiente";
});

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
const accessGranted = computed(() => Boolean(documentsAccess.value.dossier_unlocked));

const documentSteps = computed(() => {
  const access = documentsAccess.value;

  const makeState = (uploaded, approved) => {
    if (approved) {
      return {
        state: "approved",
        badge: "Aprobado",
        detail: "Documento validado por la oficina.",
      };
    }

    if (uploaded) {
      return {
        state: "pending",
        badge: "Pendiente",
        detail: "Documento recibido. Esperando revisión administrativa.",
      };
    }

    return {
      state: "idle",
      badge: "Pendiente",
      detail: "Descarga, firma y sube el documento.",
    };
  };

  return [
    {
      type: "nda",
      title: "NDA firmado",
      ...makeState(access.nda_uploaded, access.nda_approved),
    },
    {
      type: "loi",
      title: "LOI firmado",
      ...makeState(access.loi_uploaded, access.loi_approved),
    },
  ];
});

const canUpload = computed(() => {
  return !uploadingDocuments.value && (!!ndaFile.value || !!loiFile.value);
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

function formatSurface(value) {
  if (value === null || value === undefined || value === "") {
    return "No disponible";
  }

  const numberValue = Number(value);
  if (Number.isNaN(numberValue)) {
    return "No disponible";
  }

  return `${new Intl.NumberFormat("es-ES", {
    maximumFractionDigits: 0,
  }).format(numberValue)} m²`;
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
  downloadError.value = "";

  try {
    const payload = await fetchPropertyDetail(propertyId.value);

    if (!payload) {
      errorMessage.value = "Propiedad no encontrada.";
      return;
    }

    property.value = payload;
    dossierLink.value = "";
    await refreshAccessState();
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
  downloadError.value = "";
}

function closeSignatureModal() {
  showSignatureModal.value = false;
}

function triggerDownload(url, fileName) {
  try {
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute("download", fileName || "archivo.pdf");
    link.setAttribute("target", "_self");
    link.style.display = "none";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  } catch (error) {
    console.error(error);
    downloadError.value = "No se pudo iniciar la descarga.";
  }
}

function openDossier() {
  if (!accessGranted.value || !dossierLink.value) {
    return;
  }

  const fileName =
    property.value?.dossier_file?.split("/").pop() || "dossier.pdf";

  triggerDownload(dossierLink.value, fileName);
}

function downloadDocument(fileName) {
  const url = uploadsUrl(fileName);
  if (!url) {
    downloadError.value = "No se encontró la URL del documento.";
    return;
  }

  const finalName = fileName.split("/").pop() || "documento.pdf";
  triggerDownload(url, finalName);
}

async function refreshAccessState() {
  if (!propertyId.value) {
    return;
  }

  verifyingAccess.value = true;
  accessMessage.value = "";

  try {
    const response = await checkSignedAccess(propertyId.value);

    documentsAccess.value = {
      nda_uploaded: Boolean(response.access?.nda_uploaded),
      loi_uploaded: Boolean(response.access?.loi_uploaded),
      nda_approved: Boolean(response.access?.nda_approved),
      loi_approved: Boolean(response.access?.loi_approved),
      dossier_unlocked: Boolean(response.access?.dossier_unlocked),
    };

    accessMessage.value = response.message || defaultStatusDetail;

    if (documentsAccess.value.dossier_unlocked && property.value?.dossier_file) {
      dossierLink.value = buildUploadsUrl(property.value.dossier_file);
    } else {
      dossierLink.value = "";
    }
  } catch (err) {
    documentsAccess.value = {
      nda_uploaded: false,
      loi_uploaded: false,
      nda_approved: false,
      loi_approved: false,
      dossier_unlocked: false,
    };
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

    documentsAccess.value = {
      nda_uploaded: Boolean(response.access?.nda_uploaded),
      loi_uploaded: Boolean(response.access?.loi_uploaded),
      nda_approved: Boolean(response.access?.nda_approved),
      loi_approved: Boolean(response.access?.loi_approved),
      dossier_unlocked: Boolean(response.access?.dossier_unlocked),
    };

    accessMessage.value =
      response.message || "Documentos firmados. Espera validación administrativa.";

    ndaFile.value = null;
    loiFile.value = null;
    dossierLink.value = "";

    await refreshAccessState();
  } catch (err) {
    modalMessage.value = err?.message || "No se pudieron enviar los documentos.";
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
  padding: 160px;
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

.pill--text {
  background: #f2f4ff;
  color: #172a5d;
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

.metrics {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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
  font-size: 1.05rem;
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

.dossier-block h3 {
  margin: 0 0 12px;
  font-size: 1.15rem;
  color: #172a5e;
}

.step-note {
  margin: 8px 0 16px;
  color: #4c566a;
}

.dossier-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 12px;
}

.document-status-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}

.document-status-grid article {
  background: #f8fafc;
  border-radius: 12px;
  padding: 14px;
  border: 1px solid #d6dfef;
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: center;
}

.document-status-grid p {
  margin: 0;
  font-size: 0.85rem;
  color: #475569;
}

.status-chip {
  padding: 6px 12px;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.status-chip.status-approved {
  background: #dcfce7;
  color: #166534;
}

.status-chip.status-pending {
  background: #fef9c3;
  color: #92400e;
}

.status-chip.status-idle {
  background: #eceff5;
  color: #24303f;
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

.download-links .download-link {
  flex: 1;
  border-radius: 12px;
  border: 1px dashed #172a5d;
  text-align: center;
  color: #172a5d;
  font-weight: 600;
  padding: 10px 14px;
  background: none;
  cursor: pointer;
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
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  }

  .signature-modal__card {
    padding: 20px;
  }
}
</style>