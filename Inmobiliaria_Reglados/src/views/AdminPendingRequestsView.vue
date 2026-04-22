<template>
  <div class="admin-pending">
    <header class="admin-header">
      <div class="admin-header__content">
        <h1>Solicitudes Pendientes</h1>
        <p>Solicitudes de promoción a rol Real esperando revisión.</p>
      </div>
      <div class="admin-stats">
        <div class="stat-card">
          <span class="stat-value">{{ requests.length + documentReviews.length }}</span>
          <span class="stat-label">Pendientes</span>
        </div>
      </div>
    </header>

    <div class="tabs">
      <button
        class="tab-btn"
        :class="{ active: activeTab === 'roles' }"
        type="button"
        @click="activeTab = 'roles'"
      >
        Promoción de rol
        <span class="tab-counter">{{ requests.length }}</span>
      </button>
      <button
        class="tab-btn"
        :class="{ active: activeTab === 'documents' }"
        type="button"
        @click="activeTab = 'documents'"
      >
        Documentos firmados
        <span class="tab-counter">{{ documentReviews.length }}</span>
      </button>
      <button
        class="tab-btn"
        :class="{ active: activeTab === 'purchases' }"
        type="button"
        @click="activeTab = 'purchases'"
      >
        Solicitudes de compra
        <span class="tab-counter">{{ pendingPurchaseCount }}</span>
      </button>
    </div>

    <div v-if="loading" class="admin-state">
      <div class="loader-spinner"></div>
      <p>Cargando solicitudes...</p>
    </div>

    <div v-else-if="error" class="admin-state admin-state--error">
      <p>{{ error }}</p>
    </div>

    <!-- TAB: Promoción de rol -->
    <template v-else-if="activeTab === 'roles'">
    <div v-if="requests.length === 0" class="admin-state">
      <p>No hay solicitudes de promoción de rol pendientes.</p>
    </div>

    <template v-else>
    <div class="filter-bar">
      <div class="filter-search">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8" />
          <path d="M21 21l-4.35-4.35" />
        </svg>
        <input
          v-model="rolesSearch"
          type="text"
          placeholder="Buscar por email, username o nombre..."
        />
      </div>
      <span class="filter-count">{{ filteredRequests.length }} de {{ requests.length }}</span>
    </div>

    <div v-if="filteredRequests.length === 0" class="admin-state">
      <p>No hay resultados que coincidan con tu búsqueda.</p>
    </div>

    <div v-else class="pending-list">
      <div class="pending-header-row">
        <span class="col-label col-id">ID</span>
        <span class="col-label col-username">Username</span>
        <span class="col-label col-email">Email</span>
        <span class="col-label col-name">Nombre</span>
        <span class="col-label col-date">Fecha</span>
        <span class="col-label col-status">Estado</span>
        <span class="col-label col-expand">Acciones</span>
      </div>

      <div
        v-for="req in filteredRequests"
        :key="req.id"
        class="pending-item"
        :class="{ 'is-expanded': expandedId === req.id }"
      >
        <div class="pending-item__header" @click="toggleExpand(req.id)">
          <span class="pending-id">#{{ req.id }}</span>
          <span class="pending-username">{{ req.username || '-' }}</span>
          <span class="pending-email">{{ req.user_email }}</span>
          <span class="pending-name">{{ formatName(req) }}</span>
          <span class="pending-date">{{ formatDate(req.created_at) }}</span>
          <span class="status-badge status-badge--pending">Pendiente</span>
          <button class="expand-btn" type="button">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M6 9l6 6 6-6" />
            </svg>
          </button>
        </div>

        <transition name="expand">
          <div v-if="expandedId === req.id" class="pending-item__details">
            <div class="details-grid">
              <div class="details-block">
                <h4>
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                  </svg>
                  Datos del solicitante
                </h4>
                <ul>
                  <li><strong>ID solicitud:</strong> {{ req.id }}</li>
                  <li><strong>ID usuario:</strong> {{ req.user_id ?? '-' }}</li>
                  <li><strong>Username:</strong> {{ req.username || '-' }}</li>
                  <li><strong>Nombre:</strong> {{ req.first_name || '-' }}</li>
                  <li><strong>Apellidos:</strong> {{ req.last_name || '-' }}</li>
                  <li><strong>Email:</strong> {{ req.user_email }}</li>
                </ul>
              </div>

              <div class="details-block">
                <h4>
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                  </svg>
                  Información del envío
                </h4>
                <ul>
                  <li><strong>Fecha:</strong> {{ formatDate(req.created_at) }}</li>
                  <li><strong>Estado:</strong>
                    <span class="status-badge status-badge--pending">Pendiente</span>
                  </li>
                </ul>
              </div>

              <div class="details-block details-block--full">
                <h4>
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                  </svg>
                  Mensaje del solicitante
                </h4>
                <p class="message-block">{{ req.message || 'Sin mensaje.' }}</p>
              </div>
            </div>

            <div class="details-actions">
              <button
                class="action-btn action-btn--approve"
                type="button"
                :disabled="actionLoadingId === req.id"
                @click.stop="approveRequest(req)"
              >
                {{ actionLoadingId === req.id ? 'Procesando...' : 'Aprobar y asignar rol Real' }}
              </button>

              <button
                class="action-btn action-btn--reject"
                type="button"
                :disabled="actionLoadingId === req.id"
                @click.stop="rejectRequest(req)"
              >
                {{ actionLoadingId === req.id ? 'Procesando...' : 'Rechazar solicitud' }}
              </button>
            </div>
          </div>
        </transition>
      </div>
    </div>
    </template>
    </template>

    <!-- TAB: Documentos firmados -->
    <template v-else-if="activeTab === 'documents'">
    <div v-if="documentReviews.length === 0" class="admin-state">
      <p>No hay documentos firmados pendientes de aprobación.</p>
    </div>

    <template v-else>
    <div class="filter-bar">
      <div class="filter-search">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8" />
          <path d="M21 21l-4.35-4.35" />
        </svg>
        <input
          v-model="documentsSearch"
          type="text"
          placeholder="Buscar por comprador, email o propiedad..."
        />
      </div>
      <span class="filter-count">{{ filteredDocumentReviews.length }} de {{ documentReviews.length }}</span>
    </div>

    <div v-if="filteredDocumentReviews.length === 0" class="admin-state">
      <p>No hay resultados que coincidan con tu búsqueda.</p>
    </div>

    <div v-else class="pending-list">
      <div class="pending-header-row pending-header-row--docs">
        <span class="col-label">ID</span>
        <span class="col-label">Comprador</span>
        <span class="col-label">Email</span>
        <span class="col-label">Propiedad</span>
        <span class="col-label">Subido</span>
        <span class="col-label">Estado</span>
        <span class="col-label">Acciones</span>
      </div>

      <div
        v-for="doc in filteredDocumentReviews"
        :key="'doc-' + doc.id"
        class="pending-item"
        :class="{ 'is-expanded': expandedDocId === doc.id }"
      >
        <div class="pending-item__header pending-item__header--docs" @click="toggleExpandDoc(doc.id)">
          <span class="pending-id">#{{ doc.id }}</span>
          <span class="pending-username">{{ formatBuyerName(doc) }}</span>
          <span class="pending-email">{{ doc.buyer_email || '-' }}</span>
          <span class="pending-name">{{ doc.property_title || `Propiedad #${doc.property_id}` }}</span>
          <span class="pending-date">{{ formatDate(doc.created_at) }}</span>
          <span class="status-badge status-badge--pending">Pendiente</span>
          <button class="expand-btn" type="button">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M6 9l6 6 6-6" />
            </svg>
          </button>
        </div>

        <transition name="expand">
          <div v-if="expandedDocId === doc.id" class="pending-item__details">
            <div class="details-grid">
              <div class="details-block">
                <h4>
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                  </svg>
                  Comprador
                </h4>
                <ul>
                  <li><strong>ID:</strong> {{ doc.buyer_user_id }}</li>
                  <li><strong>Username:</strong> {{ doc.buyer_username || '-' }}</li>
                  <li><strong>Nombre:</strong> {{ formatBuyerName(doc) }}</li>
                  <li><strong>Email:</strong> {{ doc.buyer_email || '-' }}</li>
                  <li><strong>Teléfono:</strong> {{ doc.buyer_phone || '-' }}</li>
                </ul>
              </div>

              <div class="details-block">
                <h4>
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                  </svg>
                  Propiedad
                </h4>
                <ul>
                  <li><strong>ID:</strong> {{ doc.property_id }}</li>
                  <li><strong>Título:</strong> {{ doc.property_title || '-' }}</li>
                  <li><strong>Categoría:</strong> {{ doc.property_category || '-' }}</li>
                  <li><strong>Ciudad:</strong> {{ doc.property_city || '-' }}</li>
                  <li><strong>Zona:</strong> {{ doc.property_zone || '-' }}</li>
                </ul>
              </div>

              <div class="details-block">
                <h4>
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                  </svg>
                  Información de la revisión
                </h4>
                <ul>
                  <li><strong>ID revisión:</strong> {{ doc.id }}</li>
                  <li><strong>Subido:</strong> {{ formatDate(doc.created_at) }}</li>
                  <li><strong>Caduca:</strong> {{ formatDate(doc.expires_at) }}</li>
                  <li><strong>Revisor asignado:</strong> {{ doc.reviewer_email || '-' }}</li>
                </ul>
              </div>
            </div>

            <div class="details-actions">
              <button
                class="action-btn action-btn--approve"
                type="button"
                :disabled="actionLoadingDocId === doc.id"
                @click.stop="approveDocument(doc)"
              >
                {{ actionLoadingDocId === doc.id ? 'Procesando...' : 'Aprobar documentación' }}
              </button>

              <button
                class="action-btn action-btn--reject"
                type="button"
                :disabled="actionLoadingDocId === doc.id"
                @click.stop="rejectDocument(doc)"
              >
                {{ actionLoadingDocId === doc.id ? 'Procesando...' : 'Rechazar documentación' }}
              </button>
            </div>
          </div>
        </transition>
      </div>
    </div>
    </template>
    </template>

    <!-- TAB: Solicitudes de compra -->
    <template v-else-if="activeTab === 'purchases'">
    <div v-if="purchaseRequests.length === 0" class="admin-state">
      <p>No hay solicitudes de compra registradas.</p>
    </div>

    <template v-else>
    <div class="filter-bar">
      <div class="filter-search">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8" />
          <path d="M21 21l-4.35-4.35" />
        </svg>
        <input
          v-model="purchasesSearch"
          type="text"
          placeholder="Buscar por comprador, email o propiedad..."
        />
      </div>
      <select v-model="purchasesStatusFilter" class="filter-select">
        <option value="">Todos los estados</option>
        <option value="pending">Pendiente</option>
        <option value="contacted">Contactado</option>
        <option value="closed">Cerrado</option>
      </select>
      <span class="filter-count">{{ filteredPurchaseRequests.length }} de {{ purchaseRequests.length }}</span>
    </div>

    <div v-if="filteredPurchaseRequests.length === 0" class="admin-state">
      <p>No hay resultados que coincidan con tu búsqueda.</p>
    </div>

    <div v-else class="pending-list">
      <div class="pending-header-row pending-header-row--purchases">
        <span class="col-label">ID</span>
        <span class="col-label">Comprador</span>
        <span class="col-label">Email</span>
        <span class="col-label">Propiedad</span>
        <span class="col-label">Fecha</span>
        <span class="col-label">Estado</span>
        <span class="col-label">Acciones</span>
      </div>

      <div
        v-for="purchase in filteredPurchaseRequests"
        :key="'purchase-' + purchase.id"
        class="pending-item"
        :class="{ 'is-expanded': expandedPurchaseId === purchase.id }"
      >
        <div class="pending-item__header pending-item__header--purchases" @click="toggleExpandPurchase(purchase.id)">
          <span class="pending-id">#{{ purchase.id }}</span>
          <span class="pending-username">{{ purchase.buyer_name || '-' }}</span>
          <span class="pending-email">{{ purchase.buyer_email }}</span>
          <span class="pending-name">{{ purchase.property_title || `Propiedad #${purchase.property_id}` }}</span>
          <span class="pending-date">{{ formatDate(purchase.created_at) }}</span>
          <span
            class="status-badge"
            :class="`status-badge--${purchase.status}`"
          >
            {{ statusLabel(purchase.status) }}
          </span>
          <button class="expand-btn" type="button">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M6 9l6 6 6-6" />
            </svg>
          </button>
        </div>

        <transition name="expand">
          <div v-if="expandedPurchaseId === purchase.id" class="pending-item__details">
            <div class="details-grid">
              <div class="details-block">
                <h4>
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                  </svg>
                  Comprador interesado
                </h4>
                <ul>
                  <li><strong>ID usuario:</strong> {{ purchase.buyer_user_id }}</li>
                  <li><strong>Nombre:</strong> {{ purchase.buyer_name || '-' }}</li>
                  <li><strong>Email:</strong> {{ purchase.buyer_email }}</li>
                  <li><strong>Teléfono:</strong> {{ purchase.buyer_phone || '-' }}</li>
                </ul>
              </div>

              <div class="details-block">
                <h4>
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                  </svg>
                  Propiedad de interés
                </h4>
                <ul>
                  <li><strong>ID:</strong> {{ purchase.property_id }}</li>
                  <li><strong>Tipo:</strong> {{ purchase.property_title || '-' }}</li>
                  <li><strong>Ciudad:</strong> {{ purchase.property_city || '-' }}</li>
                  <li><strong>Zona:</strong> {{ purchase.property_zone || '-' }}</li>
                  <li v-if="purchase.property_price"><strong>Precio:</strong> {{ formatPrice(purchase.property_price) }}</li>
                </ul>
              </div>

              <div class="details-block">
                <h4>
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                  </svg>
                  Estado de la solicitud
                </h4>
                <ul>
                  <li><strong>Recibida:</strong> {{ formatDate(purchase.created_at) }}</li>
                  <li><strong>Estado actual:</strong>
                    <span class="status-badge" :class="`status-badge--${purchase.status}`">
                      {{ statusLabel(purchase.status) }}
                    </span>
                  </li>
                  <li v-if="purchase.resolved_at"><strong>Resuelta:</strong> {{ formatDate(purchase.resolved_at) }}</li>
                  <li v-if="purchase.notes"><strong>Notas:</strong> {{ purchase.notes }}</li>
                </ul>
              </div>
            </div>

            <div class="details-actions">
              <button
                v-if="purchase.status !== 'contacted'"
                class="action-btn action-btn--contact"
                type="button"
                :disabled="actionLoadingPurchaseId === purchase.id"
                @click.stop="changePurchaseStatus(purchase, 'contacted')"
              >
                {{ actionLoadingPurchaseId === purchase.id ? 'Procesando...' : 'Marcar como contactado' }}
              </button>

              <button
                v-if="purchase.status !== 'closed'"
                class="action-btn action-btn--close"
                type="button"
                :disabled="actionLoadingPurchaseId === purchase.id"
                @click.stop="changePurchaseStatus(purchase, 'closed')"
              >
                {{ actionLoadingPurchaseId === purchase.id ? 'Procesando...' : 'Cerrar solicitud' }}
              </button>

              <button
                v-if="purchase.status !== 'pending'"
                class="action-btn action-btn--reopen"
                type="button"
                :disabled="actionLoadingPurchaseId === purchase.id"
                @click.stop="changePurchaseStatus(purchase, 'pending')"
              >
                {{ actionLoadingPurchaseId === purchase.id ? 'Procesando...' : 'Reabrir' }}
              </button>
            </div>
          </div>
        </transition>
      </div>
    </div>
    </template>
    </template>

    <transition name="fade">
      <div v-if="confirmModal.show" class="custom-modal-overlay">
        <div class="custom-modal">
          <h3>{{ confirmModal.title }}</h3>
          <p>{{ confirmModal.message }}</p>
          <div class="custom-modal-actions">
            <button class="btn-cancel" type="button" @click="confirmModal.cancel">Cancelar</button>
            <button class="btn-confirm" type="button" @click="confirmModal.confirm">Confirmar</button>
          </div>
        </div>
      </div>
    </transition>

    <div class="toast-container">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="toast"
        :class="`toast--${toast.type}`"
      >
        {{ toast.message }}
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from "vue";
import {
  fetchPendingRequests,
  approvePendingRequest,
  rejectPendingRequest,
  fetchPendingDocumentReviews,
  approveDocumentReviewAsAdmin,
  rejectDocumentReviewAsAdmin,
  fetchPurchaseRequests,
  updatePurchaseRequestStatus,
} from "../services/admin";

