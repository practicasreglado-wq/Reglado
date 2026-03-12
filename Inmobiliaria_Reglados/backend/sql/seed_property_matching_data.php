<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';

mt_srand(20260312);

$catalog = [
    'Hoteles' => [
        'image' => 'hotel.png',
        'locations' => ['Madrid', 'Barcelona', 'Costa del Sol', 'Valencia', 'Baleares', 'Canarias', 'Sevilla', 'Málaga'],
        'priceRange' => [7500000, 58000000],
        'metersRange' => [3200, 18500],
        'titles' => [
            'Hotel Skyline Prime', 'Resort Mediterraneo Select', 'Urban Stay Signature', 'Grand Harbor Collection',
            'Boutique Palace One', 'Airport Suites Hub', 'Resort Blue Horizon', 'City Wave Hotel',
            'Mountain Retreat Spa', 'Business Connect Hotel', 'Coastal Family Resort', 'Luxury Heritage Stay',
            'Sunset Bay Club', 'Central Living Suites', 'Atlantic Wellness Resort',
        ],
        'questions' => [
            'q1' => ['3 estrellas', '4 estrellas', '5 estrellas', '5 estrellas GL / lujo'],
            'q2' => ['Menos de 50', '50 a 100', '101 a 200', 'Más de 200'],
            'q3' => ['Costa', 'Centro urbano', 'Periferia / aeropuerto', 'Entorno rural o montaña'],
            'q4' => ['Operativo con operador', 'Operativo libre de operador', 'Necesita reforma', 'Proyecto / reposicionamiento'],
            'q5' => ['Más del 4%', 'Más del 5,5%', 'Más del 7%', 'Más del 8,5%'],
            'q6' => ['Hasta 5 M€', '5 M€ a 15 M€', '15 M€ a 50 M€', 'Más de 50 M€'],
            'q7' => ['Vacacional', 'Corporativo', 'Boutique / lujo', 'Mixto'],
            'q8' => ['Gestión hotelera', 'Arrendamiento', 'Franquicia', 'Libre para operar'],
            'q9' => ['Activo listo para operar', 'Reforma ligera', 'Reforma integral', 'Conversión / cambio de uso'],
            'q10' => ['Más del 75%', '60% a 75%', '40% a 59%', 'Acepto reposicionar desde baja ocupación'],
            'q11' => ['Sin contrato, activo libre', '3 a 5 años', '5 a 10 años', 'Más de 10 años'],
            'q12' => ['Solo vacacional', 'Solo urbano', 'Ambos', 'Resort / lifestyle'],
            'q13' => ['Spa / wellness', 'Salas de eventos', 'Piscina', 'Restauración destacada'],
            'q14' => ['Obra nueva o reciente', '5 a 15 años', 'Más de 15 años actualizado', 'Histórico con valor añadido'],
            'q15' => ['Pleno dominio', 'Leasehold', 'Concesión', 'Joint venture'],
            'q16' => ['Core', 'Core Plus', 'Value Add', 'Oportunista'],
            'q17' => ['Imprescindible', 'Muy valorado', 'Solo en ubicaciones concretas', 'No prioritario'],
            'q18' => ['Imprescindible', 'Muy valorada', 'Deseable tras reforma', 'Indiferente'],
            'q19' => ['Solo 12 meses', 'Acepto estacionalidad moderada', 'Acepto alta estacionalidad', 'Indiferente'],
            'q20' => ['1 a 3 años', '3 a 5 años', '5 a 10 años', 'Más de 10 años'],
        ],
    ],
    'Fincas' => [
        'image' => 'finca.png',
        'locations' => ['Sevilla', 'Córdoba', 'Jaén', 'Badajoz', 'Navarra', 'Salamanca', 'Murcia', 'Valencia'],
        'priceRange' => [380000, 6800000],
        'metersRange' => [180000, 4200000],
        'titles' => [
            'Finca Vega Norte', 'Cortijo Sierra Alta', 'Estate Encinar Real', 'Olivar Premium Sur',
            'Finca Solar Verde', 'Vega Ecologica Prime', 'Dehesa Patrimonial', 'Agro Hub Levante',
            'Retiro Cinegetico', 'Huerta Productiva', 'Finca Regadio Select', 'Finca Ganadera One',
            'Estate Rural Collection', 'Campo de Inversion', 'Reserva Campestre',
        ],
        'questions' => [
            'q1' => ['Agrícola de regadío', 'Agrícola de secano', 'Ganadera', 'Recreo / cinegética'],
            'q2' => ['Hasta 20 ha', '20 a 100 ha', '101 a 300 ha', 'Más de 300 ha'],
            'q3' => ['Hasta 500.000 €', '500.000 € a 2 M€', '2 M€ a 5 M€', 'Más de 5 M€'],
            'q4' => ['Derechos de riego', 'Pozo legalizado', 'Embalse / río cercano', 'No es imprescindible'],
            'q5' => ['Explotación agrícola', 'Explotación ganadera', 'Recreo / lujo', 'Inversión mixta'],
            'q6' => ['Menos de 30 min', '30 a 60 min', 'Más de 60 min', 'Aislada si tiene valor estratégico'],
            'q7' => ['Llano', 'Ondulado', 'Mixto', 'Escarpado'],
            'q8' => ['Totalmente mecanizable', 'Mecanización parcial', 'Tradicional', 'Indiferente'],
            'q9' => ['En plena producción', 'Plantación joven', 'Preparada para desarrollar', 'Para reconversión'],
            'q10' => ['2% a 4%', '4% a 6%', '6% a 8%', 'Más del 8%'],
            'q11' => ['Sí, con renta actual', 'No, libre para gestión propia', 'Acepto ambas', 'Solo con operador solvente'],
            'q12' => ['Casa principal', 'Naves / almacenes', 'Vivienda de guardeses', 'No son necesarias'],
            'q13' => ['Imprescindible certificada', 'Apta para conversión', 'Deseable', 'Indiferente'],
            'q14' => ['Acceso asfaltado', 'Camino en buen estado', 'Acceso para maquinaria', 'No prioritario'],
            'q15' => ['Sí, como foco principal', 'Sí, como complemento', 'Solo si no interfiere', 'No'],
            'q16' => ['Imprescindible', 'Muy valorado', 'Se puede ejecutar después', 'No prioritario'],
            'q17' => ['Conexión a red', 'Autonomía solar válida', 'Ambas opciones', 'No necesario'],
            'q18' => ['1 a 3 años', '3 a 7 años', 'Más de 7 años', 'Patrimonial a largo plazo'],
            'q19' => ['Imprescindibles', 'Muy valorados', 'Deseables', 'Indiferente'],
            'q20' => ['Conservador', 'Moderado', 'Value Add', 'Oportunista'],
        ],
    ],
    'Parking' => [
        'image' => 'parking.png',
        'locations' => ['Madrid', 'Barcelona', 'Valencia', 'Sevilla', 'Málaga', 'Bilbao', 'Alicante', 'Cádiz'],
        'priceRange' => [650000, 9800000],
        'metersRange' => [900, 19000],
        'titles' => [
            'Parking Centro Prime', 'AeroPark Hub', 'Parking Residencial Mar', 'Campus Park Select',
            'Smart Parking One', 'Clinico Parking', 'Puerto Rotation Park', 'Lote Chamberi',
            'Eco Mobility Station', 'Parking Levante Hub', 'Urban Park Signature', 'Terminal Parking Plus',
            'Hospitality Parking Core', 'Metro Park Connect', 'Residential Parking Select',
        ],
        'questions' => [
            'q1' => ['Subterráneo', 'En superficie', 'Edificio de aparcamiento', 'Lote de plazas'],
            'q2' => ['Rotación', 'Abonos', 'Venta de plazas', 'Mixto'],
            'q3' => ['50 a 100', '101 a 250', '251 a 500', 'Más de 500'],
            'q4' => ['Centro urbano', 'Aeropuerto / estación', 'Hospital / campus', 'Zona residencial densa'],
            'q5' => ['Más del 5%', 'Más del 6%', 'Más del 7%', 'Más del 8%'],
            'q6' => ['Hasta 1 M€', '1 M€ a 3 M€', '3 M€ a 10 M€', 'Más de 10 M€'],
            'q7' => ['Pleno dominio', 'Concesión municipal', 'Arrendamiento a largo plazo', 'Indiferente'],
            'q8' => ['Más de 20 años', '10 a 20 años', '5 a 10 años', 'No invierto en concesiones'],
            'q9' => ['Más del 85%', '60% a 85%', 'Menos del 60% con potencial', 'Activo por estabilizar'],
            'q10' => ['Reformado', 'Buen estado', 'Mejoras ligeras', 'Reforma integral'],
            'q11' => ['Básica', 'Avanzada', 'Totalmente digital', 'No prioritaria'],
            'q12' => ['Imprescindibles', 'Necesarios por normativa', 'Instalables tras compra', 'No prioritarios'],
            'q13' => ['Operación propia', 'Arrendado a operador', 'Management externo', 'Indiferente'],
            'q14' => ['Usuarios de rotación', 'Abonados recurrentes', 'Flotas / empresas', 'Mixto'],
            'q15' => ['Premium / SUV', 'Estándar cómoda', 'Acepto plazas ajustadas', 'Depende del descuento'],
            'q16' => ['Imprescindible al día', 'Muy valorada', 'Acepto adecuación posterior', 'No prioritaria'],
            'q17' => ['Lavado / detailing', 'Lockers / paquetería', 'Publicidad / retail', 'No'],
            'q18' => ['Muy baja', 'Moderada', 'Alta si la zona es buena', 'Oportunista'],
            'q19' => ['Patrimonial', 'Operador', 'Institucional', 'Family office'],
            'q20' => ['Core', 'Core Plus', 'Value Add', 'Oportunista'],
        ],
    ],
    'Edificios' => [
        'image' => 'edificios.png',
        'locations' => ['Madrid', 'Barcelona', 'Valencia', 'Sevilla', 'Málaga', 'Bilbao', 'Granada', 'Zaragoza'],
        'priceRange' => [4200000, 62000000],
        'metersRange' => [1800, 15000],
        'titles' => [
            'Edificio Castellana Prime', 'Residencial Centro Select', 'Mixed Use Hub', 'Building Reposition One',
            'Rooftop Living', 'Office Park Plus', 'Retail Core Building', 'Green Retrofit Tower',
            'Student House Central', 'CBD Trophy Asset', 'Patio Living Center', 'Prime Offices Collection',
            'Urban Flex Building', 'Residence Hub Signature', 'Blue Tower Select',
        ],
        'questions' => [
            'q1' => ['Residencial', 'Oficinas', 'Mixto', 'Comercial'],
            'q2' => ['Hasta 5 M€', '5 M€ a 20 M€', '20 M€ a 50 M€', 'Más de 50 M€'],
            'q3' => ['Prime / CBD', 'Centro consolidado', 'Periferia urbana', 'Zona en regeneración'],
            'q4' => ['100% ocupado', 'Parcialmente ocupado', 'Vacío para reposicionar', 'Indiferente'],
            'q5' => ['Hasta 2.000 m²', '2.000 a 5.000 m²', '5.000 a 10.000 m²', 'Más de 10.000 m²'],
            'q6' => ['Más del 4%', 'Más del 5,5%', 'Más del 7%', 'Más del 8,5%'],
            'q7' => ['Listo para operar', 'Mejora ligera', 'Rehabilitación integral', 'Cambio de uso / reposicionamiento'],
            'q8' => ['Alquiler tradicional', 'Flex / temporal', 'Coliving / estudiantes', 'Venta / reposición'],
            'q9' => ['Imprescindible', 'Muy valorado', 'Deseable', 'No prioritario'],
            'q10' => ['Vertical único', 'División horizontal hecha', 'Indiferente', 'Depende del business plan'],
            'q11' => ['Rentas estabilizadas', 'Mejora moderada', 'Alta reversión', 'Value Add agresivo'],
            'q12' => ['Sí, imprescindibles', 'Sí, como plus', 'Solo si no genera conflicto', 'No'],
            'q13' => ['Certificación alta actual', 'Potencial brown-to-green', 'Deseable', 'Indiferente'],
            'q14' => ['Sin protección', 'Fachada protegida', 'Protección media', 'Protección integral'],
            'q15' => ['Sin incidencias', 'Acepto rentas antiguas', 'Acepto complejidad con descuento', 'Solo casos muy controlados'],
            'q16' => ['Menos de 3 años', '3 a 5 años', '5 a 7 años', 'Más de 7 años'],
            'q17' => ['Terrazas / rooftop', 'Patio interior', 'Zonas comunes', 'No prioritarios'],
            'q18' => ['100% equity', '40% a 60% LTV', 'Más del 60% LTV', 'Flexible según operación'],
            'q19' => ['1 a 3 años', '3 a 5 años', '5 a 10 años', 'Más de 10 años'],
            'q20' => ['Core', 'Core Plus', 'Value Add', 'Oportunista'],
        ],
    ],
    'Activos' => [
        'image' => 'activos.png',
        'locations' => ['Madrid / Barcelona', 'Capitales y costa prime', 'Valencia', 'Sevilla', 'Málaga', 'Nacional', 'Ibérico', 'Barcelona'],
        'priceRange' => [8200000, 110000000],
        'metersRange' => [0, 14000],
        'titles' => [
            'Portfolio REO Prime', 'NPL Iberia Secured', 'Healthcare Core Plus', 'Suelo BTR Select',
            'Sale Leaseback Retail', 'Distressed Land Platform', 'PBSA Urban Growth', 'Credit Opportunities',
            'Data Center Edge', 'Residential REO Lift', 'Alternative Asset Hub', 'Value Add Collection',
            'Strategic Debt Portfolio', 'Urban Growth Platform', 'Multistrategy Opportunity',
        ],
        'questions' => [
            'q1' => ['REOs', 'NPLs', 'Activos alternativos', 'Suelo / deuda inmobiliaria'],
            'q2' => ['Hasta 10 M€', '10 M€ a 50 M€', '50 M€ a 100 M€', 'Más de 100 M€'],
            'q3' => ['Core', 'Core Plus', 'Value Add', 'Oportunista'],
            'q4' => ['Madrid / Barcelona', 'Capitales y costa prime', 'Diversificación nacional', 'Internacional / ibérico'],
            'q5' => ['Secured', 'Unsecured', 'Mixta', 'No aplica'],
            'q6' => ['Residencial', 'Comercial', 'Suelo', 'Hoteles / alternativos'],
            'q7' => ['Muy alto', 'Solo con covenant fuerte', 'Caso por caso', 'No es foco'],
            'q8' => ['Activo estabilizado', 'Conversión / desarrollo', 'Me interesa selectivamente', 'No'],
            'q9' => ['Build to Sell', 'Build to Rent', 'Terciario / dotacional', 'No invierto en suelo'],
            'q10' => ['Temprana', 'Intermedia', 'Finalista', 'Solo activo terminado'],
            'q11' => ['1 a 3 años', '3 a 5 años', '5 a 10 años', 'Largo plazo'],
            'q12' => ['Sí, activamente', 'Solo de forma selectiva', 'No', 'Depende del mandato'],
            'q13' => ['Procesos institucionales', 'Off-market selectivo', 'Ambos', 'No prioritario'],
            'q14' => ['Data centers', 'Healthcare', 'Residencias / PBSA', 'Retail parks / logística'],
            'q15' => ['Mandato estricto', 'Brown-to-green', 'Deseable', 'Indiferente'],
            'q16' => ['Sí, activamente', 'Solo oportunidades claras', 'Muy selectivo', 'No'],
            'q17' => ['Single asset', 'Portfolio pequeño', 'Portfolio masivo', 'Indiferente'],
            'q18' => ['Muy baja', 'Moderada', 'Alta con descuento', 'Alta si es prime'],
            'q19' => ['Asset deal', 'Share deal', 'Flexible', 'SPV dedicada'],
            'q20' => ['Rotación rápida', 'Value Add medio plazo', 'Patrimonial', 'Multiestrategia'],
        ],
    ],
];

