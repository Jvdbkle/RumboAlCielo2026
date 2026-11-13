<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

/** Los datos de un usuario para mostrarlos en el formulario de edición (HU-03 criterio 4). */
final class DatosUsuario
{
    public function __construct(
        public readonly int $idUsuario,
        public readonly string $nombres,
        public readonly string $apellidos,
        public readonly string $correo,
        public readonly string $telefono,
        public readonly int $idRol,
    ) {
    }
}