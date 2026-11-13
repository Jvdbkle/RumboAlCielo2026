<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

/** Una fila de la lista de familiares de un empleado (HU-04 criterio 2). */
final class FilaFamiliar
{
    public function __construct(
        public readonly int $idFamiliar,
        public readonly string $nombreCompleto,
        public readonly string $parentesco,
    ) {
    }
}