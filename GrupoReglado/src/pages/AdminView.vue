<template>
  <section class="admin-page">
    <div class="admin-head">
      <div>
        <p class="admin-kicker">Administración</p>
        <h1>Usuarios registrados</h1>
      </div>
      <div class="admin-actions">
        <input 
          type="text" 
          v-model="searchQuery" 
          placeholder="Buscar por ID, usuario o email..." 
          class="admin-search input"
        />
        <button class="btn-primary" type="button" @click="loadUsers" :disabled="loading || syncingNotion">
          {{ loading ? "Cargando..." : "Actualizar" }}
        </button>
        <button class="btn-primary" type="button" @click="handleSyncNotion" :disabled="loading || syncingNotion" style="background-color: #273d5c;">
          <svg v-if="!syncingNotion" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px; vertical-align: text-bottom;"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 21v-5h5"/></svg>
          {{ syncingNotion ? "Sincronizando..." : "Sincronizar Notion" }}
        </button>
      </div>
    </div>

    <p v-if="error" class="feedback error">{{ error }}</p>

    <div class="table-shell" v-if="!error">
      <table class="users-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Nombre</th>
            <th>Email</th>
            <th>Teléfono</th>
            <th>Rol</th>
            <th>Verificado</th>
            <th>Estado</th>
            <th>Alta</th>
            <th class="col-actions">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in filteredUsers" :key="user.id" :class="{ 'is-banned': !!user.banned_at }">
            <td>{{ user.id }}</td>
            <td>{{ user.username || "-" }}</td>
            <td>{{ formatName(user) }}</td>
            <td>{{ user.email || "-" }}</td>
            <td>{{ user.phone || "-" }}</td>
            <td class="role-cell">
              <div class="custom-dropdown" v-if="user.role !== 'admin'">
                <button class="dropdown-trigger" :class="{ 'is-active': openDropdownId === user.id }" @click.stop="toggleDropdown(user, $event)" type="button">
                  <span class="role-text">{{ getRoleName(user.role) }}</span>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                  </svg>
                </button>
              </div>
              <span class="role-badge admin" v-else>Administrador</span>
            </td>
            <td>{{ user.is_email_verified ? "Sí" : "No" }}</td>
            <td>
              <span class="status-pill" :class="user.banned_at ? 'is-banned-pill' : 'is-active-pill'">
                {{ user.banned_at ? 'Baneado' : 'Activo' }}
              </span>
            </td>
            <td>{{ formatDate(user.created_at) }}</td>
            <td class="col-actions">
              <button
                v-if="!isSelfUser(user)"
                class="actions-trigger"
                :class="{ 'is-active': openActionsMenuId === user.id }"
                type="button"
                @click.stop="toggleActionsMenu(user, $event)"
                aria-label="Acciones"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="5" r="1.5"></circle>
                  <circle cx="12" cy="12" r="1.5"></circle>
                  <circle cx="12" cy="19" r="1.5"></circle>
                </svg>
              </button>
            </td>
          </tr>
          <tr v-if="!loading && filteredUsers.length === 0">
            <td colspan="10" class="empty-state">
              {{ searchQuery ? "No se encontraron usuarios para tu búsqueda." : "No hay usuarios para mostrar." }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>

  <Teleport to="body">
    <div class="custom-detached-dropdown" v-if="openDropdownId" :style="dropdownStyle">
      <ul>
        <li @click="selectRole('user')" :class="{ active: activeRole === 'user' }">Usuario</li>
        <li @click="selectRole('real')" :class="{ active: activeRole === 'real' }">Real</li>
      </ul>
    </div>

    <div class="actions-menu" v-if="openActionsMenuId" :style="actionsMenuStyle">
      <ul>
        <template v-if="!(users.find(u => u.id === openActionsMenuId) || {}).banned_at">
          <li class="action-item" @click="handleForceLogout(users.find(u => u.id === openActionsMenuId))">
            Cerrar sesión
          </li>
          <li class="action-item action-danger" @click="handleToggleBan(users.find(u => u.id === openActionsMenuId))">
            Banear
          </li>
        </template>
        <template v-else>
          <li class="action-item action-success" @click="handleToggleBan(users.find(u => u.id === openActionsMenuId))">
            Desbanear
          </li>
        </template>
      </ul>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, nextTick } from "vue";
