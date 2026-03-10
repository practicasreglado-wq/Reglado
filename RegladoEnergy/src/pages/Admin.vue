<template>
  <section class="section">
    <div class="container">
      <div class="card soft glow">
        <div class="header-row">
          <div>
            <div class="badge">Panel de administración</div>
            <h1 class="h1">Hola, administrador</h1>
            <p class="p">
              Solicitudes recibidas, ordenadas por fecha. Puedes seleccionar varias y descargar un ZIP con CSV y PDF.
            </p>
          </div>

          <div class="actions">
            <button class="btn ghost" @click="loadRows" :disabled="loading">
              {{ loading ? "Cargando..." : "Actualizar" }}
            </button>
            <button class="btn primary" @click="downloadSelected" :disabled="downloading || selectedIds.length === 0">
              {{ downloading ? "Preparando ZIP..." : `Descargar seleccionados (${selectedIds.length})` }}
            </button>
          </div>
        </div>

        <div v-if="errorMsg" class="error">
          <strong>Error:</strong> {{ errorMsg }}
        </div>

        <div v-if="successMsg" class="sent">
          <strong>{{ successMsg }}</strong>
        </div>

        <div class="table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th class="col-check">
                  <input
                    type="checkbox"
                    :checked="isAllSelected"
                    :disabled="rows.length === 0"
                    @change="toggleSelectAll($event.target.checked)"
                  />
                </th>
                <th>Fecha</th>
                <th>Nombre</th>
                <th>Teléfono</th>
                <th>Email</th>
                <th>Mensaje</th>
                <th>Factura</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!loading && rows.length === 0">
                <td colspan="7" class="empty">No hay solicitudes todavía.</td>
              </tr>
              <tr v-for="row in rows" :key="row.id">
                <td class="col-check">
                  <input type="checkbox" :value="row.id" v-model="selectedIds" />
                </td>
                <td>{{ formatDate(row.creado_en) }}</td>
                <td>{{ row.nombre }}</td>
                <td>{{ row.telefono }}</td>
                <td class="email">{{ row.email }}</td>
                <td class="mensaje">{{ row.mensaje || "-" }}</td>
                <td>
                  <span :class="row.has_pdf ? 'tag yes' : 'tag no'">
                    {{ row.has_pdf ? "Sí" : "No" }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { setSeo } from "../seo.js";
import { auth } from "../services/auth";

const BACKEND_BASE =
  import.meta.env.VITE_BACKEND_BASE ||
  "http://localhost/Reglado/RegladoEnergy/BACKEND";

const rows = ref([]);
const selectedIds = ref([]);
const loading = ref(false);
const downloading = ref(false);
const errorMsg = ref("");
const successMsg = ref("");
const isAdmin = computed(() => auth.state.user?.role === "admin");

const isAllSelected = computed(() => rows.value.length > 0 && rows.value.every((row) => selectedIds.value.includes(row.id)));

function formatDate(value) {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat("es-ES", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
  }).format(date);
}

async function loadRows() {
  loading.value = true;
  errorMsg.value = "";
  successMsg.value = "";

  try {
    if (!auth.state.token || !isAdmin.value) {
      throw new Error("Acceso restringido a administradores.");
    }

    const response = await fetch(`${BACKEND_BASE}/admin_list.php`, {
      headers: {
        Authorization: `Bearer ${auth.state.token}`,
      },
    });
    const payload = await response.json().catch(() => ({}));

    if (!response.ok || !payload.ok) {
      throw new Error(payload.message || "No se pudo cargar el panel.");
    }

    rows.value = Array.isArray(payload.items) ? payload.items : [];
    selectedIds.value = selectedIds.value.filter((id) => rows.value.some((row) => row.id === id));
  } catch (err) {
    errorMsg.value = err?.message || "No se pudo cargar el panel.";
  } finally {
    loading.value = false;
  }
}

function toggleSelectAll(checked) {
  if (checked) {
    selectedIds.value = rows.value.map((row) => row.id);
  } else {
    selectedIds.value = [];
  }
}

async function downloadSelected() {
  if (selectedIds.value.length === 0 || downloading.value) return;

  downloading.value = true;
  errorMsg.value = "";
  successMsg.value = "";

  try {
    if (!auth.state.token || !isAdmin.value) {
      throw new Error("Acceso restringido a administradores.");
    }

    const response = await fetch(`${BACKEND_BASE}/admin_download.php`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${auth.state.token}`,
      },
      body: JSON.stringify({ ids: selectedIds.value }),
    });

    const contentType = response.headers.get("content-type") || "";
    if (!response.ok || contentType.includes("application/json")) {
      const payload = await response.json().catch(() => ({}));
      throw new Error(payload.message || "No se pudo generar la descarga.");
    }

    const blob = await response.blob();
    const disposition = response.headers.get("content-disposition") || "";
    const match = disposition.match(/filename=\"?([^\";]+)\"?/i);
    const fileName = match?.[1] || `solicitudes_${Date.now()}.zip`;

    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = fileName;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);

    successMsg.value = "Descarga completada.";
  } catch (err) {
    errorMsg.value = err?.message || "No se pudo descargar el ZIP.";
  } finally {
    downloading.value = false;
  }
}

onMounted(() => {
  setSeo({
    title: "Panel de administración | Reglado Energy",
    description: "Panel interno de solicitudes de contacto y facturas.",
    canonical: "/#/admin",
  });

  auth.initialize().then(() => {
    if (isAdmin.value) {
      loadRows();
      return;
    }

    errorMsg.value = "Acceso restringido a administradores.";
  });
});
</script>

<style scoped>
.header-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  flex-wrap: wrap;
}

.badge {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 8px 12px;
  border: 1px solid rgba(242, 197, 61, 0.26);
  background: rgba(242, 197, 61, 0.08);
  margin-bottom: 10px;
  font-weight: 700;
}

.actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.table-wrap {
  margin-top: 16px;
  overflow-x: auto;
}

.admin-table {
  width: 100%;
  min-width: 900px;
  border-collapse: collapse;
}

.admin-table th,
.admin-table td {
  text-align: left;
  padding: 10px 8px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  vertical-align: top;
}

.admin-table th {
  font-size: 13px;
  color: rgba(233, 238, 246, 0.75);
}

.col-check {
  width: 40px;
  text-align: center !important;
}

.email {
  white-space: nowrap;
}

.mensaje {
  max-width: 360px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.tag {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
}

.tag.yes {
  background: rgba(91, 192, 110, 0.18);
  border: 1px solid rgba(91, 192, 110, 0.44);
}

.tag.no {
  background: rgba(230, 90, 90, 0.12);
  border: 1px solid rgba(230, 90, 90, 0.4);
}

.empty {
  text-align: center;
  color: rgba(233, 238, 246, 0.7);
  padding: 20px !important;
}

.sent {
  margin-top: 14px;
  padding: 14px;
  border-radius: var(--radius-md);
  border: 1px solid rgba(91, 192, 110, 0.32);
  background: rgba(91, 192, 110, 0.12);
}

.error {
  margin-top: 14px;
  padding: 14px;
  border-radius: var(--radius-md);
  border: 1px solid rgba(230, 90, 90, 0.45);
  background: rgba(230, 90, 90, 0.12);
  color: #ffd3d3;
}
</style>

