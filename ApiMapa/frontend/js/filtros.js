// Coordenadas por comunidad para el zoom
const comunidadesCoords = {
    "Andalucía": { 
        lat: 37.5, lng: -4.5, 
        zoom: 7,
        bounds: [[35.95, -7.55], [38.95, -1.55]]
    },
    "Aragón": { 
        lat: 41.5, lng: -0.5, 
        zoom: 7,
        bounds: [[39.8, -2.3], [43.2, 1.3]]
    },
    "Asturias": { 
        lat: 43.35, lng: -5.85, 
        zoom: 8,
        bounds: [[42.8, -7.2], [43.9, -4.5]]
    },
    "Baleares": { 
        lat: 39.5, lng: 3.0, 
        zoom: 8,
        bounds: [[38.5, 1.0], [40.5, 5.0]]
    },
    "Canarias": { 
        lat: 28.3, lng: -16.6, 
        zoom: 7,
        bounds: [[27.6, -18.2], [29.3, -13.4]]
    },
    "Cantabria": { 
        lat: 43.2, lng: -4.0, 
        zoom: 8,
        bounds: [[42.8, -4.8], [43.5, -3.2]]
    },
    "Castilla-La Mancha": { 
        lat: 39.5, lng: -3.0, 
        zoom: 7,
        bounds: [[38.5, -5.5], [41.0, -0.5]]
    },
    "Castilla y León": { 
        lat: 41.7, lng: -4.7, 
        zoom: 7,
        bounds: [[40.5, -7.5], [43.0, -1.5]]
    },
    "Cataluña": { 
        lat: 41.8, lng: 1.6, 
        zoom: 7,
        bounds: [[40.5, 0.0], [42.9, 3.3]]
    },
    "Extremadura": { 
        lat: 39.0, lng: -6.0, 
        zoom: 7,
        bounds: [[37.8, -7.5], [40.5, -4.5]]
    },
    "Galicia": { 
        lat: 42.8, lng: -7.9, 
        zoom: 7,
        bounds: [[41.8, -9.3], [43.8, -6.5]]
    },
    "Madrid": { 
        lat: 40.4168, lng: -3.7038, 
        zoom: 9,
        bounds: [[40.1, -4.1], [40.8, -3.3]]
    },
    "Murcia": { 
        lat: 37.9833, lng: -1.1333, 
        zoom: 8,
        bounds: [[37.5, -2.0], [38.5, -0.5]]
    },
    "Navarra": { 
        lat: 42.8, lng: -1.6, 
        zoom: 8,
        bounds: [[41.8, -2.5], [43.3, -0.8]]
    },
    "País Vasco": { 
        lat: 43.0, lng: -2.75, 
        zoom: 8,
        bounds: [[42.5, -3.5], [43.5, -1.5]]
    },
    "La Rioja": { 
        lat: 42.25, lng: -2.5, 
        zoom: 9,
        bounds: [[41.9, -3.0], [42.6, -1.8]]
    },
    "Valencia": { 
        lat: 39.4667, lng: -0.375, 
        zoom: 8,
        bounds: [[38.5, -1.5], [40.5, 0.5]]
    }
};

// Mapa de provincias por comunidad
const provinciasPorComunidad = {
    "Andalucía": ["Almería", "Cádiz", "Córdoba", "Granada", "Huelva", "Jaén", "Málaga", "Sevilla"],
    "Aragón": ["Huesca", "Teruel", "Zaragoza"],
    "Asturias": ["Asturias"],
    "Baleares": ["Baleares"],
    "Canarias": ["Las Palmas", "Santa Cruz de Tenerife"],
    "Cantabria": ["Cantabria"],
    "Castilla-La Mancha": ["Albacete", "Ciudad Real", "Cuenca", "Guadalajara", "Toledo"],
    "Castilla y León": ["Ávila", "Burgos", "León", "Palencia", "Salamanca", "Segovia", "Soria", "Valladolid", "Zamora"],
    "Cataluña": ["Barcelona", "Girona", "Lleida", "Tarragona"],
    "Extremadura": ["Badajoz", "Cáceres"],
    "Galicia": ["A Coruña", "Lugo", "Ourense", "Pontevedra"],
    "Madrid": ["Madrid"],
    "Murcia": ["Murcia"],
    "Navarra": ["Navarra"],
    "País Vasco": ["Álava", "Guipúzcoa", "Vizcaya"],
    "La Rioja": ["La Rioja"],
    "Valencia": ["Alicante", "Castellón", "Valencia"]
};