import { useRouter } from "vue-router";
import { auth } from "../services/auth";

const router = useRouter();
const users = ref([]);
const searchQuery = ref("");
const loading = ref(false);
const syncingNotion = ref(false);
const error = ref("");
const openDropdownId = ref(null);
const activeRole = ref("");
const dropdownStyle = ref({});
const openActionsMenuId = ref(null);
const actionsMenuStyle = ref({});

const filteredUsers = computed(() => {
  const query = searchQuery.value.toLowerCase().trim();
  if (!query) return users.value;
  return users.value.filter(user => {
    const idMatch = String(user.id).includes(query);
    const usernameMatch = user.username && user.username.toLowerCase().includes(query);
    const emailMatch = user.email && user.email.toLowerCase().includes(query);
    return idMatch || usernameMatch || emailMatch;
  });
});

function getRoleName(role) {
  if (role === "admin") return "Administrador";
  if (role === "user") return "Usuario";
  if (role === "real") return "Real";
  return role || "-";
}

async function toggleDropdown(user, event) {
  if (openDropdownId.value === user.id) {
    closeDropdowns();
    return;
  }
  openDropdownId.value = user.id;
  activeRole.value = user.role;
  
  await nextTick();
  const rect = event.currentTarget.getBoundingClientRect();
  dropdownStyle.value = {
    position: 'absolute',
    top: `${rect.bottom + window.scrollY + 5}px`,
    left: `${rect.left + window.scrollX}px`,
    minWidth: `${rect.width}px`,
    zIndex: 9999
  };
}

function closeDropdowns() {
  openDropdownId.value = null;
}

const clickOutsideListener = (e) => {
  if (!e.target.closest('.dropdown-trigger') && !e.target.closest('.custom-detached-dropdown')) {
    closeDropdowns();
  }
  if (!e.target.closest('.actions-trigger') && !e.target.closest('.actions-menu')) {
    closeActionsMenu();
  }
};

const scrollListener = () => {
  closeDropdowns();
  closeActionsMenu();
};

function selectRole(newRole) {
  const user = users.value.find(u => u.id === openDropdownId.value);
  if (user && user.role !== newRole) {
    user.role = newRole;
    updateUserRole(user.id, newRole);
  }
  closeDropdowns();
}

function isSelfUser(user) {
  return auth.state.user && auth.state.user.id === user.id;
}

async function toggleActionsMenu(user, event) {
  if (openActionsMenuId.value === user.id) {
    closeActionsMenu();
    return;
  }
  closeDropdowns();
  openActionsMenuId.value = user.id;

  await nextTick();
  const rect = event.currentTarget.getBoundingClientRect();
  actionsMenuStyle.value = {
    position: 'absolute',
    top: `${rect.bottom + window.scrollY + 5}px`,
    left: `${rect.right + window.scrollX - 160}px`,
    minWidth: '160px',
    zIndex: 9999,
  };
}

function closeActionsMenu() {
  openActionsMenuId.value = null;
}

async function handleForceLogout(user) {
  closeActionsMenu();
  if (!confirm(`¿Cerrar la sesión activa de ${user.username || user.email}?`)) return;

  const currentPassword = prompt("Confirma tu contraseña para cerrar la sesión del usuario:");
  if (!currentPassword) return;

  error.value = "";
  try {
    const res = await auth.adminForceLogout(user.id, currentPassword);
    alert(res.message || "Sesiones del usuario cerradas.");
    await loadUsers();
  } catch (err) {
    error.value = err instanceof Error ? err.message : "No se pudo cerrar la sesión.";
  }
}

async function handleToggleBan(user) {
  closeActionsMenu();
  const banning = !user.banned_at;
  const verb = banning ? "Banear" : "Desbanear";
  if (!confirm(`¿${verb} a ${user.username || user.email}?`)) return;

  const currentPassword = prompt(`Confirma tu contraseña para ${verb.toLowerCase()}:`);
  if (!currentPassword) return;

  error.value = "";
  try {
    const res = await auth.adminSetBan(user.id, banning, currentPassword);
    alert(res.message || (banning ? "Usuario baneado." : "Usuario desbaneado."));
    await loadUsers();
  } catch (err) {
    error.value = err instanceof Error ? err.message : "No se pudo actualizar el baneo.";
  }
}

