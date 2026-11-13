<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use RumboAlCielo\Adaptadores\Repositorios\UsuarioRepositorioMySql;
use RumboAlCielo\Infraestructura\BaseDeDatos\Conexion;

if ($argc !== 2) {
    echo "Uso: php database/restablecer_contrasenia.php correo\n";
    exit(1);
}

$repositorio = new UsuarioRepositorioMySql(Conexion::obtener());
$usuario = $repositorio->buscarPorCorreo($argv[1]);

if ($usuario === null) {
    echo "No existe un usuario con ese correo.\n";
    exit(1);
}

// HU-02 criterio 1: el teléfono vuelve a ser la contraseña.
$usuario->reestablecerContrasenia();
$repositorio->guardar($usuario);

echo "Listo. La contraseña de {$usuario->getCorreo()} volvió a ser su teléfono.\n";