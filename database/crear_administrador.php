<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use RumboAlCielo\Adaptadores\Repositorios\UsuarioRepositorioMySql;
use RumboAlCielo\Dominio\Entidades\Usuario;
use RumboAlCielo\Infraestructura\BaseDeDatos\Conexion;

if ($argc !== 5) {
    echo "Uso: php database/crear_administrador.php \"Nombres\" \"Apellidos\" correo telefono\n";
    exit(1);
}

[, $nombres, $apellidos, $correo, $telefono] = $argv;

$pdo = Conexion::obtener();
$pdo->beginTransaction();

try {
    $pdo->prepare('INSERT INTO persona (nombres, apellidos) VALUES (:nombres, :apellidos)')
        ->execute(['nombres' => $nombres, 'apellidos' => $apellidos]);
    $idPersona = (int) $pdo->lastInsertId();

    // Rol 1 = Administrador. El correo es el login y el teléfono es la primera contraseña.
    $usuario = Usuario::registrarNuevo(0, $idPersona, $correo, $telefono, 1);
    (new UsuarioRepositorioMySql($pdo))->guardar($usuario);

    $pdo->commit();
    echo "Administrador creado. Ingresa con el correo y, como primera contraseña, el teléfono.\n";
} catch (Throwable $error) {
    $pdo->rollBack();
    echo 'No se pudo crear el administrador: ' . $error->getMessage() . "\n";
    exit(1);
}