function formatName(user) {
  const fullName = `${user.first_name || ""} ${user.last_name || ""}`.trim();
  return fullName || user.name || "-";
}

function formatDate(value) {
  if (!value) {
    return "-";
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return date.toLocaleString("es-ES");
}

async function loadUsers() {
  loading.value = true;
  error.value = "";

  try {
    const payload = await auth.adminUsers();
    users.value = Array.isArray(payload.users) ? payload.users : [];
  } catch (err) {
    const message = err instanceof Error ? err.message : "No fue posible cargar los usuarios.";
    error.value = message;

    if (message === "forbidden") {
      router.replace("/");
    }
  } finally {
    loading.value = false;
  }
}

async function handleSyncNotion() {
  if (confirm("¿Estás seguro de que quieres archivar todos los registros en Notion y resincronizar la base de datos completa? Esta acción tardará un rato según la cantidad de usuarios.")) {
    syncingNotion.value = true;
    error.value = "";
    try {
      const response = await auth.adminSyncNotion();
      alert(`Sincronización completada.\n\nUsuarios procesados correctamente: ${response.synced_count} de ${response.total_users}`);
    } catch (err) {
      const message = err instanceof Error ? err.message : "Error desconocido durante la sincronización.";
      error.value = message;
    } finally {
      syncingNotion.value = false;
    }
  }
}

async function updateUserRole(userId, newRole) {
  error.value = "";

  // El backend exige reautenticación con la contraseña del admin para
  // mitigar escalada de privilegios desde un JWT robado.
  const currentPassword = prompt("Confirma tu contraseña para cambiar el rol:");
  if (!currentPassword) {
    await loadUsers();
    return;
  }

  try {
    await auth.adminUpdateRole(userId, newRole, currentPassword);
  } catch (err) {
    const message = err instanceof Error ? err.message : "No fue posible actualizar el rol.";
    error.value = message;

    // Regresamos el estado si fallo recargando la lista
    await loadUsers();
  }
}

onMounted(async () => {
  document.addEventListener('click', clickOutsideListener);
  window.addEventListener('scroll', scrollListener, true);

  if (!auth.state.user && auth.state.token) {
    await auth.initialize();
  }

  if (auth.state.user?.role !== "admin") {
    router.replace("/");
    return;
  }

  await loadUsers();
});

onUnmounted(() => {
  document.removeEventListener('click', clickOutsideListener);
  window.removeEventListener('scroll', scrollListener, true);
});
</script>

<style>
.custom-detached-dropdown {
  background: var(--surface);
  border-radius: 12px;
  box-shadow: var(--shadow-strong);
  border: 1px solid var(--line);
  padding: 0.4rem;
  margin: 0;
  animation: fadeInDown 0.15s ease-out;
}
.custom-detached-dropdown ul {
  list-style: none;
  margin: 0;
  padding: 0;
}
.custom-detached-dropdown li {
  padding: 0.5rem 0.8rem;
  font-size: 0.85rem;
  font-weight: 500;
  border-radius: 8px;
  color: var(--muted);
  cursor: pointer;
  transition: all 0.15s ease;
}
.custom-detached-dropdown li:hover {
  background: var(--surface-soft);
  color: var(--primary);
}
.custom-detached-dropdown li.active {
  background: var(--primary);
  color: #fff;
  font-weight: 600;
}
@keyframes fadeInDown {
  from { opacity: 0; transform: translateY(-5px); }
  to { opacity: 1; transform: translateY(0); }
}
.actions-menu {
  background: var(--surface);
  border-radius: 12px;
  box-shadow: var(--shadow-strong);
  border: 1px solid var(--line);
  padding: 0.4rem;
  animation: fadeInDown 0.15s ease-out;
}
.actions-menu ul {
  list-style: none;
  margin: 0;
  padding: 0;
}
.actions-menu .action-item {
  padding: 0.55rem 0.9rem;
  font-size: 0.86rem;
  font-weight: 500;
  border-radius: 8px;
  cursor: pointer;
  color: var(--text);
  transition: all 0.15s ease;
}
.actions-menu .action-item:hover {
  background: var(--surface-soft);
}
.actions-menu .action-item.action-danger {
  color: #c0392b;
}
.actions-menu .action-item.action-danger:hover {
  background: rgba(192, 57, 43, 0.08);
}
.actions-menu .action-item.action-success {
  color: #1f7a3a;
}
.actions-menu .action-item.action-success:hover {
  background: rgba(31, 122, 58, 0.08);
}
</style>

<style scoped>
.admin-page {
  display: grid;
  gap: 1rem;
}

.admin-head {
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.admin-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.admin-search {
  padding: 0.6rem 1rem;
  border-radius: 8px;
  border: 1px solid var(--line);
  background: var(--surface-soft);
  color: var(--text);
  font-family: inherit;
  font-size: 0.9rem;
  width: 250px;
  outline: none;
  transition: all 0.2s ease;
}

.admin-search:focus {
  border-color: #7b96b9;
  box-shadow: 0 0 0 3px rgba(123, 150, 185, 0.15);
}

.admin-search::placeholder {
  color: #8fa0b5;
}

.admin-kicker {
  margin: 0 0 0.35rem;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-size: 0.78rem;
  font-weight: 700;
}

.admin-head h1 {
  margin: 0;
  color: var(--text);
}

.table-shell {
  overflow-x: auto;
  border: 1px solid var(--line);
  border-radius: 16px;
  background: var(--surface);
  box-shadow: var(--shadow-soft);
}

.users-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 1080px;
}

.users-table th,
.users-table td {
  padding: 0.85rem 0.9rem;
  text-align: left;
  border-bottom: 1px solid var(--line);
  font-size: 0.92rem;
  color: var(--text);
}

.users-table th {
  background: var(--surface-soft);
  color: var(--text);
  font-weight: 800;
}

.users-table tbody tr:hover {
  background: var(--surface-soft);
}

.role-badge.admin {
  display: inline-block;
  background-color: var(--surface-soft);
  color: var(--muted);
  padding: 0.4rem 1rem;
  border-radius: 50px;
  font-weight: 600;
  font-size: 0.82rem;
  user-select: none;
}

.custom-dropdown {
  position: relative;
  width: max-content;
}

.dropdown-trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.6rem;
  padding: 0.4rem 0.8rem 0.4rem 1rem;
  border: 1px solid var(--line-strong);
  border-radius: 50px;
  background-color: var(--surface-soft);
  color: var(--text);
  font-weight: 600;
  font-size: 0.82rem;
  cursor: pointer;
  transition: all 0.2s ease;
  min-width: 100px;
}

