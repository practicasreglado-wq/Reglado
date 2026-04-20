---
description: Inicia el entorno de pruebas de los proyectos en la carpeta Pruebas
---

// turbo-all

Para iniciar el entorno de pruebas, debes ejecutar los 3 servidores correspondientes en segundo plano. Asegúrate de usar `SafeToAutoRun: true` y `WaitMsBeforeAsync: 1000`.

1. **ApiLoging (Pruebas)**: En `c:\xampp\htdocs\Reglado\Pruebas\ApiLoging`, ejecuta: `php -S localhost:8000`
2. **GrupoReglado (Pruebas)**: En `c:\xampp\htdocs\Reglado\Pruebas\GrupoReglado`, ejecuta: `npm run dev` (puerto 5173 por defecto)
3. **chatbotReglado (Pruebas)**: En `c:\xampp\htdocs\Reglado\Pruebas\chatbotReglado`, ejecuta: `npm run dev` (puerto 3000)

> [!WARNING]
> Estos servidores pueden entrar en conflicto con el entorno principal si ya están ocupando los puertos 8000 o 5173. Asegúrate de cerrar el entorno principal con `/finalizar_reglado` antes de iniciar este.

Reporta al usuario cuando los 3 servidores de prueba se hayan iniciado.
