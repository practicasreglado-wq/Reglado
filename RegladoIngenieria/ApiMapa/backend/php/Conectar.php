<?php
class Conectar
{
    public static function conexion()
    {
        $host     = 'auth-db1906.hstgr.io';
        $db       = 'u238278696_energeticasdb';
        $username = 'u238278696_Informatico';
        $password = 'cGwzXwBb|4';

        $dsn = "mysql:host={$host};dbname={$db};charset=utf8";

        try {
            $conexion = new PDO($dsn, $username, $password);
            $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $conexion->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode([
                'error' => 'Error de conexión: ' . $e->getMessage(),
                'line'  => $e->getLine()
            ]));
        }

        return $conexion;
    }
}