.dropdown-trigger svg {
  width: 16px;
  height: 16px;
  stroke: var(--text);
}

.dropdown-trigger:hover, .dropdown-trigger.is-active {
  border-color: var(--primary);
  background-color: var(--surface);
}

.empty-state {
  text-align: center !important;
  color: #6a7990;
}

.col-actions {
  width: 64px;
  text-align: center;
}

.actions-trigger {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 50%;
  border: 1px solid transparent;
  background: transparent;
  color: var(--text);
  cursor: pointer;
  transition: all 0.2s ease;
}
.actions-trigger svg {
  width: 18px;
  height: 18px;
  stroke: var(--text);
}
.actions-trigger:hover,
.actions-trigger.is-active {
  background: var(--surface-soft);
  border-color: var(--line-strong);
}

.status-pill {
  display: inline-block;
  padding: 0.3rem 0.75rem;
  border-radius: 50px;
  font-weight: 600;
  font-size: 0.78rem;
  user-select: none;
}
.status-pill.is-active-pill {
  background-color: var(--surface-soft);
  color: var(--muted);
}
.status-pill.is-banned-pill {
  background-color: rgba(192, 57, 43, 0.12);
  color: #c0392b;
}

.users-table tr.is-banned td {
  opacity: 0.55;
}
.users-table tr.is-banned:hover td {
  opacity: 0.75;
}
.users-table tr.is-banned td.col-actions,
.users-table tr.is-banned:hover td.col-actions {
  opacity: 1;
}

@media (max-width: 760px) {
  .admin-head {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
