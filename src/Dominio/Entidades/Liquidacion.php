<?php

declare(strict_types=1);

namespace RumboAlCielo\Dominio\Entidades;

use DateTimeImmutable;
use RumboAlCielo\Dominio\Enumeraciones\EstadoLiquidacion;
use RumboAlCielo\Dominio\Enumeraciones\Modalidad;
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

    /** Suma de lo que se descuenta a todos los empleados en el mes. */
    public function totalGeneral(): float
    {
        return array_sum(array_map(fn (DetalleLiquidacion $d) => $d->getTotal(), $this->detalles));
    }

    /** HU-27 criterio 2: la planilla solo se exporta con el mes cerrado. */
    public function exigirCerrada(): void
    {
        if ($this->estado !== EstadoLiquidacion::Cerrada) {
            throw new ReglaDeNegocioException('Primero debe cerrarse el mes para exportar la planilla.');
        }
    }

    /**
     * HU-26 criterio 4 y HU-27 criterio 1: una fila por empleado titular y modalidad, con la
     * cantidad de almuerzos y el total a descontar (incluye a sus familiares).
     *
     * @return list<array{idUsuario: int, modalidad: Modalidad, cantidad: int, total: float}>
     */
    public function consolidadoPorTitular(): array
    {
        $filas = [];
        foreach ($this->detalles as $d) {
            $clave = $d->getIdUsuario() . '|' . $d->getModalidad()->value;
            $filas[$clave] ??= ['idUsuario' => $d->getIdUsuario(), 'modalidad' => $d->getModalidad(), 'cantidad' => 0, 'total' => 0.0];
            $filas[$clave]['cantidad'] += $d->getCantidad();
            $filas[$clave]['total'] += $d->getTotal();
        }
        ksort($filas, SORT_NATURAL);
        return array_values($filas);
    }
}