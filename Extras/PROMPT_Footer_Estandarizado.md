# Prompt de Estandarización: Footer de Grupo Reglado

**Objetivo:** Garantizar que todas las nuevas páginas o proyectos web desarrollados para el ecosistema corporativo mantengan una identidad visual premium y unificada utilizando la plantilla oficial del Grupo Reglado.

---

## 📝 Instrucciones a incluir en tu Prompt (Contexto para la IA o Desarrollador)

Copia y pega el siguiente bloque de texto en tus futuras instrucciones o prompts al pedir la creación de una nueva página:

> Al construir esta página, o cualquier _layout_ general del proyecto, **DEBES** integrar de manera estricta el componente oficial de footer ubicado en el mismo repositorio, referenciado normalmente como `./RegladoFooterTemplate.vue`.
> 
> **Reglas aplicables:**
> 1. **No inventes ni rediseñes un footer desde cero.** Utiliza el código exacto de la plantilla estructurada en Vue 3. 
> 2. **Identidad Visual Premium:** Respeta la paleta de colores oscuros (`linear-gradient(145deg, #0f172a, #1e293b)`), los efectos de _glassmorphism_ predefinidos, el acento azul (`#3b82f6`), y las micro-animaciones en los enlaces e iconos.
> 3. **Adaptabilidad:** Eres libre de adaptar las URLs de la columna "Navegación" para que coincidan con las rutas del proyecto actual, pero mantén las clases CSS, la estructura de la grilla responsive y la información de la marca ("Grupo Reglado") intactas.
> 4. **Comportamiento:** Asegúrate de conservar el script de Vue que calcula dinámicamente el año de Copyright (`currentYear`).

---

### ¿Cómo usar este Prompt?

El propósito de este `.md` es servir de recordatorio. Simplemente toma el bloque entre comillas (o indícale a la IA que lea este archivo `.md` antes de empezar a maquetar) para garantizar que todo el equipo técnico e Inteligencias Artificiales respeten siempre la misma estructura corporativa.
