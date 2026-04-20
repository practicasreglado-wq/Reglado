# 📚 DOCUMENTACIÓN DEL PROYECTO: Reglado Energy


## 🎯 TIPO DE PROYECTO

**Web Corporativa**

- Aplicación web de varias páginas
- Empresa: Reglado Energy (Soluciones de energía renovable)
- Múltiples secciones y rutas de navegación
- Diseño responsivo y moderno

---

## 🛠️ TECNOLOGÍAS UTILIZADAS

| Tecnología | Versión | Función |
|---|---|---|
| **Vue 3** | ^3.5.0 | Framework JavaScript para crear la interfaz interactiva |
| **Vite** | ^5.4.0 | Herramienta de compilación/bundler (muy rápida) |
| **Vue Router** | ^4.4.0 | Sistema de enrutamiento para navegar entre páginas |
| **JavaScript (ES6+)** | - | Lenguaje de programación base |
| **CSS** | - | Estilos personalizados |

### Requisitos
- **Node.js** 18 o superior

---

## 📁 ESTRUCTURA DEL PROYECTO

```
REGLADO-ENERGY-HOSTINGER_READY_PROJECT/
│
├── index.html                ← Archivo principal HTML (punto de entrada)
├── package.json              ← Configuración del proyecto y dependencias
├── vite.config.js            ← Configuración de Vite (bundler)
├── README.md                 ← Documentación básica
├── DOCUMENTACION_PROYECTO.md ← Esta documentación
│
├── public/                   ← Archivos estáticos (imágenes, videos, favicon, etc.)
│   └── README.txt
│
└── src/                      ← CÓDIGO FUENTE (archivos modificables)
    │
    ├── main.js               ← Archivo inicial que carga Vue, Router y estilos
    ├── App.vue               ← Componente raíz (contiene el layout principal)
    ├── seo.js                ← Funciones para SEO (meta tags, títulos)
    │
    ├── assets/
    │   └── styles.css        ← Estilos globales de toda la web
    │
    ├── components/           ← Componentes reutilizables
    │   ├── SiteHeader.vue     ← Encabezado/Navbar (menú de navegación)
    │   ├── SiteFooter.vue     ← Pie de página
    │   ├── CTASticky.vue      ← Botón "llamada a la acción" flotante
    │   ├── FeatureCard.vue    ← Tarjeta para mostrar características/servicios
    │   ├── FAQ.vue            ← Preguntas frecuentes
    │   └── ProfileCard.vue    ← Tarjeta de perfil (miembros del equipo)
    │
    ├── pages/                ← Páginas completas (cada una es una ruta)
    │   ├── Home.vue          ← Página principal (inicio)
    │   ├── Services.vue      ← Página de servicios
    │   ├── Businesses.vue    ← Soluciones para empresas
    │   ├── Individuals.vue   ← Soluciones para particulares
    │   ├── PublicSector.vue  ← Soluciones para sector público
    │   ├── PropertyManagers.vue ← Para administradores de fincas
    │   ├── Clients.vue       ← Clientes/casos de éxito
    │   ├── Contact.vue       ← Formulario de contacto
    │   ├── About.vue         ← Sobre nosotros/empresa
    │   ├── Resources.vue     ← Recursos/documentos descargables
    │   ├── ClientArea.vue    ← Área privada de clientes
    │   └── NotFound.vue      ← Página 404 (ruta no encontrada)
    │
    ├── router/
    │   └── index.js          ← Configuración de rutas (mapea URLs a páginas)
    │
    └── directives/           ← Directivas Vue personalizadas (efectos visuales)
        ├── reveal.js         ← Animación para revelar elementos al scroll
        └── glow.js           ← Efecto de brillo/glow en elementos
```

---

## 📝 DESCRIPCIÓN DE CARPETAS Y ARCHIVOS CLAVE

### **src/main.js**
**Función:** Punto de entrada de la aplicación
- Crea la instancia de Vue
- Registra el Router
- Carga los estilos globales
- Registra las directivas personalizadas (reveal, glow)
- Monta la app en el elemento `#app` del HTML

### **src/App.vue**
**Función:** Componente raíz principal
- Contiene el layout general de la web (estructura)
- Incluye SiteHeader y SiteFooter
- Define el área donde se renderiza el contenido dinámico de cada página
- Se carga una sola vez, el resto de la web cambia dentro de ella

### **src/assets/styles.css**
**Función:** Estilos globales
- Estilos CSS aplicados a toda la web
- Variables CSS, tipografía, colores base
- Estilos comunes reutilizables

