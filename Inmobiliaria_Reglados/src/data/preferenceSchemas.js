const preferenceSchemas = {
  Hoteles: {
    title: "Hoteles",
    questions: [
      { key: "q1", prompt: "¿Qué categoría hotelera busca?", summaryLabel: "Categoría hotelera", options: ["3 estrellas", "4 estrellas", "5 estrellas", "5 estrellas GL / lujo"] },
      { key: "q2", prompt: "¿Cuál es el tamaño mínimo del hotel?", summaryLabel: "Número mínimo de habitaciones", options: ["Menos de 50", "50 a 100", "101 a 200", "Más de 200"] },
      { key: "q3", prompt: "¿Qué ubicación prefiere?", summaryLabel: "Ubicación preferida", options: ["Costa", "Centro urbano", "Periferia / aeropuerto", "Entorno rural o montaña"] },
      { key: "q4", prompt: "¿Qué situación operativa prefiere?", summaryLabel: "Estado del activo", options: ["Operativo con operador", "Operativo libre de operador", "Necesita reforma", "Proyecto / reposicionamiento"] },
      { key: "q5", prompt: "¿Qué rentabilidad mínima espera?", summaryLabel: "Rentabilidad mínima", options: ["Más del 4%", "Más del 5,5%", "Más del 7%", "Más del 8,5%"] },
      { key: "q6", prompt: "¿Qué ticket de inversión contempla?", summaryLabel: "Ticket de inversión", options: ["Hasta 5 M€", "5 M€ a 15 M€", "15 M€ a 50 M€", "Más de 50 M€"] },
      { key: "q7", prompt: "¿Qué segmento de demanda le interesa más?", summaryLabel: "Segmento objetivo", options: ["Vacacional", "Corporativo", "Boutique / lujo", "Mixto"] },
      { key: "q8", prompt: "¿Qué modelo contractual acepta?", summaryLabel: "Modelo de explotación", options: ["Gestión hotelera", "Arrendamiento", "Franquicia", "Libre para operar"] },
      { key: "q9", prompt: "¿Qué nivel de reforma acepta?", summaryLabel: "Nivel de CAPEX aceptado", options: ["Activo listo para operar", "Reforma ligera", "Reforma integral", "Conversión / cambio de uso"] },
      { key: "q10", prompt: "¿Qué nivel de ocupación considera aceptable?", summaryLabel: "Ocupación mínima aceptable", options: ["Más del 75%", "60% a 75%", "40% a 59%", "Acepto reposicionar desde baja ocupación"] },
      { key: "q11", prompt: "¿Qué duración mínima de contrato le interesa si hay operador?", summaryLabel: "Duración contractual mínima", options: ["Sin contrato, activo libre", "3 a 5 años", "5 a 10 años", "Más de 10 años"] },
      { key: "q12", prompt: "¿Le interesan activos con componente vacacional?", summaryLabel: "Orientación del activo", options: ["Solo vacacional", "Solo urbano", "Ambos", "Resort / lifestyle"] },
      { key: "q13", prompt: "¿Qué servicios considera clave?", summaryLabel: "Servicios prioritarios", options: ["Spa / wellness", "Salas de eventos", "Piscina", "Restauración destacada"] },
      { key: "q14", prompt: "¿Qué antigüedad del activo prefiere?", summaryLabel: "Antigüedad del activo", options: ["Obra nueva o reciente", "5 a 15 años", "Más de 15 años actualizado", "Histórico con valor añadido"] },
      { key: "q15", prompt: "¿Qué estructura de tenencia prefiere?", summaryLabel: "Régimen de tenencia", options: ["Pleno dominio", "Leasehold", "Concesión", "Joint venture"] },
      { key: "q16", prompt: "¿Qué estrategia busca?", summaryLabel: "Estrategia inversora", options: ["Core", "Core Plus", "Value Add", "Oportunista"] },
      { key: "q17", prompt: "¿Qué importancia da al aparcamiento?", summaryLabel: "Necesidad de aparcamiento", options: ["Imprescindible", "Muy valorado", "Solo en ubicaciones concretas", "No prioritario"] },
      { key: "q18", prompt: "¿Le interesan certificaciones ESG?", summaryLabel: "Certificación sostenible", options: ["Imprescindible", "Muy valorada", "Deseable tras reforma", "Indiferente"] },
      { key: "q19", prompt: "¿Acepta estacionalidad?", summaryLabel: "Tolerancia a la estacionalidad", options: ["Solo 12 meses", "Acepto estacionalidad moderada", "Acepto alta estacionalidad", "Indiferente"] },
      { key: "q20", prompt: "¿Qué horizonte de inversión maneja?", summaryLabel: "Horizonte de inversión", options: ["1 a 3 años", "3 a 5 años", "5 a 10 años", "Más de 10 años"] },
    ],
  },
  Fincas: {
    title: "Fincas",
    questions: [
      { key: "q1", prompt: "¿Qué tipo de finca busca?", summaryLabel: "Tipología de finca", options: ["Agrícola de regadío", "Agrícola de secano", "Ganadera", "Recreo / cinegética"] },
      { key: "q2", prompt: "¿Qué superficie mínima necesita?", summaryLabel: "Superficie mínima", options: ["Hasta 20 ha", "20 a 100 ha", "101 a 300 ha", "Más de 300 ha"] },
      { key: "q3", prompt: "¿Qué presupuesto contempla?", summaryLabel: "Presupuesto objetivo", options: ["Hasta 500.000 €", "500.000 € a 2 M€", "2 M€ a 5 M€", "Más de 5 M€"] },
      { key: "q4", prompt: "¿Qué disponibilidad de agua necesita?", summaryLabel: "Disponibilidad de agua", options: ["Derechos de riego", "Pozo legalizado", "Embalse / río cercano", "No es imprescindible"] },
      { key: "q5", prompt: "¿Qué uso principal quiere dar a la finca?", summaryLabel: "Uso principal", options: ["Explotación agrícola", "Explotación ganadera", "Recreo / lujo", "Inversión mixta"] },
      { key: "q6", prompt: "¿Qué cercanía a núcleo urbano prefiere?", summaryLabel: "Cercanía a ciudad", options: ["Menos de 30 min", "30 a 60 min", "Más de 60 min", "Aislada si tiene valor estratégico"] },
      { key: "q7", prompt: "¿Qué orografía prefiere?", summaryLabel: "Orografía del terreno", options: ["Llano", "Ondulado", "Mixto", "Escarpado"] },
      { key: "q8", prompt: "¿Qué nivel de mecanización necesita?", summaryLabel: "Nivel de mecanización", options: ["Totalmente mecanizable", "Mecanización parcial", "Tradicional", "Indiferente"] },
      { key: "q9", prompt: "¿Qué estado productivo le interesa?", summaryLabel: "Estado productivo", options: ["En plena producción", "Plantación joven", "Preparada para desarrollar", "Para reconversión"] },
      { key: "q10", prompt: "¿Qué rentabilidad anual objetivo busca?", summaryLabel: "Rentabilidad objetivo", options: ["2% a 4%", "4% a 6%", "6% a 8%", "Más del 8%"] },
      { key: "q11", prompt: "¿Quiere la finca arrendada actualmente?", summaryLabel: "Situación de arrendamiento", options: ["Sí, con renta actual", "No, libre para gestión propia", "Acepto ambas", "Solo con operador solvente"] },
      { key: "q12", prompt: "¿Qué nivel de edificaciones necesita?", summaryLabel: "Edificaciones existentes", options: ["Casa principal", "Naves / almacenes", "Vivienda de guardeses", "No son necesarias"] },
      { key: "q13", prompt: "¿Le interesa producción ecológica?", summaryLabel: "Producción ecológica", options: ["Imprescindible certificada", "Apta para conversión", "Deseable", "Indiferente"] },
      { key: "q14", prompt: "¿Qué accesibilidad por carretera exige?", summaryLabel: "Accesibilidad", options: ["Acceso asfaltado", "Camino en buen estado", "Acceso para maquinaria", "No prioritario"] },
      { key: "q15", prompt: "¿Le interesan usos energéticos renovables?", summaryLabel: "Potencial renovable", options: ["Sí, como foco principal", "Sí, como complemento", "Solo si no interfiere", "No"] },
      { key: "q16", prompt: "¿Qué importancia da al vallado perimetral?", summaryLabel: "Vallado perimetral", options: ["Imprescindible", "Muy valorado", "Se puede ejecutar después", "No prioritario"] },
      { key: "q17", prompt: "¿Necesita suministro eléctrico?", summaryLabel: "Suministro eléctrico", options: ["Conexión a red", "Autonomía solar válida", "Ambas opciones", "No necesario"] },
      { key: "q18", prompt: "¿Qué horizonte de inversión contempla?", summaryLabel: "Horizonte de inversión", options: ["1 a 3 años", "3 a 7 años", "Más de 7 años", "Patrimonial a largo plazo"] },
      { key: "q19", prompt: "¿Qué nivel de subvenciones o PAC espera?", summaryLabel: "Derechos PAC / subvenciones", options: ["Imprescindibles", "Muy valorados", "Deseables", "Indiferente"] },
      { key: "q20", prompt: "¿Qué perfil de riesgo asume?", summaryLabel: "Perfil de riesgo", options: ["Conservador", "Moderado", "Value Add", "Oportunista"] },
    ],
  },
  Parking: {
    title: "Parking",
    questions: [
      { key: "q1", prompt: "¿Qué tipo de parking busca?", summaryLabel: "Tipología de parking", options: ["Subterráneo", "En superficie", "Edificio de aparcamiento", "Lote de plazas"] },
      { key: "q2", prompt: "¿Qué modelo de negocio prefiere?", summaryLabel: "Modelo de negocio", options: ["Rotación", "Abonos", "Venta de plazas", "Mixto"] },
      { key: "q3", prompt: "¿Cuál es el número mínimo de plazas?", summaryLabel: "Número mínimo de plazas", options: ["50 a 100", "101 a 250", "251 a 500", "Más de 500"] },
      { key: "q4", prompt: "¿Qué ubicación le interesa más?", summaryLabel: "Ubicación preferida", options: ["Centro urbano", "Aeropuerto / estación", "Hospital / campus", "Zona residencial densa"] },
      { key: "q5", prompt: "¿Qué rentabilidad mínima busca?", summaryLabel: "Rentabilidad mínima", options: ["Más del 5%", "Más del 6%", "Más del 7%", "Más del 8%"] },
      { key: "q6", prompt: "¿Qué ticket de inversión maneja?", summaryLabel: "Ticket de inversión", options: ["Hasta 1 M€", "1 M€ a 3 M€", "3 M€ a 10 M€", "Más de 10 M€"] },
      { key: "q7", prompt: "¿Qué régimen jurídico prefiere?", summaryLabel: "Régimen de propiedad", options: ["Pleno dominio", "Concesión municipal", "Arrendamiento a largo plazo", "Indiferente"] },
      { key: "q8", prompt: "Si es concesión, ¿qué vida residual mínima acepta?", summaryLabel: "Duración residual mínima", options: ["Más de 20 años", "10 a 20 años", "5 a 10 años", "No invierto en concesiones"] },
      { key: "q9", prompt: "¿Qué nivel de ocupación actual prefiere?", summaryLabel: "Ocupación actual", options: ["Más del 85%", "60% a 85%", "Menos del 60% con potencial", "Activo por estabilizar"] },
      { key: "q10", prompt: "¿Qué estado del activo acepta?", summaryLabel: "Estado de las instalaciones", options: ["Reformado", "Buen estado", "Mejoras ligeras", "Reforma integral"] },
      { key: "q11", prompt: "¿Qué nivel de automatización exige?", summaryLabel: "Automatización", options: ["Básica", "Avanzada", "Totalmente digital", "No prioritaria"] },
      { key: "q12", prompt: "¿Qué importancia da a los cargadores eléctricos?", summaryLabel: "Puntos de recarga", options: ["Imprescindibles", "Necesarios por normativa", "Instalables tras compra", "No prioritarios"] },
      { key: "q13", prompt: "¿Qué forma de gestión prefiere tras la compra?", summaryLabel: "Gestión del activo", options: ["Operación propia", "Arrendado a operador", "Management externo", "Indiferente"] },
      { key: "q14", prompt: "¿Qué perfil de cliente quiere captar?", summaryLabel: "Cliente objetivo", options: ["Usuarios de rotación", "Abonados recurrentes", "Flotas / empresas", "Mixto"] },
      { key: "q15", prompt: "¿Qué anchura y maniobrabilidad de plaza exige?", summaryLabel: "Calidad de plazas", options: ["Premium / SUV", "Estándar cómoda", "Acepto plazas ajustadas", "Depende del descuento"] },
      { key: "q16", prompt: "¿Qué importancia da a la adaptación PMR?", summaryLabel: "Adaptación normativa", options: ["Imprescindible al día", "Muy valorada", "Acepto adecuación posterior", "No prioritaria"] },
      { key: "q17", prompt: "¿Busca ingresos complementarios?", summaryLabel: "Ingresos adicionales", options: ["Lavado / detailing", "Lockers / paquetería", "Publicidad / retail", "No"] },
      { key: "q18", prompt: "¿Qué tolerancia tiene al riesgo competitivo?", summaryLabel: "Riesgo competitivo", options: ["Muy baja", "Moderada", "Alta si la zona es buena", "Oportunista"] },
      { key: "q19", prompt: "¿Qué perfil inversor encaja mejor?", summaryLabel: "Perfil inversor", options: ["Patrimonial", "Operador", "Institucional", "Family office"] },
      { key: "q20", prompt: "¿Qué estrategia de inversión sigue?", summaryLabel: "Estrategia inversora", options: ["Core", "Core Plus", "Value Add", "Oportunista"] },
    ],
  },
  Edificios: {
    title: "Edificios",
    questions: [
      { key: "q1", prompt: "¿Qué uso principal busca para el edificio?", summaryLabel: "Uso principal", options: ["Residencial", "Oficinas", "Mixto", "Comercial"] },
      { key: "q2", prompt: "¿Qué ticket de inversión contempla?", summaryLabel: "Ticket de inversión", options: ["Hasta 5 M€", "5 M€ a 20 M€", "20 M€ a 50 M€", "Más de 50 M€"] },
      { key: "q3", prompt: "¿Qué ubicación le interesa?", summaryLabel: "Ubicación preferida", options: ["Prime / CBD", "Centro consolidado", "Periferia urbana", "Zona en regeneración"] },
      { key: "q4", prompt: "¿Qué nivel de ocupación prefiere?", summaryLabel: "Nivel de ocupación", options: ["100% ocupado", "Parcialmente ocupado", "Vacío para reposicionar", "Indiferente"] },
      { key: "q5", prompt: "¿Qué superficie aproximada busca?", summaryLabel: "Superficie objetivo", options: ["Hasta 2.000 m²", "2.000 a 5.000 m²", "5.000 a 10.000 m²", "Más de 10.000 m²"] },
      { key: "q6", prompt: "¿Qué rentabilidad mínima requiere?", summaryLabel: "Rentabilidad mínima", options: ["Más del 4%", "Más del 5,5%", "Más del 7%", "Más del 8,5%"] },
      { key: "q7", prompt: "¿Qué estado técnico acepta?", summaryLabel: "Estado técnico", options: ["Listo para operar", "Mejora ligera", "Rehabilitación integral", "Cambio de uso / reposicionamiento"] },
      { key: "q8", prompt: "¿Qué estrategia residencial o de uso valora más?", summaryLabel: "Modelo de explotación", options: ["Alquiler tradicional", "Flex / temporal", "Coliving / estudiantes", "Venta / reposición"] },
      { key: "q9", prompt: "¿Qué importancia da al aparcamiento?", summaryLabel: "Aparcamiento", options: ["Imprescindible", "Muy valorado", "Deseable", "No prioritario"] },
      { key: "q10", prompt: "¿Prefiere el edificio dividido horizontalmente?", summaryLabel: "Situación registral", options: ["Vertical único", "División horizontal hecha", "Indiferente", "Depende del business plan"] },
      { key: "q11", prompt: "¿Qué potencial de subida de rentas busca?", summaryLabel: "Potencial de reversión", options: ["Rentas estabilizadas", "Mejora moderada", "Alta reversión", "Value Add agresivo"] },
      { key: "q12", prompt: "¿Le interesan locales comerciales en planta baja?", summaryLabel: "Retail en planta baja", options: ["Sí, imprescindibles", "Sí, como plus", "Solo si no genera conflicto", "No"] },
      { key: "q13", prompt: "¿Qué nivel ESG exige?", summaryLabel: "Exigencia ESG", options: ["Certificación alta actual", "Potencial brown-to-green", "Deseable", "Indiferente"] },
      { key: "q14", prompt: "¿Qué protección patrimonial acepta?", summaryLabel: "Protección patrimonial", options: ["Sin protección", "Fachada protegida", "Protección media", "Protección integral"] },
      { key: "q15", prompt: "¿Admite incidencias legales o de posesión?", summaryLabel: "Tolerancia a incidencias", options: ["Sin incidencias", "Acepto rentas antiguas", "Acepto complejidad con descuento", "Solo casos muy controlados"] },
      { key: "q16", prompt: "¿Qué WALT mínimo prefiere si hay alquileres?", summaryLabel: "WALT mínimo", options: ["Menos de 3 años", "3 a 5 años", "5 a 7 años", "Más de 7 años"] },
      { key: "q17", prompt: "¿Qué espacios exteriores valora?", summaryLabel: "Espacios exteriores", options: ["Terrazas / rooftop", "Patio interior", "Zonas comunes", "No prioritarios"] },
      { key: "q18", prompt: "¿Qué perfil de capital usará?", summaryLabel: "Estructura de capital", options: ["100% equity", "40% a 60% LTV", "Más del 60% LTV", "Flexible según operación"] },
      { key: "q19", prompt: "¿Qué horizonte de inversión contempla?", summaryLabel: "Horizonte de inversión", options: ["1 a 3 años", "3 a 5 años", "5 a 10 años", "Más de 10 años"] },
      { key: "q20", prompt: "¿Qué estrategia sigue?", summaryLabel: "Estrategia inversora", options: ["Core", "Core Plus", "Value Add", "Oportunista"] },
    ],
  },
  Activos: {
    title: "Activos",
    questions: [
      { key: "q1", prompt: "¿Qué tipología principal de activo busca?", summaryLabel: "Tipología de activo", options: ["REOs", "NPLs", "Activos alternativos", "Suelo / deuda inmobiliaria"] },
      { key: "q2", prompt: "¿Qué ticket de inversión maneja?", summaryLabel: "Ticket de inversión", options: ["Hasta 10 M€", "10 M€ a 50 M€", "50 M€ a 100 M€", "Más de 100 M€"] },
      { key: "q3", prompt: "¿Qué perfil de retorno espera?", summaryLabel: "Perfil de retorno", options: ["Core", "Core Plus", "Value Add", "Oportunista"] },
      { key: "q4", prompt: "¿Qué exposición geográfica prioriza?", summaryLabel: "Ámbito geográfico", options: ["Madrid / Barcelona", "Capitales y costa prime", "Diversificación nacional", "Internacional / ibérico"] },
      { key: "q5", prompt: "Si compra deuda, ¿qué formato prefiere?", summaryLabel: "Tipo de deuda", options: ["Secured", "Unsecured", "Mixta", "No aplica"] },
      { key: "q6", prompt: "¿Qué colateral le interesa más?", summaryLabel: "Colateral preferido", options: ["Residencial", "Comercial", "Suelo", "Hoteles / alternativos"] },
      { key: "q7", prompt: "¿Qué interés tiene en sale & leaseback?", summaryLabel: "Interés en sale & leaseback", options: ["Muy alto", "Solo con covenant fuerte", "Caso por caso", "No es foco"] },
      { key: "q8", prompt: "¿Qué posición tiene sobre self-storage y activos operativos?", summaryLabel: "Interés en activos operativos", options: ["Activo estabilizado", "Conversión / desarrollo", "Me interesa selectivamente", "No"] },
      { key: "q9", prompt: "¿Qué estrategia sigue con suelo?", summaryLabel: "Estrategia sobre suelo", options: ["Build to Sell", "Build to Rent", "Terciario / dotacional", "No invierto en suelo"] },
      { key: "q10", prompt: "¿En qué fase de maduración acepta entrar?", summaryLabel: "Fase de entrada", options: ["Temprana", "Intermedia", "Finalista", "Solo activo terminado"] },
      { key: "q11", prompt: "¿Qué plazo de permanencia busca?", summaryLabel: "Plazo de permanencia", options: ["1 a 3 años", "3 a 5 años", "5 a 10 años", "Largo plazo"] },
      { key: "q12", prompt: "¿Usa deuda subordinada o mezzanine?", summaryLabel: "Uso de financiación híbrida", options: ["Sí, activamente", "Solo de forma selectiva", "No", "Depende del mandato"] },
      { key: "q13", prompt: "¿Qué grado de profesionalización exige en el originador?", summaryLabel: "Origen de las operaciones", options: ["Procesos institucionales", "Off-market selectivo", "Ambos", "No prioritario"] },
      { key: "q14", prompt: "¿Qué segmento alternativo le interesa más?", summaryLabel: "Segmento alternativo prioritario", options: ["Data centers", "Healthcare", "Residencias / PBSA", "Retail parks / logística"] },
      { key: "q15", prompt: "¿Qué nivel ESG exige?", summaryLabel: "Exigencia ESG", options: ["Mandato estricto", "Brown-to-green", "Deseable", "Indiferente"] },
      { key: "q16", prompt: "¿Participa en distressed y concursos?", summaryLabel: "Apetito por distressed", options: ["Sí, activamente", "Solo oportunidades claras", "Muy selectivo", "No"] },
      { key: "q17", prompt: "¿Qué formato de portfolio acepta?", summaryLabel: "Formato de portfolio", options: ["Single asset", "Portfolio pequeño", "Portfolio masivo", "Indiferente"] },
      { key: "q18", prompt: "¿Qué tolerancia tiene a procesos sin due diligence extensa?", summaryLabel: "Tolerancia a procesos fast-track", options: ["Muy baja", "Moderada", "Alta con descuento", "Alta si es prime"] },
      { key: "q19", prompt: "¿Qué estructura de adquisición prefiere?", summaryLabel: "Estructura de adquisición", options: ["Asset deal", "Share deal", "Flexible", "SPV dedicada"] },
      { key: "q20", prompt: "¿Qué horizonte estratégico sigue?", summaryLabel: "Estrategia de cartera", options: ["Rotación rápida", "Value Add medio plazo", "Patrimonial", "Multiestrategia"] },
    ],
  },
};

export function getPreferenceSchema(category) {
  return preferenceSchemas[category] || null;
}

export function sanitizePreferences(category, rawPreferences = {}) {
  const schema = getPreferenceSchema(category);

  if (!schema) {
    return {};
  }

  return schema.questions.reduce((cleaned, question) => {
    const answer = rawPreferences?.[question.key];

    if (typeof answer === "string" && answer.trim()) {
      cleaned[question.key] = answer;
    }

    return cleaned;
  }, {});
}

export function buildPreferenceEntries(category, rawPreferences = {}) {
  const schema = getPreferenceSchema(category);

  if (!schema) {
    return [];
  }

  return schema.questions
    .map((question) => {
      const value = rawPreferences?.[question.key];

      if (typeof value !== "string" || !value.trim()) {
        return null;
      }

      return {
        key: question.key,
        label: question.summaryLabel,
        value,
      };
    })
    .filter(Boolean);
}

export { preferenceSchemas };
