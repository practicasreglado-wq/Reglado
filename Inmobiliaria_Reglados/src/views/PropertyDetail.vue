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
        <button
          class="favorite-action"
          type="button"
          :class="{ active: isFavorite }"
          :disabled="favoriteLoading"
          @click="toggleFavorite"
        >
          <span v-if="favoriteLoading">Procesando...</span>
          <span v-else>{{ isFavorite ? "Favorito" : "Guardar en favoritos" }}</span>
        </button>
      </div>
    </header>

    <div class="favorite-feedbacks">
      <p v-if="favoriteFeedback" class="favorite-feedback">{{ favoriteFeedback }}</p>
      <p v-if="favoriteError" class="favorite-feedback favorite-feedback--error">{{ favoriteError }}</p>
    </div>

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
          @click="downloadLegalDocument('nda', property.confidentiality_file)"
        >
          Descargar NDA
        </button>

        <button
          v-if="property.intention_file"
          type="button"
          class="download-link"
          @click="downloadLegalDocument('loi', property.intention_file)"
        >
          Descargar LOI
        </button>
      </div>

      <div class="upload-section">
      <label>
        NDA firmado
        <div class="file-validation-field">
          <input type="file" accept=".pdf" @change="handleFileChange('nda', $event)" />

          <span
            v-if="ndaValidation.checking"
            class="file-validation-badge file-validation-badge--checking"
          >
            Comprobando...
          </span>

          <span
            v-else-if="ndaValidation.valid === true"
            class="file-validation-badge file-validation-badge--valid"
          >
            ✅ Válido
          </span>

          <span
            v-else-if="ndaValidation.valid === false"
            class="file-validation-badge file-validation-badge--invalid"
          >
            ❌ No válido
          </span>
        </div>

        <small v-if="ndaValidation.message" class="file-validation-message">
          {{ ndaValidation.message }}
        </small>
      </label>

  <label>
    LOI firmado
    <div class="file-validation-field">
      <input type="file" accept=".pdf" @change="handleFileChange('loi', $event)" />

      <span
        v-if="loiValidation.checking"
        class="file-validation-badge file-validation-badge--checking"
      >
        Comprobando...
      </span>

      <span
        v-else-if="loiValidation.valid === true"
        class="file-validation-badge file-validation-badge--valid"
      >
        ✅ Válido
      </span>

      <span
        v-else-if="loiValidation.valid === false"
        class="file-validation-badge file-validation-badge--invalid"
      >
        ❌ No válido
      </span>
    </div>

    <small v-if="loiValidation.message" class="file-validation-message">
      {{ loiValidation.message }}
    </small>
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

  <div v-if="showPurchaseModal" class="confirm-modal">
    <div class="confirm-modal__card">
      <h4>Confirmar interés de compra</h4>
      <p>
        ¿Estás seguro de que quieres enviar tu solicitud de interés para esta propiedad?
      </p>

      <div class="confirm-modal__actions">
        <button
          type="button"
          class="secondary-btn"
          @click="cancelPurchase"
          :disabled="purchaseLoading"
        >
          Cancelar
        </button>

        <button
          type="button"
          class="primary-btn"
          @click="submitPurchaseRequest"
          :disabled="purchaseLoading"
        >
          {{ purchaseLoading ? "Enviando..." : "Sí, enviar solicitud" }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useUserStore } from "../stores/user";
import { auth } from "../services/auth";
import {
  fetchPropertyDetail,
  uploadSignedDocuments,
  checkSignedAccess,
  requestPropertyPurchase,
  saveFavorite,
  removeFavorite,
} from "../services/properties";
import { buildUploadsUrl } from "../services/backend";

const route = useRoute();
const router = useRouter();
const propertyId = computed(() => Number(route.params.id ?? 0));

const property = ref(null);
const userStore = useUserStore();
const isUserLoggedIn = computed(() => userStore.isLoggedIn);
const favoriteLoading = ref(false);
const favoriteFeedback = ref("");
const favoriteError = ref("");
const isFavorite = computed(() => Boolean(property.value?.is_favorite));
const loading = ref(true);
const errorMessage = ref("");

const showSignatureModal = ref(false);
const ndaFile = ref(null);
const loiFile = ref(null);
const uploadingDocuments = ref(false);
const modalMessage = ref("");

const ndaValidation = ref({
  checking: false,
  valid: null,
  message: "",
});

const loiValidation = ref({
  checking: false,
  valid: null,
  message: "",
});

const defaultStatusDetail = "Documentos pendientes de firma.";
const accessMessage = ref(defaultStatusDetail);
const verifyingAccess = ref(false);
const dossierLink = ref("");
const downloadError = ref("");

const purchaseLoading = ref(false);
const purchaseMessage = ref("");
const showPurchaseModal = ref(false);

const documentsAccess = ref({
  nda_uploaded: false,
  loi_uploaded: false,
  nda_approved: false,
  loi_approved: false,
  dossier_unlocked: false,
  validado_admin: 0,
  status: "",
});

const signatureStatus = computed(() => {
  if (
    documentsAccess.value.validado_admin === -1 ||
    documentsAccess.value.status === "rejected"
  ) {
    return "rechazado";
  }

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
    case "rechazado":
      return "Rechazado";
    default:
      return "Pendiente";
  }
});

