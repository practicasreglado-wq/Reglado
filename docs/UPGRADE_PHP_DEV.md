# Upgrade PHP local (XAMPP) — pendiente

**Fecha del checklist:** 2026-04-27
**Aplicar cuando:** se devuelva el proyecto al compañero, o antes si hace falta probar Inmobiliaria localmente.

## Problema

XAMPP local tiene **PHP 8.0.30**. El backend de `Inmobiliaria_Reglados` usa `dompdf v3.1`, que arrastra como dep transitiva `thecodingmachine/safe ^3.4` que requiere **PHP 8.1+**.

Síntoma al ejecutar cualquier endpoint del backend de Inmobiliaria localmente:

```
Fatal error: Uncaught Error: Call to undefined function Safe\class_alias()
in vendor\sabberworm\php-css-parser\src\Rule\Rule.php:16
```

El backend escucha en el puerto 8002 pero no atiende ninguna request por este error en el autoloader.

## Producción no está afectada

Hostinger sirve PHP 8.3.30 (verificable en cabecera `X-Powered-By` de cualquier respuesta de los backends desplegados). Las deps de dompdf funcionan sin tocar nada en prod.

## Qué hacer

Cualquiera de estas dos vías arregla el local:

### A) Upgrade XAMPP a PHP 8.1+ (recomendado)

1. Descargar XAMPP con PHP 8.2 (o superior) desde https://www.apachefriends.org/download.html.
2. Hacer copia de seguridad de:
   - `c:/xampp/htdocs/` (contiene todos los proyectos).
   - `c:/xampp/mysql/data/` (datos de BBDD locales).
   - `c:/xampp/php/php.ini` (configs custom si las hay).
3. Desinstalar XAMPP actual.
4. Instalar la versión nueva.
5. Restaurar `htdocs/` y `mysql/data/`.
6. Re-ejecutar `composer install` en proyectos que tengan vendor cacheado de versión vieja.
7. Levantar Reglado normal (`/levantar-reglado`).

**Riesgo**: si tienes otros proyectos en XAMPP que dependen de PHP 8.0 (poco probable), revisar antes.

### B) Bajar `dompdf` a v2.x en Inmobiliaria

1. Editar `Inmobiliaria_Reglados/composer.json`:
   ```json
   "dompdf/dompdf": "^2.0"
   ```
2. `composer update dompdf/dompdf`.
3. Probar generación de PDFs en endpoints que lo usen (búsqueda: `grep -rn "use Dompdf" Inmobiliaria_Reglados/backend/`).

**Riesgo**: dompdf 2.x → 3.x cambió APIs internas. Si la migración inversa no compila al primer intento, hay que portar manualmente.

## Recomendación

Opción **A** (upgrade PHP). Es la fix definitiva y alinea local con prod. La opción B es parche temporal con deuda técnica.