export default {
  name: "AdminPendingRequestsView",
  setup() {
    const requests = ref([]);
    const documentReviews = ref([]);
    const purchaseRequests = ref([]);
    const loading = ref(true);
    const error = ref(null);
    const expandedId = ref(null);
    const expandedDocId = ref(null);
    const expandedPurchaseId = ref(null);
    const actionLoadingId = ref(null);
    const actionLoadingDocId = ref(null);
    const actionLoadingPurchaseId = ref(null);
    const activeTab = ref("roles");

    const pendingPurchaseCount = computed(() =>
      purchaseRequests.value.filter((p) => p.status === "pending").length
    );

    const rolesSearch = ref("");
    const documentsSearch = ref("");
    const purchasesSearch = ref("");
    const purchasesStatusFilter = ref("");

    function matchesText(value, query) {
      return String(value || "").toLowerCase().includes(query);
    }

    const filteredRequests = computed(() => {
      const q = rolesSearch.value.trim().toLowerCase();
      if (!q) return requests.value;
      return requests.value.filter((r) =>
        matchesText(r.user_email, q) ||
        matchesText(r.username, q) ||
        matchesText(r.first_name, q) ||
        matchesText(r.last_name, q)
      );
    });

    const filteredDocumentReviews = computed(() => {
      const q = documentsSearch.value.trim().toLowerCase();
      if (!q) return documentReviews.value;
      return documentReviews.value.filter((d) =>
        matchesText(d.buyer_email, q) ||
        matchesText(d.buyer_username, q) ||
        matchesText(d.buyer_first_name, q) ||
        matchesText(d.buyer_last_name, q) ||
        matchesText(d.property_title, q) ||
        matchesText(d.property_city, q) ||
        matchesText(d.property_zone, q)
      );
    });

    const filteredPurchaseRequests = computed(() => {
      const q = purchasesSearch.value.trim().toLowerCase();
      const statusFilter = purchasesStatusFilter.value;
      return purchaseRequests.value.filter((p) => {
        if (statusFilter && p.status !== statusFilter) return false;
        if (!q) return true;
        return (
          matchesText(p.buyer_email, q) ||
          matchesText(p.buyer_name, q) ||
          matchesText(p.property_title, q) ||
          matchesText(p.property_city, q) ||
          matchesText(p.property_zone, q)
        );
      });
    });

    const toasts = ref([]);
    const confirmModal = ref({
      show: false,
      title: "",
      message: "",
      confirm: () => {},
      cancel: () => {},
    });

    function showToast(message, type = "success") {
      const id = Date.now() + Math.random();
      toasts.value.push({ id, message, type });
      setTimeout(() => {
        toasts.value = toasts.value.filter((t) => t.id !== id);
      }, 3200);
    }

    function showConfirm({ title, message }) {
      return new Promise((resolve) => {
        confirmModal.value = {
          show: true,
          title,
          message,
          confirm: () => {
            confirmModal.value.show = false;
            resolve(true);
          },
          cancel: () => {
            confirmModal.value.show = false;
            resolve(false);
          },
        };
      });
    }

    async function load() {
      loading.value = true;
      error.value = null;
      try {
        const [rolesPayload, docsPayload, purchasesPayload] = await Promise.all([
          fetchPendingRequests(),
          fetchPendingDocumentReviews(),
          fetchPurchaseRequests(false),
        ]);
        requests.value = rolesPayload.requests || [];
        documentReviews.value = docsPayload.reviews || [];
        purchaseRequests.value = purchasesPayload.requests || [];
      } catch (e) {
        error.value = e.message || "Error al cargar las solicitudes";
        requests.value = [];
        documentReviews.value = [];
        purchaseRequests.value = [];
      } finally {
        loading.value = false;
      }
    }

    function toggleExpand(id) {
      expandedId.value = expandedId.value === id ? null : id;
    }

    function toggleExpandDoc(id) {
      expandedDocId.value = expandedDocId.value === id ? null : id;
    }

    function formatBuyerName(doc) {
      const fullName = [doc.buyer_first_name, doc.buyer_last_name].filter(Boolean).join(" ").trim();
      return fullName || doc.buyer_username || "-";
    }

    function formatDate(iso) {
      if (!iso) return "-";
      const d = new Date(iso.replace(" ", "T"));
      if (Number.isNaN(d.getTime())) return iso;
      return d.toLocaleString("es-ES", {
        day: "2-digit",
        month: "long",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      });
    }

    function formatName(req) {
      const fullName = [req.first_name, req.last_name].filter(Boolean).join(" ").trim();
      return fullName || "-";
    }

    async function approveRequest(req) {
      const confirmed = await showConfirm({
        title: "Aprobar solicitud",
        message: `¿Aprobar a ${req.user_email} como usuario Real?`,
      });
      if (!confirmed) return;

      actionLoadingId.value = req.id;
      try {
        const result = await approvePendingRequest(req.id);
        if (!result?.success) throw new Error(result?.message || "No se pudo aprobar.");
        requests.value = requests.value.filter((r) => r.id !== req.id);
        if (expandedId.value === req.id) expandedId.value = null;
        showToast("Solicitud aprobada correctamente", "success");
      } catch (e) {
        showToast(e.message || "Error al aprobar la solicitud", "error");
      } finally {
        actionLoadingId.value = null;
      }
    }

    async function rejectRequest(req) {
      const confirmed = await showConfirm({
        title: "Rechazar solicitud",
        message: `¿Rechazar la solicitud de ${req.user_email}?`,
      });
      if (!confirmed) return;

      actionLoadingId.value = req.id;
      try {
        const result = await rejectPendingRequest(req.id);
        if (!result?.success) throw new Error(result?.message || "No se pudo rechazar.");
        requests.value = requests.value.filter((r) => r.id !== req.id);
        if (expandedId.value === req.id) expandedId.value = null;
        showToast("Solicitud rechazada correctamente", "success");
      } catch (e) {
        showToast(e.message || "Error al rechazar la solicitud", "error");
      } finally {
        actionLoadingId.value = null;
      }
    }

    function toggleExpandPurchase(id) {
      expandedPurchaseId.value = expandedPurchaseId.value === id ? null : id;
    }

    function statusLabel(status) {
      const labels = {
        pending: "Pendiente",
        contacted: "Contactado",
        closed: "Cerrado",
      };
      return labels[status] || status;
    }

    function formatPrice(price) {
      return new Intl.NumberFormat("es-ES", {
        style: "currency",
        currency: "EUR",
        maximumFractionDigits: 0,
      }).format(Number(price || 0));
    }

    async function changePurchaseStatus(purchase, newStatus) {
      const labels = { contacted: "marcar como contactada", closed: "cerrar", pending: "reabrir" };
      const confirmed = await showConfirm({
        title: "Cambiar estado",
        message: `¿Quieres ${labels[newStatus]} la solicitud de ${purchase.buyer_email}?`,
      });
      if (!confirmed) return;

      actionLoadingPurchaseId.value = purchase.id;
      try {
        const result = await updatePurchaseRequestStatus(purchase.id, newStatus);
        if (!result?.success) throw new Error(result?.message || "No se pudo actualizar.");
        purchase.status = newStatus;
        purchase.resolved_at = newStatus === "pending" ? null : new Date().toISOString();
        showToast("Estado actualizado correctamente", "success");
      } catch (e) {
        showToast(e.message || "Error al actualizar el estado", "error");
      } finally {
        actionLoadingPurchaseId.value = null;
      }
    }

    async function approveDocument(doc) {
      const confirmed = await showConfirm({
        title: "Aprobar documentación",
        message: `¿Aprobar los documentos firmados de ${doc.buyer_email || formatBuyerName(doc)} para la propiedad "${doc.property_title || '#' + doc.property_id}"?`,
      });
      if (!confirmed) return;

      actionLoadingDocId.value = doc.id;
      try {
        const result = await approveDocumentReviewAsAdmin(doc.id);
        if (!result?.success) throw new Error(result?.message || "No se pudo aprobar.");
        documentReviews.value = documentReviews.value.filter((d) => d.id !== doc.id);
        if (expandedDocId.value === doc.id) expandedDocId.value = null;
        showToast("Documentación aprobada correctamente", "success");
      } catch (e) {
        showToast(e.message || "Error al aprobar la documentación", "error");
      } finally {
        actionLoadingDocId.value = null;
      }
    }

    async function rejectDocument(doc) {
      const confirmed = await showConfirm({
        title: "Rechazar documentación",
        message: `¿Rechazar los documentos firmados de ${doc.buyer_email || formatBuyerName(doc)}? El comprador deberá volver a subirlos.`,
      });
      if (!confirmed) return;

      actionLoadingDocId.value = doc.id;
      try {
        const result = await rejectDocumentReviewAsAdmin(doc.id);
        if (!result?.success) throw new Error(result?.message || "No se pudo rechazar.");
        documentReviews.value = documentReviews.value.filter((d) => d.id !== doc.id);
        if (expandedDocId.value === doc.id) expandedDocId.value = null;
        showToast("Documentación rechazada correctamente", "success");
      } catch (e) {
        showToast(e.message || "Error al rechazar la documentación", "error");
      } finally {
        actionLoadingDocId.value = null;
      }
    }

    onMounted(load);

    return {
      requests,
      documentReviews,
      purchaseRequests,
      filteredRequests,
      filteredDocumentReviews,
      filteredPurchaseRequests,
      rolesSearch,
      documentsSearch,
      purchasesSearch,
      purchasesStatusFilter,
      loading,
      error,
      expandedId,
      expandedDocId,
      expandedPurchaseId,
      actionLoadingId,
      actionLoadingDocId,
      actionLoadingPurchaseId,
      activeTab,
      pendingPurchaseCount,
      toasts,
      confirmModal,
      toggleExpand,
      toggleExpandDoc,
      toggleExpandPurchase,
      formatDate,
      formatName,
      formatBuyerName,
      formatPrice,
      statusLabel,
      approveRequest,
      rejectRequest,
      approveDocument,
      rejectDocument,
      changePurchaseStatus,
    };
  },
};
</script>

