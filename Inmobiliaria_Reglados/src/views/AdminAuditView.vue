<template>
  <div class="admin-audit">
    <header class="admin-header">
      <div class="admin-header__content">
        <h1>Registro de Auditoría</h1>
        <p>Acciones críticas registradas en el sistema.</p>
      </div>
      <div class="admin-stats">
        <div class="stat-card">
          <span class="stat-value">{{ total }}</span>
          <span class="stat-label">Eventos registrados</span>
        </div>
      </div>
    </header>

    <div class="admin-controls">
      <div class="search-box">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8" />
          <path d="M21 21l-4.35-4.35" />
        </svg>
        <input
          v-model="filters.action"
          type="text"
          placeholder="Buscar por acción (ej: document.download)..."
          @keyup.enter="applyFilters"
        />
      </div>

      <div class="filter-group">
        <input
          v-model="filters.user_email"
          type="text"
          placeholder="Email del usuario..."
          class="filter-input"
          @keyup.enter="applyFilters"
        />
      </div>

      <div class="filter-group">
        <input
          v-model="filters.date_from"
          type="date"
          class="filter-input"
          title="Desde"
        />
      </div>

      <div class="filter-group">
        <input
          v-model="filters.date_to"
          type="date"
          class="filter-input"
          title="Hasta"
        />
      </div>

      <div class="filter-actions">
        <button class="btn-apply" type="button" @click="applyFilters">Aplicar</button>
        <button class="btn-clear" type="button" @click="resetFilters">Limpiar</button>
      </div>
    </div>

    <div v-if="loading" class="admin-state">
      <div class="loader-spinner"></div>
      <p>Cargando registros...</p>
    </div>

    <div v-else-if="error" class="admin-state admin-state--error">
      <p>{{ error }}</p>
    </div>

    <div v-else-if="entries.length === 0" class="admin-state">
      <p>No se encontraron registros que coincidan con los filtros.</p>
    </div>

    <div v-else class="audit-list">
      <div class="audit-header-row">
        <div class="audit-info-main">
          <span class="col-label col-id">ID</span>
          <span class="col-label col-action">Acción</span>
          <span class="col-label col-role">Rol</span>
          <span class="col-label col-user">Usuario</span>
        </div>
        <div class="audit-meta-summary">
          <span class="col-label col-date">Fecha</span>
          <span class="col-label col-ip">IP</span>
          <span class="col-label col-expand">Detalles</span>
        </div>
      </div>

      <div
        v-for="entry in entries"
        :key="entry.id"
        class="audit-item"
        :class="{ 'is-expanded': expandedId === entry.id }"
      >
        <div class="audit-item__header" @click="toggleExpand(entry.id)">
          <div class="audit-info-main">
            <span class="audit-id">#{{ entry.id }}</span>
            <span class="audit-action-badge">{{ describeAction(entry.action) }}</span>
            <span
              v-if="entry.user_role"
              class="role-badge"
              :class="`role-badge--${entry.user_role}`"
            >
              {{ entry.user_role }}
            </span>
            <h3 class="audit-user">{{ entry.user_email || 'Anónimo' }}</h3>
          </div>

          <div class="audit-meta-summary">
            <span class="audit-date">{{ formatDate(entry.timestamp) }}</span>
            <span class="audit-ip">{{ entry.ip_address || '-' }}</span>
            <button class="expand-btn" type="button">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M6 9l6 6 6-6" />
              </svg>
            </button>
          </div>
        </div>

        <transition name="expand">
          <div v-if="expandedId === entry.id" class="audit-item__details">
            <div class="details-grid">
              <div class="details-block">
                <h4>
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                  </svg>
                  Información del evento
                </h4>
                <ul>
                  <li><strong>ID:</strong> {{ entry.id }}</li>
                  <li><strong>Descripción:</strong> {{ describeAction(entry.action) }}</li>
                  <li><strong>Código técnico:</strong> <code class="tech-code">{{ entry.action }}</code></li>
                  <li><strong>Fecha:</strong> {{ formatDate(entry.timestamp) }}</li>
                  <li><strong>Resultado:</strong> {{ entry.success ? 'Exitoso' : 'Fallido' }}</li>
                </ul>
              </div>

              <div class="details-block">
                <h4>
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                  </svg>
                  Usuario
                </h4>
                <ul>
                  <li><strong>ID:</strong> {{ entry.user_id ?? '-' }}</li>
                  <li><strong>Email:</strong> {{ entry.user_email || '-' }}</li>
                  <li><strong>Rol:</strong> {{ entry.user_role || '-' }}</li>
                </ul>
              </div>

              <div class="details-block">
                <h4>
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/>
                    <path d="M13 2v7h7"/>
                  </svg>
                  Recurso afectado
                </h4>
                <ul>
                  <li><strong>Tipo:</strong> {{ entry.resource_type || '-' }}</li>
                  <li><strong>ID:</strong> {{ entry.resource_id || '-' }}</li>
                </ul>
              </div>

              <div class="details-block">
                <h4>
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                    <circle cx="12" cy="10" r="3"/>
                  </svg>
                  Origen
                </h4>
                <ul>
                  <li><strong>IP:</strong> {{ entry.ip_address || '-' }}</li>
                  <li><strong>User Agent:</strong> <span class="ua-text">{{ entry.user_agent || '-' }}</span></li>
                </ul>
              </div>

              <div v-if="entry.metadata" class="details-block details-block--full">
                <h4>
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 3h18v18H3z"/>
                    <path d="M8 8h8M8 12h8M8 16h5"/>
                  </svg>
                  Metadatos
                </h4>
                <pre class="metadata-block">{{ JSON.stringify(entry.metadata, null, 2) }}</pre>
              </div>
            </div>
          </div>
        </transition>
      </div>
    </div>

    <footer v-if="!loading && !error && entries.length > 0" class="audit-pagination">
      <button class="page-btn" :disabled="page <= 1" @click="changePage(page - 1)">
        ‹ Anterior
      </button>
      <span class="page-info">
        Página <strong>{{ page }}</strong> de <strong>{{ totalPages }}</strong> · {{ total }} registros
      </span>
      <button class="page-btn" :disabled="page >= totalPages" @click="changePage(page + 1)">
        Siguiente ›
      </button>
    </footer>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from "vue";
