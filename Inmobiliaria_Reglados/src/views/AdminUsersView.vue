<template>
  <div class="admin-users">
    <header class="admin-header">
      <div class="admin-header__content">
        <h1>Gestión de Usuarios</h1>
        <p>Usuarios registrados en el grupo y su actividad en Inmobiliaria.</p>
      </div>
      <div class="admin-stats">
        <div class="stat-card">
          <span class="stat-value">{{ users.length }}</span>
          <span class="stat-label">{{ tabLabel }}</span>
        </div>
      </div>
    </header>

    <div class="tabs">
      <button class="tab-btn" :class="{ active: activeTab === 'active' }" type="button" @click="changeTab('active')">
        Activos en Inmobiliaria
      </button>
      <button class="tab-btn" :class="{ active: activeTab === 'all' }" type="button" @click="changeTab('all')">
        Todos los del grupo
      </button>
      <button class="tab-btn" :class="{ active: activeTab === 'blocked' }" type="button" @click="changeTab('blocked')">
        Bloqueados
      </button>
    </div>

    <div v-if="loading" class="admin-state">
      <div class="loader-spinner"></div>
      <p>Cargando usuarios...</p>
    </div>

    <div v-else-if="error" class="admin-state admin-state--error">
      <p>{{ error }}</p>
    </div>

    <template v-else>
    <div class="filter-bar">
      <div class="filter-search">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8" /><path d="M21 21l-4.35-4.35" />
        </svg>
        <input v-model="search" type="text" placeholder="Buscar por nombre, email o username..." />
      </div>
      <select v-model="roleFilter" class="filter-select">
        <option value="">Todos los roles</option>
        <option value="admin">Admin</option>
        <option value="real">Real</option>
        <option value="user">User</option>
      </select>
      <span class="filter-count">{{ filteredUsers.length }} de {{ users.length }}</span>
    </div>

    <div v-if="filteredUsers.length === 0" class="admin-state">
      <p>No hay usuarios que coincidan con los filtros.</p>
    </div>

    <div v-else class="users-list">
      <div class="users-header-row">
        <span class="col-label">ID</span>
        <span class="col-label">Username</span>
        <span class="col-label">Email</span>
        <span class="col-label">Rol</span>
        <span class="col-label">Actividad</span>
        <span class="col-label">Estado</span>
        <span class="col-label">Detalles</span>
      </div>

      <div
        v-for="u in filteredUsers"
        :key="u.id"
        class="user-item"
        :class="{ 'is-expanded': expandedId === u.id, 'is-blocked': u.is_blocked }"
      >
        <div class="user-item__header" @click="toggleExpand(u.id)">
          <span class="user-id">#{{ u.id }}</span>
          <span class="user-username">{{ u.username || '-' }}</span>
          <span class="user-email">{{ u.email }}</span>
          <span class="role-badge" :class="`role-badge--${u.role}`">{{ u.role }}</span>
          <span class="activity-badge" :class="u.is_active_in_inmo ? 'activity-badge--active' : 'activity-badge--inactive'">
            {{ u.is_active_in_inmo ? 'Activo' : 'Sin actividad' }}
          </span>
          <span v-if="u.is_blocked" class="status-badge status-badge--blocked">Bloqueado</span>
          <span v-else class="status-badge status-badge--ok">Activo</span>
          <button class="expand-btn" type="button">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M6 9l6 6 6-6" />
            </svg>
          </button>
        </div>

        <transition name="expand">
          <div v-if="expandedId === u.id" class="user-item__details">
            <div class="details-grid">
              <div class="details-block">
                <h4>Datos personales</h4>
                <ul>
                  <li><strong>ID:</strong> {{ u.id }}</li>
                  <li><strong>Username:</strong> {{ u.username || '-' }}</li>
                  <li><strong>Nombre:</strong> {{ u.first_name || '-' }} {{ u.last_name || '' }}</li>
                  <li><strong>Email:</strong> {{ u.email }}</li>
                  <li><strong>Teléfono:</strong> {{ u.phone || '-' }}</li>
                  <li><strong>Registrado:</strong> {{ formatDate(u.created_at) }}</li>
                </ul>
              </div>

              <div class="details-block">
                <h4>Actividad en Inmobiliaria</h4>
                <ul>
                  <li><strong>Propiedades creadas:</strong> {{ u.activity.properties }}</li>
                  <li><strong>Eventos auditados:</strong> {{ u.activity.audit_events }}</li>
                  <li><strong>Solicitudes de compra:</strong> {{ u.activity.purchase_requests }}</li>
                  <li><strong>Accesos a documentos:</strong> {{ u.activity.document_accesses }}</li>
                  <li><strong>Última acción:</strong> {{ u.activity.last_action ? formatDate(u.activity.last_action) : 'Nunca' }}</li>
                </ul>
              </div>

              <div class="details-block">
                <h4>Estado en Inmobiliaria</h4>
                <ul>
                  <li><strong>Bloqueado:</strong>
                    <span v-if="u.is_blocked" class="status-badge status-badge--blocked">Sí</span>
                    <span v-else class="status-badge status-badge--ok">No</span>
                  </li>
                  <li v-if="u.inmo_status?.last_token_invalidated_at">
                    <strong>Última invalidación:</strong> {{ formatDate(u.inmo_status.last_token_invalidated_at) }}
                  </li>
                  <li v-if="u.inmo_status?.notes"><strong>Notas:</strong> {{ u.inmo_status.notes }}</li>
                </ul>
              </div>
            </div>

            <div v-if="u.role !== 'admin'" class="details-actions">
              <button
                v-if="u.role !== 'real'"
                class="action-btn action-btn--promote"
                type="button"
                :disabled="actionLoadingId === u.id"
                @click.stop="changeRole(u, 'real')"
              >
                {{ actionLoadingId === u.id ? 'Procesando...' : 'Promover a Real' }}
              </button>

              <button
                v-if="u.role !== 'user'"
                class="action-btn action-btn--demote"
                type="button"
                :disabled="actionLoadingId === u.id"
                @click.stop="changeRole(u, 'user')"
              >
                {{ actionLoadingId === u.id ? 'Procesando...' : 'Cambiar a User' }}
              </button>

              <button
                class="action-btn action-btn--relogin"
                type="button"
                :disabled="actionLoadingId === u.id"
                @click.stop="forceRelogin(u)"
              >
                {{ actionLoadingId === u.id ? 'Procesando...' : 'Forzar re-login' }}
              </button>

              <button
                v-if="!u.is_blocked"
                class="action-btn action-btn--block"
                type="button"
                :disabled="actionLoadingId === u.id"
                @click.stop="blockUser(u)"
              >
                {{ actionLoadingId === u.id ? 'Procesando...' : 'Bloquear acceso' }}
              </button>

              <button
                v-else
                class="action-btn action-btn--unblock"
                type="button"
                :disabled="actionLoadingId === u.id"
                @click.stop="unblockUser(u)"
              >
                {{ actionLoadingId === u.id ? 'Procesando...' : 'Desbloquear' }}
              </button>
            </div>

            <div v-else class="admin-protected-note">
              Este usuario es administrador. No se permiten acciones desde este panel.
            </div>
          </div>
        </transition>
      </div>
    </div>
    </template>

    <transition name="fade">
      <div v-if="confirmModal.show" class="custom-modal-overlay">
        <div class="custom-modal">
          <h3>{{ confirmModal.title }}</h3>
          <p>{{ confirmModal.message }}</p>
          <input
            v-if="confirmModal.withInput"
            v-model="confirmModal.inputValue"
            :placeholder="confirmModal.inputPlaceholder"
            class="modal-input"
          />
          <div class="custom-modal-actions">
            <button class="btn-cancel" type="button" @click="confirmModal.cancel">Cancelar</button>
            <button class="btn-confirm" type="button" @click="confirmModal.confirm">Confirmar</button>
          </div>
        </div>
      </div>
    </transition>

    <div class="toast-container">
      <div v-for="toast in toasts" :key="toast.id" class="toast" :class="`toast--${toast.type}`">
        {{ toast.message }}
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from "vue";
import {
  fetchInmoUsers,
  updateUserRole,
  blockInmoUser,
  unblockInmoUser,
  forceUserRelogin,
} from "../services/admin";