<style scoped>
.admin-pending {
  padding: 40px 100px;
  width: 100%;
  max-width: 100%;
  margin: 90px 0 0 0;
  min-height: 100vh;
  background: linear-gradient(180deg, #eaedf1, #bdd3ec);
  color: #1e293b;
}

.admin-header {
  background: linear-gradient(135deg, #1e3a8a 0%, #1e293b 100%);
  padding: 50px 60px;
  border-radius: 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 50px;
  color: white;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
  position: relative;
  overflow: hidden;
}

.admin-header::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: #c4aa1c;
}

.admin-header h1 {
  font-size: 2.8rem;
  font-family: 'Playfair Display', serif;
  margin: 0 0 12px 0;
  color: #fff;
}

.admin-header p {
  color: rgba(255, 255, 255, 0.7);
  font-size: 1.2rem;
  margin: 0;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  font-weight: 500;
}

.stat-card {
  background: rgba(255, 255, 255, 0);
  backdrop-filter: blur(10px);
  padding: 20px 35px;
  border-radius: 20px;
  border: 1px solid rgba(255, 255, 255, 0.15);
  display: flex;
  flex-direction: column;
  align-items: center;
}

.stat-value {
  font-size: 2.75rem;
  font-weight: 800;
  color: #c4aa1c;
}

.stat-label {
  font-size: 0.8rem;
  color: rgba(255, 255, 255, 0.756);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-top: 5px;
}

.tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 30px;
  background: rgba(255, 255, 255, 0.5);
  backdrop-filter: blur(12px);
  padding: 6px;
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.3);
  width: fit-content;
}

