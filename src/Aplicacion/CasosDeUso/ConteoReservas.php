<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

/** Reservas de un grupo en el tablero: total, entregadas y pendientes (HU-23 criterio 1). */
final class ConteoReservas
{
    public function __construct(
        public readonly int $total = 0,
        public readonly int $entregadas = 0,
        public readonly int $pendientes = 0,
    ) {
    }
}