<?php

class Conexion
{
    private static array $instancias = [];

    public static function obtener(string $rol = 'app'): mysqli
    {
        if (isset(self::$instancias[$rol])) {
            return self::$instancias[$rol];
        }

        $host = getenv('SPORTANA_DB_HOST') ?: 'localhost';
        $user = $rol === 'app'
            ? (getenv('SPORTANA_DB_USER') ?: 'sportana_app')
            : 'sportana_' . $rol;
        $pass = getenv('SPORTANA_DB_PASS') ?: '';
        $db   = getenv('SPORTANA_DB_NAME') ?: 'sportana';
        $port = (int) (getenv('SPORTANA_DB_PORT') ?: 3306);

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $conexion = new mysqli($host, $user, $pass, $db, $port);
        $conexion->set_charset('utf8mb4');
        self::$instancias[$rol] = $conexion;
        return $conexion;
    }
}