.tab-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 22px;
  background: transparent;
  border: none;
  border-radius: 12px;
  font-weight: 700;
  font-size: 0.95rem;
  color: #64748b;
  cursor: pointer;
  transition: all 0.25s ease;
}

.tab-btn:hover {
  background: rgba(255, 255, 255, 0.6);
  color: #1e293b;
}

.tab-btn.active {
  background: #1e293b;
  color: white;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.18);
}

.tab-counter {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 24px;
  height: 24px;
  padding: 0 8px;
  background: rgba(196, 170, 28, 0.2);
  color: #c4aa1c;
  border-radius: 999px;
  font-size: 0.8rem;
  font-weight: 800;
}

.tab-btn.active .tab-counter {
  background: #c4aa1c;
  color: #1e293b;
}

.filter-bar {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 20px;
  background: rgba(255, 255, 255, 0.5);
  backdrop-filter: blur(12px);
  padding: 14px 18px;
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.3);
  flex-wrap: wrap;
}

.filter-search {
  flex: 1;
  min-width: 280px;
  position: relative;
  display: flex;
  align-items: center;
}

.filter-search svg {
  position: absolute;
  left: 16px;
  color: #94a3b8;
  pointer-events: none;
}

.filter-search input {
  width: 100%;
  padding: 12px 16px 12px 44px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  background: white;
  color: #1e293b;
  font-size: 0.95rem;
  transition: all 0.25s ease;
}

