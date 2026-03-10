<template>
  <div class="aportar-container">
    <div class="form-header">
      <h1>Publicar Nuevo Activo</h1>
      <p>Complete los datos técnicos para ejecutar el motor de matching con nuestra base de inversores.</p>
    </div>

    <form @submit.prevent="handleSubmit" class="activo-form">
      
      <div class="form-section">
        <h3><span class="step">1</span> Información General</h3>
        <div class="grid-row">
          <div class="form-group">
            <label>Categoría de Activo</label>
            <select v-model="form.tipo_activo" required>
              <option value="" disabled>Seleccione una categoría</option>
              <option value="Edificios">Edificio</option>
              <option value="Hoteles">Hotel</option>
              <option value="Parking">Parking</option>
              <option value="Activos Singulares">Activo Singular</option>
              <option value="Fincas">Finca</option>
            </select>
          </div>
          <div class="form-group">
            <label>Tipo de Operación</label>
            <select v-model="form.operacion" required>
              <option value="Venta">Venta</option>
              <option value="Inversión">Inversión / Rentabilidad</option>
              <option value="Concesión">Concesión</option>
              <option value="Alquiler">Alquiler</option>
            </select>
          </div>
        </div>

        <div class="grid-row">
          <div class="form-group">
            <label>Precio (€)</label>
              <input 
                type="text"
                :value="precioFormateado"
                @input="handlePrecio"
                placeholder="Ej: 2.500.000"
                maxlength="11"
                required 
              />
          </div>
          <div class="form-group">
            <label>Superficie Total (m²)</label>
            <input type="number" v-model="form.superficie" placeholder="Ej: 1200" required />
          </div>
        </div>
      </div>

      <div class="form-section">
        <h3><span class="step">2</span> Ubicación y Privacidad</h3>
        <div class="form-group">
          <label>Ubicación / Zona</label>
          <input type="text" v-model="form.ubicacion" placeholder="Ej: Madrid, Barrio de Salamanca" required />
        </div>
        
        <div class="confidential-card">
          <div class="checkbox-wrapper">
            <input type="checkbox" id="confidential" v-model="form.es_confidencial" />
            <label for="confidential">
              <strong>Marcar como Activo OFF-MARKET (Confidencial)</strong>
              <span>La dirección exacta y fotos sensibles solo se mostrarán tras firma de NDA.</span>
            </label>
          </div>
        </div>
      </div>

      <div class="form-section">
  <h3><span class="step">3</span> Detalles Técnicos</h3>
  
  <div class="form-group">
    <label>Descripción corta para inversores</label>
    <textarea v-model="form.descripcion" rows="4" placeholder="Resumen de la oportunidad..."></textarea>
  </div>

  <!-- ===== EDIFICIOS ===== -->
  <template v-if="form.tipo_activo === 'Edificios'">
    <div class="detail-group">
      <label>Uso</label>
      <div class="chips">
        <label v-for="op in ['Residencial','Oficinas','Comercial','Industrial']" :key="op" class="chip">
          <input type="radio" v-model="form.extras.uso" :value="op" /> {{ op }}
        </label>
      </div>
    </div>
    <div class="detail-group">
      <label>Características</label>
      <div class="chips">
        <label v-for="op in ['Ascensor','Garaje','Terraza','Reformado','Nuevo']" :key="op" class="chip">
          <input type="checkbox" v-model="form.extras.caracteristicas" :value="op" /> {{ op }}
        </label>
      </div>
    </div>
    <div class="detail-group">
      <label>Zona</label>
      <div class="chips">
        <label v-for="op in ['Centro','Periferia','Zona financiera']" :key="op" class="chip">
          <input type="radio" v-model="form.extras.zona" :value="op" /> {{ op }}
        </label>
      </div>
    </div>
  </template>

  <!-- ===== HOTELES ===== -->
  <template v-if="form.tipo_activo === 'Hoteles'">
    <div class="detail-group">
      <label>Estrellas</label>
      <div class="chips">
        <label v-for="op in ['3 estrellas','4 estrellas','5 estrellas']" :key="op" class="chip gold">
          <input type="radio" v-model="form.extras.estrellas" :value="op" /> {{ op }}
        </label>
      </div>
    </div>
    <div class="detail-group">
      <label>Servicios</label>
      <div class="chips">
        <label v-for="op in ['Spa','Piscina','Gimnasio','Parking privado','Restaurante','Room Service','Vista al mar']" :key="op" class="chip">
          <input type="checkbox" v-model="form.extras.caracteristicas" :value="op" /> {{ op }}
        </label>
      </div>
    </div>
    <div class="detail-group">
      <label>Ubicación</label>
      <div class="chips">
        <label v-for="op in ['Centro ciudad','Playa','Montaña','Zona rural']" :key="op" class="chip">
          <input type="radio" v-model="form.extras.ubicacion_tipo" :value="op" /> {{ op }}
        </label>
      </div>
    </div>
  </template>

  <!-- ===== PARKING ===== -->
  <template v-if="form.tipo_activo === 'Parking'">
    <div class="detail-group">
      <label>Tipo</label>
      <div class="chips">
        <label v-for="op in ['Subterráneo','Exterior','Privado','Público']" :key="op" class="chip">
          <input type="radio" v-model="form.extras.tipo_parking" :value="op" /> {{ op }}
        </label>
      </div>
    </div>
    <div class="detail-group">
      <label>Características</label>
      <div class="chips">
        <label v-for="op in ['Vigilancia 24h','Acceso automático','Cámaras de seguridad','Carga eléctrica']" :key="op" class="chip">
          <input type="checkbox" v-model="form.extras.caracteristicas" :value="op" /> {{ op }}
        </label>
      </div>
    </div>
    <div class="detail-group">
      <label>Ubicación</label>
      <div class="chips">
        <label v-for="op in ['Centro','Residencial','Comercial']" :key="op" class="chip">
          <input type="radio" v-model="form.extras.ubicacion_tipo" :value="op" /> {{ op }}
        </label>
      </div>
    </div>
  </template>

  <!-- ===== ACTIVOS SINGULARES ===== -->
  <template v-if="form.tipo_activo === 'Activos Singulares'">
    <div class="detail-group">
      <label>Tipo de activo</label>
      <div class="chips">
        <label v-for="op in ['Comercial','Industrial','Residencial']" :key="op" class="chip">
          <input type="radio" v-model="form.extras.tipo_singular" :value="op" /> {{ op }}
        </label>
      </div>
    </div>
    <div class="detail-group">
      <label>Características</label>
      <div class="chips">
        <label v-for="op in ['Fachada renovada','Cercano a transporte público','Espacios adaptados']" :key="op" class="chip">
          <input type="checkbox" v-model="form.extras.caracteristicas" :value="op" /> {{ op }}
        </label>
      </div>
    </div>
    <div class="detail-group">
      <label>Ubicación</label>
      <div class="chips">
        <label v-for="op in ['Centro de la ciudad','Zona industrial']" :key="op" class="chip">
          <input type="radio" v-model="form.extras.ubicacion_tipo" :value="op" /> {{ op }}
        </label>
      </div>
    </div>
  </template>

  <!-- ===== FINCAS ===== -->
  <template v-if="form.tipo_activo === 'Fincas'">
    <div class="detail-group">
      <label>Tipo</label>
      <div class="chips">
        <label v-for="op in ['Rural','Agrícola','Forestal','Cinegéticas']" :key="op" class="chip">
          <input type="radio" v-model="form.extras.tipo_finca" :value="op" /> {{ op }}
        </label>
      </div>
    </div>
    <div class="detail-group">
      <label>Características</label>
      <div class="chips">
        <label v-for="op in ['Agua potable','Acceso por carretera','Parcela vallada']" :key="op" class="chip">
          <input type="checkbox" v-model="form.extras.caracteristicas" :value="op" /> {{ op }}
        </label>
      </div>
    </div>
    <div class="detail-group">
      <label>Hectáreas</label>
      <input type="number" v-model="form.extras.hectareas" placeholder="Ej: 15" class="input-hectareas" />
    </div>
    <div class="detail-group">
      <label>Ubicación</label>
      <div class="chips">
        <label v-for="op in ['Zona rural','Cerca de río','Montaña']" :key="op" class="chip">
          <input type="radio" v-model="form.extras.ubicacion_tipo" :value="op" /> {{ op }}
        </label>
      </div>
    </div>
  </template>

