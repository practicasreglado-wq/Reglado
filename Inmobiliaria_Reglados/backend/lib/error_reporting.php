<?php
declare(strict_types=1);

/**
 * Genera un identificador opaco de 8 caracteres, loguea la excepción completa
 * con ese ID contra error_log() y devuelve el ID para mostrarlo al cliente.
 *
 * El cliente recibe solo el ID (ej. "a3f2c8b1"); el mensaje real de la
 * excepción, archivo, línea y pila completa quedan únicamente en el log del
 * servidor. Si más tarde un usuario reporta un error con ese código, un admin
 * puede localizar el log exacto haciendo `grep <ID>` sobre los logs de PHP.
 */
function logAndReferenceError(string $module, Throwable $exception): string
{
    try {
        $errorId = bin2hex(random_bytes(4));
    } catch (Throwable $_) {
        $errorId = substr(md5(uniqid('', true)), 0, 8);
    }

    error_log(sprintf(
        '[%s] error_id=%s message=%s file=%s line=%d trace=%s',
        $module,
        $errorId,
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    ));

    return $errorId;
}