.filter-search input:focus {
  outline: none;
  border-color: #c4aa1c;
  box-shadow: 0 0 0 3px rgba(196, 170, 28, 0.15);
}

.filter-select {
  padding: 12px 40px 12px 16px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  background: white;
  color: #1e293b;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 14px center;
  min-width: 180px;
}

.filter-select:focus {
  outline: none;
  border-color: #c4aa1c;
  box-shadow: 0 0 0 3px rgba(196, 170, 28, 0.15);
}

.filter-count {
  font-size: 0.85rem;
  color: #64748b;
  font-weight: 600;
  background: white;
  padding: 8px 14px;
  border-radius: 999px;
  border: 1px solid #e2e8f0;
  white-space: nowrap;
}

.pending-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.pending-header-row--docs,
.pending-item__header--docs {
  grid-template-columns: 60px 160px 1fr 1.2fr 180px 120px 50px;
}

.pending-header-row--purchases,
.pending-item__header--purchases {
  grid-template-columns: 60px 160px 1fr 1.2fr 180px 130px 50px;
}

.status-badge--contacted {
  background: rgba(59, 130, 246, 0.12);
  color: #1e40af;
  border-color: rgba(59, 130, 246, 0.3);
}

.status-badge--closed {
  background: rgba(100, 116, 139, 0.12);
  color: #334155;
  border-color: rgba(100, 116, 139, 0.3);
}

