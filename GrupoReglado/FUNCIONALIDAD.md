# Funcionalidad de GrupoReglado

## Propósito Central
GrupoReglado es el portal web principal y el concentrador de la experiencia de usuario. Actúa como el punto de encuentro donde los usuarios gestionan su identidad global y desde donde pueden saltar a los diferentes productos del ecosistema.

## Lógica de Negocio Principal
1. **Orquestación de Identidad (SSO)**: Implementa el flujo de Single Sign-On. Si un usuario intenta entrar en Energy o Maps y no está autenticado, estos proyectos lo redirigen aquí. Tras loguearse, GrupoReglado lo devuelve automáticamente al proyecto de origen.
2. **Gestión de Redirección (`returnTo`)**: Maneja parámetros de retorno de forma segura. Valida que el destino sea un dominio autorizado antes de redirigir al usuario con su nuevo token.
3. **Mantenimiento de Perfil**: Es el único lugar donde el usuario puede realizar cambios sensibles en su cuenta, como actualizar su número de teléfono, nombre o solicitar un cambio de correo electrónico (que requiere una nueva verificación).
4. **Consola de Administración**: Proporciona una interfaz visual para los administradores del sistema, permitiéndoles monitorizar la base de usuarios y asegurar que los datos en Notion estén sincronizados con la base de datos SQL.

## Interfaz de Usuario (UX)
- Diseñado con Vue.js para ofrecer una experiencia rápida y fluida.
- Utiliza un sistema de estado reactivo para que los cambios de perfil se reflejen instantáneamente en la interfaz sin recargar la página.
