<template>
  <div class="mapa-view-container">
    <div class="map-wrapper">

      <!-- Drawer estilo Side-Nav para Filtros -->
      <div class="filters-drawer-container" :class="{ 'is-open': isFiltersOpen }">
        <button class="filters-toggle-btn" @click="isFiltersOpen = !isFiltersOpen">
          <i class="fas fa-filter"></i>
          <span class="filters-btn-label">Filtros</span>
        </button>

        <div class="panel-filtros">
          <div class="header-filtros" style="display: none;">
            <h2><i class="fas fa-sliders-h"></i> Filtros</h2>
            <button id="toggle-filtros"><i class="fas fa-chevron-down"></i></button>
          </div>

          <div class="contenido-filtros">
            <div class="filtro">
              <label><i class="fas fa-bolt"></i> Tipo de Instalación:</label>
              <div class="icon-filters" id="iconos_filtros">
                <div class="icon-option" data-value="subestacion" id="subestacion">
                  <img src="/apimapa/assets/img/subestacion.png" alt="Subestación">
                  <span>Subestaciones</span>
                </div>
                <div class="icon-option" data-value="eolica" id="eolica">
                  <img src="/apimapa/assets/img/eolica.png" alt="Eólica">
                  <span>Eólica</span>
                </div>
                <div class="icon-option" data-value="solar" id="solar">
                  <img src="/apimapa/assets/img/solar.png" alt="Solar">
                  <span>Solar</span>
                </div>
                <div class="icon-option" data-value="biometano" id="biometano">
                  <img src="/apimapa/assets/img/biometano.png" alt="Biometano">
                  <span>Biometano</span>
                </div>
                <div class="icon-option" data-value="hidrogeno" id="hidrogeno">
                  <img src="/apimapa/assets/img/hidrogeno.png" alt="Hidrógeno">
                  <span>Hidrógeno</span>
                </div>
                <div class="icon-option" data-value="biodiesel" id="biodiesel">
                  <img src="/apimapa/assets/img/biodiesel.png" alt="Biodiésel">
                  <span>Biodiésel</span>
                </div>
                <div class="icon-option" data-value="hidraulica" id="hidraulica">
                  <img src="/apimapa/assets/img/Hidraulica.png" alt="Hidraulica">
                  <span>Hidraulica</span>
                </div>
              </div>
              <select id="filtro-tipo" class="select2" multiple style="display:none;">
                <option value="subestacion">Subestaciones</option>
                <option value="eolica">Eólica</option>
                <option value="solar">Solar</option>
                <option value="biometano">Biometano</option>
                <option value="hidrogeno">Hidrógeno</option>
                <option value="biodiesel">Biodiésel</option>
                <option value="hidraulica">Hidraulica</option>
              </select>
            </div>

            <div class="filtro">
              <label><i class="fas fa-map-marked-alt"></i> Comunidad Autónoma:</label>
              <select id="filtro-comunidad" class="select2">
                <option value="">Todas las comunidades</option>
                <option value="Andalucía">Andalucía</option>
                <option value="Aragón">Aragón</option>
                <option value="Asturias">Asturias</option>
                <option value="Baleares">Baleares</option>
                <option value="Canarias">Canarias</option>
                <option value="Cantabria">Cantabria</option>
                <option value="Castilla-La Mancha">Castilla-La Mancha</option>
                <option value="Castilla y León">Castilla y León</option>
                <option value="Cataluña">Cataluña</option>
                <option value="Extremadura">Extremadura</option>
                <option value="Galicia">Galicia</option>
                <option value="Madrid">Madrid</option>
                <option value="Murcia">Murcia</option>
                <option value="Navarra">Navarra</option>
                <option value="País Vasco">País Vasco</option>
                <option value="La Rioja">La Rioja</option>
                <option value="Valencia">Valencia</option>
              </select>
            </div>

            <div class="filtro">
              <label><i class="fas fa-map-marked-alt"></i> Provincia:</label>
              <select id="filtro-provincia" class="select2" disabled>
                <option value="">Selecciona una comunidad primero</option>
              </select>
            </div>

            <button id="aplicar-filtros" class="btn-filtrar">
              <i class="fas fa-filter"></i> Aplicar Filtros
            </button>

            <button id="cargar-todo" class="btn-filtrar">
              <i class="fas fa-map"></i> Cargar todas las comunidades
            </button>

            <button id="reset-filtros" class="btn-reset">
              <i class="fas fa-redo"></i> Reiniciar
            </button>
          </div>
        </div>
      </div>

      <div id="map"></div>

      <!-- Panel de Leyenda Desplegable Nativo -->
      <div class="panel-leyenda custom-leyenda-drawer" :class="{ 'is-open': isLeyendaOpen }">
        <button class="leyenda-toggle-btn-vue" @click="isLeyendaOpen = !isLeyendaOpen">
          <span class="leyenda-btn-text">Leyenda</span>
          <i class="fas fa-layer-group"></i>
        </button>
        <div class="leyenda-content">
          <div class="map-info-panel">
            <div class="legend-section">
              <h4 id="tipo_instalaciones_leyenda"><i class="fas fa-map-marker-alt"></i>‎ Tipo de instalaciones</h4>
              <div class="legend-items">
                <div class="legend-item" data-type="eolica">
                  <img src="/apimapa/assets/img/eolica.png" alt="Eólica">
                  <span>Eólica</span>
                  <span class="legend-count">0</span>
                </div>
                <div class="legend-item" data-type="solar">
                  <img src="/apimapa/assets/img/solar.png" alt="Solar">
                  <span>Solar</span>
                  <span class="legend-count">0</span>
                </div>
                <div class="legend-item" data-type="subestacion">
                  <img src="/apimapa/assets/img/subestacion.png" alt="Subestación">
                  <span>Subestaciones</span>
                  <span class="legend-count">0</span>
                </div>
                <div class="legend-item" data-type="biometano">
                  <img src="/apimapa/assets/img/biometano.png" alt="Biometano">
                  <span>Biometano</span>
                  <span class="legend-count">0</span>
                </div>
                <div class="legend-item" data-type="hidrogeno">
                  <img src="/apimapa/assets/img/hidrogeno.png" alt="Hidrógeno">
                  <span>Hidrógeno</span>
                  <span class="legend-count">0</span>
                </div>
                <div class="legend-item" data-type="biodiesel">
                  <img src="/apimapa/assets/img/biodiesel.png" alt="Biodiésel">
                  <span>Biodiésel</span>
                  <span class="legend-count">0</span>
                </div>
                <div class="legend-item" data-type="hidraulica">
                  <img src="/apimapa/assets/img/Hidraulica.png" alt="Hidraulica">
                  <span>Hidraulica</span>
                  <span class="legend-count">0</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'MapView',
  data() {
    return {
      scriptsCargados: false,
      scriptsGenerados: [],
      isFiltersOpen: true,
      isLeyendaOpen: false
    }
  },
  mounted() {
    this.cargarScriptsMapa();
  },
  unmounted() {
    // Limpieza de globales y de DOM
    if (window.map && typeof window.map.remove === 'function') {
      try { window.map.remove(); } catch (e) { }
    }
    this.scriptsGenerados.forEach(s => {
      if (s && s.parentNode) s.parentNode.removeChild(s);
    });
  },
  methods: {
    cargarScriptsMapa() {
      // Load CSS
      this.loadCSS('https://unpkg.com/leaflet@1.7.1/dist/leaflet.css');
      this.loadCSS('https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css');
      this.loadCSS('https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css');
      this.loadCSS('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
      this.loadCSS('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
      this.loadCSS('https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css');
      this.loadCSS('https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css');
      const version = '?v=1.2'; // Cache-buster para obligar a los navegadores a soltar la memoria
      this.loadCSS('/apimapa/styles/main.css' + version);
      this.loadCSS('/apimapa/styles/leyenda.css' + version);

      // Load JS in strict sequence to avoid dependencies errors
      this.loadScript('https://code.jquery.com/jquery-3.6.0.min.js')
        .then(() => Promise.all([
          this.loadScript('https://unpkg.com/leaflet@1.7.1/dist/leaflet.js'),
          this.loadScript('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js'),
          this.loadScript('https://cdn.jsdelivr.net/npm/sweetalert2@11')
        ]))
        .then(() => this.loadScript('https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js'))
        .then(() => this.loadScript('https://unpkg.com/leaflet-i18n'))
        .then(() => this.loadScript('/apimapa/js/app.js' + version))
        .then(() => this.loadScript('/apimapa/js/loadData.js' + version))
        .then(() => this.loadScript('/apimapa/js/filtros.js' + version))
        .then(() => {
          this.scriptsCargados = true;

          // Ejecutar los inicializadores nativos manualmente en vez de esperar DOMContentLoaded
          if (typeof window.iniciarFiltrosUI === 'function') window.iniciarFiltrosUI();
          if (typeof window.iniciarCargaDatos === 'function') window.iniciarCargaDatos();

          // Disparar las lógicas de Vue Router si hay querystring ?energia=X 
          const energyParam = this.$route && this.$route.query.energia ? this.$route.query.energia : null;
          if (typeof window.iniciarDeepLinking === 'function') {
            window.iniciarDeepLinking(energyParam);
          }

          setTimeout(() => {
            if (typeof window.map !== 'undefined' && window.map) {
              window.map.invalidateSize();
              console.log("Leaflet resize forzado");
            }
          }, 600);
        })
        .catch(err => console.error("Error al inyectar librerías del ApiMapa", err));
    },
    loadScript(src) {
      return new Promise((resolve, reject) => {
        if (document.querySelector(`script[src="${src}"]`)) { resolve(); return; }
        const script = document.createElement('script');
        script.src = src;
        script.onload = resolve;
        script.onerror = reject;
        document.body.appendChild(script);
        this.scriptsGenerados.push(script);
      });
    },
    loadCSS(href) {
      if (document.querySelector(`link[href="${href}"]`)) return;
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = href;
      document.head.appendChild(link);
      this.scriptsGenerados.push(link);
    }
  }
}
</script>

<style scoped>
.mapa-view-container {
  /* En vista de mapa completo el header se oculta, extendemos a pantalla total */
  padding-top: 0px;
  width: 100vw;
  height: 100vh;
  box-sizing: border-box;
  background-color: #1a1a1a;
  /* Fondo oscuro por defecto del mapa */
}

.map-wrapper {
  position: relative;
  width: 100%;
  height: 100%;
}

#map {
  width: 100% !important;
  height: 100vh !important;
  z-index: 1;
}

