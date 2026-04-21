# 🤖 Funcionalidades del Chatbot Reglado

## 1. Atención al Cliente 24/7
- **ChatBot siempre responde**: Responde dudas de forma amable y natural en español.
- **Tono Profesional**: Mantiene un estilo cercano pero serio, enfocado en ayudar al cliente.


## 2. Agenda de Citas Telefónicas
- **Reserva Automática**: Si El Chatbot no puede resolver tu duda sugiere agendar cita. O si un cliente quiere hablar con una persona, ChatBot le pide su nombre, email, teléfono y el motivo.
- **Control de Horarios**: Solo permite agendar citas de **Lunes a Viernes, de 07:00 a 15:00**. Si alguien lo intenta fuera de hora, ChatBot le avisa amablemente.
- **¡Sin dobles citas!**: El sistema es inteligente y no permite que dos personas agenden a la misma hora. Deja un margen de **30 minutos** entre cita y cita para que tengas tiempo de sobra.
- **Confirmación al cliente por email**: En cuanto se confirma la cita, el cliente recibe automáticamente un correo con su nombre, fecha, hora y motivo de la cita.
- **Aviso interno por email**: Reglado recibe simultáneamente un correo con todos los datos del cliente (nombre, email, teléfono, día, hora y motivo) para tenerlo todo listo antes de la llamada.
- **Google Calendar**: La cita se agenda automáticamente en el calendario de Reglado en el momento de la confirmación. Sin anotar nada a mano.


## 3. Sincronización Total
- **Google Calendar**: En cuanto se confirma una cita, aparece mágicamente en tu calendario de Google. ¡No tienes que anotar nada a mano!
- **Notificaciones por Email**: 
  - El **cliente** recibe un correo profesional confirmando su cita.
  - La **Empresa (Reglado)** recibe un correo con todos los datos del cliente, dia, hora y motivo de la cita. En caso de subida de archivos también.
- **Bases de Datos**: El chatbot tiene bases de datos para garantizar persistencia:
  - `usuarios`: asigna un ID, nombre etc al cliente cuando se reserva cita o adjunta archivo.
  - `citas`: relaciona cita con ID y Nombre de cliente. Con fecha y hora.
  - `archivos`: relaciona subida de archivos con el ID y nombre del usuario que lo adjunta.
  - `conversaciones`: guarda las conversaciones entre cliente-agente. Y archiva qué agente le atendió en esa sesión (Guarda el nombre del agente).


## 4. Subida de Archivos y Seguridad
- **Registro Obligatorio**: Por seguridad, antes de permitir la subida de cualquier archivo, el chatbot asegura que el cliente haya facilitado sus datos (Nombre, Email y Teléfono) para asociar el documento correctamente en la base de datos. Si no se ha identificado, ChatBot le pedirá los datos antes de habilitar el clip.
- **Botón de Adjuntos (Clip)**: Una vez identificado, el usuario puede enviar fotos, facturas o PDFs directamente a través del chat. Este archivo se sube a una carpeta en el servidor. Con un limite de 100MB de peso por archivo para mayor seguridad.
- **Aviso con Adjunto por Email**: La empresa recibe una notificación inmediata en `[EMAIL_ADDRESS]` (borjagonzalezarazo- cambiar después) con los datos del cliente y el **archivo adjunto directamente en el correo** para facilitar su descarga y gestión sin pasos adicionales.


## 5. Atención Híbrida a Agente Humano vía Telegram
- **Derivación Inteligente**: Cuando un cliente requiere ayuda sofisticada o pide explícitamente hablar con un operador, la IA pausa su intervención y envía una alerta instantánea a los agentes disponibles a través de **Telegram**.
- **Asignación Rápida "Claim"**: A los agentes (Borja, Alexandra, etc.) les llega un mensaje con el motivo de la consulta y un botón interactivo de "🙋‍♂️ Tomar conversación". El primero en pulsarlo asume el mando, desactivándose el botón para el resto de los compañeros instantáneamente.
- **Chat en Directo desde el Móvil**: Todo lo que el cliente escribe en el navegador le llega al agente adjudicado a su Telegram de forma transparente. Todo lo que el agente responde por Telegram se renderiza al instante en la web del cliente.
- **Cola en Memoria Ultra-Rápida (RAM)**: Para mantener un flujo directo, veloz y a prueba de desconexiones o cambios de hora local, los mensajes de chat en vivo vuelan a través de la memoria volátil del servidor directamente a la pantalla del cliente, sin escribir "basura" o ralentizar la base de datos MySQL.
- **Comando de Finalización**: Cuando el agente termina la gestión, envía el comando `/finalizar` por su app de Telegram. Esto cierra inmediatamente su sesión, avisa al cliente de que se ha marchado, y **le devuelve el control a la IA (ChatBot)** para que el asistente virtual retome el mando.
- **Retorno por No Aceptación (5 min)**: Si ningún agente acepta la conversación en un plazo de **5 minutos**, el sistema informará al cliente de que no hay agentes disponibles en ese momento, sugerirá agendar una cita telefónica y devolverá automáticamente el control al asistente virtual.
- **Cierre Automático Anti-olvidos (Timeout)**: Si un agente toma una conversación pero se olvida de cerrarla y se da un escenario de inactividad de **5 minutos de silencio absoluto** (ni el cliente ni el agente hablan), el sistema es lo suficientemente inteligente para realizar un `/finalizar` automático, asegurando que el cliente nunca se quede bloqueado "al otro lado de la línea" esperando para siempre.
- **Conversaciones Simultáneas**: El sistema soporta múltiples conversaciones humanas activas al mismo tiempo, con distintos agentes atendiéndolas en paralelo. Cada agente solo puede gestionar una conversación a la vez.
- **Protección de Flujo por Agente Ocupado**: Cuando llega un aviso de nuevo cliente, los agentes que ya tienen una conversación activa reciben la notificación de forma informativa pero **sin botón de aceptar**, evitando que rompan su conversación en curso. Solo los agentes libres ven el botón y pueden tomar la nueva solicitud.


