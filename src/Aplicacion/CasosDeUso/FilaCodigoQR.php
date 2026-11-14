<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

/** Un código QR del día: la persona que retira el almuerzo y el código de su reserva. */
final class FilaCodigoQR
{
    public function __construct(
        public readonly int $idReserva,
        public readonly string $nombrePersona,
        public readonly string $codigo,
        public readonly float $importe,
    ) {
    }
}