import { fetchAuditLog } from "../services/admin";

const ACTION_LABELS = {
  "admin.list_all_properties": "Consultó listado de propiedades",
  "admin.list_pending_requests": "Consultó solicitudes pendientes de rol",
  "admin.list_pending_document_reviews": "Consultó documentos pendientes de aprobación",
  "admin.list_purchase_requests": "Consultó solicitudes de compra",
  "admin.list_appointments": "Consultó listado de citas",
  "admin.list_users": "Consultó listado de usuarios",
  "property.create": "Creó una propiedad",
  "property.create_from_text": "Creó una propiedad a partir de texto",
  "property.delete": "Eliminó una propiedad",
  "property.status_change": "Cambió el estado de una propiedad",
  "property.deletion_requested": "Solicitó la eliminación de una propiedad",
  "property.deletion_approved": "Aprobó la eliminación de una propiedad",
  "property.deletion_rejected": "Rechazó la eliminación de una propiedad",
  "document.legal.download": "Descargó documento legal (NDA / LOI)",
  "document.signed.upload": "Subió documentos firmados",
  "document.signed.approve": "Aprobó documentos firmados",
  "document.signed.reject": "Rechazó documentos firmados",
  "document.review.approve": "Aprobó revisión de documento",
  "document.review.reject": "Rechazó revisión de documento",
  "purchase.request": "Envió una solicitud de compra",
  "purchase_request.status_change": "Cambió el estado de una solicitud de compra",
  "appointment.delete": "Eliminó una cita",
  "appointment.completed": "Marcó una cita como completada",
  "appointment.cancelled": "Canceló una cita",
  "buyer_intent.create": "Creó una preferencia de búsqueda",
  "role.promotion_requested": "Solicitó promoción a usuario Premium",
  "role.promotion.approve": "Aprobó solicitud de rol Premium",
  "role.promotion.reject": "Rechazó solicitud de rol Premium",
  "user.role_change": "Cambió el rol de un usuario",
  "user.role_change_rate_limited": "Intento de cambio de rol bloqueado por límite de frecuencia",
  "user.role_change_auth_failed": "Intento de cambio de rol con autenticación fallida",
  "user.blocked_inmo": "Bloqueó el acceso de un usuario a Inmobiliaria",
  "user.unblocked_inmo": "Desbloqueó el acceso de un usuario a Inmobiliaria",
  "user.force_relogin": "Forzó re-login de un usuario",
};

