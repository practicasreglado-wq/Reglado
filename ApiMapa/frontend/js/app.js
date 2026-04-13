/**
 * Mapa de nombres alternativos para normalización de comunidades autónomas
 * @type {Object}
 */
const comunidadNamesMap = {
    'Andalucia': 'Andalucía',
    'Aragon': 'Aragón',
    'Castilla La Mancha': 'Castilla-La Mancha',
    'Castilla La-Mancha': 'Castilla-La Mancha',
    'castilla la mancha': 'castilla-la mancha',
    'castilla-la mancha': 'castilla-la mancha',
    'Castilla León': 'Castilla y León',
    'Castilla y León': 'Castilla y León',
    'Castilla Y León': 'Castilla y León',
    'Pais Vasco': 'País Vasco',
    'Valencia': 'Comunidad Valenciana',
    'Murcia': 'Región de Murcia'
};

/**
 * Configuración inicial del mapa Leaflet
 * @type {L.Map}
 */
const map = L.map('map', {
    center: [40.4168, -3.7038],
    zoomControl: false,
});

// Capa base de OpenStreetMap
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

// Variables globales
let instalaciones = [];

/**
 * Configuración de clusters para agrupación de marcadores
 * @type {L.MarkerClusterGroup}
 */
const markers = L.markerClusterGroup({
    spiderfyOnMaxZoom: true,
    showCoverageOnHover: true,
    zoomToBoundsOnClick: false,
    maxClusterRadius: 80,
    disableClusteringAtZoom: 14,
    iconCreateFunction: function (cluster) {
        const markers = cluster.getAllChildMarkers();
        const total = markers.length;

        // 1. Contar por tipo
        const typeCounts = {};
        markers.forEach(m => {
            // la clase del icono es "icon-<tipo>"
            const cls = m.options.icon.options.className;
            const tipo = cls.replace('icon-', '');
            typeCounts[tipo] = (typeCounts[tipo] || 0) + 1;
        });

        // 2. Definir colores por tipo
        const colorMap = {
            subestacion: '#C62828',   // rojo apagado
            eolica: '#64B5F6',   // azul pastel
            solar: '#FFF176',   // amarillo suave
            biometano: '#BA68C8',   // lila claro
            hidrogeno: '#8D6E63',   // marrón grisáceo
            biodiesel: '#81C784',   // verde apagado
            Hidraulica: '#5C6BC0'    // azul moderado
        };


        // 3. Construir segmentos para CSS conic-gradient
        let start = 0;
        const segments = Object.entries(typeCounts).map(([tipo, cnt]) => {
            const slice = (cnt / total) * 360;
            const seg = `${colorMap[tipo] || '#999'} ${start}deg ${start + slice}deg`;
            start += slice;
            return seg;
        });
        const background = `conic-gradient(${segments.join(', ')})`;

        // 4. Calcular tamaño de la burbuja
        const size = Math.min(Math.max(30 + Math.log2(total) * 3, 30), 70);

        // 5. Generar HTML
        const html = `
            <div class="cluster-container" style="
                background: ${background};
                width: ${size}px;
                height: ${size}px;
                line-height: ${size}px;
                border: 2px solid white;
                box-shadow: 0 0 0 ${size / 15}px rgba(0,0,0,0.25);">
                <span class="cluster-count">${total > 999 ? '999+' : total}</span>
            </div>`;

        return new L.DivIcon({
            html,
            className: 'dynamic-cluster',
            iconSize: [size, size]
        });
    }

});

// Zoom directo al hacer clic en cluster
markers.on('clusterclick', function (a) {
    const cluster = a.layer;
    map.fitBounds(cluster.getBounds(), {
        padding: [50, 50],
        maxZoom: 18
    });
});

/**
 * Iconos personalizados para los diferentes tipos de instalaciones
 * @type {Object}
 */