export default {
  name: "AdminUsersView",
  setup() {
    const users = ref([]);
    const loading = ref(true);
    const error = ref(null);
    const expandedId = ref(null);
    const actionLoadingId = ref(null);
    const activeTab = ref("active");
    const search = ref("");
    const roleFilter = ref("");
    const toasts = ref([]);
    const confirmModal = ref({
      show: false, title: "", message: "", withInput: false,
      inputValue: "", inputPlaceholder: "",
      confirm: () => {}, cancel: () => {},
    });

    const tabLabel = computed(() => {
      if (activeTab.value === "active") return "Activos";
      if (activeTab.value === "blocked") return "Bloqueados";
      return "Total";
    });

    const filteredUsers = computed(() => {
      const q = search.value.trim().toLowerCase();
      const r = roleFilter.value;
      return users.value.filter((u) => {
        if (r && u.role !== r) return false;
        if (!q) return true;
        return (
          String(u.email || "").toLowerCase().includes(q) ||
          String(u.username || "").toLowerCase().includes(q) ||
          String(u.first_name || "").toLowerCase().includes(q) ||
          String(u.last_name || "").toLowerCase().includes(q)
        );
      });
    });

    function showToast(message, type = "success") {
      const id = Date.now() + Math.random();
      toasts.value.push({ id, message, type });
      setTimeout(() => { toasts.value = toasts.value.filter((t) => t.id !== id); }, 3200);
    }

    function showConfirm({ title, message, withInput = false, inputPlaceholder = "" }) {
      return new Promise((resolve) => {
        confirmModal.value = {
          show: true, title, message, withInput, inputValue: "", inputPlaceholder,
          confirm: () => {
            const value = confirmModal.value.inputValue;
            confirmModal.value.show = false;
            resolve(withInput ? value : true);
          },
          cancel: () => { confirmModal.value.show = false; resolve(false); },
        };
      });
    }

    async function load() {
      loading.value = true;
      error.value = null;
      try {
        const payload = await fetchInmoUsers(activeTab.value);
        users.value = payload.users || [];
      } catch (e) {
        error.value = e.message || "Error al cargar usuarios";
        users.value = [];
      } finally {
        loading.value = false;
      }
    }

    function changeTab(tab) {
      activeTab.value = tab;
      expandedId.value = null;
      load();
    }

    function toggleExpand(id) {
      expandedId.value = expandedId.value === id ? null : id;
    }

    function formatDate(iso) {
      if (!iso) return "-";
      const d = new Date(iso.replace(" ", "T"));
      if (Number.isNaN(d.getTime())) return iso;
      return d.toLocaleString("es-ES", { day: "2-digit", month: "long", year: "numeric", hour: "2-digit", minute: "2-digit" });
    }

    async function changeRole(user, newRole) {
      const confirmed = await showConfirm({
        title: "Cambiar rol",
        message: `¿Cambiar el rol de ${user.email} a "${newRole}"? Se le obligará a re-login y se enviará un email.`,
      });
      if (!confirmed) return;

      actionLoadingId.value = user.id;
      try {
        const result = await updateUserRole(user.id, newRole);
        if (!result?.success) throw new Error(result?.message || "Error al cambiar rol");
        user.role = newRole;
        showToast("Rol cambiado correctamente", "success");
      } catch (e) {
        showToast(e.message || "Error al cambiar rol", "error");
      } finally {
        actionLoadingId.value = null;
      }
    }

    async function blockUser(user) {
      const notes = await showConfirm({
        title: "Bloquear acceso a Inmobiliaria",
        message: `¿Bloquear el acceso de ${user.email}? Podrá seguir usando otros servicios del grupo, pero no Inmobiliaria. Se le enviará un email.`,
        withInput: true,
        inputPlaceholder: "Notas (opcional, motivo del bloqueo)",
      });
      if (notes === false) return;

      actionLoadingId.value = user.id;
      try {
        const result = await blockInmoUser(user.id, notes || "");
        if (!result?.success) throw new Error(result?.message || "Error al bloquear");
        user.is_blocked = true;
        if (activeTab.value === "active") {
          users.value = users.value.filter((x) => x.id !== user.id);
        }
        showToast("Usuario bloqueado correctamente", "success");
      } catch (e) {
        showToast(e.message || "Error al bloquear", "error");
      } finally {
        actionLoadingId.value = null;
      }
    }

    async function unblockUser(user) {
      const confirmed = await showConfirm({
        title: "Desbloquear usuario",
        message: `¿Restaurar el acceso de ${user.email} a Inmobiliaria? Se le enviará un email.`,
      });
      if (!confirmed) return;

      actionLoadingId.value = user.id;
      try {
        const result = await unblockInmoUser(user.id);
        if (!result?.success) throw new Error(result?.message || "Error al desbloquear");
        user.is_blocked = false;
        if (activeTab.value === "blocked") {
          users.value = users.value.filter((x) => x.id !== user.id);
        }
        showToast("Usuario desbloqueado correctamente", "success");
      } catch (e) {
        showToast(e.message || "Error al desbloquear", "error");
      } finally {
        actionLoadingId.value = null;
      }
    }

    async function forceRelogin(user) {
      const confirmed = await showConfirm({
        title: "Forzar re-login",
        message: `¿Invalidar la sesión actual de ${user.email}? Tendrá que volver a iniciar sesión la próxima vez.`,
      });
      if (!confirmed) return;

      actionLoadingId.value = user.id;
      try {
        const result = await forceUserRelogin(user.id);
        if (!result?.success) throw new Error(result?.message || "Error al forzar re-login");
        showToast("Sesión invalidada correctamente", "success");
      } catch (e) {
        showToast(e.message || "Error", "error");
      } finally {
        actionLoadingId.value = null;
      }
    }

    onMounted(load);

    return {
      users, loading, error, expandedId, actionLoadingId, activeTab, search, roleFilter,
      toasts, confirmModal, tabLabel, filteredUsers,
      changeTab, toggleExpand, formatDate, changeRole, blockUser, unblockUser, forceRelogin,
    };
  },
};
</script>