/* Forzar sobreescrituras para evitar que Bootstrap rompa la estructura de Vue principal */
:global(.panel-filtros) {
  position: static !important;
  box-shadow: none !important;
  border-radius: 0 16px 16px 0 !important;
  width: 320px !important;
  max-width: 85vw !important;
  height: 100% !important;
  max-height: calc(100vh - 80px) !important;
  margin: 0 !important;
  overflow-y: auto !important;
  overflow-x: hidden !important;
  padding-bottom: 20px;
  background: white !important;
  /* Asegurar fondo para que el recorte funcione */
}

/* Redondear esquinas de los Popups de Leaflet globalmente */
:global(.leaflet-popup-content-wrapper) {
  border-radius: 16px !important;
  overflow: hidden !important;
  padding: 0 !important;
}

/* Sobrescribir diseño del drawer estilo Side Nav */
.filters-drawer-container {
  position: absolute;
  top: 100px;
  left: 0;
  z-index: 1000;
  transform: translateX(-100%);
  transition: transform 0.4s cubic-bezier(0.25, 1, 0.5, 1);
  background: transparent;
  /* Dejamos que el panel interno maneje su propio fondo recortado */
  border-radius: 0 16px 16px 0;
  box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
  display: flex;
}

.filters-drawer-container.is-open {
  transform: translateX(0);
}

