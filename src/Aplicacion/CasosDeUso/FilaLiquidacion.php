<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Dominio\Enumeraciones\Modalidad;

/** Una persona en la liquidación mensual (HU-26). */
final class FilaLiquidacion
{
    public function __construct(
        public readonly string $nombrePersona,
        public readonly int $idUsuario,
        public readonly string $nombreTitular,
        public readonly Modalidad $modalidad,
        public readonly int $cantidad,
        public readonly float $penalidad,
        public readonly float $total,
    ) {
    }
}