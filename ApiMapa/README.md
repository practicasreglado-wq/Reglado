# ApiMapa

**Backend de datos del mapa energético** que alimenta a [`RegladoMaps`](../RegladoMaps/).
Sirve los puntos georeferenciados (plantas eólicas, solares, hidráulicas,
biodiésel, biometano, hidrógeno) que se pintan sobre el mapa interactivo.

## Qué hace

- Endpoints PHP que devuelven los datos geográficos en JSON.
- Conexión directa a la BD de plantas energéticas.
- Frontend legacy (HTML + JS vanilla con Leaflet) — mantenido como referencia
  histórica; el frontend activo del ecosistema es `RegladoMaps` (Vue + Vite).

## Stack

- Backend: PHP 8 + MySQL.
- Frontend legacy: HTML estático + Leaflet (vanilla JS).
- Sin Composer, sin frameworks — código directo sobre `php -S` o Apache.

## Cómo arrancar (dev)

En desarrollo se sirve con **XAMPP/Apache** (no con `php -S` standalone)
porque en producción vive bajo `regladoconsultores.com/mapa/`. Si XAMPP está
arrancado y este repo está en `c:/xampp/htdocs/Reglado/`, queda accesible en:

```
http://localhost/Reglado/ApiMapa/
http://localhost/Reglado/ApiMapa/backend/php/api.php
```

Para acceder desde `RegladoMaps` en dev, configurar `VITE_API_MAPA_URL`
en su `.env` apuntando a esa URL.

Requisitos: **PHP 8+** vía XAMPP/Apache, **MySQL/MariaDB** con la BD de plantas.

## Servicio en el ecosistema

- **Es consumido por**: `RegladoMaps` (frontend Vue) — recibe los datos del mapa.
- **No depende de** ApiLoging: los datos geográficos son públicos y no requieren autenticación.

## URL en producción

`https://regladoconsultores.com/mapa/backend/php/api.php`

(Vive bajo el dominio corporativo `regladoconsultores.com`, no tiene subdominio propio.)

## Estructura

```
ApiMapa/
├── index.html                  # frontend legacy (Leaflet vanilla)
├── backend/
│   └── php/
│       ├── Conectar.php        # conexión MySQL
│       └── api.php             # endpoints REST
└── frontend/
    ├── assets/                 # imágenes
    ├── js/                     # app.js, loadData.js
    └── styles/                 # CSS
```

## Notas

- Proyecto desarrollado originalmente como prácticas externas (2025).
- El frontend `RegladoMaps` (Vue + Vite) reemplaza al frontend legacy de aquí; el backend sigue siendo el mismo y se mantiene estable.
- No requiere autenticación: los datos del mapa son públicos.

## Más documentación

- [RegladoMaps/README.md](../RegladoMaps/README.md) — frontend que consume estos datos.
- [README raíz del repo](../README.md) — visión global del ecosistema.
