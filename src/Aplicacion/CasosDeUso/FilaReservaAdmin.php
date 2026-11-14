<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use DateTimeImmutable;
use RumboAlCielo\Dominio\Enumeraciones\EstadoReserva;
use RumboAlCielo\Dominio\Enumeraciones\Modalidad;

/** Una reserva en la consulta del administrador (HU-28 criterio 1). */
final class FilaReservaAdmin
{
    public function __construct(
        public readonly int $idReserva,
        public readonly DateTimeImmutable $fecha,
        public readonly string $nombrePersona,
        public readonly string $nombreTitular,
        public readonly EstadoReserva $estado,
        public readonly Modalidad $modalidad,
        public readonly float $importe,
    ) {
    }
}