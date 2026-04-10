---
description: Despliega todos los servidores de la aplicación Reglado
---
// turbo-all

Para iniciar el entorno, debes ejecutar 4 servidores en paralelo. Usa la herramienta `run_command` para cada uno, enviándolos a segundo plano asegurándote de usar `WaitMsBeforeAsync: 1000` para que no bloqueen tu ejecución y establecer `SafeToAutoRun: true`.

1. En la ruta `c:\xampp\htdocs\Reglado\ApiLoging`, ejecuta el comando: `php -S localhost:8000`
2. En la ruta `c:\xampp\htdocs\Reglado\gruporeglado`, ejecuta el comando: `npm run dev`
3. En la ruta `c:\xampp\htdocs\Reglado\regladoenergy`, ejecuta el comando: `npm run dev`
4. En la ruta `c:\xampp\htdocs\Reglado\Inmobiliaria_Reglados`, ejecuta el comando: `npm run dev`

Recuerda reportar al usuario cuando los 4 servidores se hayan iniciado.