$insert = $pdo->prepare('
    INSERT INTO propiedades (
        categoria,
        titulo,
        ubicacion_general,
        precio,
        metros_cuadrados,
        imagen_principal,
        caracteristicas_json,
        owner_user_id
    ) VALUES (
        :categoria,
        :titulo,
        :ubicacion_general,
        :precio,
        :metros_cuadrados,
        :imagen_principal,
        :caracteristicas_json,
        NULL
    )
');

$pdo->beginTransaction();

try {
    $pdo->exec('DELETE FROM propiedades_favoritas');
    $pdo->exec('DELETE FROM propiedades WHERE owner_user_id IS NULL');

    foreach ($catalog as $category => $config) {
        for ($i = 0; $i < 15; $i++) {
            $characteristics = [];
            $profileIndex = $i % 4;

            foreach ($config['questions'] as $key => $options) {
                $useProfileOption = random_int(1, 100) <= 68;
                $characteristics[$key] = $useProfileOption
                    ? $options[$profileIndex]
                    : $options[array_rand($options)];
            }

            $insert->execute([
                'categoria' => $category,
                'titulo' => $config['titles'][$i],
                'ubicacion_general' => $config['locations'][array_rand($config['locations'])],
                'precio' => random_int($config['priceRange'][0], $config['priceRange'][1]),
                'metros_cuadrados' => random_int($config['metersRange'][0], $config['metersRange'][1]),
                'imagen_principal' => $config['image'],
                'caracteristicas_json' => json_encode($characteristics, JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    $pdo->commit();
    echo "Seeder completado: 75 propiedades generadas.\n";
} catch (Throwable $exception) {
    $pdo->rollBack();
    fwrite(STDERR, "Error al generar propiedades: " . $exception->getMessage() . PHP_EOL);
    exit(1);
}
