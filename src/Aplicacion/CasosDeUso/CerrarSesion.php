<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Aplicacion\Puertos\Sesion;

/**
 * HU-05 criterio 1: el usuario presiona "Cerrar Sesión" y el sistema finaliza su sesión.
 * La redirección al login la hace el controlador (capa de adaptadores).
 */
final class CerrarSesion
{
    public function __construct(private Sesion $sesion)
    {
    }

    public function ejecutar(): void
    {
        $this->sesion->destruir();
    }
}