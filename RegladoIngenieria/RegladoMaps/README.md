# RegladoMaps

Visualización cartográfica interactiva de activos del ecosistema Reglado.

## Propósito
Permite a los usuarios visualizar inmuebles y recursos energéticos sobre un mapa interactivo, facilitando la búsqueda por ubicación y el análisis espacial de activos.

## Integración
- **Autenticación**: Al igual que el resto del ecosistema, depende de la cookie compartida `reglado_auth_token`.
- **Datos**: Consume APIs locales para obtener las coordenadas y metadatos de los puntos a mostrar.
- **Interfaz**: Construido con Vue.js y Vite para un rendimiento óptimo.

## Requisitos
- Node.js 18+
- [ApiLoging](file:///c:/xampp/htdocs/Reglado/ApiLoging) activo para validación de tokens.

## Comandos de Desarrollo
```bash
npm install
npm run dev -- --port 5176
```

## Estructura de Archivos Clave
- `src/App.vue`: Punto de entrada de la aplicación y lógica de inicialización.
- `src/router.js`: Definición de rutas y navegación.
- `src/components/`: Componentes específicos de mapas y filtros.
