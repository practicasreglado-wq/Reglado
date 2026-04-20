var datosCargados = {
    eolica: null,
    solar: null,
    subestaciones: null,
    biometano: null,
    hidrogeno: null,
    hidraulica: null,
    biodiesel: null
};

var HOSTINGER_API_URL = 'https://regladoconsultores.com/mapa/backend/php/api.php';

function cargarTipo(tipo) {
    if (datosCargados[tipo]) return Promise.resolve(datosCargados[tipo]);

    console.log(`=== INICIO CARGA ${tipo.toUpperCase()} ===`);
    const url = `${HOSTINGER_API_URL}?tipo=${encodeURIComponent(tipo)}`;

    return fetch(url)
        .then(response => {
            if (!response.ok) throw new Error(`Error al cargar datos para ${tipo}: ${response.status}`);
            return response.json();
        })
        .then(data => {
            const datosNormalizados = data.map(inst => {
                const lat = parseFloat(inst.lat || inst.Latitud);
                const lng = parseFloat(inst.lng || inst.Longitud);
                return {
                    ...inst,
                    tipo: tipo === 'subestaciones' ? 'subestacion' : tipo,
                    comunidad: inst.ComunidadAutonoma || inst.comunidadAutonoma || inst.comunidad,
                    nombre: inst.Nombre || inst.nombre || 'Sin nombre',
                    capacidad: parseFloat(inst.Capacidad) || 0,
                    lat: isNaN(lat) ? null : lat,
                    lng: isNaN(lng) ? null : lng
                };
            });

            const datosValidos = datosNormalizados.filter(inst =>
                inst.lat !== null && inst.lng !== null &&
                !isNaN(inst.lat) && !isNaN(inst.lng)
            );

            console.log(`Registros válidos: ${datosValidos.length}/${data.length}`);
            datosCargados[tipo] = datosValidos;
            return datosValidos;
        })
        .catch(error => {
            console.error(`Error al cargar tipo ${tipo}:`, error);
            throw error;
        });
}

function cargarDatosIniciales() {
    Swal.fire({
        title: 'Cargando datos',
        html: 'Por favor espera mientras se cargan los datos del mapa...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    Promise.all([
        cargarTipo('eolica'),
        cargarTipo('solar'),
        cargarTipo('subestaciones'),
        cargarTipo('biometano'),
        cargarTipo('hidrogeno'),
        cargarTipo('hidraulica'),
        cargarTipo('biodiesel')
    ])
    .then(datos => {
        const todosDatos = [].concat(...datos);
        console.log("TOTAL GENERAL DE INSTALACIONES:", todosDatos.length);

        if (window.inicializarMapaConDatos) {
            window.inicializarMapaConDatos(todosDatos);
        }

        Swal.fire({
            icon: 'success',
            title: 'Datos cargados',
            text: `Se han cargado ${todosDatos.length} instalaciones`,
            showConfirmButton: false,
            timer: 2000
        });
    })
    .catch(error => {
        console.error('Error al cargar datos:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error al cargar datos',
            text: error.message,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#2c3e50'
        });
    });
}

window.iniciarCargaDatos = cargarDatosIniciales;