.filters-toggle-btn {
  position: absolute;
  left: 100%;
  top: 15px;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  padding-left: 20px;
  /* Aumentado a 20px para mantener centrado en 64px */
  background: rgba(10, 11, 16, 0.75);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-left: none;
  border-radius: 0 16px 16px 0;
  height: 48px;
  width: 64px;
  /* Aumentado su tamaño en reposo */
  color: white;
  cursor: pointer;
  transition: width 0.3s ease-in-out, background 0.3s ease, box-shadow 0.3s ease;
  overflow: hidden;
  white-space: nowrap;
  box-shadow: 2px 4px 12px rgba(0, 0, 0, 0.15);
}

.filters-toggle-btn i {
  font-size: 1.1rem;
}

.filters-toggle-btn:hover {
  background: rgba(44, 152, 224, 0.85);
  /* Azul tipo botón */
  width: 135px;
  box-shadow: 4px 6px 16px rgba(0, 0, 0, 0.3);
}

.filters-btn-label {
  opacity: 0;
  margin-left: 10px;
  font-size: 0.95rem;
  font-weight: 500;
  transition: opacity 0.2s ease;
}

.filters-toggle-btn:hover .filters-btn-label {
  opacity: 1;
  transition-delay: 0.1s;
}

/* Ajustes Responsive Drawer */
@media (max-width: 480px) {
  .filters-drawer-container {
    top: 80px;
  }
}

