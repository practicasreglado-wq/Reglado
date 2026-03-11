<template>
  <section class="admin-page">
    <div class="admin-head">
      <div>
        <p class="admin-kicker">Administracion</p>
        <h1>Usuarios registrados</h1>
      </div>
      <button class="btn-primary" type="button" @click="loadUsers" :disabled="loading">
        {{ loading ? "Cargando..." : "Actualizar" }}
      </button>
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
            <th>Telefono</th>
            <th>Rol</th>
            <th>Verificado</th>
            <th>Alta</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in users" :key="user.id">
            <td>{{ user.id }}</td>
            <td>{{ user.username || "-" }}</td>
            <td>{{ formatName(user) }}</td>
            <td>{{ user.email || "-" }}</td>
            <td>{{ user.phone || "-" }}</td>
            <td>{{ user.role || "-" }}</td>
            <td>{{ user.is_email_verified ? "Si" : "No" }}</td>
            <td>{{ formatDate(user.created_at) }}</td>
          </tr>
          <tr v-if="!loading && users.length === 0">
            <td colspan="8" class="empty-state">No hay usuarios para mostrar.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { auth } from "../services/auth";

const router = useRouter();
const users = ref([]);
const loading = ref(false);
const error = ref("");

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

onMounted(async () => {
  if (!auth.state.user && auth.state.token) {
    await auth.initialize();
  }

  if (auth.state.user?.role !== "admin") {
    router.replace("/");
    return;
  }

  await loadUsers();
});
</script>

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
