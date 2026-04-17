<template>
  <div class="admin-properties">
    <header class="admin-header">
      <div class="admin-header__content">
        <h1>Administración de Propiedades</h1>
        <p>Gestiona y supervisa todos los inmuebles del sistema.</p>
      </div>
      <div class="admin-stats">
        <div class="stat-card">
          <span class="stat-value">{{ properties.length }}</span>
          <span class="stat-label">Total Propiedades</span>
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
          v-model="searchQuery" 
          type="text" 
          placeholder="Buscar por título, ciudad o ID..."
        >
      </div>
      
      <div class="filter-group">
        <select v-model="filterCategory">
          <option value="">Todas las categorías</option>
          <option v-for="cat in categories" :key="cat" :value="cat">
            {{ cat.charAt(0).toUpperCase() + cat.slice(1) }}
          </option>
        </select>
      </div>
    </div>

    <div v-if="loading" class="admin-state">
      <div class="loader-spinner"></div>
      <p>Cargando propiedades...</p>
    </div>

    <div v-else-if="filteredProperties.length === 0" class="admin-state">
      <p>No se encontraron propiedades que coincidan con la búsqueda.</p>
    </div>

    <div v-else class="properties-list">
      <div 
        v-for="prop in filteredProperties" 
        :key="prop.id" 
        class="prop-item"
        :class="{ 'is-expanded': expandedId === prop.id }"
      >
        <div class="prop-item__header" @click="toggleExpand(prop.id)">
          <div class="prop-info-main">
            <span class="prop-id">#{{ prop.id }}</span>
            <span class="prop-category-badge">{{ prop.categoria }}</span>
            <h3 class="prop-title">{{ prop.titulo }}</h3>
          </div>
          <div class="prop-meta-summary">
            <span class="prop-price">{{ formatPrice(prop.precio) }}</span>
            <span class="prop-location">{{ prop.ubicacion_general }}</span>
            <button class="expand-btn">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M6 9l6 6 6-6" />
              </svg>
            </button>
          </div>
        </div>

        <transition name="expand">
          <div v-if="expandedId === prop.id" class="prop-item__details">
            <div class="details-grid">
              <!-- Información General -->
              <div class="details-block">
                <h4><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M13 2v7h7"/></svg> Información General</h4>
                <ul>
                  <li><strong>ID:</strong> {{ prop.id }}</li>
                  <li><strong>Título:</strong> {{ prop.titulo }}</li>
                  <li><strong>Tipo:</strong> {{ prop.categoria }}</li>
                  <li><strong>Precio:</strong> {{ formatPrice(prop.precio) }}</li>
                </ul>
              </div>

              <!-- Ubicación -->
              <div class="details-block">
                <h4><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> Ubicación</h4>
                <ul>
                  <li><strong>Ciudad/Zona:</strong> {{ prop.ubicacion_general }}</li>
                  <li><strong>País:</strong> España</li>
                </ul>
              </div>

              <!-- Características -->
              <div class="details-block">
                <h4><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg> Características</h4>
                <ul>
                  <li><strong>M²:</strong> {{ prop.metros_cuadrados }} m²</li>
                  <li v-if="prop.caracteristicas?.q2"><strong>Capacidad/Tamaño:</strong> {{ prop.caracteristicas.q2 }}</li>
                  <li v-if="prop.caracteristicas?.q1"><strong>Subtipo:</strong> {{ prop.caracteristicas.q1 }}</li>
                  <li v-if="prop.caracteristicas?.q4"><strong>Estado conservación:</strong> {{ prop.caracteristicas.q4 }}</li>
                </ul>
              </div>

              <!-- Propietario -->
              <div class="details-block owner-info">
                <h4>
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                  </svg>
                  Propietario
                </h4>
                <ul>
                  <li><strong>ID Usuario:</strong> <span>{{ prop.owner?.id ?? 'N/A' }}</span></li>
                  <li><strong>Nombre:</strong> <span>{{ prop.owner?.nombre ?? '-' }}</span></li>
                  <li><strong>Email:</strong> <span>{{ prop.owner?.email ?? '-' }}</span></li>
                  <li><strong>Username:</strong> <span>{{ prop.owner?.username ?? '-' }}</span></li>
                  <li><strong>Teléfono:</strong> <span>{{ prop.owner?.phone ?? '-' }}</span></li>
                </ul>
              </div>

              <!-- Sistema -->
              <div class="details-block">
                <h4><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Sistema</h4>
                <ul>
                  <li><strong>Creado:</strong> {{ formatDate(prop.created_at) }}</li>
                  <li><strong>Actualizado:</strong> {{ formatDate(prop.updated_at) }}</li>
                </ul>
              </div>
            </div>
            <div class="details-actions">
              <button class="action-btn--ficha" @click.stop="openModal(prop)">
                Ver Ficha Detallada
              </button>
            </div>

          </div>
        </transition>
      </div>
    </div>

    <!-- Modal de Ficha Detallada -->
    <transition name="fade">
      <div v-if="showModal" class="modal-overlay" @click="closeModal">
        <div class="modal-content" @click.stop>
          <button class="close-modal-btn" @click="closeModal">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M18 6L6 18M6 6l12 12" />
            </svg>
          </button>

          <header class="modal-header">
            <div class="modal-header-info">
              <span class="modal-badge-id">ID #{{ selectedProp.id }}</span>
              <h2>{{ selectedProp.titulo }}</h2>
              <p>{{ selectedProp.categoria }} en {{ selectedProp.ubicacion_general }}</p>
            </div>
            <div class="modal-header-price">
              {{ formatPrice(selectedProp.precio) }}
            </div>
          </header>

          <div class="modal-body">
  <div class="modal-section">
    <h3>
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/>
        <path d="M13 2v7h7"/>
      </svg>
      Resumen del inmueble
    </h3>
    <div class="modal-grid">
      <div class="modal-field"><label>ID:</label> <span>{{ selectedProp.id }}</span></div>
      <div class="modal-field"><label>Título:</label> <span>{{ selectedProp.titulo }}</span></div>
      <div class="modal-field"><label>Tipo propiedad:</label> <span>{{ selectedProp.tipo_propiedad || '-' }}</span></div>
      <div class="modal-field"><label>Categoría:</label> <span>{{ selectedProp.categoria || '-' }}</span></div>
      <div class="modal-field"><label>Precio:</label> <span>{{ formatPrice(selectedProp.precio || 0) }}</span></div>
      <div class="modal-field"><label>Metros cuadrados:</label> <span>{{ selectedProp.metros_cuadrados || '-' }}</span></div>
      <div class="modal-field"><label>Ciudad:</label> <span>{{ selectedProp.ciudad || '-' }}</span></div>
      <div class="modal-field"><label>Zona:</label> <span>{{ selectedProp.zona || '-' }}</span></div>
      <div class="modal-field"><label>Dirección:</label> <span>{{ selectedProp.direccion || '-' }}</span></div>
      <div class="modal-field"><label>Ubicación general:</label> <span>{{ selectedProp.ubicacion_general || '-' }}</span></div>
    </div>
  </div>

  <div class="modal-section">
    <h3>
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
        <circle cx="12" cy="7" r="4"/>
      </svg>
      Usuario propietario
    </h3>
    <div class="modal-grid">
      <div v-for="field in getUserFields(selectedProp.owner)" :key="'owner-' + field.key" class="modal-field">
        <label>{{ field.label }}:</label>
        <span>{{ field.value }}</span>
      </div>
    </div>
  </div>

  <div class="modal-section">
    <h3>
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
        <circle cx="12" cy="7" r="4"/>
      </svg>
      Usuario creador del registro
    </h3>
    <div class="modal-grid">
      <div v-for="field in getUserFields(selectedProp.creator)" :key="'creator-' + field.key" class="modal-field">
        <label>{{ field.label }}:</label>
        <span>{{ field.value }}</span>
      </div>
    </div>
  </div>

  <div v-if="selectedProp.caracteristicas && Object.keys(selectedProp.caracteristicas).length > 0" class="modal-section full-width">
    <h3>
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
      </svg>
      Características específicas
    </h3>
    <div class="modal-grid modal-grid--detailed">
      <div v-for="(val, key) in selectedProp.caracteristicas" :key="key" class="modal-field">
        <label>{{ getCharacteristicLabel(key) }}:</label>
        <span>{{ formatValue(val) }}</span>
      </div>
    </div>
  </div>

  <div class="modal-section full-width">
    <h3>
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/>
        <polyline points="12 6 12 12 16 14"/>
      </svg>
      Auditoría de sistema
    </h3>
    <div class="modal-grid">
      <div class="modal-field"><label>Fecha creación:</label> <span>{{ formatDate(selectedProp.created_at) }}</span></div>
      <div class="modal-field"><label>Última actualización:</label> <span>{{ formatDate(selectedProp.updated_at) }}</span></div>
      <div class="modal-field"><label>Owner User ID:</label> <span>{{ selectedProp.owner_user_id ?? '-' }}</span></div>
      <div class="modal-field"><label>Created By User ID:</label> <span>{{ selectedProp.created_by_user_id ?? '-' }}</span></div>
      <div class="modal-field"><label>Owner Email Pending:</label> <span>{{ selectedProp.owner_email_pending ?? '-' }}</span></div>
      <div class="modal-field"><label>Captador ID:</label> <span>{{ selectedProp.captador_id ?? '-' }}</span></div>
    </div>
  </div>

  <div class="modal-section full-width">
    <h3>
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M3 3h18v18H3z"/>
        <path d="M8 8h8M8 12h8M8 16h5"/>
      </svg>
      Todos los campos de la propiedad
    </h3>
    <div class="modal-grid modal-grid--detailed">
      <div
        v-for="field in getDynamicPropertyFields(selectedProp)"
        :key="'prop-' + field.key"
        class="modal-field"
      >
        <label>{{ field.label }}:</label>
        <span class="modal-field__value">{{ field.value }}</span>
      </div>
    </div>
  </div>

  <div v-if="selectedProp.descripcion" class="modal-section full-width">
    <h3>Descripción</h3>
    <p class="modal-description">{{ selectedProp.descripcion }}</p>
  </div>