const iconos = {
    subestacion: L.icon({
        iconUrl: '/mapa/frontend/assets/img/subestacion.png',
        iconSize: [32, 32],
        className: 'icon-subestacion'
    }),
    eolica: L.icon({
        iconUrl: '/mapa/frontend/assets/img/eolica.png',
        iconSize: [32, 32],
        className: 'icon-eolica'
    }),
    solar: L.icon({
        iconUrl: '/mapa/frontend/assets/img/solar.png',
        iconSize: [35, 35],
        className: 'icon-solar'
    }),
    biometano: L.icon({
        iconUrl: '/mapa/frontend/assets/img/biometano.png',
        iconSize: [32, 32],
        className: 'icon-biometano'
    }),
    hidrogeno: L.icon({
        iconUrl: '/mapa/frontend/assets/img/hidrogeno.png',
        iconSize: [32, 32],
        className: 'icon-hidrogeno'
    }),
    hidraulica: L.icon({
        iconUrl: '/mapa/frontend/assets/img/Hidraulica.png',
        iconSize: [32, 32],
        className: 'icon-Hidraulica'
    }),
    biodiesel: L.icon({
        iconUrl: '/mapa/frontend/assets/img/biodiesel.png',
        iconSize: [32, 32],
        className: 'icon-biodiesel'
    })
};

/**
 * Crea el contenido del popup para una instalación
 * @param {Object} inst - Datos de la instalación
 * @return {string} HTML del popup
 */