:global(.panel-leyenda) {
  top: 100px !important;
  /* Misma altura que el de filtros */
  border-radius: 16px 0 0 16px !important;
  box-shadow: -4px 0 20px rgba(0, 0, 0, 0.15) !important;
  z-index: 1000 !important;
}

:global(.leyenda-content) {
  border-radius: 16px 0 0 16px !important;
  max-height: calc(100vh - 140px) !important;
  color: #2c3e50;
}

/* Ocultamos cualquier rastro del header legacy vainilla */
:global(.leyenda-header) {
  display: none !important;
}

/* ===================================================
   DRAWER NATIVO VUE PARA LA LEYENDA (DERECHA)
   =================================================== */
.custom-leyenda-drawer {
  position: absolute !important;
  top: 100px !important;
  right: 0 !important;
  z-index: 1000 !important;
  transform: translateX(100%) !important;
  transition: transform 0.4s cubic-bezier(0.25, 1, 0.5, 1) !important;
  border-radius: 16px 0 0 16px !important;
  box-shadow: -4px 0 20px rgba(0, 0, 0, 0.15) !important;
  background: white !important;
}

.custom-leyenda-drawer.is-open {
  transform: translateX(0) !important;
}

.leyenda-toggle-btn-vue {
  position: absolute;
  right: 100%;
  top: 15px;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  /* Alinear icon a la derecha */
  padding-right: 20px;
  /* margen equilibrado al izquierdo de filtros */
  background: rgba(10, 11, 16, 0.75);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-right: none;
  border-radius: 16px 0 0 16px;
  height: 48px;
  width: 64px;
  /* Aumentado su tamaño en reposo */
  color: white;
  cursor: pointer;
  transition: width 0.3s ease-in-out, background 0.3s ease, box-shadow 0.3s ease;
  overflow: hidden;
  white-space: nowrap;
  box-shadow: -2px 4px 12px rgba(0, 0, 0, 0.15);
}

.leyenda-toggle-btn-vue i {
  font-size: 1.1rem;
  flex-shrink: 0;
  transition: none;
  /* Sin animaciones residuales */
}

.leyenda-toggle-btn-vue:hover {
  background: rgba(44, 152, 224, 0.85);
  /* Azul tipo botón */
  width: 135px;
  /* Crece simétricamente hacia la izquierda garantizando espacio al texto */
  box-shadow: -4px 6px 16px rgba(0, 0, 0, 0.3);
}

.leyenda-btn-text {
  opacity: 0;
  font-size: 0.95rem;
  font-weight: 500;
  transition: opacity 0.2s ease;
  margin-right: 10px;
  /* exacto al margin-left de filtros */
}

.leyenda-toggle-btn-vue:hover .leyenda-btn-text {
  opacity: 1;
  transition-delay: 0.1s;
}

/* Evitar que el color: white del #app vue general invisibilice las letras de los filtros sobre fondo blanco */
:global(.icon-option span) {
  color: #2c3e50;
  font-weight: 500;
}

:global(.leyenda-content) {
  color: #2c3e50;
}

:global(.select2-container) {
  color: #2c3e50 !important;
}

:global(.select2-results__option) {
  color: #2c3e50;
}
</style>