.action-btn--contact {
  background: rgba(59, 130, 246, 0.12);
  color: #1e40af;
  border-color: rgba(59, 130, 246, 0.3);
}

.action-btn--contact:hover:not(:disabled) {
  background: #2563eb;
  color: #fff;
  transform: translateY(-2px);
  box-shadow: 0 8px 18px rgba(37, 99, 235, 0.22);
}

.action-btn--close {
  background: rgba(100, 116, 139, 0.12);
  color: #334155;
  border-color: rgba(100, 116, 139, 0.3);
}

.action-btn--close:hover:not(:disabled) {
  background: #475569;
  color: #fff;
  transform: translateY(-2px);
  box-shadow: 0 8px 18px rgba(71, 85, 105, 0.22);
}

.action-btn--reopen {
  background: rgba(245, 158, 11, 0.12);
  color: #b45309;
  border-color: rgba(245, 158, 11, 0.3);
}

.action-btn--reopen:hover:not(:disabled) {
  background: #d97706;
  color: #fff;
  transform: translateY(-2px);
  box-shadow: 0 8px 18px rgba(217, 119, 6, 0.22);
}

.pending-header-row {
  display: grid;
  grid-template-columns: 60px 130px 1fr 160px 200px 120px 50px;
  align-items: center;
  padding: 0 30px 6px 30px;
  gap: 16px;
}

