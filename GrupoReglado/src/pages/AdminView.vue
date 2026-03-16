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
        <button class="btn-primary" type="button" @click="loadUsers" :disabled="loading">
          {{ loading ? "Cargando..." : "Actualizar" }}
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
            <th>Alta</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in filteredUsers" :key="user.id">
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
            <td>{{ formatDate(user.created_at) }}</td>
          </tr>
          <tr v-if="!loading && filteredUsers.length === 0">
            <td colspan="8" class="empty-state">
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
        <li @click="selectRole('user')" :class="{ active: activeRole === 'user' }">User</li>
        <li @click="selectRole('real')" :class="{ active: activeRole === 'real' }">Real</li>
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
const error = ref("");
const openDropdownId = ref(null);
const activeRole = ref("");
const dropdownStyle = ref({});

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
  if (role === "user") return "User";
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
};

const scrollListener = () => closeDropdowns();

function selectRole(newRole) {
  const user = users.value.find(u => u.id === openDropdownId.value);
  if (user && user.role !== newRole) {
    user.role = newRole;
    updateUserRole(user.id, newRole);
  }
  closeDropdowns();
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

async function updateUserRole(userId, newRole) {
  error.value = "";
  try {
    await auth.adminUpdateRole(userId, newRole);
    // Notificamos que se cambio el rol visualmente si gustamos
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
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 14px rgba(15, 32, 57, 0.12);
  border: 1px solid #e7edf5;
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
  color: #5a6981;
  cursor: pointer;
  transition: all 0.15s ease;
}
.custom-detached-dropdown li:hover {
  background: #f1f5fb;
  color: #273d5c;
}
.custom-detached-dropdown li.active {
  background: #273d5c;
  color: #fff;
  font-weight: 600;
}
@keyframes fadeInDown {
  from { opacity: 0; transform: translateY(-5px); }
  to { opacity: 1; transform: translateY(0); }
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
  border: 1px solid #d8e0ed;
  background: #fff;
  color: #273d5c;
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
  color: #5a6981;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-size: 0.78rem;
  font-weight: 700;
}

.admin-head h1 {
  margin: 0;
  color: #273d5c;
}

.table-shell {
  overflow-x: auto;
  border: 1px solid #d8e0ed;
  border-radius: 16px;
  background: #fff;
  box-shadow: 0 12px 24px rgba(15, 32, 57, 0.06);
}

.users-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 920px;
}

.users-table th,
.users-table td {
  padding: 0.85rem 0.9rem;
  text-align: left;
  border-bottom: 1px solid #e7edf5;
  font-size: 0.92rem;
}

.users-table th {
  background: #f7f9fc;
  color: #273d5c;
  font-weight: 800;
}

.users-table tbody tr:hover {
  background: #fafcff;
}

.role-badge.admin {
  display: inline-block;
  background-color: #f1f5fb;
  color: #8fa0b5;
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
  border: 1px solid #c0d0e6;
  border-radius: 50px;
  background-color: #f7f9fc;
  color: #273d5c;
  font-weight: 600;
  font-size: 0.82rem;
  cursor: pointer;
  transition: all 0.2s ease;
  min-width: 100px;
}

.dropdown-trigger svg {
  width: 16px;
  height: 16px;
  stroke: #5a6981;
}

.dropdown-trigger:hover, .dropdown-trigger.is-active {
  border-color: #9cb1cc;
  background-color: #ffffff;
}

.empty-state {
  text-align: center !important;
  color: #6a7990;
}

@media (max-width: 760px) {
  .admin-head {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