</div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import { fetchAllProperties } from '../services/admin';
import { preferenceSchemas } from '../data/preferenceSchemas';

export default {
  name: 'AdminPropertiesView',
  setup() {
    const properties = ref([]);
    const loading = ref(true);
    const expandedId = ref(null);
    const searchQuery = ref('');
    const filterCategory = ref('');

    const ignoredDynamicKeys = new Set([
      'owner',
      'creator',
      'caracteristicas',
      'titulo',
      'ubicacion_general',
      'owner_name',
      'owner_email',
      'owner_id',
      'creator_name',
      'creator_email',
      'creator_id'
    ]);

    const formatValue = (value) => {
      if (value === null || value === undefined || value === '') return '-';

      if (typeof value === 'boolean') return value ? 'Sí' : 'No';

      if (typeof value === 'number') return String(value);

      if (Array.isArray(value)) {
        return value.length ? value.join(', ') : '-';
      }

      if (typeof value === 'object') {
        try {
          return JSON.stringify(value, null, 2);
        } catch {
          return '[Objeto]';
        }
      }

      return String(value);
    };

    const formatLabel = (key) => {
      return String(key)
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
    };

    const getDynamicPropertyFields = (prop) => {
      if (!prop || typeof prop !== 'object') return [];

      return Object.entries(prop)
        .filter(([key, value]) => {
          if (ignoredDynamicKeys.has(key)) return false;
          if (typeof value === 'function') return false;
          return true;
        })
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([key, value]) => ({
          key,
          label: formatLabel(key),
          value: formatValue(value)
        }));
    };

    const getUserFields = (userObj) => {
      if (!userObj || typeof userObj !== 'object') return [];

      return Object.entries(userObj)
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([key, value]) => ({
          key,
          label: formatLabel(key),
          value: formatValue(value)
        }));
    };
    
    // Modal state
    const showModal = ref(false);
    const selectedProp = ref(null);

    const categories = computed(() => {
      const values = properties.value
        .map((p) => String(p.categoria || '').trim())
        .filter(Boolean);

      return [...new Set(values)].sort((a, b) => a.localeCompare(b, 'es'));
    });

    const loadProperties = async () => {
      loading.value = true;
      try {
        properties.value = await fetchAllProperties();
      } catch (error) {
        console.error("Error cargando propiedades:", error);
      } finally {
        loading.value = false;
      }
    };

    const toggleExpand = (id) => {
      expandedId.value = expandedId.value === id ? null : id;
    };

    const openModal = (prop) => {
      selectedProp.value = prop;
      showModal.value = true;
      document.body.style.overflow = 'hidden'; // Prevent scroll
    };

    const closeModal = () => {
      showModal.value = false;
      selectedProp.value = null;
      document.body.style.overflow = ''; // Restore scroll
    };

    const formatPrice = (price) => {
      return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(price);
    };

    const formatDate = (dateStr) => {
      if (!dateStr) return '-';
      return new Date(dateStr).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    };

    const getCharacteristicLabel = (key) => {
      if (!selectedProp.value || !selectedProp.value.categoria) return key;
      
      const schema = preferenceSchemas[selectedProp.value.categoria];
      if (schema && schema.questions) {
        const question = schema.questions.find(q => q.key === key);
        if (question && question.summaryLabel) {
          return question.summaryLabel;
        }
      }

      const labels = {
        q1: 'Subtipo',
        q2: 'Capacidad/Plazas',
        q3: 'Baños/Habitaciones',
        q4: 'Estado de Conservación',
        q5: 'Equipamiento',
        q6: 'Extras/Instalaciones',
        q7: 'Servicios',
        q8: 'Certificaciones',
        q9: 'Detalles Económicos',
        q10: 'Referencia/Uso'
      };
      return labels[key] || key.charAt(0).toUpperCase() + key.slice(1);
    };

    const filteredProperties = computed(() => {
  return properties.value.filter((p) => {
    const titulo = String(p.titulo || '').toLowerCase();
    const ubicacion = String(p.ubicacion_general || '').toLowerCase();
    const categoria = String(p.categoria || '').trim().toLowerCase();
    const search = searchQuery.value.toLowerCase().trim();
    const selectedCategory = String(filterCategory.value || '').trim().toLowerCase();

    const matchesSearch =
      titulo.includes(search) ||
      ubicacion.includes(search) ||
      String(p.id || '').includes(search);

    const matchesCategory =
      !selectedCategory || categoria === selectedCategory;

    return matchesSearch && matchesCategory;
  });
});

    onMounted(loadProperties);

    return {
      properties,
      loading,
      expandedId,
      searchQuery,
      filterCategory,
      categories,
      filteredProperties,
      toggleExpand,
      formatPrice,
      formatDate,
      showModal,
      selectedProp,
      openModal,
      closeModal,
      getCharacteristicLabel,
      formatValue,
      formatLabel,
      getDynamicPropertyFields,
      getUserFields
    };
  }
};
</script>

