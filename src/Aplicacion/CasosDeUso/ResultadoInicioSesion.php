<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Dominio\Entidades\Rol;
use RumboAlCielo\Dominio\Entidades\Usuario;

final class ResultadoInicioSesion
{
    public function __construct(
        public readonly Usuario $usuario,
        public readonly Rol $rol,
        public readonly bool $requiereCambioContrasenia,
    ) {
    }
}