const preferenceSchemas = {
  Hoteles: {
    title: "Hoteles",
    questions: [
      { key: "q1", prompt: "¿Qué tipo de hotel te interesa más?", summaryLabel: "Tipo de propiedad", options: ["Hotel en ciudad", "Hotel de vacaciones", "Apartahotel", "Hotel pequeño con encanto"] },
      { key: "q2", prompt: "¿Qué tipo de ubicación prefieres?", summaryLabel: "Ubicación", options: ["Centro de ciudad", "Zona premium y muy demandada", "Zona turística", "Zona más tranquila o a las afueras"] },
      { key: "q3", prompt: "¿Qué tamaño mínimo buscas?", summaryLabel: "Metros cuadrados", options: ["Hasta 500 m²", "De 500 a 1.500 m²", "De 1.500 a 5.000 m²", "Más de 5.000 m²"] },
      { key: "q4", prompt: "¿Cuál es tu presupuesto máximo?", summaryLabel: "Precio", options: ["Hasta 1 M€", "De 1 M€ a 5 M€", "De 5 M€ a 15 M€", "Más de 15 M€"] },
      { key: "q5", prompt: "¿Para qué uso principal lo quieres?", summaryLabel: "Uso principal", options: ["Solo hotel", "Turístico", "Uso mixto", "Para reformar y darle otro enfoque"] },
      { key: "q6", prompt: "¿Te interesa que también pueda tener otro uso?", summaryLabel: "Uso alternativo", options: ["Viviendas", "Apartamentos turísticos", "Uso de oficinas o terciario", "No, solo hotel"] },
      { key: "q7", prompt: "¿Qué superficie construida necesitas?", summaryLabel: "Superficie construida", options: ["Hasta 1.000 m²", "De 1.000 a 3.000 m²", "De 3.000 a 8.000 m²", "Más de 8.000 m²"] },
      { key: "q8", prompt: "¿Cómo prefieres comprar el activo?", summaryLabel: "Tipo de propiedad", options: ["Propiedad completa", "Concesión", "Derecho de superficie", "Me da igual"] },
      { key: "q9", prompt: "¿Lo quieres libre y listo para tomar el control?", summaryLabel: "Entrega", options: ["Sí, libre", "No es necesario", "Me da igual"] },
      { key: "q10", prompt: "¿Qué tipo de operación te interesa más?", summaryLabel: "Oportunidad", options: ["Que ya funcione y genere ingresos", "Que se pueda mejorar para ganar más", "Para reformarlo o cambiarle el enfoque", "Con más riesgo pero también más rentabilidad"] },
    ],
  },

  Fincas: {
    title: "Fincas",
    questions: [
      { key: "q1", prompt: "¿Qué tipo de finca buscas?", summaryLabel: "Tipo de propiedad", options: ["Agrícola", "Ganadera", "De recreo", "Mixta"] },
      { key: "q2", prompt: "¿Qué tipo de ubicación prefieres?", summaryLabel: "Ubicación", options: ["Cerca de un núcleo urbano", "Zona interior", "Zona de regadío", "Zona de secano"] },
      { key: "q3", prompt: "¿Qué superficie mínima buscas?", summaryLabel: "Metros cuadrados", options: ["Hasta 10.000 m²", "De 10.000 a 50.000 m²", "De 50.000 a 200.000 m²", "Más de 200.000 m²"] },
      { key: "q4", prompt: "¿Cuál es tu presupuesto máximo?", summaryLabel: "Precio", options: ["Hasta 300.000 €", "De 300.000 € a 1 M€", "De 1 M€ a 5 M€", "Más de 5 M€"] },
      { key: "q5", prompt: "¿Qué uso principal quieres darle?", summaryLabel: "Uso principal", options: ["Agrícola", "Ganadero", "Uso personal o recreativo", "Como inversión"] },
      { key: "q6", prompt: "¿Te interesa que también tenga otro uso posible?", summaryLabel: "Uso alternativo", options: ["Turismo rural", "Energía o placas solares", "Residencial vinculado", "No"] },
      { key: "q7", prompt: "¿Necesitas construcciones dentro de la finca?", summaryLabel: "Superficie construida", options: ["No", "Hasta 500 m²", "De 500 a 2.000 m²", "Más de 2.000 m²"] },
      { key: "q8", prompt: "¿Cómo prefieres comprar la finca?", summaryLabel: "Tipo de propiedad", options: ["Propiedad completa", "Parte indivisa", "Concesión", "Me da igual"] },
      { key: "q9", prompt: "¿La quieres libre para usarla desde el primer momento?", summaryLabel: "Entrega", options: ["Sí, libre", "No es necesario", "Me da igual"] },
      { key: "q10", prompt: "¿Qué tipo de inversión te interesa más?", summaryLabel: "Oportunidad", options: ["Segura y estable", "Equilibrada", "Con margen claro de mejora", "Con más riesgo pero más potencial"] },
    ],
  },

  Parking: {
    title: "Parking",
    questions: [
      { key: "q1", prompt: "¿Qué tipo de parking buscas?", summaryLabel: "Tipo de propiedad", options: ["Parking completo", "Conjunto de plazas", "Edificio de aparcamiento", "Plazas sueltas"] },
      { key: "q2", prompt: "¿Qué tipo de ubicación prefieres?", summaryLabel: "Ubicación", options: ["Centro", "Zona residencial", "Cerca de hospital o campus", "Cerca de estación o aeropuerto"] },
      { key: "q3", prompt: "¿Qué tamaño mínimo buscas?", summaryLabel: "Metros cuadrados", options: ["Hasta 500 m²", "De 500 a 2.000 m²", "De 2.000 a 5.000 m²", "Más de 5.000 m²"] },
      { key: "q4", prompt: "¿Cuál es tu presupuesto máximo?", summaryLabel: "Precio", options: ["Hasta 500.000 €", "De 500.000 € a 2 M€", "De 2 M€ a 10 M€", "Más de 10 M€"] },
      { key: "q5", prompt: "¿Qué uso principal buscas?", summaryLabel: "Uso principal", options: ["Rotación de clientes", "Abonos mensuales", "Mezcla de ambos", "Pensado para explotarlo de otra forma"] },
      { key: "q6", prompt: "¿Te interesa que tenga un uso complementario?", summaryLabel: "Uso alternativo", options: ["Trasteros", "Pequeña logística urbana", "Uso comercial auxiliar", "No"] },
      { key: "q7", prompt: "¿Qué superficie construida necesitas?", summaryLabel: "Superficie construida", options: ["Hasta 1.000 m²", "De 1.000 a 3.000 m²", "De 3.000 a 8.000 m²", "Más de 8.000 m²"] },
      { key: "q8", prompt: "¿Cómo prefieres comprar el activo?", summaryLabel: "Tipo de propiedad", options: ["Propiedad completa", "Concesión", "Derecho de superficie", "Me da igual"] },
      { key: "q9", prompt: "¿Lo quieres libre para empezar a gestionarlo tú?", summaryLabel: "Entrega", options: ["Sí, libre", "No es necesario", "Me da igual"] },
      { key: "q10", prompt: "¿Qué tipo de operación te interesa más?", summaryLabel: "Oportunidad", options: ["Que ya funcione bien", "Que se pueda mejorar en gestión", "Que tenga recorrido de mejora claro", "Con más riesgo pero más rentabilidad"] },
    ],
  },

  Edificios: {
    title: "Edificios",
    questions: [
      { key: "q1", prompt: "¿Qué tipo de edificio buscas?", summaryLabel: "Tipo de propiedad", options: ["Residencial", "Oficinas", "Mixto", "Comercial"] },
      { key: "q2", prompt: "¿Qué tipo de ubicación prefieres?", summaryLabel: "Ubicación", options: ["Zona premium y muy demandada", "Centro", "Periferia urbana", "Zona con potencial de crecimiento"] },
      { key: "q3", prompt: "¿Qué tamaño mínimo buscas?", summaryLabel: "Metros cuadrados", options: ["Hasta 1.000 m²", "De 1.000 a 3.000 m²", "De 3.000 a 10.000 m²", "Más de 10.000 m²"] },
      { key: "q4", prompt: "¿Cuál es tu presupuesto máximo?", summaryLabel: "Precio", options: ["Hasta 2 M€", "De 2 M€ a 10 M€", "De 10 M€ a 30 M€", "Más de 30 M€"] },
      { key: "q5", prompt: "¿Qué uso principal prefieres?", summaryLabel: "Uso principal", options: ["Viviendas", "Oficinas", "Locales o comercial", "Mixto"] },
      { key: "q6", prompt: "¿Te interesa que se pueda destinar a otro uso?", summaryLabel: "Uso alternativo", options: ["Sí, claramente", "No", "Depende de la operación"] },
      { key: "q7", prompt: "¿Qué superficie construida buscas?", summaryLabel: "Superficie construida", options: ["Hasta 2.000 m²", "De 2.000 a 5.000 m²", "De 5.000 a 15.000 m²", "Más de 15.000 m²"] },
      { key: "q8", prompt: "¿Cómo prefieres comprar el edificio?", summaryLabel: "Tipo de propiedad", options: ["Propiedad completa", "Derecho de superficie", "Concesión", "Me da igual"] },
      { key: "q9", prompt: "¿Lo quieres libre para poder actuar desde el inicio?", summaryLabel: "Entrega", options: ["Sí, libre", "No es necesario", "Me da igual"] },
      { key: "q10", prompt: "¿Qué tipo de operación te interesa más?", summaryLabel: "Oportunidad", options: ["Que ya genere ingresos", "Que se pueda mejorar para ganar más", "Para reformar o cambiar de uso", "Con más riesgo pero más potencial"] },
    ],
  },

  Activos: {
    title: "Activos",
    questions: [
      { key: "q1", prompt: "¿Qué tipo de activo buscas?", summaryLabel: "Tipo de propiedad", options: ["Residencial", "Comercial", "Industrial", "Mixto"] },
      { key: "q2", prompt: "¿Qué tipo de ubicación prefieres?", summaryLabel: "Ubicación", options: ["Zona premium y muy demandada", "Centro", "Periferia", "Zona en crecimiento"] },
      { key: "q3", prompt: "¿Qué superficie mínima buscas?", summaryLabel: "Metros cuadrados", options: ["Hasta 500 m²", "De 500 a 2.000 m²", "De 2.000 a 10.000 m²", "Más de 10.000 m²"] },
      { key: "q4", prompt: "¿Cuál es tu presupuesto máximo?", summaryLabel: "Precio", options: ["Hasta 1 M€", "De 1 M€ a 5 M€", "De 5 M€ a 20 M€", "Más de 20 M€"] },
      { key: "q5", prompt: "¿Qué uso principal buscas?", summaryLabel: "Uso principal", options: ["Residencial", "Comercial", "Industrial", "Terciario"] },
      { key: "q6", prompt: "¿Te interesa que tenga otro uso posible?", summaryLabel: "Uso alternativo", options: ["Residencial", "Hotelero", "Logístico", "No"] },
      { key: "q7", prompt: "¿Qué superficie construida necesitas?", summaryLabel: "Superficie construida", options: ["Hasta 1.000 m²", "De 1.000 a 5.000 m²", "De 5.000 a 15.000 m²", "Más de 15.000 m²"] },
      { key: "q8", prompt: "¿Cómo prefieres comprar el activo?", summaryLabel: "Tipo de propiedad", options: ["Propiedad completa", "Concesión", "Derecho de superficie", "Me da igual"] },
      { key: "q9", prompt: "¿Lo quieres libre para tomar el control desde el principio?", summaryLabel: "Entrega", options: ["Sí, libre", "No es necesario", "Me da igual"] },
      { key: "q10", prompt: "¿Qué tipo de operación te interesa más?", summaryLabel: "Oportunidad", options: ["Que ya funcione y genere ingresos", "Que se pueda mejorar para ganar más", "Para reformar o darle otro enfoque", "Con más riesgo pero más rentabilidad"] },
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
