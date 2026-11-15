<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

/** Los indicadores del día que muestra el tablero del administrador (HU-23). */
final class Tablero
{
    public function __construct(
        public readonly int $empleadosHabilitados,
        public readonly string $estadoMenu,
        public readonly ConteoReservas $pensionados,
        public readonly ConteoReservas $noPensionados,
    ) {
    }

    public function totalReservasHoy(): int
    {
        return $this->pensionados->total + $this->noPensionados->total;
    }
}