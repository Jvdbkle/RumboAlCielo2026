<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use RumboAlCielo\Dominio\Enumeraciones\EstadoReserva;

/** Una reserva dentro de un día del calendario: de quién es, en qué estado está y si aún se puede cancelar. */
final class ReservaDelCalendario
{
    public function __construct(
        public readonly int $idReserva,
        public readonly string $nombrePersona,
        public readonly EstadoReserva $estado,
        public readonly float $importe,
        public readonly bool $puedeCancelarse,
    ) {
    }
}