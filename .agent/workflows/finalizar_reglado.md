---
description: Finaliza y suspende los servidores de Reglado
---
// turbo-all

Para finalizar la ejecución de las aplicaciones de Reglado, tu objetivo es terminar los procesos de desarrollo que se quedaron funcionando en segundo plano. NO pidas permiso, utiliza `SafeToAutoRun: true` en todas las ejecuciones.

1. Si aún conservas los `CommandId` de los procesos en segundo plano de la sesión de inicio, usa el tool `send_command_input` indicando `Terminate: true` para cancelar cada uno de esos 4 comandos.
2. Si no es posible porque el chat acaba de empezar o se refrescó el contexto, ejecuta directamente el siguiente comando en PowerShell usando `run_command` para forzar el cierre de los servidores de desarrollo de Node y PHP:
   `taskkill /F /IM node.exe ; taskkill /F /IM php.exe`

Avisa al usuario cuando todos los servicios estén apagados y listos.