</div>

      <div class="form-actions">
        <button type="submit" :disabled="loading" class="submit-btn">
          {{ loading ? 'Procesando...' : 'Publicar Activo y Buscar Matches' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { useUserStore } from "@/stores/user";
import { useRouter } from "vue-router";

const userStore = useUserStore();
const router = useRouter();
const loading = ref(false);

const form = ref({
  tipo_activo: "",
  operacion: "Venta",
  precio: null,
  superficie: null,
  ubicacion: "",
  es_confidencial: false,
  descripcion: "",
  id_aportador: userStore.user?.id,
  extras: { caracteristicas: [] }  // ← añade esto
});

// ← añade este watch
watch(() => form.value.tipo_activo, () => {
  form.value.extras = { caracteristicas: [] };
});

const handleSubmit = async () => {
  loading.value = true;
  try {
    const response = await fetch("http://localhost/inmobiliaria/backend/save_activo.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(form.value)
    });

    const data = await response.json();

    if (data.success) {
      alert("Activo publicado correctamente. Iniciando motor de matching...");
      router.push('/dashboard');
    } else {
      alert("Error: " + data.message);
    }
  } catch (error) {
    console.error("Error al guardar:", error);
    alert("Error de conexión con el servidor");
  } finally {
    loading.value = false;
  }
};

// Valor visual formateado
const precioFormateado = computed(() => {
  if (!form.value.precio) return '';
  return Number(form.value.precio).toLocaleString('es-ES');
});

// Al escribir, limpia puntos y guarda solo el número
const handlePrecio = (e) => {
  const soloNumeros = e.target.value.replace(/\D/g, '').slice(0, 8);;
  form.value.precio = soloNumeros ? Number(soloNumeros) : null;
};
</script>

<style scoped>
.aportar-container {
  max-width: 800px;
  margin: 60px auto;
  padding: 20px;
  font-family: 'Inter', sans-serif;
}

.form-header {
  text-align: center;
  margin-bottom: 40px;
}

.form-header h1 {
  font-size: 2.5rem;
  font-weight: 300;
  color: #24386b;
}

.activo-form {
  background: white;
  padding: 40px;
  border-radius: 20px;
  box-shadow: 0 15px 50px rgba(0,0,0,0.1);
}

.form-section {
  margin-bottom: 40px;
}

.form-section h3 {
  display: flex;
  align-items: center;
  gap: 15px;
  font-size: 1.2rem;
  color: #333;
  margin-bottom: 25px;
  border-bottom: 1px solid #eee;
  padding-bottom: 10px;
}

.step {
  background: #d4af37;
  color: white;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  font-size: 0.9rem;
}

.grid-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.form-group {
  margin-bottom: 20px;
  display: flex;
  flex-direction: column;
}

.form-group label {
  font-weight: 600;
  margin-bottom: 8px;
  font-size: 0.9rem;
}

input, select, textarea {
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 1rem;
}

.confidential-card {
  background: #fff9e6;
  padding: 20px;
  border-radius: 12px;
  border: 1px solid #ffe699;
}

.checkbox-wrapper {
  display: flex;
  gap: 15px;
  align-items: flex-start;
}

.checkbox-wrapper label {
  display: flex;
  flex-direction: column;
}

.checkbox-wrapper span {
  font-size: 0.85rem;
  color: #666;
}

.submit-btn {
  width: 100%;
  padding: 18px;
  background: #24386b;
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 1.1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.3s;
}

.submit-btn:hover {
  background: #1a2a50;
}

.submit-btn:disabled {
  background: #ccc;
}

/* ===== CAMPOS DINÁMICOS POR CATEGORÍA ===== */
.detail-group {
  margin-top: 22px;
}

.detail-group > label:first-child {
  display: block;
  font-weight: 600;
  font-size: 0.9rem;
  color: #333;
  margin-bottom: 12px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.chips {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.chip {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 9px 18px;
  border: 1.5px solid #ddd;
  border-radius: 8px; /* igual que tus inputs */
  cursor: pointer;
  font-size: 0.9rem;
  font-family: 'Inter', sans-serif;
  font-weight: 500;
  transition: all 0.2s ease;
  background: white;
  color: #333;
  user-select: none;
}

.chip:hover {
  border-color: #24386b;
  color: #24386b;
}

/* Seleccionado — usa el azul de tu .submit-btn */
.chip:has(input:checked) {
  border-color: #24386b;
  background-color: #24386b;
  color: white;
}

/* Para los radio de estrellas usa el dorado de .step */
.chip.gold:has(input:checked) {
  border-color: #d4af37;
  background-color: #d4af37;
  color: white;
}

.chip input {
  display: none;
}

/* Input de hectáreas — igual que tus demás inputs */
.input-hectareas {
  width: 200px;
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 1rem;
  font-family: 'Inter', sans-serif;
  transition: border-color 0.2s;
}

.input-hectareas:focus {
  outline: none;
  border-color: #24386b;
}

/* Separador visual entre el textarea y los campos dinámicos */
.detail-group:first-of-type {
  padding-top: 10px;
  border-top: 1px solid #eee;
  margin-top: 25px;
}
</style>