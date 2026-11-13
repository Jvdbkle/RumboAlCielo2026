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
        public function listarTodos(): array
    {
        $filas = $this->pdo->query('SELECT idRol, nombreRol FROM rol ORDER BY idRol')->fetchAll();
        return array_map(fn (array $fila) => new Rol((int) $fila['idRol'], $fila['nombreRol']), $filas);
    }
}