function normalizeComunidadName(comunidad) {
    if (!comunidad) return '';
    
    const nombresAlternativos = {
        'andaluci': 'andalucía',
        'aragon': 'aragón',
        'castilla la mancha': 'castilla-la mancha',
        'castilla-la mancha': 'castilla-la mancha',
        'castilla León': 'castilla y león',
        'castilla y León': 'castilla y león',
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

function inicializarFiltros() {
    // Inicializar select2
    $('#filtro-comunidad').select2({
        width: '100%',
        placeholder: "Selecciona una comunidad",
        minimumResultsForSearch: Infinity
    }).val('').trigger('change');

    $('#filtro-provincia').select2({
        width: '100%',
        placeholder: "Selecciona una comunidad primero",
        minimumResultsForSearch: Infinity,
        disabled: true
    });

    // Evento para actualizar provincias cuando cambia la comunidad
    $('#filtro-comunidad').on('change', function() {
        const comunidad = $(this).val();
        const $provinciaSelect = $('#filtro-provincia');
        
        $provinciaSelect.empty().append('<option value="">Todas las provincias</option>');
        
        if (comunidad && provinciasPorComunidad[comunidad]) {
            $provinciaSelect.prop('disabled', false);
            provinciasPorComunidad[comunidad].forEach(provincia => {
                $provinciaSelect.append(`<option value="${provincia}">${provincia}</option>`);
            });
        } else {
            $provinciaSelect.prop('disabled', true);
        }
        
        $provinciaSelect.trigger('change');
    });

    // Toggle panel de filtros
// Al pulsar cualquier parte del header-filtros
document.querySelector('.header-filtros')
  .addEventListener('click', () => {
    const panel = document.querySelector('.panel-filtros');
    const icon  = document.querySelector('#toggle-filtros i');
    panel.classList.toggle('colapsado');
    icon.classList.toggle('fa-chevron-up');
    icon.classList.toggle('fa-chevron-down');
});


    // Event listeners
    document.getElementById('aplicar-filtros').addEventListener('click', aplicarFiltros);
    document.getElementById('reset-filtros').addEventListener('click', resetearFiltros);
    document.getElementById('cargar-todo').addEventListener('click', cargarTodasComunidades);
    
    // Actualizar leyenda cuando cambian los filtros
    document.getElementById('filtro-tipo').addEventListener('change', actualizarLeyendaYEstadisticas);
    document.getElementById('filtro-comunidad').addEventListener('change', actualizarLeyendaYEstadisticas);
    document.getElementById('filtro-provincia').addEventListener('change', actualizarLeyendaYEstadisticas);
}

function inicializarFiltrosIconos() {
    // 1. Bind click handler to cada icono de filtro
    document.querySelectorAll('.icon-option').forEach(option => {
        option.addEventListener('click', function() {
            const value = this.getAttribute('data-value');
            
            // Toggle visual “selected”
            this.classList.toggle('selected');
            
            // Sincronizar con el <select> oculto
            const select = document.getElementById('filtro-tipo');
            const optionElement = select.querySelector(`option[value="${value}"]`);
            optionElement.selected = this.classList.contains('selected');
            
            // Actualizar el mapa, la leyenda y estadísticas
            actualizarLeyendaYEstadisticas();
        });
    });

    // 2. Hacer que clicar la leyenda actúe como filtro (p.ej. hidráulica)
    document.querySelectorAll('.legend-item').forEach(item => {
        item.style.cursor = 'pointer';
        item.addEventListener('click', function() {
            // 'data-type' debe coincidir con el valor de data-value en .icon-option
            const tipo = this.getAttribute('data-type');
            const iconoFiltro = document.querySelector(`.icon-option[data-value="${tipo}"]`);
            if (iconoFiltro) {
                // Simular clic sobre el icono de filtro
                iconoFiltro.click();
            }
        });
    });
}



function aplicarFiltros() {
    // 1. Obtener valores seleccionados
    const tiposSeleccionados = Array.from(document.getElementById('filtro-tipo').selectedOptions)
        .map(option => option.value);
    
    const comunidadSeleccionada = document.getElementById('filtro-comunidad').value;
    const provinciaSeleccionada = document.getElementById('filtro-provincia').value;

    // Validar que se han seleccionado AMBOS: tipo Y comunidad
    if (tiposSeleccionados.length === 0 || !comunidadSeleccionada) {
        Swal.fire({
            icon: 'warning',
            title: 'Filtros incompletos',
            html: 'Para aplicar filtros debes seleccionar:<br><br>'
                + '• <strong>Al menos un tipo de instalación</strong><br>'
                + '• <strong>Una comunidad autónoma</strong>',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#2c3e50'
        });
        return;
    }

    // Mostrar alerta de carga
    let swalInstance = Swal.fire({
        title: 'Aplicando filtros',
        html: 'Procesando los datos...',
        allowOutsideClick: false,
        // didOpen: () => {
        //     Swal.showLoading();
        // }
    });

    // 2. Filtrar instalaciones
    const marcadoresFiltrados = instalaciones.filter(inst => {
        const cumpleTipo = tiposSeleccionados.length === 0 || tiposSeleccionados.includes(inst.tipo);
        const comunidadNormalizada = normalizeComunidadName(inst.comunidad);
        const comunidadBuscada = normalizeComunidadName(comunidadSeleccionada);
        const cumpleComunidad = !comunidadSeleccionada || comunidadNormalizada === comunidadBuscada;
        
        const cumpleProvincia = !provinciaSeleccionada || 
            (inst.provincia && inst.provincia.toLowerCase().trim() === provinciaSeleccionada.toLowerCase().trim());
        
        return cumpleTipo && cumpleComunidad && cumpleProvincia;
    });

    // 3. Limpiar y añadir marcadores
    markers.clearLayers();
    const markersBatch = marcadoresFiltrados.map(inst => {
        if (inst.lat && inst.lng) {
            const marker = L.marker([inst.lat, inst.lng], {
                icon: iconos[inst.tipo],
                zIndexOffset: 1000
            }).bindPopup(crearPopup(inst));
            return marker;
        }
        return null;
    }).filter(m => m !== null);

    // Cerrar alerta de carga
    swalInstance.close();

    if (markersBatch.length > 0) {
        markers.addLayers(markersBatch);
        map.addLayer(markers);

        // 4. Ajustar vista del mapa - CON SOLUCIÓN PARA ALBACETE Y BURGOS
        if (provinciaSeleccionada === 'Albacete') {
            // CASO ESPECIAL PARA ALBACETE
            const albaceteCenter = [38.9946, -1.8584]; // Coordenadas del centro de Albacete
            const albaceteZoom = 9; // Nivel de zoom adecuado para Albacete
            
            map.flyTo(albaceteCenter, albaceteZoom, {
                duration: 1,
                easeLinearity: 0.25
            });

            setTimeout(() => {
                const markersBounds = markers.getBounds();
                if (markersBounds.isValid() && !markersBounds.isFlat()) {
                    map.flyToBounds(markersBounds, {
                        padding: [50, 50],
                        maxZoom: albaceteZoom,
                        duration: 0.8
                    });
                }
            }, 500);
        }
        else if (provinciaSeleccionada === 'Burgos') {
            // CASO ESPECIAL PARA BURGOS
            const burgosCenter = [42.3439, -3.6968]; // Coordenadas del centro de Burgos
            const burgosZoom = 9; // Nivel de zoom adecuado para Burgos
            
            map.flyTo(burgosCenter, burgosZoom, {
                duration: 1,
                easeLinearity: 0.25
            });

            setTimeout(() => {
                const markersBounds = markers.getBounds();
                if (markersBounds.isValid() && !markersBounds.isFlat()) {
                    map.flyToBounds(markersBounds, {
                        padding: [50, 50],
                        maxZoom: burgosZoom,
                        duration: 0.8
                    });
                }
            }, 500);
        }
        else if (comunidadSeleccionada && comunidadesCoords[comunidadSeleccionada]) {
            // LÓGICA ORIGINAL PARA OTRAS PROVINCIAS
            const comunidadData = comunidadesCoords[comunidadSeleccionada];
            
            if (comunidadData.bounds) {
                map.flyToBounds(L.latLngBounds(comunidadData.bounds), {
                    padding: [30, 30],
                    maxZoom: comunidadData.zoom,
                    duration: 1
                });

                setTimeout(() => {
                    const markersBounds = markers.getBounds();
                    if (markersBounds.isValid()) {
                        map.flyToBounds(markersBounds, {
                            padding: [50, 50],
                            maxZoom: comunidadData.zoom + 1,
                            duration: 0.8
                        });
                    }
                }, 800);
            } else {
                map.flyTo([comunidadData.lat, comunidadData.lng], comunidadData.zoom, {
                    duration: 1,
                    easeLinearity: 0.25
                });
            }
        } else {
            const markersBounds = markers.getBounds();
            if (markersBounds.isValid()) {
                map.flyToBounds(markersBounds, {
                    padding: [50, 50],
                    maxZoom: 7,
                    duration: 1
                });
            } else {
                map.flyTo([40.4168, -3.7038], 7);
            }
        }
    } else {
        Swal.fire({
            icon: 'info',
            title: 'Sin resultados',
            text: 'No se encontraron instalaciones con los criterios seleccionados',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#2c3e50'
        });
    }

    // 5. Actualizar UI
    markers.bringToFront();
    actualizarLeyendaYEstadisticas();
}

function cargarTodasComunidades() {
    if (instalaciones.length > 0) {
        // Obtener los tipos seleccionados
        const tiposSeleccionados = Array.from(document.getElementById('filtro-tipo').selectedOptions)
            .map(option => option.value);
        
        // Verificar si se ha seleccionado algún tipo
        if(tiposSeleccionados.length === 0){
            Swal.fire({
                icon: 'warning',
                title: 'Selecciona al menos un tipo de instalación',
                text: 'Debes seleccionar uno o más tipos de instalación para cargar los datos de toda España',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#2c3e50'
            });
            return;
        }

        // Filtrar instalaciones de los tipos seleccionados
        const instalacionesFiltradas = instalaciones.filter(inst => 
            tiposSeleccionados.includes(inst.tipo)
        );

        Swal.fire({
            title: `Cargando instalaciones...`,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        setTimeout(() => {
            cargarInstalaciones(instalacionesFiltradas);
            map.flyTo([40.4168, -3.7038], 7, {
                duration: 1,
                easeLinearity: 0.25
            });
            
            // Resetear los filtros visualmente (manteniendo los tipos seleccionados)
            document.querySelectorAll('.icon-option').forEach(option => {
                option.classList.remove('selected');
                if (tiposSeleccionados.includes(option.getAttribute('data-value'))) {
                    option.classList.add('selected');
                }
            });
            
            $('#filtro-tipo').val(tiposSeleccionados).trigger('change');
            $('#filtro-comunidad').val('').trigger('change');
            $('#filtro-provincia').val('').prop('disabled', true).trigger('change');
            
            // Forzar actualización de leyenda
            actualizarLeyendaYEstadisticas();
            
            Swal.fire({
                icon: 'success',
                title: `Instalaciones cargadas`,
                html: `Se han cargado ${instalacionesFiltradas.length} instalaciones de tipo: <br>
                       ${tiposSeleccionados.map(t => capitalizeFirstLetter(t)).join(', ')}`,
                showConfirmButton: false,
                timer: 2000
            });
        }, 500);
    } else {
        Swal.fire({
            icon: 'info',
            title: 'Datos no cargados',
            text: 'Los datos aún no se han cargado completamente. Por favor, espera unos segundos.',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#2c3e50'
        });
    }
}

// Función auxiliar para capitalizar la primera letra
function capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function resetearFiltros() {
    // Resetear UI
    document.querySelectorAll('.icon-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    const select = document.getElementById('filtro-tipo');
    Array.from(select.options).forEach(option => {
        option.selected = false;
    });
    
    $('#filtro-comunidad').val('').trigger('change');
    $('#filtro-provincia').val('').prop('disabled', true).trigger('change');
    
    // Limpiar el mapa pero NO centrar en España
    markers.clearLayers();
    actualizarLeyendaYEstadisticas();
    
    Swal.fire({
        icon: 'success',
        title: 'Filtros reiniciados',
        text: 'Todos los filtros han sido restablecidos',
        showConfirmButton: false,
        timer: 1500
    });
}

// Evento DOMContentLoaded unificado 
document.addEventListener('DOMContentLoaded', () => {
    inicializarFiltros();
    inicializarFiltrosIconos();

    const toggleLeyenda = document.getElementById('toggle-leyenda');
    const panelLeyenda = document.querySelector('.panel-leyenda');

    if (toggleLeyenda && panelLeyenda) {
        toggleLeyenda.addEventListener('click', function(e) {
            e.stopPropagation();
            panelLeyenda.classList.toggle('abierto');
            
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-layer-group');
            icon.classList.toggle('fa-times');
        });
    }
});

/**
 * 🗺️ Lógica de Deep Linking: Selección de Energía y Carga Nacional
 */
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const energiaURL = urlParams.get('energia')?.toLowerCase();

    if (energiaURL) {
        console.log("🛠️ Procesando enlace para:", energiaURL);

        // ⏳ 1. Esperar a que la data esté cargada en memoria para evitar errores de carreras
        const wait_data = setInterval(() => {
            if (typeof instalaciones !== 'undefined' && instalaciones.length > 0) {
                clearInterval(wait_data);
                console.log("✅ Datos listos. Aplicando filtro nacional para:", energiaURL);

                // 2. Marcar el icono visualmente (Efecto .selected)
                const icono = document.querySelector(`.icon-option[data-value="${energiaURL}"]`);
                if (icono) icono.classList.add('selected');

                // 3. Sincronizar el <select> oculto para que funcione cargarTodasComunidades()
                const selectElement = document.getElementById('filtro-tipo');
                if (selectElement) {
                    const option = selectElement.querySelector(`option[value="${energiaURL}"]`);
                    if (option) option.selected = true;
                    // Forzar el trigger para cualquier listener conectado
                    $(selectElement).trigger('change'); 
                }

                // 4. Ejecutar la función nativa que ya tienes para filtrar Toda España!
                if (typeof cargarTodasComunidades === 'function') {
                    // Esperar 200ms adicionales para que Select2 digiera el cambio
                    setTimeout(() => {
                        cargarTodasComunidades();
                    }, 200);
                }
            }
        }, 300); // Reintentar cada 300ms
    }
});