function crearPopup(inst) {
    if (!inst.lat || !inst.lng) {
        return `<div class="popup-content-single">
            <div class="popup-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h4>Instalación sin ubicación</h4>
            </div>
            <div class="popup-body">
                <p>Esta instalación no tiene coordenadas válidas.</p>
            </div>
        </div>`;
    }

    const instalacionesEnMismaUbicacion = instalaciones.filter(i =>
        Math.abs(i.lat - inst.lat) < 0.0001 &&
        Math.abs(i.lng - inst.lng) < 0.0001
    );

    const comunidadNormalizada = comunidadNamesMap[inst.comunidad] || inst.comunidad;

    // Colores por empresa para subestaciones
    const empresaColors = {
        'Endesa': '#ff0000',
        'Iberdrola': '#0070c0',
        'Naturgy': '#00b050',
        'Viesgo': '#ffc000',
        'EDP': '#7030a0',
        'Aduriz': '#ff6600',
        'Pitarch': '#00b0f0'
    };

    const footerInfo = `
    <div class="popup-footer">
        <div class="comunidad-info">Comunidad: ${comunidadNormalizada}</div>
        ${inst.provincia ? `<div class="provincia-info">Provincia: ${inst.provincia}</div>` : ''}
        <div class="contact-link-container">
            <a href="https://www.regladoconsultores.com/contacto" target="_blank" class="contact-link">
                <i class="fas fa-envelope"></i> Contactar para más información
            </a>
        </div>
    </div>`;

    // Función para generar campos adicionales según el tipo de instalación
    const generarCamposAdicionales = (instalacion) => {
        let campos = '';

        switch (instalacion.tipo) {
            case 'subestacion':
                campos += `
                     <div class="popup-field">
                        <span class="field-label">Capacidad ocupada:</span>
                        <span class="field-value">${inst.capacidad || '0'} MW</span>
                    </div>
                    <div class="popup-field">
                        <span class="field-label">Capacidad disponible:</span>
                        <a href="https://www.regladoconsultores.com/contacto" target="_blank" class="contact-info-link">+info</a>
                    </div>
                    <div class="popup-field">
                        <span class="field-label">Empresa:</span>
                        <span class="field-value" style="color: ${empresaColors[instalacion.empresa] || '#333'}; font-weight: bold;">
                            ${instalacion.empresa || 'No especificada'}
                        </span>
                    </div>
                    ${instalacion.nivelTension ? `
                    <div class="popup-field">
                        <span class="field-label">Nivel tensión:</span>
                        <span class="field-value">${instalacion.nivelTension}</span>
                    </div>` : ''}
                    
                    ${instalacion.estado ? `
                    <div class="popup-field">
                        <span class="field-label">Estado:</span>
                        <span class="field-value">${instalacion.estado}</span>
                    </div>` : ''}`;
                break;

            case 'biometano':
                campos += `
                    ${instalacion.produ_anyo ? `
                    <div class="popup-field">
                        <span class="field-label me-2">Producción / Año (GWh): </span>
                        <span class="field-value">${instalacion.produ_anyo}</span>
                    </div>` : ''}
                    ${instalacion.produ_hora ? `
                        <div class="popup-field">
                            <span class="field-label me-2">Producción / Hora (Nm3): </span>
                            <span class="field-value">
                                <a href="https://www.regladoconsultores.com/contacto" target="_blank" class="contact-info-link">+info</a>                            
                            </span>
                        </div>` : ''}
                    ${instalacion.estado ? `
                        <div class="popup-field">
                            <span class="field-label">Estado: </span>
                            <span class="field-value">${instalacion.estado}</span>
                        </div>` : ''}
                    ${instalacion.promotor ? `
                    <div class="popup-field">
                        <span class="field-label">Promotor: </span>
                        <span class="field-value">${instalacion.promotor}</span>
                    </div>` : ''}
                    ${instalacion.tipo_gas ? `
                        <div class="popup-field">
                            <span class="field-label">GNL / GNC: </span>
                            <span class="field-value">${instalacion.tipo_gas}</span>
                        </div>` : ''}
                    ${instalacion.origen ? `
                        <div class="popup-field">
                            <span class="field-label">Origen: </span>
                            <span class="field-value">${instalacion.origen}</span>
                        </div>` : ''}`


                break;

            case 'eolica':
                campos += `
                        ${instalacion.Propietario ? `
                        <div class="popup-field">
                            <span class="field-label">Propietario: </span>
                            <span class="field-value">${instalacion.Propietario}</span>
                        </div>` : ''}
                        ${instalacion.PuestaEnMarcha ? `
                        <div class="popup-field">
                            <span class="field-label">Puesta en marcha: </span>
                            <span class="field-value">${instalacion.PuestaEnMarcha}</span>
                        </div>` : ''}
                        ${instalacion.Potencia ? `
                        <div class="popup-field">
                            <span class="field-label">Potencia: </span>
                            <span class="field-value">${instalacion.Potencia} MW</span>
                        </div>` : ''}
                        ${instalacion.Municipio ? `
                        <div class="popup-field">
                            <span class="field-label">Municipio: </span>
                            <span class="field-value">${instalacion.Municipio} </span>
                        </div>` : ''}
                        ${instalacion.Estado ? `
                        <div class="popup-field">
                            <span class="field-label">Estado: </span>
                            <span class="field-value">${instalacion.Estado} </span>
                        </div>` : ''}
                        ${instalacion.nombreFase ? `
                        <div class="popup-field">
                            <span class="field-label">Fase: </span>
                            <span class="field-value">${instalacion.nombreFase}</span>
                        </div>` : ''}`;

                break;

            case 'solar':
                campos += `
                        ${instalacion.Capacidad ? `
                        <div class="popup-field">
                            <span class="field-label">Capacidad: </span>
                            <span class="field-value">${instalacion.Capacidad} ${instalacion.CapacidadNominal}</span>
                        </div>` : ''}
                        ${instalacion.Tecnología ? `
                        <div class="popup-field">
                            <span class="field-label">Tecnología: </span>
                            <span class="field-value">${instalacion.Tecnología}</span>
                        </div>` : ''}
                        ${instalacion.Estado ? `
                        <div class="popup-field">
                            <span class="field-label">Estado: </span>
                            <span class="field-value">${instalacion.Estado}</span>
                        </div>` : ''}
                        ${instalacion.puestaEnMarcha ? `
                        <div class="popup-field">
                            <span class="field-label">Puesta en marcha: </span>
                            <span class="field-value">${instalacion.puestaEnMarcha}</span>
                        </div>` : ''}
                        ${instalacion.empresa ? `
                        <div class="popup-field">
                            <span class="field-label">Propietario: </span>
                            <span class="field-value">${instalacion.empresa}</span>
                        </div>` : ''}
                        ${instalacion.puestaEnMarcha ? `
                        <div class="popup-field">
                            <span class="field-label">Puesta en marcha: </span>
                            <span class="field-value">${instalacion.puestaEnMarcha}</span>
                        </div>` : ''}`;
                break;

            case 'hidrogeno':
                campos += `
                        ${instalacion.ProcesoDeProducción ? `
                        <div class="popup-field">
                            <span class="field-label">Proceso: </span>
                            <span class="field-value">${instalacion.ProcesoDeProducción} (producción) </span>
                        </div>` : ''}
                        ${instalacion.TipoDeUso ? `
                        <div class="popup-field">
                            <span class="field-label">Tipo de uso: </span>
                            <span class="field-value">${instalacion.TipoDeUso}</span>
                        </div>` : ''}
                        ${instalacion.CapacidadDeConsumo ? `
                        <div class="popup-field">
                            <span class="field-label">Consumo: </span>
                            <span class="field-value">${instalacion.CapacidadDeConsumo} (toneladas/año)</span>
                        </div>` : ''}
                        ${instalacion.CapacidadDeProducción ? `
                        <div class="popup-field">
                            <span class="field-label">Capacidad: </span>
                            <span class="field-value">${instalacion.CapacidadDeProducción} (MW)</span>
                        </div>` : ''}

                        ${instalacion.AñoDeInicio ? `
                        <div class="popup-field">
                            <span class="field-label">Año de inicio: </span>
                            <span class="field-value">${instalacion.AñoDeInicio}</span>
                        </div>` : ''}
                        ${instalacion.empresa ? `
                        <div class="popup-field">
                            <span class="field-label">Empresa: </span>
                            <span class="field-value">${instalacion.empresa}</span>
                        </div>` : ''}
                        ${instalacion.EstadoDelProyecto ? `
                        <div class="popup-field">
                            <span class="field-label">Estado del proyecto: </span>
                            <span class="field-value">${instalacion.EstadoDelProyecto}</span>
                        </div>` : ''}
                        ${instalacion.puestaEnMarcha ? `
                        <div class="popup-field">
                            <span class="field-label">Puesta en marcha: </span>
                            <span class="field-value">${instalacion.puestaEnMarcha}</span>
                        </div>` : ''}`;
                break;
            case 'biodiesel':
                campos += `
                    ${instalacion.Capacidad ? `
                    <div class="popup-field">
                        <span class="field-label">Capacidad:</span>
                        <span class="field-value">${instalacion.Capacidad} (toneladas/año)</span>
                    </div>` : ''}
                    ${instalacion.Estado ? `
                    <div class="popup-field">
                        <span class="field-label">Estado:</span>
                        <span class="field-value">${instalacion.Estado}</span>
                    </div>` : ''}
                    ${instalacion.Tecnología ? `
                    <div class="popup-field">
                        <span class="field-label">Tecnología:</span>
                        <span class="field-value">${instalacion.Tecnología}</span>
                    </div>` : ''}
                    ${instalacion.Certificación ? `
                    <div class="popup-field">
                        <span class="field-label">Certificación:</span>
                        <span class="field-value">${instalacion.Certificación} </span>
                    </div>` : ''}
                    ${instalacion.Propietario ? `
                    <div class="popup-field">
                        <span class="field-label">Empresa:</span>
                        <span class="field-value">${instalacion.Propietario} kt/año</span>
                    </div>` : ''}`;
                break;

            case 'hidraulica':
                campos += `
                    ${instalacion.Capacidad ? `
                    <div class="popup-field">
                        <span class="field-label">Capacidad:</span>
                        <span class="field-value">${instalacion.Capacidad} (MW)</span>
                    </div>` : ''}
                    ${instalacion.Binacional ? `
                    <div class="popup-field">
                        <span class="field-label">Binacional:</span>
                        <span class="field-value">${instalacion.Binacional}</span>
                    </div>` : ''}
                    ${instalacion.Estado ? `
                    <div class="popup-field">
                        <span class="field-label">Estado:</span>
                        <span class="field-value">${instalacion.Estado} </span>
                    </div>` : ''}
                    ${instalacion.Turbinas ? `
                    <div class="popup-field">
                        <span class="field-label">Número de turbinas:</span>
                        <span class="field-value">${instalacion.Turbinas} </span>
                    </div>` : ''}
                    ${instalacion.Rio ? `
                    <div class="popup-field">
                        <span class="field-label">Río:</span>
                        <span class="field-value">${instalacion.Rio} </span>
                    </div>` : ''}

                    ${instalacion.Propietario ? `
                    <div class="popup-field">
                        <span class="field-label">Empresa:</span>
                        <span class="field-value">${instalacion.Propietario} </span>
                    </div>` : ''}
                    ${instalacion.puestaEnMarcha ? `
                    <div class="popup-field">
                        <span class="field-label">Puesta en marcha:</span>
                        <span class="field-value">${instalacion.puestaEnMarcha} </span>
                    </div>` : ''}
                    ${instalacion.saltoDeAgua ? `
                    <div class="popup-field">
                        <span class="field-label">Salto de Agua:</span>
                        <span class="field-value">${instalacion.saltoDeAgua} (metros) </span>
                    </div>` : ''}
                    ${instalacion.Tecnología ? `
                    <div class="popup-field">
                        <span class="field-label">Tecnología:</span>
                        <span class="field-value">${instalacion.Tecnología} </span>
                    </div>` : ''}`;
                break;

            default:
                campos += `
                    ${instalacion.empresa ? `
                    <div class="popup-field">
                        <span class="field-label">Empresa:</span>
                        <span class="field-value">${instalacion.empresa}</span>
                    </div>` : ''}`;
        }

        return campos;
    };

    if (instalacionesEnMismaUbicacion.length > 1) {
        let contenido = `
            <div class="popup-content-single">
                <div class="popup-header">
                    <i class="fas fa-map-pin"></i>
                    <h4>${instalacionesEnMismaUbicacion.length} instalaciones en este punto</h4>
                </div>
                <div class="popup-scrollable">
                    <div class="popup-body multiple-instalaciones">
                        ${instalacionesEnMismaUbicacion.map(i => {
            return `
                            <div class="instalacion-item">
                                <div class="instalacion-header">
                                    <strong>${i.nombre}</strong>
                                    ${i.capacidad ? `
                                    <span class="capacidad" data-capacidad="${i.capacidad || 0}">
                                        ${i.capacidad} MW
                                    </span>` : ''}
                                </div>
                                <div class="instalacion-details">
                                    <div class="popup-field">
                                        <span class="field-label">Tipo:</span>
                                        <span class="field-value">${i.tipo.charAt(0).toUpperCase() + i.tipo.slice(1)}</span>
                                    </div>
                                  
                                    ${generarCamposAdicionales(i)}
                                    <div class="popup-field">
                                        <span class="field-label">Ubicación:</span>
                                        <span class="field-value">${i.municipio || i.provincia || 'N/A'}</span>
                                    </div>
                                </div>
                            </div>`;
        }).join('')}
                    </div>
                </div>
                ${footerInfo}
            </div>`;
        return contenido;
    } else {
        return `
            <div class="popup-content-single">
                <div class="popup-header">
                    <i class="fas ${iconos[inst.tipo] ? iconos[inst.tipo].options.className.replace('icon-', 'fa-') : 'fa-map-marker'}"></i>
                    <h4>${inst.nombre}</h4>
                </div>
                <div class="popup-body">
                    <div class="popup-field">
                        <span class="field-label">Tipo:</span>
                        <span class="field-value">${inst.tipo.charAt(0).toUpperCase() + inst.tipo.slice(1)}</span>
                    </div>
                   
                    ${generarCamposAdicionales(inst)}
                    <div class="popup-field">
                        <span class="field-label">Ubicación:</span>
                        <span class="field-value">${inst.municipio || inst.provincia || 'N/A'}</span>
                    </div>
                </div>
                ${footerInfo}
            </div>`;
    }
}