## 6. Control de Uso y Límite de Créditos por Sesión
- **Coste por Sesión en Tiempo Real**: Cada conversación (IA-Cliente) acumula los tokens consumidos y el gasto en euros con la API de Claude. Este dato se guarda en la base de datos conversaciones.
Tambien se guardan los datos de tokens consumidos y gasto en euros que consume la memoria a largo plazo (generacion de resúmenes) en la base de datos memoria_usuario.
- **Aviso Inteligente (Umbral Blando)**: Cuando el coste de una sesión supera los **0,60 €**, añade automáticamente un mensaje sugiriendo al cliente que, si lo desea, puede ser atendido por un agente humano para una atención más personalizada. La IA sigue respondiendo con normalidad.
- **Límite Duro y Derivación Automática**: Al alcanzar los **0,80 €**, el sistema actúa de forma diferente según el horario:
  - **En horario laboral (L–V, 07:00–15:00)**: La IA notifica a los agentes por Telegram.
  - **Fuera de horario**: La IA ofrece agendar una llamada para el siguiente día hábil.
  - **Finaliza el flujo**: La IA informa del límite y da gracias por su comprensión.
- **Configuración Flexible**: Los umbrales se definen en el archivo `.env`


## 7. Memoria
- **Memoria Contextual Inmediata**: ChatBot recuerda los últimos **10 mensajes** de la conversación activa para entender el contexto (por ejemplo, si preguntas "¿y de qué color es?" después de hablar de un producto).
- **Memoria Larga por Sesión (Persistente)**: Además de la memoria inmediata, el sistema guarda un resumen de cada conversación en base de datos, vinculado al `sessionId` del navegador del cliente. Esto permite que ChatBot recuerde contexto de sesiones anteriores siempre que se utilice el mismo navegador (sin el modo incognito) sin que el cliente tenga que identificarse:
  - Resumen: Se guarda en BBDD un resumen con los datos importantes de las conversaciones (datos cliente, intención, ultima acción e incidencias) para no aumentar tanto el peso y el gasto de tokens. Esto suficiente y eficaz para que la IA tenga memoria a largo plazo.
  - El resumen se actualiza automáticamente **cada 5 mensajes** y siempre que ocurre una acción importante (cita, registro, solicitud de agente).
  - Los resúmenes se eliminan de la BBDD automáticamente a los **15 días** de inactividad.
  - Coste extra estimado: **menos del 5%** sobre el coste base de la API.


## 8. Seguridad

- **Validación de Datos**: ChatBot comprueba que el email y el teléfono sigan un formato real antes de dar por buena la cita.
- **Fechas Reales**: No permite agendar citas en el pasado (ayer o una hora que ya pasó hoy).

- **Rate Limiting (Anti-bots y abuso):** El servidor limita automáticamente las peticiones por IP. El endpoint `/chat` acepta un máximo de **20 mensajes por minuto** y `/api/upload` un máximo de **3 subidas por hora**. Si se supera el límite, el sistema informa al usuario y bloquea temporalmente la petición. Ambos límites son configurables desde `.env` (`RATE_LIMIT_CHAT_RPM`, `RATE_LIMIT_UPLOAD_RPH`).

- **Archivos Privados:** Los archivos subidos por los clientes (facturas, documentos) no son accesibles públicamente. Solo se pueden descargar presentando el `sessionId` de la sesión que los subió. Sin él, el servidor devuelve un error de acceso denegado.

- **Protección del Repositorio:** El archivo `.gitignore` está configurado para excluir del control de versiones todos los datos sensibles: variables de entorno (`.env`), credenciales de Google (`google-credentials.json`) y los archivos subidos por clientes (`uploads/`). Esto evita exposiciones accidentales si el proyecto se sube a un repositorio público.

---
*Este chatbot ha sido diseñado para liberar tu tiempo, asegurar que ningún lead se pierda y proporcionar una experiencia híbrida completa de Inteligencia Artificial + Soporte Humano de primer nivel.*
