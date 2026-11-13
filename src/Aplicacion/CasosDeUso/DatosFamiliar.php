<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

/** Los datos de un familiar para mostrarlos en el formulario de edición (HU-04 criterio 3). */
final class DatosFamiliar
{
    public function __construct(
        public readonly int $idFamiliar,
        public readonly int $idEmpleado,
        public readonly string $nombres,
        public readonly string $apellidos,
        public readonly string $parentesco,
    ) {
    }
}