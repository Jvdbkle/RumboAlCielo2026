<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

/** Una fila de la lista de usuarios (HU-03 criterio 3). */
final class FilaUsuario
{
    public function __construct(
        public readonly int $idUsuario,
        public readonly string $nombreCompleto,
        public readonly string $correo,
        public readonly string $rol,
        public readonly string $estado,
    ) {
    }
}