<style scoped>
.admin-users {
  padding: 40px 100px;
  width: 100%;
  margin: 90px 0 0 0;
  min-height: 100vh;
  background: linear-gradient(180deg, #eaedf1, #bdd3ec);
  color: #1e293b;
}
.admin-header {
  background: linear-gradient(135deg, #1e3a8a 0%, #1e293b 100%);
  padding: 50px 60px; border-radius: 24px;
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 30px; color: white;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
  position: relative; overflow: hidden;
}
.admin-header::before {
  content: ""; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: #c4aa1c;
}
.admin-header h1 {
  font-size: 2.8rem; font-family: 'Playfair Display', serif; margin: 0 0 12px 0; color: #fff;
}
.admin-header p {
  color: rgba(255, 255, 255, 0.7); font-size: 1.2rem; margin: 0;
  text-transform: uppercase; letter-spacing: 0.1em; font-weight: 500;
}
.stat-card {
  background: rgba(255, 255, 255, 0); padding: 20px 35px;
  border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.15);
  display: flex; flex-direction: column; align-items: center;
}
.stat-value { font-size: 2.75rem; font-weight: 800; color: #c4aa1c; }
.stat-label {
  font-size: 0.8rem; color: rgba(255, 255, 255, 0.756);
  text-transform: uppercase; letter-spacing: 0.05em; margin-top: 5px;
}

.tabs {
  display: flex; gap: 8px; margin-bottom: 24px;
  background: rgba(255, 255, 255, 0.5); backdrop-filter: blur(12px);
  padding: 6px; border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.3); width: fit-content;
}
.tab-btn {
  padding: 12px 22px; background: transparent; border: none;
  border-radius: 12px; font-weight: 700; font-size: 0.95rem;
  color: #64748b; cursor: pointer; transition: all 0.25s ease;
}
.tab-btn:hover { background: rgba(255, 255, 255, 0.6); color: #1e293b; }
.tab-btn.active {
  background: #1e293b; color: white;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.18);
}

.filter-bar {
  display: flex; align-items: center; gap: 14px; margin-bottom: 20px;
  background: rgba(255, 255, 255, 0.5); backdrop-filter: blur(12px);
  padding: 14px 18px; border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.3); flex-wrap: wrap;
}
.filter-search { flex: 1; min-width: 280px; position: relative; display: flex; align-items: center; }
.filter-search svg { position: absolute; left: 16px; color: #94a3b8; pointer-events: none; }
.filter-search input {
  width: 100%; padding: 12px 16px 12px 44px; border-radius: 12px;
  border: 1px solid #e2e8f0; background: white; color: #1e293b; font-size: 0.95rem;
}
.filter-search input:focus { outline: none; border-color: #c4aa1c; box-shadow: 0 0 0 3px rgba(196, 170, 28, 0.15); }
.filter-select {
  padding: 12px 40px 12px 16px; border-radius: 12px; border: 1px solid #e2e8f0;
  background: white; color: #1e293b; font-size: 0.9rem; font-weight: 600;
  cursor: pointer; appearance: none; min-width: 160px;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 14px center;
}
.filter-count {
  font-size: 0.85rem; color: #64748b; font-weight: 600;
  background: white; padding: 8px 14px; border-radius: 999px; border: 1px solid #e2e8f0;
}

.users-list { display: flex; flex-direction: column; gap: 15px; }

.users-header-row {
  display: grid;
  grid-template-columns: 60px 130px 1fr 80px 130px 120px 50px;
  align-items: center; padding: 0 30px 6px 30px; gap: 16px;
}
.col-label {
  font-size: 0.7rem; font-weight: 800; text-transform: uppercase;
  letter-spacing: 0.1em; color: #94a3b8; text-align: center;
}

.user-item {
  background: rgba(255, 255, 255, 0.5); backdrop-filter: blur(12px);
  border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.3);
  overflow: hidden; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}
.user-item:hover {
  border-color: rgba(255, 255, 255, 0.5); background: rgba(255, 255, 255, 0.65);
  transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
}
.user-item.is-expanded {
  border-color: #c4aa1c; background: rgba(255, 255, 255, 0.7);
  box-shadow: 0 20px 40px rgba(196, 170, 28, 0.15);
}
.user-item.is-blocked { opacity: 0.7; border-color: rgba(185, 28, 28, 0.3); }

.user-item__header {
  padding: 22px 30px; display: grid;
  grid-template-columns: 60px 130px 1fr 80px 130px 120px 50px;
  align-items: center; cursor: pointer; user-select: none; gap: 16px;
}

.user-id {
  font-family: 'JetBrains Mono', monospace; color: #c4aa1c; font-weight: 700;
  background: rgba(196, 170, 28, 0.1); padding: 6px 12px; border-radius: 10px;
  font-size: 0.85rem; justify-self: center;
}
.user-username { text-align: center; font-weight: 600; color: #1e293b; }
.user-email { text-align: center; color: #1e293b; font-weight: 500; overflow-wrap: anywhere; }

.role-badge {
  font-size: 0.7rem; font-weight: 800; text-transform: uppercase;
  letter-spacing: 0.06em; padding: 4px 10px; border-radius: 999px;
  justify-self: center;
}
.role-badge--admin { background: rgba(196, 170, 28, 0.15); color: #92400e; border: 1px solid rgba(196, 170, 28, 0.3); }
.role-badge--real { background: rgba(30, 64, 175, 0.1); color: #1e40af; border: 1px solid rgba(30, 64, 175, 0.25); }
.role-badge--user { background: rgba(100, 116, 139, 0.1); color: #475569; border: 1px solid rgba(100, 116, 139, 0.25); }

.activity-badge {
  font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
  padding: 4px 10px; border-radius: 999px; justify-self: center;
}
.activity-badge--active { background: rgba(34, 197, 94, 0.12); color: #15803d; border: 1px solid rgba(34, 197, 94, 0.3); }
.activity-badge--inactive { background: rgba(148, 163, 184, 0.12); color: #64748b; border: 1px solid rgba(148, 163, 184, 0.3); }

.status-badge {
  font-size: 0.7rem; font-weight: 800; text-transform: uppercase;
  padding: 4px 10px; border-radius: 999px; justify-self: center;
}
.status-badge--ok { background: rgba(34, 197, 94, 0.12); color: #15803d; }
.status-badge--blocked { background: rgba(239, 68, 68, 0.12); color: #b91c1c; border: 1px solid rgba(239, 68, 68, 0.3); }

.expand-btn {
  background: #f8fafc; border: 1px solid #e2e8f0; color: #94a3b8;
  width: 40px; height: 40px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.3s ease; justify-self: center;
}
.is-expanded .expand-btn { transform: rotate(180deg); background: #c4aa1c; border-color: #c4aa1c; color: white; }

.user-item__details { padding: 0 30px 30px 30px; border-top: 1px solid #f1f5f9; background: #fcfcfc; }

.details-grid {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 30px; padding: 30px 0;
}
.details-block h4 {
  font-size: 0.85rem; text-transform: uppercase; color: #c4aa1c;
  margin: 0 0 18px 0; letter-spacing: 0.1em; font-weight: 800;
  border-bottom: 2px solid rgba(196, 170, 28, 0.1); padding-bottom: 8px;
}
.details-block ul { list-style: none; padding: 0; margin: 0; }
.details-block li {
  margin-bottom: 10px; font-size: 0.95rem; color: #1e293b;
  display: grid; grid-template-columns: 160px 1fr; gap: 12px; align-items: start;
}
.details-block li strong { color: #64748b; font-weight: 500; }
.details-block li span { overflow-wrap: anywhere; }

.details-actions {
  display: flex; justify-content: center; align-items: center;
  gap: 12px; flex-wrap: wrap; padding-top: 25px;
  border-top: 1px solid rgba(0, 0, 0, 0.05); margin-top: 10px;
}
.action-btn {
  padding: 10px 20px; border-radius: 12px; font-weight: 700;
  font-size: 0.92rem; cursor: pointer; transition: all 0.25s ease;
  border: 1px solid transparent;
}
.action-btn:disabled { opacity: 0.55; cursor: not-allowed; }

.action-btn--promote { background: rgba(30, 64, 175, 0.12); color: #1e40af; border-color: rgba(30, 64, 175, 0.3); }
.action-btn--promote:hover:not(:disabled) { background: #1e40af; color: white; transform: translateY(-2px); }

.action-btn--demote { background: rgba(100, 116, 139, 0.12); color: #475569; border-color: rgba(100, 116, 139, 0.3); }
.action-btn--demote:hover:not(:disabled) { background: #475569; color: white; transform: translateY(-2px); }

.action-btn--relogin { background: rgba(245, 158, 11, 0.12); color: #b45309; border-color: rgba(245, 158, 11, 0.3); }
.action-btn--relogin:hover:not(:disabled) { background: #d97706; color: white; transform: translateY(-2px); }

.action-btn--block { background: rgba(239, 68, 68, 0.12); color: #b91c1c; border-color: rgba(239, 68, 68, 0.3); }
.action-btn--block:hover:not(:disabled) { background: #dc2626; color: white; transform: translateY(-2px); }

.action-btn--unblock { background: rgba(34, 197, 94, 0.12); color: #15803d; border-color: rgba(34, 197, 94, 0.3); }
.action-btn--unblock:hover:not(:disabled) { background: #16a34a; color: white; transform: translateY(-2px); }

.admin-protected-note {
  text-align: center; padding: 18px;
  background: rgba(196, 170, 28, 0.1); border-radius: 12px;
  color: #92400e; font-weight: 600; margin-top: 20px;
}

.expand-enter-active, .expand-leave-active { transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1); max-height: 1200px; }
.expand-enter-from, .expand-leave-to { max-height: 0; opacity: 0; transform: translateY(-20px); }

.admin-state { text-align: center; padding: 100px 0; color: #94a3b8; }
.admin-state--error { color: #b91c1c; }
.loader-spinner {
  width: 50px; height: 50px;
  border: 4px solid #f1f5f9; border-top-color: #c4aa1c;
  border-radius: 50%; margin: 0 auto 25px; animation: spin 1s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.custom-modal-overlay {
  position: fixed; inset: 0; background: rgba(15, 23, 42, 0.68);
  backdrop-filter: blur(6px); z-index: 3000;
  display: flex; align-items: center; justify-content: center; padding: 20px;
}
.custom-modal {
  width: 100%; max-width: 480px; background: #ffffff;
  border-radius: 22px; padding: 28px;
  box-shadow: 0 30px 60px rgba(0, 0, 0, 0.22); text-align: center;
}
.custom-modal h3 { margin: 0 0 10px 0; font-size: 1.35rem; color: #0f172a; }
.custom-modal p { margin: 0 0 16px; color: #475569; line-height: 1.6; }
.modal-input {
  width: 100%; padding: 12px 16px; border: 1px solid #e2e8f0;
  border-radius: 10px; font-size: 0.95rem; margin-top: 8px; box-sizing: border-box;
}
.modal-input:focus { outline: none; border-color: #c4aa1c; }
.custom-modal-actions {
  display: flex; justify-content: center; gap: 12px;
  margin-top: 22px; flex-wrap: wrap;
}
.btn-cancel, .btn-confirm {
  min-width: 130px; min-height: 44px; border-radius: 12px;
  font-weight: 700; cursor: pointer; border: none;
}
.btn-cancel { background: #e2e8f0; color: #0f172a; }
.btn-cancel:hover { background: #cbd5e1; }
.btn-confirm { background: #1e293b; color: #ffffff; }
.btn-confirm:hover { background: #0f172a; }

.toast-container {
  position: fixed; top: 22px; right: 22px; z-index: 4000;
  display: flex; flex-direction: column; gap: 10px;
  max-width: min(360px, calc(100vw - 24px));
}
.toast {
  padding: 14px 18px; border-radius: 14px; color: white; font-weight: 700;
  box-shadow: 0 18px 30px rgba(15, 23, 42, 0.18);
}
.toast--success { background: linear-gradient(135deg, #16a34a, #15803d); }
.toast--error { background: linear-gradient(135deg, #dc2626, #b91c1c); }

.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

@media (max-width: 1024px) {
  .admin-users { padding: 40px 60px; }
  .admin-header { padding: 35px 40px; }
}

@media (max-width: 768px) {
  .admin-users { padding: 20px; margin-top: 70px; }
  .admin-header { flex-direction: column; text-align: center; gap: 25px; padding: 30px 20px; }
  .admin-header h1 { font-size: 2rem; }
  .users-header-row { display: none; }
  .user-item__header {
    display: flex; flex-direction: column; align-items: flex-start;
    gap: 10px; padding: 20px; grid-template-columns: none;
  }
  .user-id, .user-username, .user-email, .role-badge,
  .activity-badge, .status-badge, .expand-btn { justify-self: auto; }
  .details-actions { flex-direction: column; }
  .action-btn { width: 100%; }
}
</style>