### **src/components/**
**Función:** Componentes reutilizables
- **SiteHeader.vue** → Menú de navegación y encabezado (aparece en todas las páginas)
- **SiteFooter.vue** → Pie de página (aparece en todas las páginas)
- **CTASticky.vue** → Botón flotante de "llamada a la acción" (ej: "Contactar ahora")
- **FeatureCard.vue** → Tarjeta reutilizable para mostrar servicios/características
- **FAQ.vue** → Componente de preguntas frecuentes
- **ProfileCard.vue** → Tarjeta para mostrar perfiles de equipo

### **src/pages/**
**Función:** Páginas completas, cada una corresponde a una ruta
- Se cargan dinámicamente según la URL actual
- Cada página es independiente pero usa componentes comunes
- Pueden importar componentes reutilizables

### **src/router/index.js**
**Función:** Sistema de enrutamiento
- Define todas las rutas de la web (qué URL corresponde a qué página)
- Configura el comportamiento del navegador (historial, scroll)
- Es el "mapa" de la aplicación

### **src/directives/**
**Función:** Efectos visuales personalizados
- **reveal.js** → Hace aparecer elementos con animación cuando entran en vista (al hacer scroll)
- **glow.js** → Añade efecto de brillo/luminiscencia a elementos

---

## 🔄 FLUJO DE FUNCIONAMIENTO

```
1. Usuario abre la web
   ↓
2. Se carga index.html
   ↓
3. Se ejecuta main.js
   ↓
4. Vue + Router se inicializan
   ↓
5. App.vue se carga (layout base)
   ↓
6. Router detecta la URL actual
   ↓
7. Se renderiza la página correspondiente (Home.vue, Services.vue, etc.)
   ↓
8. Usuario navega → Router cambia la página sin recargar (SPA)
```

---

## 📊 RUTAS DISPONIBLES

```
/                           → Home.vue          (Página principal)
/servicios                  → Services.vue      (Servicios)
/clientes                   → Clients.vue       (Clientes/Casos de éxito)
/particulares               → Individuals.vue   (Soluciones particulares)
/empresas                   → Businesses.vue    (Soluciones empresas)
/sector-publico             → PublicSector.vue  (Sector público)
/administradores-fincas     → PropertyManagers.vue (Administradores)
/contacto                   → Contact.vue       (Contacto)
/recursos                   → Resources.vue     (Recursos)
/sobre-nosotros             → About.vue         (Sobre nosotros)
/area-clientes              → ClientArea.vue    (Área clientes)
/:pathMatch(.*)* 	    → NotFound.vue      (404 - No encontrado)
```

---

## ✅ COMANDOS PRINCIPALES

```bash
# Instalar dependencias (solo la primera vez)
npm install

# Ejecutar en desarrollo (abre http://localhost:5173)
npm run dev

# Compilar para producción (genera carpeta dist/)
npm run build

# Previsualizar la versión de producción en local
npm run preview
```

---

## 🎨 ESTRUCTURA TÍPICA DE UN COMPONENTE VUE

```vue
<template>
  <!-- HTML de la interfaz -->
  <div class="componente">
    <h1>{{ titulo }}</h1>
    <button @click="accion">Haz clic</button>
  </div>
</template>

<script>
export default {
  data() {
    return {
      titulo: "Mi componente"
    }
  },
  methods: {
    accion() {
      console.log("Botón clickeado");
    }
  }
}
</script>

<style scoped>
/* Estilos solo para este componente */
.componente {
  padding: 20px;
}
</style>
```

---

## 📋 ARCHIVOS IMPORTANTES POR FUNCIÓN

| Si necesitas cambiar... | Edita el archivo... |
|---|---|
| Menú de navegación | `src/components/SiteHeader.vue` |
| Pie de página | `src/components/SiteFooter.vue` |
| Estilos globales | `src/assets/styles.css` |
| Contenido de inicio | `src/pages/Home.vue` |
| Contenido de servicios | `src/pages/Services.vue` |
| Formulario de contacto | `src/pages/Contact.vue` |
| Agregar una nueva página | Crear nuevo archivo `.vue` en `src/pages/` + agregar ruta en `src/router/index.js` |
| Agregar un nuevo componente | Crear nuevo archivo `.vue` en `src/components/` |

---

## 🚀 PARA EMPEZAR A MODIFICAR

1. Abre la carpeta del proyecto en VS Code
2. Ejecuta `npm run dev` en la terminal
3. La web se abrirá en `http://localhost:5173`
4. Edita cualquier archivo `.vue` o `.css` y verás los cambios automáticamente (hot reload)
5. NO edites `index.html` directamente (Vite se encarga)

---

## 💡 CONSEJOS ÚTILES

- **Hot Reload:** Los cambios en archivos se aplican automáticamente sin recargar
- **Vue DevTools:** Instala la extensión para debuggear componentes
- **Componentes vs Páginas:** Componentes son pequeños y reutilizables, páginas son vistas completas
- **Router:** Define qué página mostrar según la URL
- **SEO:** Usa `seo.js` para cambiar títulos y meta tags de cada página

---


