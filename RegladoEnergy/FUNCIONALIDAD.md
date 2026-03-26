# Funcionalidad de RegladoEnergy

## Propósito Central
RegladoEnergy es la cara pública del sector energético del grupo. Su función es informar sobre los servicios de energía y captar clientes potenciales mediante herramientas interactivas y formularios de contacto especializados.

## Funciones Clave
1. **Consumo de Identidad Compartida**: A diferencia de GrupoReglado, este proyecto no registra usuarios. En su lugar, "escucha" la presencia de la cookie global `reglado_auth_token`. Si el usuario ya se logueó en el portal principal, Energy lo reconoce automáticamente ("Silent Login").
2. **Sistema de Captación de Leads**: Posee un backend en PHP dedicado exclusivamente a procesar el formulario de contacto. Este sistema no solo envía un correo electrónico, sino que guarda la solicitud en una base de datos local y permite adjuntar archivos (como facturas eléctricas) para su estudio.
3. **Panel de Gestión Energética**: Incluye un área administrativa protegida donde el equipo de Reglado puede listar, filtrar y descargar las facturas y solicitudes enviadas por los clientes potenciales.
4. **Validación de Roles**: Aunque un usuario sea "user" en el sistema global, solo aquellos marcados como "admin" podrán ver y utilizar el panel de gestión de facturas dentro de esta web.

## Lógica de Backend
- El backend PHP actúa de forma independiente para la persistencia de datos del negocio (facturas/leads), pero consulta siempre a `ApiLoging` para validar la identidad del usuario que intenta acceder a los datos sensibles.