.col-label {
  font-size: 0.7rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: #94a3b8;
  text-align: center;
}

.pending-item {
  background: rgba(255, 255, 255, 0.5);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-radius: 20px;
  border: 1px solid rgba(255, 255, 255, 0.3);
  overflow: hidden;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

.pending-item:hover {
  border-color: rgba(255, 255, 255, 0.5);
  background: rgba(255, 255, 255, 0.65);
  transform: translateY(-2px);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
}

.pending-item.is-expanded {
  border-color: #c4aa1c;
  background: rgba(255, 255, 255, 0.7);
  box-shadow: 0 20px 40px rgba(196, 170, 28, 0.15);
}

.pending-item__header {
  padding: 22px 30px;
  display: grid;
  grid-template-columns: 60px 130px 1fr 160px 200px 120px 50px;
  align-items: center;
  cursor: pointer;
  user-select: none;
  gap: 16px;
}

.pending-id {
  font-family: 'JetBrains Mono', monospace;
  color: #c4aa1c;
  font-weight: 700;
  background: rgba(196, 170, 28, 0.1);
  padding: 6px 12px;
  border-radius: 10px;
  font-size: 0.85rem;
  justify-self: center;
}

.pending-username {
  text-align: center;
  font-weight: 600;
  color: #1e293b;
  overflow-wrap: anywhere;
}

.pending-email {
  text-align: center;
  color: #1e293b;
  font-weight: 500;
  overflow-wrap: anywhere;
}

.pending-name {
  text-align: center;
  color: #475569;
  font-weight: 500;
}

.pending-date {
  text-align: center;
  color: #64748b;
  font-size: 0.9rem;
  white-space: nowrap;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 6px 14px;
  border-radius: 999px;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  border: 1px solid transparent;
  white-space: nowrap;
  justify-self: center;
}

.status-badge--pending {
  background: rgba(245, 158, 11, 0.12);
  color: #b45309;
  border-color: rgba(245, 158, 11, 0.3);
}

.expand-btn {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  color: #94a3b8;
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  cursor: pointer;
  justify-self: center;
}

.is-expanded .expand-btn {
  transform: rotate(180deg);
  background: #c4aa1c;
  border-color: #c4aa1c;
  color: white;
}

.pending-item__details {
  padding: 0 30px 30px 30px;
  border-top: 1px solid #f1f5f9;
  background: #fcfcfc;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 30px;
  padding: 30px 0;
}

.details-block--full {
  grid-column: 1 / -1;
}

.details-block h4 {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 0.85rem;
  text-transform: uppercase;
  color: #c4aa1c;
  margin: 0 0 18px 0;
  letter-spacing: 0.1em;
  font-weight: 800;
  border-bottom: 2px solid rgba(196, 170, 28, 0.1);
  padding-bottom: 8px;
}

.details-block ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.details-block li {
  margin-bottom: 10px;
  font-size: 0.95rem;
  color: #1e293b;
  display: grid;
  grid-template-columns: 130px 1fr;
  gap: 12px;
  align-items: start;
}

.details-block li strong {
  color: #64748b;
  font-weight: 500;
}

.message-block {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 18px;
  margin: 0;
  color: #1e293b;
  font-size: 0.95rem;
  line-height: 1.6;
  white-space: pre-line;
}

.details-actions {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 14px;
  flex-wrap: wrap;
  padding-top: 25px;
  border-top: 1px solid rgba(0, 0, 0, 0.05);
  margin-top: 10px;
}

.action-btn {
  padding: 12px 28px;
  border-radius: 12px;
  font-weight: 700;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.25s ease;
  border: 1px solid transparent;
  white-space: nowrap;
}

.action-btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.action-btn--approve {
  background: rgba(34, 197, 94, 0.12);
  color: #15803d;
  border-color: rgba(34, 197, 94, 0.3);
}

.action-btn--approve:hover:not(:disabled) {
  background: #16a34a;
  color: #fff;
  transform: translateY(-2px);
  box-shadow: 0 8px 18px rgba(22, 163, 74, 0.22);
}

.action-btn--reject {
  background: rgba(239, 68, 68, 0.12);
  color: #b91c1c;
  border-color: rgba(239, 68, 68, 0.3);
}

.action-btn--reject:hover:not(:disabled) {
  background: #dc2626;
  color: #fff;
  transform: translateY(-2px);
  box-shadow: 0 8px 18px rgba(220, 38, 38, 0.22);
}

.expand-enter-active,
.expand-leave-active {
  transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
  max-height: 1200px;
}

.expand-enter-from,
.expand-leave-to {
  max-height: 0;
  opacity: 0;
  transform: translateY(-20px);
}

.admin-state {
  text-align: center;
  padding: 100px 0;
  color: #94a3b8;
}

.admin-state--error {
  color: #b91c1c;
}

.loader-spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #f1f5f9;
  border-top-color: #c4aa1c;
  border-radius: 50%;
  margin: 0 auto 25px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.custom-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.68);
  backdrop-filter: blur(6px);
  z-index: 3000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.custom-modal {
  width: 100%;
  max-width: 440px;
  background: #ffffff;
  border-radius: 22px;
  padding: 28px;
  box-shadow: 0 30px 60px rgba(0, 0, 0, 0.22);
  border: 1px solid rgba(226, 232, 240, 0.9);
  text-align: center;
}