const statusChipClass = computed(() => `signature-status signature-status--${signatureStatus.value}`);
const accessGranted = computed(() => Boolean(documentsAccess.value.dossier_unlocked));

const documentSteps = computed(() => {
    const access = documentsAccess.value;

    const makeState = (uploaded, approved) => {
    if (
      documentsAccess.value.validado_admin === -1 ||
      documentsAccess.value.status === "rejected"
    ) {
      return {
        state: "rejected",
        badge: "Rechazado",
        detail: "Documento rechazado. Debes volver a subirlo.",
      };
    }

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
  return (
    !uploadingDocuments.value &&
    !!ndaFile.value &&
    !!loiFile.value &&
    ndaValidation.value.valid === true &&
    loiValidation.value.valid === true
  );
});

function buildDownloadUrl(fileName) {
  if (!fileName) return "";

  const apiBase = (
    import.meta.env.VITE_API_BASE_URL ||
    "http://localhost/Reglado/Inmobiliaria_Reglados/backend/api"
  ).replace(/\/+$/, "");

  return `${apiBase}/download_document.php?file=${encodeURIComponent(fileName)}`;
}

function buildLegalDownloadUrl(type) {
  const apiBase = (
    import.meta.env.VITE_API_BASE_URL ||
    "http://localhost/Reglado/Inmobiliaria_Reglados/backend/api"
  ).replace(/\/+$/, "");

  const normalized = String(type || "").trim().toLowerCase();
  if (!propertyId.value || (normalized !== "nda" && normalized !== "loi")) {
    return "";
  }

  return `${apiBase}/download_legal_document.php?property_id=${encodeURIComponent(
    String(propertyId.value)
  )}&type=${encodeURIComponent(normalized)}`;
}

const uploadsUrl = (fileName) => buildDownloadUrl(fileName);

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

async function validateSelectedDocument(type, file) {
  const target = type === "nda" ? ndaValidation.value : loiValidation.value;

  target.checking = true;
  target.valid = null;
  target.message = "Comprobando firma...";

  try {
    const formData = new FormData();
    formData.append("file", file);
    formData.append("type", type);
    formData.append("property_id", String(propertyId.value || 0));

    const apiBase = (
      import.meta.env.VITE_API_BASE_URL ||
      "http://localhost/Reglado/Inmobiliaria_Reglados/backend/api"
    ).replace(/\/+$/, "");

    const response = await fetch(`${apiBase}/validate_signed_document.php`, {
      method: "POST",
      credentials: "include",
      body: formData,
    });

    const data = await response.json();

    if (!response.ok || data?.success === false) {
      throw new Error(data?.message || "No se pudo validar el documento.");
    }

    target.valid = Boolean(data.accepted);
    target.message = data.reason || (data.accepted ? "Documento válido." : "Documento no válido.");
  } catch (error) {
    target.valid = false;
    target.message = error?.message || "Error comprobando el documento.";
  } finally {
    target.checking = false;
  }
}

async function handleFileChange(field, event) {
  const file = event.target.files?.[0] ?? null;

  if (field === "nda") {
    ndaFile.value = file;
    ndaValidation.value = {
      checking: false,
      valid: null,
      message: "",
    };

    if (file) {
      await validateSelectedDocument("nda", file);
    }
  } else if (field === "loi") {
    loiFile.value = file;
    loiValidation.value = {
      checking: false,
      valid: null,
      message: "",
    };

    if (file) {
      await validateSelectedDocument("loi", file);
    }
  }
}

function openSignatureModal() {
  showSignatureModal.value = true;
  modalMessage.value = "";
  downloadError.value = "";
  ndaFile.value = null;
  loiFile.value = null;

  ndaValidation.value = {
    checking: false,
    valid: null,
    message: "",
  };

  loiValidation.value = {
    checking: false,
    valid: null,
    message: "",
  };
}

function closeSignatureModal() {
  showSignatureModal.value = false;
  ndaFile.value = null;
  loiFile.value = null;

  ndaValidation.value = {
    checking: false,
    valid: null,
    message: "",
  };

  loiValidation.value = {
    checking: false,
    valid: null,
    message: "",
  };
}

async function triggerDownload(url, fileName) {
  try {
    downloadError.value = "";

    const response = await fetch(url, {
      method: "GET",
      credentials: "include",
      headers: auth.authHeaders(),
    });

    if (!response.ok) {
      const text = await response.text();
      throw new Error(text || "Error descargando archivo");
    }

    const blob = await response.blob();
    const blobUrl = window.URL.createObjectURL(blob);

    const link = document.createElement("a");
    link.href = blobUrl;
    link.download = fileName || "archivo.pdf";
    document.body.appendChild(link);
    link.click();
    link.remove();

    setTimeout(() => {
      window.URL.revokeObjectURL(blobUrl);
    }, 1000);
  } catch (error) {
    console.error("Error descargando:", error);
    downloadError.value =
      error?.message || "No se pudo iniciar la descarga.";
  }
}

async function openDossier() {
  if (!accessGranted.value) {
    downloadError.value = "El dossier aún no está disponible.";
    return;
  }

  const fileName = property.value?.dossier_file;
  if (!fileName) {
    downloadError.value = "No se encontró el dossier.";
    return;
  }

  const url = buildDownloadUrl(fileName);
  const finalName = fileName.split("/").pop() || "dossier.pdf";

  await triggerDownload(url, finalName);
}

async function downloadDocument(fileName) {
  const ndaFile = property.value?.confidentiality_file || "";
  const loiFile = property.value?.intention_file || "";

  const normalized = String(fileName || "");
  const isNda = normalized && ndaFile && normalized === ndaFile;
  const isLoi = normalized && loiFile && normalized === loiFile;

  if ((isNda || isLoi) && propertyId.value) {
    const docType = isNda ? "nda" : "loi";
    const apiBase = (
      import.meta.env.VITE_API_BASE_URL ||
      "http://localhost/Reglado/Inmobiliaria_Reglados/backend/api"
    ).replace(/\/+$/, "");

    const url = `${apiBase}/download_legal_document.php?property_id=${encodeURIComponent(
      String(propertyId.value)
    )}&type=${encodeURIComponent(docType)}`;

    const finalName = normalized.split("/").pop() || `${docType}.pdf`;
    await triggerDownload(url, finalName);
    return;
  }

  const url = uploadsUrl(fileName);
  if (!url) {
    downloadError.value = "No se encontró la URL del documento.";
    return;
  }

  const finalName = fileName.split("/").pop() || "documento.pdf";
  await triggerDownload(url, finalName);
}

async function downloadLegalDocument(type, fileName) {
  const url = buildLegalDownloadUrl(type);
  if (!url) {
    downloadError.value = "No se encontrÃ³ la URL del documento.";
    return;
  }

  const safeName = String(fileName || "");
  const finalName =
    safeName.split("/").pop() ||
    (String(type || "").trim().toLowerCase() === "loi" ? "loi.pdf" : "nda.pdf");

  await triggerDownload(url, finalName);
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
      validado_admin: Number(response.access?.validado_admin ?? 0),
      status: String(response.access?.status ?? ""),
    };

    if (
      documentsAccess.value.validado_admin === -1 ||
      documentsAccess.value.status === "rejected"
    ) {
      accessMessage.value = "La documentación ha sido rechazada. Debes volver a subirla.";
    } else {
      accessMessage.value = response.message || defaultStatusDetail;
    }

    if (documentsAccess.value.dossier_unlocked && property.value?.dossier_file) {
      dossierLink.value = buildDownloadUrl(property.value.dossier_file);
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
      validado_admin: 0,
      status: "",
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
      validado_admin: Number(response.access?.validado_admin ?? 0),
      status: String(response.access?.status ?? ""),
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

function confirmPurchase() {
  if (!property.value) {
    return;
  }

  if (!accessGranted.value) {
    purchaseMessage.value = "Debes validar el acceso al dossier antes de solicitar la compra.";
    return;
  }

  showPurchaseModal.value = true;
}

function cancelPurchase() {
  if (purchaseLoading.value) {
    return;
  }

  showPurchaseModal.value = false;
}

async function submitPurchaseRequest() {
  if (!property.value) {
    return;
  }

  purchaseLoading.value = true;
  purchaseMessage.value = "";

  try {
    const response = await requestPropertyPurchase(property.value.id);
    purchaseMessage.value = response.message || "Solicitud enviada correctamente.";
    showPurchaseModal.value = false;
  } catch (err) {
    purchaseMessage.value = err?.message || "No se pudo enviar la solicitud.";
  } finally {
    purchaseLoading.value = false;
  }
}

async function toggleFavorite() {
  if (!property.value?.id || favoriteLoading.value) {
    return;
  }

  if (!isUserLoggedIn.value) {
    router.push("/login");
    return;
  }

  favoriteLoading.value = true;
  favoriteFeedback.value = "";
  favoriteError.value = "";

  try {
    if (isFavorite.value) {
      await removeFavorite(property.value.id);
      favoriteFeedback.value = "Propiedad eliminada de favoritos.";
    } else {
      await saveFavorite(property.value.id);
      favoriteFeedback.value = "Propiedad guardada en favoritos.";
    }

    property.value = {
      ...property.value,
      is_favorite: !isFavorite.value,
    };
  } catch (err) {
    favoriteError.value = err?.message || "No se pudo actualizar el favorito.";
    if (favoriteError.value.toLowerCase().includes("usuario no")) {
      router.push("/login");
    }
  } finally {
    favoriteLoading.value = false;
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
@keyframes floatSoft {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-4px);
  }
}

@keyframes glowPulse {
  0%, 100% {
    box-shadow: 0 0 0 rgba(244, 208, 120, 0);
  }
  50% {
    box-shadow: 0 0 28px rgba(244, 208, 120, 0.14);
  }
}

@keyframes fadeSlideUp {
  from {
    opacity: 0;
    transform: translateY(14px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes softShine {
  0% {
    transform: translateX(-140%) skewX(-18deg);
    opacity: 0;
  }
  20% {
    opacity: 0.22;
  }
  100% {
    transform: translateX(220%) skewX(-18deg);
    opacity: 0;
  }
}

.property-detail > * {
  animation: fadeSlideUp 0.5s ease both;
}

.property-detail > *:nth-child(2) {
  animation-delay: 0.06s;
}

.property-detail > *:nth-child(3) {
  animation-delay: 0.12s;
}

.property-detail > *:nth-child(4) {
  animation-delay: 0.18s;
}

.property-detail {
  padding: 160px;
  display: flex;
  flex-direction: column;
  gap: 24px;
  min-height: 100vh;
  background:
    radial-gradient(circle at top right, rgba(244, 208, 120, 0.10), transparent 24%),
    linear-gradient(180deg, #b6c6d6 0%, #eef2f7 100%);
}

.property-detail__hero {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 16px;
  padding: 28px 30px;
  border-radius: 28px;
  background:
    radial-gradient(circle at top right, rgba(244, 208, 120, 0.18), transparent 30%),
    linear-gradient(135deg, #12244d 0%, #20386b 55%, #3a5ca9 100%);
  box-shadow: 0 22px 48px rgba(18, 36, 77, 0.20);
  color: #fff;
  position: relative;
  overflow: hidden;
  transition: transform 0.28s ease, box-shadow 0.28s ease;
  animation: glowPulse 5.5s ease-in-out infinite;
}

.property-detail__hero::after,
.property-detail__hero::before {
  transition: transform 0.4s ease, opacity 0.4s ease;
}

.property-detail__hero:hover::before {
  transform: scale(1.05);
  opacity: 0.9;
}

.property-detail__hero:hover::after {
  transform: scale(1.08);
  opacity: 0.9;
}

.property-detail__hero:hover {
  transform: translateY(-2px);
  box-shadow: 0 28px 56px rgba(18, 36, 77, 0.26);
}

.property-detail__hero::before,
.property-detail__hero::after {
  content: "";
  position: absolute;
  border-radius: 999px;
  pointer-events: none;
  opacity: 0.72;
}

.property-detail__hero::before {
  width: 220px;
  height: 220px;
  right: -70px;
  top: -90px;
  background: rgba(255,255,255,0.08);
}

.property-detail__hero::after {
  width: 150px;
  height: 150px;
  left: -50px;
  bottom: -80px;
  background: rgba(255,204,84,0.12);
}

.property-detail__hero > * {
  position: relative;
  z-index: 2;
}

.property-detail__hero h1 {
  margin: 0;
  font-size: clamp(2rem, 1.5rem + 1vw, 2.4rem);
  line-height: 1.08;
  font-weight: 800;
  color: #fff;
}

.eyebrow {
  text-transform: uppercase;
  font-size: 0.78rem;
  letter-spacing: 0.18em;
  color: rgba(255,255,255,0.74);
  margin-bottom: 10px;
  font-weight: 700;
}

.location {
  margin: 10px 0 0;
  color: rgba(255,255,255,0.82);
  line-height: 1.5;
}

.hero-actions {
  display: flex;
  align-items: center;
  gap: 18px;
  justify-content: flex-end;
  flex-wrap: wrap;
}

.favorite-action {
  border: none;
  border-radius: 999px;
  padding: 10px 18px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: #1a2545;
  background: #f9f9f9;
  border: 1px solid #d6dbf0;
  cursor: pointer;
  transition: 0.2s ease, box-shadow 0.2s ease;
  min-width: 180px;
}

.favorite-action.active {
  background: #1f4aa8;
  color: #fff;
  border-color: #1f4aa8;
  box-shadow: 0 10px 26px rgba(31, 74, 168, 0.2);
}

.favorite-action:disabled {
  cursor: not-allowed;
  opacity: 0.7;
  box-shadow: none;
}

.favorite-feedbacks {
  margin-top: 12px;
  min-height: 26px;
}

.favorite-feedback {
  margin: 0;
  font-size: 0.9rem;
  color: #1f4aa8;
}

.favorite-feedback--error {
  color: #9f2d2d;
}

.input-pill {
  padding: 7px 14px;
  border-radius: 999px;
  border: 1px solid rgba(244, 208, 120, 0.32);
  font-size: 0.76rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  background: rgba(255,255,255,0.10);
  color: #f4d078;
  backdrop-filter: blur(8px);
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.08);
}

.pill--text {
  background: rgba(255,255,255,0.10);
  color: #f4d078;
}

.price {
  font-size: 1.65rem;
  font-weight: 800;
  color: #ffffff;
  letter-spacing: -0.02em;
}

.property-detail__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
}

.info-block,
.dossier-block {
  background: linear-gradient(180deg, #ffffff, #f8fafc);
  padding: 24px;
  border-radius: 24px;
  box-shadow: 0 14px 32px rgba(23, 42, 93, 0.10);
  border: 1px solid #dfe6f2;
  transition: transform 0.24s ease, box-shadow 0.24s ease, border-color 0.24s ease;
}

.info-block:hover,
.dossier-block:hover {
  transform: translateY(-10px);
  box-shadow: 0 22px 42px rgba(23, 42, 93, 0.34);
  border-color: #cdd9ea;
}

.metrics {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 14px;
  margin-bottom: 20px;
  align-items: stretch;
}

.metrics article {
  background: linear-gradient(180deg, #f7faff, #eef3f9);
  border-radius: 18px;
  padding: 18px 16px;
  text-align: center;
  border: 1px solid #d9e3f0;
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.8);
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  min-height: 118px;
  height: 100%;
  position: relative;
  overflow: hidden;
  transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
}

.metrics article::before {
  content: "";
  position: absolute;
  top: 0;
  left: -40%;
  width: 28%;
  height: 100%;
  background: linear-gradient(
    90deg,
    rgba(255,255,255,0) 0%,
    rgba(255,255,255,0.45) 50%,
    rgba(255,255,255,0) 100%
  );
  transform: skewX(-18deg);
  opacity: 0;
  pointer-events: none;
}

.metrics article:hover {
  transform: translateY(-4px);
  box-shadow: 0 16px 28px rgba(23, 42, 93, 0.10);
  border-color: #cbd9eb;
}

.metrics article:hover::before {
  animation: softShine 0.9s ease;
}

.metrics span,
.metrics strong {
  width: 100%;
  text-align: center;
  display: flex;
  justify-content: center;
  align-items: center;
}

.metrics span {
  min-height: 20px;
  font-size: 0.8rem;
  color: #6b7b95;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-weight: 700;
}

.metrics strong {
  margin-top: 8px;
  min-height: 34px;
  line-height: 1.25;
  word-break: break-word;
  text-wrap: balance;
  font-size: 1.05rem;
  color: #172a5d;
  font-weight: 800;
}

.info-block .metrics {
  align-content: start;
}

.signature-status {
  margin: 16px 0;
  padding: 14px 16px;
  border-radius: 16px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  font-weight: 600;
  background: #f5f7ff;
  color: #1b3a7a;
  border: 1px solid #d8e2f7;
}

.signature-status--pendiente {
  background: #fff6e8;
  color: #a35e00;
  border: 1px solid #f2d39a;
}

.signature-status--firmado {
  background: #eef3ff;
  color: #1b3a7a;
  border: 1px solid #d8e3ff;
}

.signature-status--validado {
  background: #e8f8ef;
  color: #0c6b34;
  border: 1px solid #bfe8cf;
}

.signature-status--rechazado {
  background: #fff1f2;
  color: #b42318;
  border: 1px solid #fecdd3;
}

.signature-status__label {
  font-size: 0.82rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  font-weight: 800;
}

.signature-status__detail {
  font-size: 0.95rem;
  line-height: 1.45;
}

.dossier-block h3 {
  margin: 0 0 12px;
  font-size: 1.15rem;
  color: #172a5e;
  font-weight: 800;
}

.step-note {
  margin: 10px 0 18px;
  padding: 16px 18px;
  border-radius: 18px;
  background:
    linear-gradient(180deg, rgba(255,255,255,0.92), rgba(241,246,255,0.95));
  border: 1px solid rgba(176, 193, 226, 0.85);
  color: #44556f;
  line-height: 1.65;
  box-shadow:
    0 10px 24px rgba(23, 42, 93, 0.06),
    inset 0 1px 0 rgba(255,255,255,0.95);
  backdrop-filter: blur(8px);
}

.step-note strong {
  color: #172a5d;
}

.dossier-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 12px;
}

.document-status-grid article {
  transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
}

.document-status-grid article:hover {
  transform: translateY(-3px);
  box-shadow: 0 14px 26px rgba(23, 42, 93, 0.10);
  border-color: #c7d5e8;
}

.document-status-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}

.status-chip {
  transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
}

.status-chip:hover {
  transform: scale(1.04);
  filter: brightness(1.02);
}

.document-status-grid article {
  background: linear-gradient(180deg, #ffffff, #f7faff);
  border-radius: 16px;
  padding: 14px;
  border: 1px solid #d6dfef;
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: center;
  box-shadow: 0 8px 18px rgba(23, 42, 93, 0.05);
}

.document-status-grid p {
  margin: 0;
  font-size: 0.85rem;
  color: #475569;
  line-height: 1.45;
}

.status-chip {
  padding: 6px 12px;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  border: 1px solid transparent;
}

.status-chip.status-approved {
  background: #dcfce7;
  color: #166534;
  border-color: #b8ebc7;
}

.status-chip.status-pending {
  background: #fff1bf;
  color: #92400e;
  border-color: #f3d37e;
}

.status-chip.status-idle {
  background: #eceff5;
  color: #24303f;
  border-color: #d8e0ea;
}

.status-chip.status-rejected {
  background: #ffe4e6;
  color: #be123c;
  border-color: #fecdd3;
}

.dossier-download-btn {
  border-radius: 999px;
  border: none;
  padding: 12px 28px;
  font-weight: 800;
  cursor: pointer;
  color: #172a5d;
  background: linear-gradient(135deg, #f4d078, #bd9b2c);
  box-shadow: 0 12px 24px rgba(189, 155, 44, 0.24);
  transition: opacity 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
  margin-bottom: 12px;
}

.dossier-download-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 16px 28px rgba(189, 155, 44, 0.30);
}

.dossier-download-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  box-shadow: none;
}

.primary-btn,
.secondary-btn {
  border: none;
  border-radius: 999px;
  padding: 12px 24px;
  font-weight: 800;
  cursor: pointer;
  transition:
    transform 0.18s ease,
    box-shadow 0.18s ease,
    background 0.18s ease,
    filter 0.18s ease,
    opacity 0.18s ease;
}

.signature-modal {
  animation: fadeSlideUp 0.22s ease;
}

.signature-modal__card,
.confirm-modal__card {
  animation: fadeSlideUp 0.28s ease;
  transition: transform 0.22s ease, box-shadow 0.22s ease;
}

.signature-modal__card:hover,
.confirm-modal__card:hover {
  box-shadow: 0 28px 64px rgba(0, 0, 0, 0.28);
}

.primary-btn:hover:not(:disabled),
.secondary-btn:hover:not(:disabled),
.dossier-download-btn:hover:not(:disabled) {
  filter: brightness(1.03);
}

.primary-btn:active:not(:disabled),
.secondary-btn:active:not(:disabled),
.dossier-download-btn:active:not(:disabled),
.download-links .download-link:active,
.upload-section input[type="file"]::file-selector-button:active {
  transform: translateY(0) scale(0.98);
}

.primary-btn {
  background: linear-gradient(135deg, #172a5d, #3654ae);
  color: #fff;
  box-shadow: 0 12px 24px rgba(23, 42, 93, 0.20);
}

.primary-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 16px 30px rgba(23, 42, 93, 0.26);
}

.primary-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.secondary-btn {
  background: rgba(255,255,255,0.92);
  border: 1px solid #cfd9e7;
  color: #172a5d;
  display: inline-flex;
  justify-content: center;
  margin-top: 12px;
  box-shadow: 0 8px 18px rgba(23, 42, 93, 0.06);
}

.secondary-btn:hover {
  transform: translateY(-2px);
  background: #f7faff;
  box-shadow: 0 12px 22px rgba(23, 42, 93, 0.10);
}

.status {
  margin-top: 14px;
  padding: 13px 15px;
  border-radius: 14px;
  background: linear-gradient(180deg, #eef4ff, #e6eeff);
  border: 1px solid #cedcf8;
  color: #172a5e;
  font-weight: 700;
  line-height: 1.5;
  box-shadow: 0 8px 18px rgba(23, 42, 93, 0.06);
}

.purchase-block {
  background: linear-gradient(180deg, #ffffff, #f8fafc);
  padding: 24px;
  border-radius: 24px;
  box-shadow: 0 14px 32px rgba(23, 42, 93, 0.10);
  border: 1px solid #dfe6f2;
  display: flex;
  flex-direction: column;
  gap: 14px;
  align-items: center;
  text-align: center;
  transition: transform 0.24s ease, box-shadow 0.24s ease, border-color 0.24s ease;
}

.purchase-block:hover {
  transform: translateY(-3px);
  box-shadow: 0 22px 42px rgba(23, 42, 93, 0.14);
  border-color: #cdd9ea;
}

.purchase-block .primary-btn,
.purchase-block .secondary-btn,
.purchase-block .dossier-download-btn {
  min-width: 220px;
  justify-content: center;
}

.signature-modal,
.confirm-modal {
  position: fixed;
  inset: 0;
  background: rgba(15, 22, 49, 0.64);
  backdrop-filter: blur(5px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  z-index: 100;
}

.signature-modal__card,
.confirm-modal__card {
  width: min(560px, 100%);
  background: linear-gradient(180deg, #ffffff, #f8fafc);
  padding: 28px;
  border-radius: 24px;
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.24);
  border: 1px solid #dde5f0;
}

.confirm-modal__card {
  width: min(480px, 100%);
  text-align: center;
}

.confirm-modal__card h4 {
  margin: 0 0 14px;
  color: #172a5d;
  font-size: 1.2rem;
  font-weight: 800;
}

.confirm-modal__card p {
  margin: 0;
  color: #475569;
  line-height: 1.6;
}

.confirm-modal__actions {
  display: flex;
  justify-content: center;
  gap: 12px;
  margin-top: 24px;
  flex-wrap: wrap;
}

.signature-modal__header {
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
  margin-bottom: 22px;
  text-align: center;
}

.signature-modal__header h3,
.signature-modal__header h4 {
  margin: 0;
  width: 100%;
  text-align: center;
  color: #172a5d;
  font-weight: 800;
}

.signature-modal__header button {
  position: absolute;
  right: 0;
  top: 50%;
  transform: translateY(-50%);
  border: none;
  background: linear-gradient(180deg, #eef3ff, #dfe8ff);
  color: #172a5d;
  width: 42px;
  height: 42px;
  border-radius: 999px;
  font-weight: 700;
  cursor: pointer;
  transition: transform 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
  box-shadow: 0 8px 20px rgba(23, 42, 93, 0.10);
}

.signature-modal__header button:hover {
  transform: translateY(-50%) scale(1.3);
  background: linear-gradient(180deg, #e7efff, #d8e3ff);
  box-shadow: 0 12px 24px rgba(23, 42, 93, 0.16);
}

.signature-modal__card .primary-btn {
  display: flex;
  justify-content: center;
  align-items: center;
  margin: 18px auto 0;
  min-width: 220px;
}

.download-links {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.download-links .download-link {
  flex: 1;
  border-radius: 16px;
  border: 1px dashed rgba(54, 84, 174, 0.45);
  text-align: center;
  color: #172a5d;
  font-weight: 800;
  padding: 14px 16px;
  background:
    linear-gradient(180deg, rgba(255,255,255,0.96), rgba(239,244,255,0.96));
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease, border-color 0.2s ease;
  box-shadow:
    0 10px 22px rgba(23, 42, 93, 0.06),
    inset 0 1px 0 rgba(255,255,255,0.95);
  backdrop-filter: blur(8px);
}

.download-links .download-link:hover {
  transform: translateY(-2px);
  background:
    radial-gradient(circle at top right, rgba(244, 208, 120, 0.16), transparent 35%),
    linear-gradient(180deg, #ffffff, #e7efff);
  border-color: rgba(189, 155, 44, 0.55);
  box-shadow: 0 14px 26px rgba(23, 42, 93, 0.10);
}

.upload-section label {
  transition: transform 0.18s ease, color 0.18s ease;
}

.upload-section label:hover {
  transform: translateX(2px);
  color: #20386b;
}

.upload-section input[type="file"] {
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease,
    background 0.2s ease,
    transform 0.18s ease;
}

.upload-section input[type="file"]:hover {
  transform: translateY(-1px);
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
  font-weight: 700;
  color: #172a5e;
}

.upload-section input[type="file"] {
  border-radius: 14px;
  border: 1px solid #d6dbf0;
  padding: 10px 12px;
  background: linear-gradient(180deg, #ffffff, #f8fbff);
  color: #172a5d;
  font-weight: 600;
  box-shadow: 0 8px 18px rgba(23, 42, 93, 0.05);
  transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.upload-section input[type="file"]:hover {
  background: linear-gradient(180deg, #ffffff, #eef4ff);
  border-color: #b9caea;
}

.upload-section input[type="file"]:focus {
  outline: none;
  border-color: #3654ae;
  box-shadow: 0 0 0 4px rgba(54, 84, 174, 0.10);
}

.upload-section input[type="file"]::file-selector-button {
  margin-right: 12px;
  border: none;
  border-radius: 999px;
  padding: 10px 16px;
  background: linear-gradient(135deg, #172a5d, #3654ae);
  color: #ffffff;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 8px 18px rgba(23, 42, 93, 0.16);
  transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
}

.upload-section input[type="file"]::file-selector-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 12px 22px rgba(23, 42, 93, 0.22);
}

.upload-section input:focus {
  outline: none;
  border-color: #3654ae;
  box-shadow: 0 0 0 4px rgba(54, 84, 174, 0.10);
}

.file-validation-field {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.file-validation-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 38px;
  padding: 8px 12px;
  border-radius: 999px;
  font-size: 0.8rem;
  font-weight: 800;
  white-space: nowrap;
  border: 1px solid transparent;
}

.file-validation-badge--checking {
  background: #eef3ff;
  color: #1b3a7a;
  border-color: #d8e3ff;
}

.file-validation-badge--valid {
  background: #e8f8ef;
  color: #0c6b34;
  border-color: #bfe8cf;
}

.file-validation-badge--invalid {
  background: #fff1f2;
  color: #b42318;
  border-color: #fecdd3;
}

.file-validation-message {
  display: block;
  margin-top: 6px;
  font-size: 0.84rem;
  color: #475569;
  line-height: 1.4;
}

@media (max-width: 768px) {
  .property-detail__grid {
    grid-template-columns: 1fr;
  }

  .property-detail__hero {
    flex-direction: column;
    align-items: flex-start;
    padding: 22px;
  }

  .hero-actions {
    width: 100%;
    justify-content: flex-start;
  }

  .metrics {
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  }

  .signature-modal__card {
    padding: 20px;
  }
}

@media (max-width: 1200px) {
  .property-detail {
    padding: 132px 48px 48px;
  }

  .property-detail__hero {
    padding: 24px 26px;
  }

  .property-detail__grid {
    grid-template-columns: 1fr;
    gap: 20px;
  }

  .info-block,
  .dossier-block,
  .purchase-block {
    padding: 22px;
  }

  .metrics {
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
  }
}

@media (max-width: 992px) {
  .property-detail {
    padding: 116px 28px 32px;
    gap: 20px;
  }

  .property-detail__hero {
    flex-direction: column;
    align-items: flex-start;
    gap: 18px;
    padding: 22px 22px;
    border-radius: 24px;
  }

  .property-detail__hero h1 {
    font-size: clamp(1.8rem, 1.35rem + 1.1vw, 2.2rem);
  }

  .hero-actions {
    width: 100%;
    justify-content: flex-start;
    gap: 12px;
  }

  .input-pill,
  .pill--text {
    font-size: 0.72rem;
    padding: 7px 12px;
  }

  .price {
    font-size: 1.45rem;
  }

  .property-detail__grid {
    grid-template-columns: 1fr;
    gap: 18px;
  }

  .metrics {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
  }

  .document-status-grid {
    grid-template-columns: 1fr;
  }

  .dossier-actions {
    gap: 10px;
  }

  .dossier-download-btn,
  .primary-btn,
  .secondary-btn {
    min-height: 46px;
  }

  .signature-modal,
  .confirm-modal {
    padding: 18px;
  }

  .signature-modal__card {
    width: min(640px, 100%);
    padding: 22px;
    border-radius: 22px;
  }

  .download-links {
    flex-direction: column;
  }

  .download-links .download-link {
    width: 100%;
  }
}

@media (max-width: 768px) {
  .property-detail {
    padding: 104px 18px 24px;
    gap: 16px;
  }

  .property-detail__hero {
    padding: 20px 18px;
    border-radius: 22px;
  }

  .property-detail__hero::before {
    width: 160px;
    height: 160px;
    right: -50px;
    top: -60px;
  }

  .property-detail__hero::after {
    width: 110px;
    height: 110px;
    left: -30px;
    bottom: -50px;
  }

  .property-detail__hero h1 {
    font-size: 1.65rem;
    line-height: 1.1;
  }

  .eyebrow {
    font-size: 0.7rem;
    letter-spacing: 0.14em;
    margin-bottom: 8px;
  }

  .location {
    font-size: 0.95rem;
  }

  .hero-actions {
    flex-direction: column;
    align-items: stretch;
    width: 100%;
  }

  .hero-actions > * {
    width: 100%;
  }

  .input-pill,
  .pill--text {
    display: inline-flex;
    justify-content: center;
    text-align: center;
    width: 100%;
    box-sizing: border-box;
  }

  .price {
    font-size: 1.35rem;
    width: 100%;
    text-align: left;
  }

  .info-block,
  .dossier-block,
  .purchase-block {
    padding: 18px;
    border-radius: 20px;
  }

  .metrics {
    grid-template-columns: 1fr 1fr;
    gap: 10px;
  }

  .metrics article {
    min-height: 92px;
    padding: 14px 12px;
    border-radius: 16px;
  }

  .metrics span {
    font-size: 0.72rem;
  }

  .metrics strong {
    font-size: 0.96rem;
  }

  .signature-status {
    padding: 12px 14px;
    border-radius: 14px;
  }

  .signature-status__label {
    font-size: 0.74rem;
  }

  .signature-status__detail {
    font-size: 0.9rem;
  }

  .step-note,
  .status {
    padding: 13px 14px;
    border-radius: 14px;
    font-size: 0.92rem;
  }

  .document-status-grid article {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }

  .status-chip {
    align-self: flex-start;
  }

  .dossier-actions {
    flex-direction: column;
    align-items: stretch;
  }

  .dossier-download-btn,
  .primary-btn,
  .secondary-btn,
  .purchase-block .primary-btn,
  .purchase-block .secondary-btn,
  .purchase-block .dossier-download-btn,
  .confirm-modal__actions .primary-btn,
  .confirm-modal__actions .secondary-btn {
    width: 100%;
    min-width: 0;
    justify-content: center;
  }

  .signature-modal,
  .confirm-modal {
    padding: 14px;
    align-items: flex-end;
  }

  .signature-modal__card,
  .confirm-modal__card {
    width: 100%;
    max-height: 88vh;
    overflow-y: auto;
    padding: 18px;
    border-radius: 20px 20px 16px 16px;
  }

  .signature-modal__header {
    margin-bottom: 18px;
  }

  .signature-modal__header h3,
  .signature-modal__header h4 {
    font-size: 1.15rem;
    padding: 0 48px 0 8px;
    line-height: 1.2;
  }

  .signature-modal__header button {
    width: 38px;
    height: 38px;
    right: 0;
  }

  .signature-modal__card .primary-btn {
    width: 100%;
    min-width: 0;
  }

  .confirm-modal__actions {
    flex-direction: column;
  }

  .download-links {
    gap: 10px;
  }

  .download-links .download-link {
    padding: 13px 14px;
    font-size: 0.95rem;
  }

  .upload-section {
    gap: 10px;
  }

  .upload-section label {
    font-size: 0.92rem;
  }

  .upload-section input[type="file"] {
    width: 100%;
    box-sizing: border-box;
    padding: 10px;
    font-size: 0.92rem;
  }

  .upload-section input[type="file"]::file-selector-button {
    padding: 9px 14px;
    margin-right: 10px;
  }
}

@media (max-width: 520px) {
  .property-detail {
    padding: 96px 12px 18px;
    gap: 14px;
  }

  .property-detail__hero {
    padding: 16px;
    border-radius: 18px;
  }

  .property-detail__hero h1 {
    font-size: 1.42rem;
  }

  .eyebrow {
    font-size: 0.66rem;
    letter-spacing: 0.12em;
  }

  .location {
    font-size: 0.88rem;
  }

  .price {
    font-size: 1.2rem;
  }

  .info-block,
  .dossier-block,
  .purchase-block {
    padding: 15px;
    border-radius: 18px;
  }

  .dossier-block h3 {
    font-size: 1rem;
  }

  .metrics {
    grid-template-columns: 1fr;
    gap: 8px;
  }

  .metrics article {
    min-height: 84px;
    padding: 12px 10px;
  }

  .metrics span {
    font-size: 0.68rem;
  }

  .metrics strong {
    font-size: 0.92rem;
  }

  .signature-status,
  .step-note,
  .status {
    font-size: 0.88rem;
  }

  .document-status-grid article {
    padding: 12px;
    border-radius: 14px;
  }

  .document-status-grid p {
    font-size: 0.8rem;
  }

  .status-chip {
    font-size: 0.66rem;
    padding: 5px 10px;
  }

  .dossier-download-btn,
  .primary-btn,
  .secondary-btn {
    padding: 11px 16px;
    font-size: 0.92rem;
    border-radius: 999px;
  }

  .signature-modal,
  .confirm-modal {
    padding: 10px;
  }

  .signature-modal__card,
  .confirm-modal__card {
    padding: 15px;
    border-radius: 18px 18px 14px 14px;
  }

  .signature-modal__header h3,
  .signature-modal__header h4,
  .confirm-modal__card h4 {
    font-size: 1rem;
    padding-right: 44px;
  }

  .signature-modal__header button {
    width: 34px;
    height: 34px;
    font-size: 0.9rem;
  }

  .download-links .download-link {
    font-size: 0.9rem;
    padding: 12px;
    border-radius: 14px;
  }

  .upload-section label {
    font-size: 0.88rem;
  }

  .upload-section input[type="file"] {
    font-size: 0.88rem;
    padding: 9px;
  }

  .upload-section input[type="file"]::file-selector-button {
    padding: 8px 12px;
    font-size: 0.85rem;
  }
}
</style>