/**
 * Carga las instalaciones en el mapa
 * @param {Array} datos - Array de instalaciones
 */
function cargarInstalaciones(datos) {
    console.log("Datos recibidos para cargar:", datos);

    // Filtrar datos con coordenadas válidas
    const datosValidos = datos.filter(inst => {
        const valido = inst.lat !== undefined &&
            inst.lng !== undefined &&
            !isNaN(inst.lat) &&
            !isNaN(inst.lng) &&
            Math.abs(inst.lat) <= 90 &&
            Math.abs(inst.lng) <= 180;

        if (!valido) {
            console.warn("Instalación descartada por coordenadas inválidas:", inst);
        }
        return valido;
    });

    console.log(`Datos válidos: ${datosValidos.length}/${datos.length}`);

    // Limpiar marcadores existentes
    markers.clearLayers();

    // Crear y añadir nuevos marcadores
    const markersBatch = datosValidos.map(inst => {
        if (inst.lat && inst.lng) {
            console.log(`Creando marcador para ${inst.nombre} (${inst.tipo}) en ${inst.lat}, ${inst.lng}`);
            const marker = L.marker([inst.lat, inst.lng], {
                icon: iconos[inst.tipo],
                zIndexOffset: 1000
            }).bindPopup(crearPopup(inst));
            return marker;
        }
        return null;
    }).filter(m => m !== null);

    console.log(`Marcadores creados: ${markersBatch.length}`);

    markers.addLayers(markersBatch);
    map.addLayer(markers);
    markers.bringToFront();

    // Actualizar estadísticas
    actualizarLeyendaYEstadisticas();
}