.custom-modal h3 {
  margin: 0 0 10px 0;
  font-size: 1.35rem;
  color: #0f172a;
}

.custom-modal p {
  margin: 0;
  color: #475569;
  line-height: 1.6;
}

.custom-modal-actions {
  display: flex;
  justify-content: center;
  gap: 12px;
  margin-top: 22px;
  flex-wrap: wrap;
}

.btn-cancel,
.btn-confirm {
  min-width: 130px;
  min-height: 44px;
  border-radius: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.25s ease;
  border: none;
}

.btn-cancel {
  background: #e2e8f0;
  color: #0f172a;
}

.btn-cancel:hover {
  background: #cbd5e1;
}

.btn-confirm {
  background: #1e293b;
  color: #ffffff;
}

.btn-confirm:hover {
  background: #0f172a;
}

.toast-container {
  position: fixed;
  top: 22px;
  right: 22px;
  z-index: 4000;
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-width: min(360px, calc(100vw - 24px));
}

.toast {
  padding: 14px 18px;
  border-radius: 14px;
  color: white;
  font-weight: 700;
  box-shadow: 0 18px 30px rgba(15, 23, 42, 0.18);
  animation: toastSlideIn 0.28s ease;
}

.toast--success {
  background: linear-gradient(135deg, #16a34a, #15803d);
}

.toast--error {
  background: linear-gradient(135deg, #dc2626, #b91c1c);
}

@keyframes toastSlideIn {
  from { transform: translateX(36px); opacity: 0; }
  to { transform: translateX(0); opacity: 1; }
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

@media (max-width: 1024px) {
  .admin-pending {
    padding: 40px 60px;
  }
  .admin-header {
    padding: 35px 40px;
  }
}

@media (max-width: 768px) {
  .admin-pending {
    padding: 20px;
    margin-top: 70px;
  }
  .admin-header {
    flex-direction: column;
    text-align: center;
    gap: 25px;
    padding: 30px 20px;
  }
  .admin-header h1 {
    font-size: 2rem;
  }
  .pending-header-row {
    display: none;
  }
  .pending-item__header {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
    padding: 20px;
    grid-template-columns: none;
  }
  .pending-id,
  .pending-username,
  .pending-email,
  .pending-name,
  .pending-date,
  .status-badge,
  .expand-btn {
    justify-self: auto;
    text-align: left;
  }
  .details-actions {
    flex-direction: column;
  }
  .action-btn {
    width: 100%;
  }
}
</style>
