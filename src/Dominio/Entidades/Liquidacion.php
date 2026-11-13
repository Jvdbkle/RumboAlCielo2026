<?php

declare(strict_types=1);

namespace RumboAlCielo\Dominio\Entidades;

use DateTimeImmutable;
use RumboAlCielo\Dominio\Enumeraciones\EstadoLiquidacion;
use RumboAlCielo\Dominio\Excepciones\ReglaDeNegocioException;

final class Liquidacion
{
    private DateTimeImmutable $mes;

    /** @var list<DetalleLiquidacion> */
    private array $detalles = [];

    public function __construct(
        private int $idLiquidacion,
        DateTimeImmutable $mes,
        private EstadoLiquidacion $estado = EstadoLiquidacion::Generada,
    ) {
        $this->mes = $mes->modify('first day of this month')->setTime(0, 0);
    }

    public function getIdLiquidacion(): int
    {
        return $this->idLiquidacion;
    }

    public function getMes(): DateTimeImmutable
    {
        return $this->mes;
    }

    public function getEstado(): EstadoLiquidacion
    {
        return $this->estado;
    }

    /** @return list<DetalleLiquidacion> */
    public function getDetalles(): array
    {
        return $this->detalles;
    }

    public function agregarDetalle(DetalleLiquidacion $detalle): void
    {
        $this->detalles[] = $detalle;
    }

    /** HU-26: calcula el total de cada renglón y deja la liquidación en estado Generada. */
    public function generar(): void
    {
        if ($this->estado === EstadoLiquidacion::Cerrada) {
            throw new ReglaDeNegocioException('La liquidación del mes ya está cerrada.');
        }
        foreach ($this->detalles as $detalle) {
            $detalle->calcularTotal();
        }
        $this->estado = EstadoLiquidacion::Generada;
    }

    /** HU-27 criterio 2: el mes se cierra antes de exportar la planilla. */
    public function cerrar(): void
    {
        if ($this->estado !== EstadoLiquidacion::Generada) {
            throw new ReglaDeNegocioException('Solo se puede cerrar una liquidación generada.');
        }
        $this->estado = EstadoLiquidacion::Cerrada;
    }

    /** HU-26 criterio 4: total a descontar al empleado titular (suma de él y sus familiares). */
    public function totalPorEmpleado(int $idUsuario): float
    {
        $total = 0.0;
        foreach ($this->detalles as $detalle) {
            if ($detalle->getIdUsuario() === $idUsuario) {
                $total += $detalle->getTotal();
            }
        }
        return $total;
    }

    /** HU-27 criterios 1 y 2: solo con el mes cerrado. Devuelve las filas en texto separado por ";". */
    public function exportarPlanilla(): string
    {
        if ($this->estado !== EstadoLiquidacion::Cerrada) {
            throw new ReglaDeNegocioException('Primero debe cerrarse el mes para exportar la planilla.');
        }
        $filas = ['COD;MOD;CANT;TOT'];
        foreach ($this->detalles as $d) {
            $filas[] = implode(';', [$d->getIdPersona(), $d->getModalidad()->value, $d->getCantidad(), number_format($d->getTotal(), 2, '.', '')]);
        }
        return implode("\n", $filas);
    }
}