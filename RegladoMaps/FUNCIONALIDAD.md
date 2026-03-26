# Funcionalidad de RegladoMaps

## Propósito Central
RegladoMaps es la herramienta de visualización espacial del grupo. Su objetivo es proporcionar una interfaz cartográfica donde se ubiquen geográficamente todos los activos (parques eólicos, plantas solares, inmuebles, etc.) del ecosistema.

## Funciones Principales
1. **Visualización de Activos Reales**: Renderiza en un mapa interactivo la ubicación exacta de las infraestructuras energéticas y propiedades, permitiendo al usuario entender la presencia territorial de Reglado.
2. **Filtrado Multicapa**: Permite alternar entre diferentes tipos de "energías" o activos (Eólica, Solar, Hidrógeno, Biometano, etc.) mediante una interfaz lateral dinámica que actualiza los puntos del mapa en tiempo real.
3. **Landing Page Inmersiva**: Posee una página de inicio con alto impacto visual que utiliza animaciones activadas por scroll y vídeos de fondo para explicar cada tipo de tecnología renovable antes de que el usuario entre al mapa interactivo.
4. **Navegación Intuitiva**: Facilita el salto directo a puntos de interés específicos basándose en la categoría seleccionada por el usuario desde la página de inicio.

## Tecnología y Rendimiento
- Utiliza **Vite** para garantizar tiempos de carga extremadamente rápidos.
- Implementa **IntersectionObservers** para cargar animaciones y recursos solo cuando son visibles, optimizando el rendimiento del navegador.
- Al igual que el resto, reconoce al usuario autenticado para permitirle (en futuras fases) guardar filtros personalizados o ver activos privados.
