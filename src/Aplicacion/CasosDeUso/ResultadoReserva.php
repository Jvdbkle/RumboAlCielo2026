<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use DateTimeImmutable;

/** Lo que pasó al reservar un día (HU-16, HU-17 y HU-20 criterios 1 y 3). */
final class ResultadoReserva
{
    /**
     * @param array<int, array{0: string, 1: float}> $registradas Pares [nombre, importe] de cada reserva nueva.
     * @param array<int, array{0: string, 1: string}> $omitidas Pares [nombre, motivo] de quienes no se reservó.
     * @param list<array{evento: string, datos: array<string, mixed>}> $eventos Avisos para el administrador.
     */
    public function __construct(
        public readonly DateTimeImmutable $fecha,
        public readonly array $registradas,
        public readonly array $omitidas,
        public readonly array $eventos = [],
    ) {
    }

    public function total(): float
    {
        return array_sum(array_column($this->registradas, 1));
    }
}