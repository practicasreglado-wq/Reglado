# 🚀 Guía de Implementación en Otros Proyectos Web

Esta guía explica cómo integrar el Chatbot Reglado en cualquier sitio web de forma rápida y sencilla.

## 1. Configuración del Backend (Servidor)

El servidor centralizado en `chatbotReglado/backend` puede dar servicio a múltiples sitios web simultáneamente. No necesitas crear un servidor nuevo para cada proyecto.

### Paso A: Autorizar el nuevo dominio
Para que la nueva web pueda comunicarse con el chatbot, debes añadir su dominio a la lista de permitidos en el archivo `.env` del proyecto original:

1. Abre `chatbotReglado/.env`.
2. Busca la línea `ALLOWED_ORIGINS`.
3. Añade la URL de tu nueva web separada por una coma.
   - *Ejemplo:* `ALLOWED_ORIGINS=http://localhost:3000,https://gruporeglado.com`

---

## 2. Integración en el Frontend (Web de Destino)

Copia y pega el siguiente código al final de tu archivo HTML (usualmente `index.html`), justo antes de la etiqueta de cierre `</body>`.

### Paso B: Bloque de Configuración
Personaliza el aspecto y comportamiento de **ChatBot** para que encaje con la nueva web.

```html
<!-- Configuración del Chatbot Reglado -->
<script>
  window.ChatbotRegladoConfig = {
    apiUrl: 'http://localhost:3000/chat', // URL del servidor Node.js
    title: 'Asistente Inteligente',       // Título de la ventana. puede cambiar en cada web
    greeting: '¡Hola! ¿En qué puedo ayudarte?', // saludo inicial. puede cambiar en cada web
    primaryColor: '#2563eb',             // Color corporativo (Hex). puede cambiar en cada web
    placeholder: 'Escribe tu duda aquí...', // placeholder del input. puede cambiar en cada web
    sendButtonLabel: 'Enviar' 
  };
</script>
```

### Paso C: Cargar el Script del Widget
Apunta a la ubicación del archivo `chatbotReglado.js`. Puedes usar una ruta relativa si están en el mismo servidor o una URL absoluta.

```html
<!-- Carga del Widget desde el servidor del Chatbot -->
<script src="http://localhost:3000/widget/chatbotReglado.js"></script>
```

---

## 3. Manejo de Imágenes y Iconos

El widget busca automáticamente el icono del robot en la carpeta `widget/assets/`. 

- Si el icono no aparece, asegúrate de que la carpeta `assets` esté accesible respecto a la ubicación del archivo `.js`.
- El script intentará resolver la ruta automáticamente, pero en entornos de producción complejos, puede ser necesario mover la carpeta `assets` a la raíz de la nueva web.

## 4. Resumen de Estados (Citas)

Recuerda que para gestionar las citas desde el panel de administración de tu nueva web, los estados recomendados en la base de datos son:
- **pendiente**: Estado por defecto. El hueco está bloqueado.
- **finalizado**: La cita ha sido atendida.
- **cancelada**: La cita se anula y el hueco queda **libre** automáticamente en el chatbot.

---

