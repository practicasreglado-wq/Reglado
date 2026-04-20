---
description: Despliega todos los servidores de la aplicación Reglado
---

// turbo-all

Para iniciar el entorno, debes ejecutar 7 servidores secuencialmente. Usa la herramienta `run_command` para cada uno, enviándolos a segundo plano asegurándote de usar `WaitMsBeforeAsync: 1000` para que no bloqueen tu ejecución y establecer `SafeToAutoRun: true`.

1. En la ruta `c:\xampp\htdocs\Reglado\ApiLoging`, ejecuta el comando: `php -S localhost:8000`
2. En la ruta `c:\xampp\htdocs\Reglado\GrupoReglado`, ejecuta el comando: `npm run dev` (puerto 5173)
3. En la ruta `c:\xampp\htdocs\Reglado\RegladoEnergy`, ejecuta el comando: `npm run dev` (puerto 5174)
4. En la ruta `c:\xampp\htdocs\Reglado\Inmobiliaria_Reglados`, ejecuta el comando: `npm run dev -- --port 5175` (puerto 5175)
5. En la ruta `c:\xampp\htdocs\Reglado\RegladoMaps`, ejecuta el comando: `npm run dev` (puerto 5176)
6. En la ruta `c:\xampp\htdocs\Reglado\RegladoEnergy\BACKEND`, ejecuta el comando: `php -S localhost:8001`
7. En la ruta `c:\xampp\htdocs\Reglado\Inmobiliaria_Reglados\backend`, ejecuta el comando: `php -S localhost:8002`

Recuerda reportar al usuario cuando los 7 servidores se hayan iniciado.