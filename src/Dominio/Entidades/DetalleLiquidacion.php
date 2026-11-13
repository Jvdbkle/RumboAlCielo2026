<?php

declare(strict_types=1);

namespace RumboAlCielo\Dominio\Entidades;

use RumboAlCielo\Dominio\Enumeraciones\Modalidad;

final class DetalleLiquidacion
{
    /** HU-26 criterio 2: mínimo de almuerzos para un pensionado. */
    public const MINIMO_PENSIONADO = 10;

    public function __construct(
        private int $idDetalle,
        private int $idLiquidacion,
        private int $idPersona,
        private int $idUsuario,
        private Modalidad $modalidad,
        private int $cantidad,
        private float $penalidad = 0.0,
        private float $total = 0.0,
    ) {
    }

    public function getIdDetalle(): int
    {
        return $this->idDetalle;
    }

    public function getIdLiquidacion(): int
    {
        return $this->idLiquidacion;
    }

    public function getIdPersona(): int
    {
        return $this->idPersona;
    }

    public function getIdUsuario(): int
    {
        return $this->idUsuario;
    }

    public function getModalidad(): Modalidad
    {
        return $this->modalidad;
    }

    public function getCantidad(): int
    {
        return $this->cantidad;
    }

    public function getPenalidad(): float
    {
        return $this->penalidad;
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    /**
     * HU-26 criterios 1 a 3.
     * Diaria: cantidad × 18. Pensionada: cantidad × 15; si consumió menos de 10 se cobra
     * el equivalente a 10 almuerzos (150 Bs) y la penalidad es lo que falta para llegar a ese mínimo.
     */
    public function calcularTotal(): float
    {
        if ($this->modalidad === Modalidad::Diaria) {
            $this->penalidad = 0.0;
            $this->total = $this->cantidad * Reserva::TARIFA_DIARIA;
            return $this->total;
        }

        $consumo = $this->cantidad * Reserva::TARIFA_PENSIONADA;
        $this->penalidad = $this->cantidad < self::MINIMO_PENSIONADO
            ? (self::MINIMO_PENSIONADO - $this->cantidad) * Reserva::TARIFA_PENSIONADA
            : 0.0;
        $this->total = $consumo + $this->penalidad;
        return $this->total;
    }
}