/**
 * Actualiza la leyenda y estadísticas según los filtros aplicados
 */
function actualizarLeyendaYEstadisticas() {
    const tipos = ['eolica', 'solar', 'subestacion', 'biometano', 'hidrogeno', 'biodiesel', 'hidraulica'];

    // Obtener filtros actuales
    const tiposSeleccionados = Array.from(document.getElementById('filtro-tipo').selectedOptions)
        .map(option => option.value);
    const comunidadSeleccionada = document.getElementById('filtro-comunidad').value;
    const provinciaSeleccionada = document.getElementById('filtro-provincia').value;

    // Determinar si estamos mostrando todas las comunidades
    const mostrarTodas = !comunidadSeleccionada;

    // Filtrar instalaciones según los filtros
    const instalacionesFiltradas = instalaciones.filter(inst => {
        if (mostrarTodas) {
            return tiposSeleccionados.length === 0 || tiposSeleccionados.includes(inst.tipo);
        } else {
            const cumpleTipo = tiposSeleccionados.length === 0 || tiposSeleccionados.includes(inst.tipo);
            const comunidadNormalizada = normalizeComunidadName(inst.comunidad);
            const comunidadBuscada = normalizeComunidadName(comunidadSeleccionada);
            const cumpleComunidad = comunidadNormalizada === comunidadBuscada;

            const cumpleProvincia = !provinciaSeleccionada ||
                (inst.provincia && inst.provincia.toLowerCase() === provinciaSeleccionada.toLowerCase());

            return cumpleTipo && cumpleComunidad && cumpleProvincia;
        }
    });

    // Calcular totales para normalizar las barras
    const totalCapacidad = instalacionesFiltradas.reduce((sum, inst) => sum + (inst.capacidad || 0), 0);
    const totalProduccion = instalacionesFiltradas.reduce((sum, inst) => sum + (inst.produ_anyo || inst.produ_anyo_ || 0), 0);

    // Actualizar contadores y gráficos
    tipos.forEach(tipo => {
        const instalacionesTipo = instalacionesFiltradas.filter(i => i.tipo === tipo);
        const count = instalacionesTipo.length;
        const countElement = document.querySelector(`.legend-item[data-type="${tipo}"] .legend-count`);
        if (countElement) countElement.textContent = count;

        // Datos específicos por tipo de instalación
        let statText = '';
        let barValue = 0;
        let barMax = 1; // Para evitar división por cero

        switch (tipo) {
            case 'subestacion':
                const capacidadSub = instalacionesTipo.reduce((sum, inst) => sum + (inst.capacidad || 0), 0);
                statText = `${Math.round(capacidadSub)} MW`;
                barValue = capacidadSub;
                barMax = totalCapacidad || 1;
                break;

            case 'biometano':
                const produccionBio = instalacionesTipo.reduce((sum, inst) => sum + (inst.produ_anyo || inst.produ_anyo_ || 0), 0);
                const plantasOperativas = instalacionesTipo.filter(i => i.estado && i.estado.toLowerCase().includes('explotación')).length;
                statText = `${Math.round(produccionBio)} GWh/año | ${plantasOperativas} operativas`;
                barValue = produccionBio;
                barMax = totalProduccion || 1;
                break;

            case 'eolica':
                const capacidadEolica = instalacionesTipo.reduce((sum, inst) => sum + (inst.capacidad || 0), 0);
                const aerogeneradores = instalacionesTipo.reduce((sum, inst) => sum + (inst.numAerogeneradores || 0), 0);
                statText = `${Math.round(capacidadEolica)} MW | ${aerogeneradores} aerog.`;
                barValue = capacidadEolica;
                barMax = totalCapacidad || 1;
                break;

            case 'solar':
                const capacidadSolar = instalacionesTipo.reduce((sum, inst) => sum + (inst.capacidad || 0), 0);
                const areaTotal = instalacionesTipo.reduce((sum, inst) => sum + (inst.area || 0), 0);
                statText = `${Math.round(capacidadSolar)} MW | ${Math.round(areaTotal)} ha`;
                barValue = capacidadSolar;
                barMax = totalCapacidad || 1;
                break;

            case 'hidrogeno':
                const capacidadH2 = instalacionesTipo.reduce((sum, inst) => sum + (inst.capacidad || 0), 0);
                const electrolizadores = instalacionesTipo.filter(i => i.tecnologia === 'electrolisis').length;
                statText = `${Math.round(capacidadH2)} MW | ${electrolizadores} electroliz.`;
                barValue = capacidadH2;
                barMax = totalCapacidad || 1;
                break;

            case 'hidraulica':
                // Suma de la capacidad instalada (MW)
                const capacidadHidraulica = instalacionesTipo
                    .reduce((sum, inst) => sum + (inst.capacidad || 0), 0);

                // Suma del número de turbinas (suponiendo que la prop es `turbinas`)
                const totalTurbinas = instalacionesTipo
                    .reduce((sum, inst) => sum + (inst.turbinas || 0), 0);

                // Texto de estadísticas: MW y número de turbinas
                statText = `${Math.round(capacidadHidraulica)} MW | ${totalTurbinas} Turbinas`;

                // Valor de barra proporcional a la capacidad
                barValue = capacidadHidraulica;
                barMax = totalCapacidad || 1;
                break;

            case 'biodiesel':
                const capacidadBiod = instalacionesTipo.reduce((sum, inst) => sum + (inst.capacidad || 0), 0);
                const produccionAnual = instalacionesTipo.reduce((sum, inst) => sum + (inst.capacidadProduccion || 0), 0);
                statText = `${Math.round(capacidadBiod)} MW | ${Math.round(produccionAnual)} kt/año`;
                barValue = capacidadBiod;
                barMax = totalCapacidad || 1;
                break;
        }

        const porcentaje = (barValue / barMax) * 100;

        const statItem = document.querySelector(`.stat-item[data-type="${tipo}"]`);
        if (statItem) {
            statItem.querySelector('.stat-bar').style.width = `${porcentaje}%`;
            statItem.querySelector('.stat-value').textContent = statText;
        }
    });
}