export default {
  name: "AdminAuditView",
  setup() {
    const entries = ref([]);
    const total = ref(0);
    const page = ref(1);
    const perPage = ref(50);
    const totalPages = ref(1);
    const loading = ref(false);
    const error = ref(null);
    const expandedId = ref(null);

    const filters = reactive({
      action: "",
      user_email: "",
      date_from: "",
      date_to: "",
    });

    async function load() {
      loading.value = true;
      error.value = null;
      try {
        const payload = await fetchAuditLog({
          page: page.value,
          per_page: perPage.value,
          action: filters.action || undefined,
          user_email: filters.user_email || undefined,
          date_from: filters.date_from || undefined,
          date_to: filters.date_to || undefined,
        });
        entries.value = payload.entries || [];
        total.value = payload.total || 0;
        totalPages.value = payload.pages || 1;
      } catch (e) {
        error.value = e.message || "Error al cargar el registro";
        entries.value = [];
      } finally {
        loading.value = false;
      }
    }

    function applyFilters() {
      page.value = 1;
      load();
    }

    function resetFilters() {
      filters.action = "";
      filters.user_email = "";
      filters.date_from = "";
      filters.date_to = "";
      page.value = 1;
      load();
    }

    function changePage(newPage) {
      if (newPage < 1 || newPage > totalPages.value) return;
      page.value = newPage;
      load();
    }

    function toggleExpand(id) {
      expandedId.value = expandedId.value === id ? null : id;
    }

    function describeAction(action) {
      return ACTION_LABELS[action] || action;
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

    onMounted(load);

    return {
      entries,
      total,
      page,
      totalPages,
      loading,
      error,
      filters,
      expandedId,
      applyFilters,
      resetFilters,
      changePage,
      toggleExpand,
      formatDate,
      describeAction,
    };
  },
};
</script>

<style scoped>
.admin-audit {
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

.admin-controls {
  display: grid;
  grid-template-columns: 2fr 1.3fr 1fr 1fr auto;
  gap: 18px;
  margin-bottom: 40px;
  align-items: stretch;
}

.search-box {
  position: relative;
  display: flex;
  align-items: center;
}

.search-box svg {
  position: absolute;
  left: 20px;
  color: #94a3b8;
}

.search-box input {
  width: 100%;
  padding: 16px 20px 16px 55px;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  background: white;
  color: #1e293b;
  font-size: 1.05rem;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
}

.search-box input:focus {
  outline: none;
  border-color: #c4aa1c;
  box-shadow: 0 10px 25px rgba(196, 170, 28, 0.1);
}

.filter-input {
  padding: 16px 20px;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  background: white;
  color: #1e293b;
  font-size: 1rem;
  font-weight: 500;
  width: 100%;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
  transition: all 0.3s ease;
}

.filter-input:focus {
  outline: none;
  border-color: #c4aa1c;
  box-shadow: 0 10px 25px rgba(196, 170, 28, 0.1);
}

.filter-actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.btn-apply,
.btn-clear {
  padding: 14px 22px;
  border-radius: 14px;
  font-weight: 700;
  font-size: 0.95rem;
  cursor: pointer;
  border: 1px solid transparent;
  transition: all 0.25s ease;
  white-space: nowrap;
}

.btn-apply {
  background: #1e293b;
  color: white;
}

.btn-apply:hover {
  background: #0f172a;
  transform: translateY(-2px);
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.22);
}

.btn-clear {
  background: white;
  color: #1e293b;
  border-color: #e2e8f0;
}

.btn-clear:hover {
  background: #f1f5f9;
  transform: translateY(-2px);
}

.audit-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.audit-header-row {
  display: grid;
  grid-template-columns: 60px 260px 90px 1fr 200px 90px 50px;
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

.tech-code {
  background: #1e293b;
  color: #c4aa1c;
  padding: 2px 8px;
  border-radius: 6px;
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.85rem;
}

.tech-code {
  background: #1e293b;
  color: #c4aa1c;
  padding: 2px 8px;
  border-radius: 6px;
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.85rem;
}

.audit-item {
  background: rgba(255, 255, 255, 0.5);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-radius: 20px;
  border: 1px solid rgba(255, 255, 255, 0.3);
  overflow: hidden;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

.audit-item:hover {
  border-color: rgba(255, 255, 255, 0.5);
  background: rgba(255, 255, 255, 0.65);
  transform: translateY(-2px);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
}

.audit-item.is-expanded {
  border-color: #c4aa1c;
  background: rgba(255, 255, 255, 0.7);
  box-shadow: 0 20px 40px rgba(196, 170, 28, 0.15);
}

.audit-item__header {
  padding: 22px 30px;
  display: grid;
  grid-template-columns: 60px 260px 90px 1fr 200px 90px 50px;
  align-items: center;
  cursor: pointer;
  user-select: none;
  gap: 16px;
}

.audit-info-main {
  display: contents;
}

.audit-meta-summary {
  display: contents;
}

.audit-id {
  font-family: 'JetBrains Mono', monospace;
  color: #c4aa1c;
  font-weight: 700;
  background: rgba(196, 170, 28, 0.1);
  padding: 6px 12px;
  border-radius: 10px;
  font-size: 0.85rem;
}

.audit-action-badge {
  font-size: 0.78rem;
  font-weight: 800;
  text-transform: lowercase;
  background: #1e293b;
  color: white;
  padding: 6px 14px;
  border-radius: 10px;
  letter-spacing: 0.03em;
  font-family: 'JetBrains Mono', monospace;
}

.role-badge {
  font-size: 0.7rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  padding: 4px 10px;
  border-radius: 999px;
}

.role-badge--admin {
  background: rgba(196, 170, 28, 0.15);
  color: #92400e;
  border: 1px solid rgba(196, 170, 28, 0.3);
}

.role-badge--real {
  background: rgba(30, 64, 175, 0.1);
  color: #1e40af;
  border: 1px solid rgba(30, 64, 175, 0.25);
}

.audit-user {
  margin: 0;
  font-size: 1.05rem;
  color: #1e293b;
  font-weight: 600;
  overflow-wrap: anywhere;
}

.audit-date {
  color: #64748b;
  font-size: 0.9rem;
  font-weight: 500;
  white-space: nowrap;
  text-align: center;
}

.audit-ip {
  font-family: 'JetBrains Mono', monospace;
  color: #94a3b8;
  font-size: 0.85rem;
  text-align: center;
}

.audit-id,
.audit-action-badge,
.role-badge {
  justify-self: center;
}

.audit-user {
  text-align: center;
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

.audit-item__details {
  padding: 0 30px 30px 30px;
  border-top: 1px solid #e2e8f0;
  background: #f1f5f9;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
  padding: 24px 0;
}

.details-block {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 14px;
  padding: 22px;
  box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
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
  margin: 0 0 16px 0;
  letter-spacing: 0.1em;
  font-weight: 800;
  border-bottom: 2px solid rgba(196, 170, 28, 0.2);
  padding-bottom: 10px;
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
  grid-template-columns: 85px minmax(0, 1fr);
  gap: 8px;
  align-items: start;
  overflow-wrap: anywhere;
  word-break: break-word;
}

.details-block li strong {
  color: #64748b;
  font-weight: 500;
}

.details-block li span {
  overflow-wrap: anywhere;
  word-break: break-word;
}

.ua-text {
  font-size: 0.8rem;
  color: #475569;
  font-family: 'JetBrains Mono', monospace;
}

.metadata-block {
  background: #0f172a;
  color: #f1f5f9;
  padding: 16px;
  border-radius: 12px;
  font-size: 0.85rem;
  font-family: 'JetBrains Mono', monospace;
  overflow-x: auto;
  margin: 0;
  line-height: 1.6;
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

.audit-pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 30px;
  padding: 18px 24px;
  background: rgba(255, 255, 255, 0.5);
  backdrop-filter: blur(12px);
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.3);
}

.page-btn {
  padding: 10px 20px;
  border-radius: 12px;
  background: white;
  border: 1px solid #e2e8f0;
  font-weight: 700;
  color: #1e293b;
  cursor: pointer;
  transition: all 0.25s ease;
}

.page-btn:hover:not(:disabled) {
  background: #1e293b;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.22);
}

.page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.page-info {
  font-size: 0.95rem;
  color: #64748b;
  font-weight: 500;
}

.page-info strong {
  color: #1e293b;
}

@media (max-width: 1024px) {
  .admin-audit {
    padding: 40px 60px;
  }

  .admin-header {
    padding: 35px 40px;
  }

  .admin-controls {
    grid-template-columns: 1fr 1fr;
  }

  .filter-actions {
    grid-column: 1 / -1;
    justify-content: flex-end;
  }
}

@media (max-width: 768px) {
  .admin-audit {
    padding: 20px;
    margin-top: 70px;
  }

  .admin-header {
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 25px;
    padding: 30px 20px;
  }

  .admin-header h1 {
    font-size: 2rem;
  }

  .admin-header p {
    font-size: 1rem;
  }

  .admin-controls {
    grid-template-columns: 1fr;
    gap: 12px;
  }

  .filter-actions {
    grid-column: auto;
    flex-direction: column;
  }

  .btn-apply,
  .btn-clear {
    width: 100%;
  }

  .audit-header-row {
    display: none;
  }

  .audit-item__header {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 15px;
    padding: 20px;
    grid-template-columns: none;
  }

  .audit-info-main {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
  }

  .audit-meta-summary {
    display: flex;
    width: 100%;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    border-top: 1px solid rgba(0, 0, 0, 0.05);
    padding-top: 12px;
  }

  .audit-id,
  .audit-action-badge,
  .role-badge,
  .audit-date,
  .audit-ip,
  .expand-btn {
    justify-self: auto;
  }

  .audit-pagination {
    flex-direction: column;
    gap: 12px;
  }
}

@media (max-width: 480px) {
  .admin-audit {
    padding: 15px 10px;
  }

  .admin-header h1 {
    font-size: 1.6rem;
  }

  .audit-id,
  .audit-action-badge,
  .role-badge {
    font-size: 0.65rem;
    padding: 3px 8px;
  }

  .audit-user {
    font-size: 0.9rem;
  }

  .details-grid {
    grid-template-columns: 1fr;
    gap: 20px;
  }
}
</style>
