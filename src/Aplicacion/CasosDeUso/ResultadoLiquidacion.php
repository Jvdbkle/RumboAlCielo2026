<?php

declare(strict_types=1);

namespace RumboAlCielo\Aplicacion\CasosDeUso;

use DateTimeImmutable;
use RumboAlCielo\Dominio\Enumeraciones\EstadoLiquidacion;

/** La liquidación de un mes lista para mostrarse: filas por persona y total por empleado titular. */
final class ResultadoLiquidacion
{
    /**
     * @param FilaLiquidacion[] $filas
     * @param array<int, array{nombre: string, total: float}> $totalesPorEmpleado Indexado por id de usuario titular.
     */
    public function __construct(
        public readonly DateTimeImmutable $mes,
        public readonly EstadoLiquidacion $estado,
        public readonly array $filas,
        public readonly array $totalesPorEmpleado,
        public readonly float $totalGeneral,
    ) {
    }

    public function estaCerrada(): bool
    {
        return $this->estado === EstadoLiquidacion::Cerrada;
    }
}