/**
 * Normaliza nombres de comunidades para comparación
 * @param {string} comunidad - nombre de la comunidad a normalizar
 * @return {string} nombre normalizado
 */
function normalizeComunidadName(comunidad) {
    if (!comunidad) return '';

    const nombresAlternativos = {
        'andaluci': 'andalucía',
        'aragon': 'aragón',
        'castilla la mancha': 'castilla-la mancha',
        'castilla-la mancha': 'castilla-la mancha',
        'castilla león': 'castilla y león',
        'castilla y león': 'castilla y león',
        'pais vasco': 'país vasco',
        'valencia': 'comunidad valenciana',
        'murcia': 'región de murcia',
        'islas baleares': 'baleares',
        'illes balears': 'baleares',
        'comunidad valenciana': 'valencia'
    };

    let nombreNormalizado = comunidad
        .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
        .toLowerCase()
        .replace(/\s+/g, ' ')
        .trim();

    for (const [key, value] of Object.entries(nombresAlternativos)) {
        if (nombreNormalizado.includes(key)) {
            nombreNormalizado = value;
            break;
        }
    }

    return nombreNormalizado;
}

/**
 * Inicializa el mapa con datos.
 * Si la URL contiene ?energia=<tipo>, en cuanto los datos llegan
 * se selecciona ese tipo y se ejecuta cargarTodasComunidades()
 * automáticamente, sin polling y sin depender del estado del Swal.
 * @param {Array} datos - Datos de instalaciones
 */