<style scoped>
.admin-properties {
  padding: 40px 100px;
  width: 100%;
  max-width: 100%;
  margin: 90px 0 0 0;
  min-height: 100vh;
  background: linear-gradient(180deg, #eaedf1, #bdd3ec);
  color: #1e293b;
}

.admin-header {
  background: #1e3a8a; 
  background: linear-gradient(135deg, #1e3a8a 0%, #1e293b 100%);
  padding: 50px 60px;
  border-radius: 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 50px;
  color: white;
  box-shadow: 0 10px 40px rgba(0,0,0,0.1);
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
  background: #c4aa1c; /* Gold accent */
}

.admin-header h1 {
  font-size: 2.8rem;
  font-family: 'Playfair Display', serif;
  margin: 0 0 12px 0;
  color: #fff;
}

.admin-header p {
  color: rgba(255,255,255,0.7);
  font-size: 1.2rem;
  margin: 0;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  font-weight: 500;
}

.stat-card {
  background: rgba(255, 255, 255, 0); /* Transparent as requested */
  backdrop-filter: blur(10px);
  padding: 20px 35px;
  border-radius: 20px;
  border: 1px solid rgba(255,255,255,0.15);
  display: flex;
  flex-direction: column;
  align-items: center;
}

.stat-value {
  font-size: 2.75rem;
  font-weight: 800;
  color: #c4aa1c; /* Gold accent */
}

.stat-label {
  font-size: 0.8rem;
  color: rgba(255, 255, 255, 0.756);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-top: 5px;
}

/* Controls */
.admin-controls {
  display: flex;
  gap: 25px;
  margin-bottom: 40px;
  align-items: stretch;
}

.search-box {
  flex: 1;
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
  font-size: 1.1rem;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 2px 10px rgba(0,0,0,0.02);
}

.search-box input:focus {
  outline: none;
  border-color: #c4aa1c;
  box-shadow: 0 10px 25px rgba(196, 170, 28, 0.1);
}

.filter-group select {
  padding: 0 45px 0 25px;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  background: white;
  font-size: 1.05rem;
  font-weight: 600;
  color: #1e293b;
  cursor: pointer;
  box-shadow: 0 2px 10px rgba(0,0,0,0.02);
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 20px center;
  min-width: 220px;
}

/* List */
.properties-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.prop-item {
  background: rgba(255, 255, 255, 0.5); /* Semi-transparent */
  backdrop-filter: blur(12px); /* Glassmorphism effect */
  -webkit-backdrop-filter: blur(12px);
  border-radius: 20px;
  border: 1px solid rgba(255, 255, 255, 0.3);
  overflow: hidden;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 4px 20px rgba(0,0,0,0.05);
}

.prop-item:hover {
  border-color: rgba(255, 255, 255, 0.5);
  background: rgba(255, 255, 255, 0.65);
  transform: translateY(-2px);
  box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}

.prop-item.is-expanded {
  border-color: #c4aa1c;
  background: rgba(255, 255, 255, 0.7);
  box-shadow: 0 20px 40px rgba(196, 170, 28, 0.15);
}

.prop-item__header {
  padding: 25px 35px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  user-select: none;
}

.prop-info-main {
  display: flex;
  align-items: center;
  gap: 20px;
}

.prop-id {
  font-family: 'JetBrains Mono', monospace;
  color: #c4aa1c;
  font-weight: 700;
  background: rgba(196, 170, 28, 0.1);
  padding: 6px 12px;
  border-radius: 10px;
  font-size: 0.9rem;
}

.prop-category-badge {
  font-size: 0.8rem;
  font-weight: 800;
  text-transform: uppercase;
  background: #1e293b;
  color: white;
  padding: 6px 16px;
  border-radius: 10px;
  letter-spacing: 0.05em;
}

.prop-title {
  margin: 0;
  font-size: 1.25rem;
  color: #1e293b;
  font-weight: 700;
}

.prop-meta-summary {
  display: flex;
  align-items: center;
  gap: 40px;
}

.prop-price {
  font-size: 1.15rem;
  font-weight: 800;
  color: #1e293b;
}

.prop-location {
  color: #64748b;
  font-size: 1rem;
  font-weight: 500;
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
}

.is-expanded .expand-btn {
  transform: rotate(180deg);
  background: #c4aa1c;
  border-color: #c4aa1c;
  color: white;
}

/* Details */
.prop-item__details {
  padding: 0 35px 35px 35px;
  border-top: 1px solid #f1f5f9;
  background: #fcfcfc;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 40px;
  padding: 35px 0;
}

.details-block h4 {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 0.85rem;
  text-transform: uppercase;
  color: #c4aa1c;
  margin: 0 0 20px 0;
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
  margin-bottom: 12px;
  font-size: 1rem;
  color: #1e293b;
  display: grid;
  grid-template-columns: 140px 1fr;
  gap: 12px;
  align-items: start;
}

.details-block li strong,
.details-block li span {
  min-width: 0;
}

.details-block li span {
  overflow-wrap: anywhere;
  word-break: break-word;
}

.details-block li strong {
  color: #64748b;
  font-weight: 500;
}

.owner-card p {
  margin: 10px 0;
  font-size: 0.95rem;
  display: flex;
  justify-content: space-between;
  border-bottom: 1px solid #f1f5f9;
  padding-bottom: 8px;
}

.owner-card p:last-child {
  border-bottom: none;
}

.details-actions {
  display: flex;
  justify-content: center;
  padding-top: 25px;
  border-top: 1px solid rgba(0, 0, 0, 0.05);
  margin-top: 10px;
}

.action-btn--ficha {
  padding: 10px 24px;
  background: rgba(196, 171, 28, 0.221);
  border: 1px solid rgba(196, 170, 28, 0.3);
  color: #c4aa1c;
  border-radius: 12px;
  font-weight: 700;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.3s ease;
  white-space: nowrap;
}

.action-btn--ficha:hover {
  background: #b19c23f5;
  color: #ffffff;
  box-shadow: 0 0 15px rgba(196, 170, 28, 0.4);
  transform: translateY(-2px);
}

/* Animations */
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

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(15, 23, 42, 0.8);
  backdrop-filter: blur(8px);
  z-index: 2000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px;
}

.modal-content {
  background: white;
  width: 100%;
  max-width: 1100px;
  max-height: 90vh;
  border-radius: 24px;
  position: relative;
  display: flex;
  flex-direction: column;
  box-shadow: 0 30px 60px rgba(0,0,0,0.5);
  overflow: hidden;
  border: 1px solid rgba(255,255,255,0.1);
}

.close-modal-btn {
  position: absolute;
  top: 25px;
  right: 25px;
  background: #f1f5f9;
  border: none;
  width: 45px;
  height: 45px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 10;
  transition: all 0.2s;
  color: #64748b;
}

.close-modal-btn:hover {
  background: #ef4444;
  color: white;
  transform: rotate(90deg);
}

.modal-header {
  padding: 40px 60px;
  background: #1e293b;
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  border-bottom: 4px solid #c4aa1c;
}

.modal-badge-id {
  display: inline-block;
  background: rgba(196, 170, 28, 0.2);
  color: #c4aa1c;
  padding: 4px 12px;
  border-radius: 6px;
  font-family: monospace;
  font-weight: 700;
  margin-bottom: 12px;
}

.modal-header h2 {
  font-size: 2.2rem;
  margin: 0 0 5px 0;
  font-family: 'Playfair Display', serif;
}

.modal-header p {
  margin: 0;
  color: #94a3b8;
  font-size: 1.1rem;
}

.modal-field__value {
  white-space: pre-wrap;
  overflow-wrap: anywhere;
  word-break: break-word;
  line-height: 1.45;
}

.modal-header-price {
  font-size: 2rem;
  font-weight: 800;
  color: #c4aa1c;
}

.modal-body {
  padding: 40px 60px;
  overflow-y: auto;
  flex: 1;
  background: #f8fafc;
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 40px;
  /* Hide scrollbar for IE, Edge and Firefox */
  -ms-overflow-style: none;
  scrollbar-width: none;
}

/* Hide scrollbar for Chrome, Safari and Opera */
.modal-body::-webkit-scrollbar {
  display: none;
}

.modal-section {
  background: white;
  padding: 30px;
  border-radius: 20px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 15px rgba(0,0,0,0.02);
}

.modal-section.full-width {
  grid-column: span 2;
}

.modal-section h3 {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 1rem;
  text-transform: uppercase;
  color: #1e293b;
  margin: 0 0 25px 0;
  letter-spacing: 0.05em;
  border-bottom: 2px solid #f1f5f9;
  padding-bottom: 12px;
}

.modal-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
}

