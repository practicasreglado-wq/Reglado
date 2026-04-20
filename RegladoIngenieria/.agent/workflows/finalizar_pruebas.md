---
description: Finaliza los servidores del entorno de pruebas
---

// turbo-all

Para finalizar el entorno de pruebas, detén los procesos de Node y PHP que se iniciaron previamente. NO pidas permiso, utiliza `SafeToAutoRun: true` en todas las ejecuciones.

1. Si aún conservas los `CommandId` de los procesos de la sesión actual, usa `send_command_input` con `Terminate: true`.
2. Si no es posible, ejecuta directamente en PowerShell:
   `taskkill /F /IM node.exe ; taskkill /F /IM php.exe`

> [!NOTE]
> Este comando cerrará **todos** los procesos de Node y PHP activos en el sistema, incluyendo los del entorno principal si estuvieran abiertos.

Informa al usuario cuando el entorno de pruebas haya sido detenido.