window.inicializarMapaConDatos = function (datos) {
    instalaciones = datos;
    actualizarLeyendaYEstadisticas();

    // Lógica para Deep Linking (Filtro por URL)
    const urlParams = new URLSearchParams(window.location.search);
    const energiaURL = urlParams.get('energia')?.toLowerCase().trim();

    if (!energiaURL) return;

    const selectElement = document.getElementById('filtro-tipo');
    if (!selectElement) return;

    // 1. Marcar el icono visualmente (sin .click() para no disparar el toggle)
    const icono = document.querySelector(`.icon-option[data-value="${energiaURL}"]`);
    if (icono) icono.classList.add('selected');

    // 2. Seleccionar solo este tipo en el select usando jQuery para Select2
    $(selectElement).val([energiaURL]).trigger('change');

    function lanzarCarga() {
        if (typeof cargarTodasComunidades === 'function') {
            cargarTodasComunidades();
        }
    }

    // 3. Ejecutar la carga tras un breve delay que deje resolver modales previos
    setTimeout(lanzarCarga, 800);
};

/**
 * Inicializa el mapa vacío
 */
function inicializarMapa() {
    instalaciones = [];
    markers.clearLayers();
    actualizarLeyendaYEstadisticas();
    map.on('layeradd', () => markers.bringToFront());

    // Centrar en España peninsular (coordenadas de Madrid) y zoom 6
    map.setView([40, -3.7038], 7);
}

// Inicializar el mapa cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', inicializarMapa);