.modal-grid--detailed {
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 30px;
}

.modal-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 0;
}

.modal-field label {
  font-size: 0.8rem;
  text-transform: uppercase;
  color: #94a3b8;
  font-weight: 700;
}

.modal-field span {
  font-size: 1rem;
  color: #1e293b;
  font-weight: 600;
  width: 100%;
  min-width: 0;
  overflow-wrap: anywhere;
  word-break: break-word;
  white-space: pre-wrap;
  line-height: 1.45;
}

.modal-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 20px;
  align-items: start;
}

.modal-grid--detailed {
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 20px;
  align-items: start;
}

.modal-header-info,
.modal-header-info h2,
.modal-header-info p {
  min-width: 0;
  overflow-wrap: anywhere;
  word-break: break-word;
}

.modal-description {
  line-height: 1.8;
  color: #475569;
  font-size: 1.05rem;
  white-space: pre-line;
}

/* Transitions */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.fade-enter-active .modal-content {
  transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.fade-enter-from .modal-content {
  transform: scale(0.9) translateY(20px);
}

@media (max-width: 1024px) {
  .admin-properties {
    padding: 40px 60px;
  }
  .admin-header {
    padding: 35px 40px;
  }
  .prop-meta-summary {
    gap: 20px;
  }
}

@media (max-width: 768px) {
  .admin-properties {
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
    flex-direction: column;
    gap: 15px;
    margin-bottom: 30px;
  }
  .search-box input {
    padding: 12px 15px 12px 45px;
    font-size: 0.95rem;
  }
  .search-box svg {
    left: 15px;
    width: 18px;
    height: 18px;
  }
  .filter-group select {
    padding: 0 35px 0 15px;
    font-size: 0.95rem;
    height: 48px;
    min-width: 100%;
    background-position: right 15px center;
  }
  
  .prop-item__header {
    flex-direction: column;
    align-items: flex-start;
    gap: 15px;
    padding: 20px;
  }
  .prop-info-main {
    flex-wrap: wrap;
    gap: 12px;
  }
  .prop-meta-summary {
    width: 100%;
    justify-content: space-between;
    gap: 10px;
    border-top: 1px solid rgba(0,0,0,0.05);
    padding-top: 12px;
  }
  
  .modal-overlay {
    padding: 10px;
  }
  .modal-content {
    max-height: 96vh;
    width: 98vw;
    border-radius: 12px;
  }
  .modal-header {
    padding: 15px;
    gap: 8px;
  }
  .modal-header h2 {
    font-size: 1.15rem;
  }
  .modal-header-price {
    font-size: 1.15rem;
  }
  .modal-body {
    padding: 12px;
    gap: 10px;
  }
  .modal-section {
    padding: 12px;
  }
  .modal-section.full-width {
    grid-column: span 1;
  }
}

@media (max-width: 480px) {
  .admin-properties {
    padding: 15px 10px;
  }
  .admin-header h1 {
    font-size: 1.6rem;
  }
  .admin-header p {
    font-size: 0.85rem;
  }
  .stat-card {
    padding: 12px 20px;
  }
  .stat-value {
    font-size: 1.8rem;
  }

  .search-box input {
    padding: 10px 10px 10px 40px;
    font-size: 0.9rem;
  }

  .prop-item__header {
    padding: 12px 15px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    position: relative;
    padding-bottom: 45px; /* Espacio para el botón abajo */
  }

  .prop-title {
    font-size: 0.95rem;
    line-height: 1.3;
    font-weight: 600;
  }
  .prop-id {
    font-size: 0.7rem;
    padding: 2px 5px;
  }
  .prop-category-badge {
    font-size: 0.6rem;
    padding: 2px 6px;
  }
  
  .prop-meta-summary {
    border-top: 1px solid rgba(0,0,0,0.05);
    padding-top: 8px;
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .prop-price {
    font-size: 0.9rem;
  }
  .prop-location {
    font-size: 0.8rem;
    max-width: 60%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .expand-btn {
    position: absolute;
    bottom: 8px;
    left: 50%;
    transform: translateX(-50%);
    width: 32px;
    height: 32px;
    background: #f1f5f9;
  }

  .is-expanded .expand-btn {
    transform: translateX(-50%) rotate(180deg);
  }
  
  .prop-item__details {
    padding: 0 15px 15px 15px;
  }
  
  .details-grid {
    grid-template-columns: 1fr !important;
    padding: 15px 0;
    gap: 15px;
  }
  .details-block h4 {
    font-size: 0.75rem;
    margin-bottom: 15px;
    gap: 8px;
  }
  .details-block li {
    font-size: 0.85rem;
    margin-bottom: 8px;
  }
  .details-actions {
    padding-top: 15px;
  }
  .action-btn--ficha {
    padding: 8px 16px;
    font-size: 0.85rem;
    width: 100%;
  }

  .modal-header {
    padding: 15px;
  }
  .modal-badge-id {
    font-size: 0.65rem;
    padding: 2px 6px;
    margin-bottom: 5px;
  }
  .modal-header-info h2 {
    font-size: 0.9rem;
    line-height: 1.1;
  }
  .modal-header-info p {
    font-size: 0.7rem;
  }
  .modal-header-price {
    font-size: 1rem;
  }
  .modal-body {
    grid-template-columns: 1fr !important;
    padding: 10px;
    gap: 15px;
  }
  .modal-section {
    padding: 10px;
    border-radius: 8px;
  }
  .modal-section h3 {
    font-size: 0.75rem;
    margin-bottom: 10px;
    padding-bottom: 5px;
    gap: 6px;
  }
  .modal-section h3 svg {
    width: 14px;
    height: 14px;
  }
  .modal-grid,
  .modal-grid--detailed {
    grid-template-columns: 1fr !important;
    gap: 8px;
  }
  .modal-field {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 6px;
  }
  .modal-field label {
    font-size: 0.65rem;
    margin-bottom: 0;
  }
  .modal-field span {
    font-size: 0.85rem;
    text-align: left;
    width: 100%;
  }
  .modal-description {
    font-size: 0.8rem;
    line-height: 1.4;
  }
  .close-modal-btn {
    top: 5px;
    right: 5px;
    width: 28px;
    height: 28px;
  }
  .close-modal-btn svg {
    width: 16px;
    height: 16px;
  }
}
</style>
