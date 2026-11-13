<?php

declare(strict_types=1);

namespace RumboAlCielo\Tests\Dobles;

use RumboAlCielo\Aplicacion\Repositorios\RolRepositorio;
use RumboAlCielo\Dominio\Entidades\Rol;

/** Repositorio falso para las pruebas: guarda los roles en memoria, sin base de datos. */
final class RolRepositorioEnMemoria implements RolRepositorio
{
    /** @var array<int, Rol> */
    private array $roles = [];

    public function agregar(Rol $rol): void
    {
        $this->roles[$rol->getIdRol()] = $rol;
    }

    public function buscarPorId(int $idRol): ?Rol
    {
        return $this->roles[$idRol] ?? null;
    }
}