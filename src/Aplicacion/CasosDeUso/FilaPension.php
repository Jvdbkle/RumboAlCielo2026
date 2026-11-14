<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use DateTimeImmutable;
use RumboAlCielo\Dominio\Enumeraciones\EstadoPension;

/** Una pensión con los nombres y los almuerzos reservados y recogidos (HU-09, HU-10 y HU-11). */
final class FilaPension
{
    public function __construct(
        public readonly int $idPension,
        public readonly string $nombrePersona,
        public readonly string $nombreTitular,
        public readonly DateTimeImmutable $mes,
        public readonly EstadoPension $estado,
        public readonly int $reservados,
        public readonly int $recogidos,
    ) {
    }
}