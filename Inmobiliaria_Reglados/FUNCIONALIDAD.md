# Funcionalidad de Reglado Real Estate

## Propósito Central
Inmobiliaria_Reglados es la plataforma vertical para el sector de bienes raíces. Permite a los usuarios explorar inmuebles, gestionar sus favoritos y mantener una configuración de búsqueda personalizada.

## Lógica de Negocio Híbrida
1. **Perfil de Usuario Expandido**: Utiliza la identidad global de `ApiLoging` pero la extiende con datos locales. El `iduser` de la API central se vincula con una tabla local de "preferencias" donde se guardan las categorías de interés del usuario en el sector inmobiliario.
2. **Gestión de Activos y Favoritos**: Permite a los usuarios marcar propiedades como favoritas. Esta relación se guarda en la base de datos local de Inmobiliaria, asegurando que cada producto del ecosistema mantenga sus propios datos de negocio separados de la autenticación.
3. **Dashboard de Cliente**: Ofrece una vista personalizada donde el usuario puede ver el estado de sus propiedades guardadas y recibir recomendaciones basadas en su categoría de interés (Compra, Alquiler, etc.).
4. **Seguridad de Operaciones**: El backend PHP valida que cualquier intento de guardar o modificar una preferencia pertenezca realmente al usuario identificado en el token JWT, impidiendo que un usuario acceda a los datos de otro.

## Integración
- Se apoya en un servicio de backend propio (`backend/`) que orquestra la mezcla de datos entre la API de auth central y la base de datos MySQL local de inmobiliaria.
