<?php

declare(strict_types=1);

namespace RumboAlCielo\Infraestructura\BaseDeDatos;

use PDO;
use RuntimeException;


final class Conexion
{
    private static ?PDO $pdo = null;

    public static function obtener(?string $rutaEnv = null): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $config = self::leerEnv($rutaEnv ?? dirname(__DIR__, 3) . '/.env');

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['DB_HOST'] ?? '127.0.0.1',
            $config['DB_PUERTO'] ?? '3306',
            $config['DB_NOMBRE'] ?? 'rumbo_al_cielo',
        );

        self::$pdo = new PDO($dsn, $config['DB_USUARIO'] ?? 'root', $config['DB_CLAVE'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$pdo;
    }

    /** @return array<string, string> */
    public static function leerEnv(string $ruta): array
    {
        if (!is_file($ruta)) {
            throw new RuntimeException('No existe el archivo .env con los datos de la base de datos.');
        }

        $config = [];
        foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
            $linea = trim($linea);
            if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
                continue;
            }
            [$clave, $valor] = explode('=', $linea, 2);
            $config[trim($clave)] = trim($valor);
        }
        return $config;
    }
}