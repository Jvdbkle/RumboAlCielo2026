<?php

declare(strict_types=1);

namespace RumboAlCielo\Adaptadores\Repositorios;

use PDO;
use RumboAlCielo\Aplicacion\Repositorios\RolRepositorio;
use RumboAlCielo\Dominio\Entidades\Rol;

final class RolRepositorioMySql implements RolRepositorio
{
    public function __construct(private PDO $pdo)
    {
    }

    public function buscarPorId(int $idRol): ?Rol
    {
        $consulta = $this->pdo->prepare('SELECT idRol, nombreRol FROM rol WHERE idRol = :idRol');
        $consulta->execute(['idRol' => $idRol]);
        $fila = $consulta->fetch();

        if ($fila === false) {
            return null;
        }
        return new Rol((int) $fila['idRol'], $fila['nombreRol']);
    }
}