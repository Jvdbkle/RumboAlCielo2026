<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\Repositorios;

use RumboAlCielo\Dominio\Entidades\Rol;

interface RolRepositorio
{
    public function buscarPorId(int $idRol): ?Rol;
}